<?php
// src/Services/PreviewService.php

namespace App\Services;

use App\Repositories\DocumentRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html;

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
            mkdir($this->cachePath, 0777, true);
        }
    }

    /**
     * Tente de générer et retourner un aperçu pour un document.
     * Gère la mise en cache et la conversion à la volée.
     *
     * @param int $docId L'ID du document.
     * @return array ['filePath' => string, 'fileName' => string, 'mimeType' => string]
     * @throws \RuntimeException Si la génération de l'aperçu échoue.
     */
    public function getPreview(int $docId): array
    {
        $document = $this->documentRepository->find($docId);
        if (!$document) {
            throw new \RuntimeException("Document non trouvé.", 404);
        }

        $originalFilePath = $this->storagePath . $document['stored_filename'];
        if (!file_exists($originalFilePath)) {
            throw new \RuntimeException("Fichier source introuvable sur le serveur.", 404);
        }

        // Types de fichiers nativement supportés par le navigateur
        $nativeMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'text/plain'];
        if (in_array($document['mime_type'], $nativeMimeTypes)) {
            return [
                'filePath' => $originalFilePath,
                'fileName' => $document['original_filename'],
                'mimeType' => $document['mime_type']
            ];
        }

        $cacheKey = $document['id'] . '_' . filemtime($originalFilePath);

        // --- NOUVEAU : Logique pour les tableurs avec PhpSpreadsheet ---
        $spreadsheetMimeTypes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv'
        ];

        if (in_array($document['mime_type'], $spreadsheetMimeTypes)) {
            $cachedHtmlPath = $this->cachePath . $cacheKey . '.html';

            if (file_exists($cachedHtmlPath)) {
                return [
                    'filePath' => $cachedHtmlPath,
                    'fileName' => basename($document['original_filename']) . '.html',
                    'mimeType' => 'text/html'
                ];
            }

            return $this->convertWithPhpSpreadsheet($originalFilePath, $cachedHtmlPath, $document['original_filename']);
        }

        // --- Logique existante pour la conversion PDF via LibreOffice ---
        $cachedPdfPath = $this->cachePath . $cacheKey . '.pdf';
        if (file_exists($cachedPdfPath)) {
            return [
                'filePath' => $cachedPdfPath,
                'fileName' => basename($document['original_filename']) . '.pdf',
                'mimeType' => 'application/pdf'
            ];
        }

        $this->convertToPdf($originalFilePath, $cachedPdfPath);

        if (!file_exists($cachedPdfPath)) {
            throw new \RuntimeException("La conversion en PDF a échoué. Vérifiez que LibreOffice est installé.", 500);
        }

        return [
            'filePath' => $cachedPdfPath,
            'fileName' => basename($document['original_filename']) . '.pdf',
            'mimeType' => 'application/pdf'
        ];
    }

    /**
     * Convertit un fichier tableur en HTML en utilisant PhpSpreadsheet.
     *
     * @param string $sourcePath
     * @param string $outputPath
     * @param string $originalFilename
     * @return array
     */
    private function convertWithPhpSpreadsheet(string $sourcePath, string $outputPath, string $originalFilename): array
    {
        try {
            $spreadsheet = IOFactory::load($sourcePath);
            $writer = new Html($spreadsheet);
            $writer->save($outputPath);

            return [
                'filePath' => $outputPath,
                'fileName' => basename($originalFilename) . '.html',
                'mimeType' => 'text/html'
            ];
        } catch (\Exception $e) {
            error_log("Erreur de conversion PhpSpreadsheet: " . $e->getMessage());
            throw new \RuntimeException("La conversion du tableur a échoué.", 500);
        }
    }


    /**
     * Convertit un fichier en PDF en utilisant LibreOffice en ligne de commande.
     *
     * @param string $sourcePath Le chemin du fichier source.
     * @param string $outputPath Le chemin où enregistrer le PDF de sortie.
     */
    private function convertToPdf(string $sourcePath, string $outputPath): void
    {
        // Nettoie l'environnement pour éviter les conflits avec LibreOffice
        putenv('HOME=/tmp');
        
        $command = "soffice --headless --convert-to pdf " . escapeshellarg($sourcePath) . " --outdir " . escapeshellarg(dirname($outputPath));
        
        // Exécute la commande et capture la sortie et le code de retour
        exec($command . ' 2>&1', $output, $return_var);

        if ($return_var !== 0) {
            error_log("Erreur de conversion LibreOffice: " . implode("\n", $output));
            // Ne pas lancer d'exception ici pour permettre à getPreview de gérer le fichier manquant
        } else {
            // LibreOffice nomme le fichier de sortie comme l'original, mais avec .pdf
            $expectedPdf = dirname($outputPath) . '/' . pathinfo($sourcePath, PATHINFO_FILENAME) . '.pdf';
            if(file_exists($expectedPdf)) {
                // On renomme le fichier pour qu'il corresponde à notre nom de cache
                rename($expectedPdf, $outputPath);
            }
        }
    }
}
