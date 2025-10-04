<?php
// src/Services/PreviewService.php

namespace App\Services;

use App\Repositories\DocumentRepository;
use RuntimeException;

class PreviewService
{
    private DocumentRepository $documentRepository;
    private string $storagePath;
    private string $cachePath;

    public function __construct()
    {
        $this->documentRepository = new DocumentRepository();
        $this->storagePath = dirname(__DIR__, 2) . '/storage/';
        $this->cachePath = dirname(__DIR__, 2) . '/storage/cache/previews/';

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0775, true);
        }
    }

    /**
     * Retourne le chemin et le type de contenu pour l'aperçu d'un document.
     * @param int $docId
     * @return array ['filePath', 'mimeType', 'fileName']
     * @throws RuntimeException
     */
    public function getPreview(int $docId): array
    {
        $doc = $this->documentRepository->find($docId);
        if (!$doc) {
            throw new RuntimeException('Document non trouvé.', 404);
        }

        $filePath = $this->storagePath . $doc['stored_filename'];
        if (!file_exists($filePath)) {
            throw new RuntimeException('Fichier non trouvé sur le serveur.', 404);
        }

        // --- NOUVEAU : Gestion améliorée des types de fichiers ---

        // Cas 1: Fichiers texte et HTML affichés nativement
        if (str_starts_with($doc['mime_type'], 'text/html') || str_starts_with($doc['mime_type'], 'text/plain')) {
            return [
                'filePath' => $filePath,
                'mimeType' => $doc['mime_type'],
                'fileName' => $doc['original_filename']
            ];
        }

        // Cas 2: Fichiers CSV convertis en tableau HTML
        if ($doc['mime_type'] === 'text/csv') {
            $cachedHtmlPath = $this->cachePath . pathinfo($doc['stored_filename'], PATHINFO_FILENAME) . '.html';
             if (file_exists($cachedHtmlPath)) {
                return [
                    'filePath' => $cachedHtmlPath,
                    'mimeType' => 'text/html',
                    'fileName' => pathinfo($doc['original_filename'], PATHINFO_FILENAME) . '.html'
                ];
            }
            return $this->convertCsvToHtml($filePath, $cachedHtmlPath, $doc['original_filename']);
        }
        
        // Cas 3: Fichiers images et PDF affichés nativement
        $nativeMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($doc['mime_type'], $nativeMimeTypes)) {
            return [
                'filePath' => $filePath,
                'mimeType' => $doc['mime_type'],
                'fileName' => $doc['original_filename']
            ];
        }

        // Cas 4: Formats bureautiques à convertir en PDF (liste étendue)
        $convertibleMimeTypes = [
            // Microsoft Office
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            // OpenDocument (LibreOffice/OpenOffice)
            'application/vnd.oasis.opendocument.text', // .odt
            'application/vnd.oasis.opendocument.spreadsheet', // .ods
            'application/vnd.oasis.opendocument.presentation', // .odp
            // Autres
            'application/rtf',
        ];

        if (in_array($doc['mime_type'], $convertibleMimeTypes)) {
            $cachedPdfPath = $this->cachePath . pathinfo($doc['stored_filename'], PATHINFO_FILENAME) . '.pdf';

            if (file_exists($cachedPdfPath)) {
                return [
                    'filePath' => $cachedPdfPath,
                    'mimeType' => 'application/pdf',
                    'fileName' => pathinfo($doc['original_filename'], PATHINFO_FILENAME) . '.pdf'
                ];
            }

            return $this->convertToPdf($filePath, $cachedPdfPath, $doc['original_filename']);
        }

        // Si le format n'est pris en charge par aucune des méthodes ci-dessus
        throw new RuntimeException('La prévisualisation n\'est pas supportée pour ce type de fichier.', 415);
    }

    /**
     * Convertit un document en PDF en utilisant LibreOffice en ligne de commande.
     */
    private function convertToPdf(string $sourcePath, string $destinationPath, string $originalFilename): array
    {
        $command = "soffice --headless --convert-to pdf " . escapeshellarg($sourcePath) . " --outdir " . escapeshellarg(dirname($destinationPath));
        
        shell_exec("timeout 30 " . $command . " 2>&1");

        $convertedFilePath = dirname($destinationPath) . '/' . pathinfo(basename($sourcePath), PATHINFO_FILENAME) . '.pdf';

        if (!file_exists($convertedFilePath)) {
            throw new RuntimeException("La conversion du document a échoué. Assurez-vous que LibreOffice est installé sur le serveur.", 500);
        }

        rename($convertedFilePath, $destinationPath);

        return [
            'filePath' => $destinationPath,
            'mimeType' => 'application/pdf',
            'fileName' => pathinfo($originalFilename, PATHINFO_FILENAME) . '.pdf'
        ];
    }
    
    /**
     * NOUVELLE MÉTHODE : Convertit un fichier CSV en un tableau HTML simple.
     */
    private function convertCsvToHtml(string $sourcePath, string $destinationPath, string $originalFilename): array
    {
        $html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">';
        $html .= '<title>Aperçu de ' . htmlspecialchars($originalFilename) . '</title>';
        $html .= '<style>
            body { font-family: sans-serif; padding: 1rem; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            thead { background-color: #f2f2f2; }
            tr:nth-child(even) { background-color: #f9f9f9; }
        </style></head><body>';
        $html .= '<h1>' . htmlspecialchars($originalFilename) . '</h1>';
        $html .= '<table>';

        if (($handle = fopen($sourcePath, "r")) !== FALSE) {
            $isHeader = true;
            // CORRECTION APPLIQUÉE ICI pour supprimer l'avertissement de dépréciation
            while (($data = fgetcsv($handle, 2000, ";", '"', "\\")) !== FALSE) {
                if ($isHeader) {
                    $html .= '<thead><tr>';
                    foreach ($data as $cell) {
                        $html .= '<th>' . htmlspecialchars($cell) . '</th>';
                    }
                    $html .= '</tr></thead><tbody>';
                    $isHeader = false;
                } else {
                    $html .= '<tr>';
                    foreach ($data as $cell) {
                        $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                    }
                    $html .= '</tr>';
                }
            }
            fclose($handle);
        }

        $html .= '</tbody></table></body></html>';
        
        file_put_contents($destinationPath, $html);

        return [
            'filePath' => $destinationPath,
            'mimeType' => 'text/html',
            'fileName' => pathinfo($originalFilename, PATHINFO_FILENAME) . '.html'
        ];
    }
}
