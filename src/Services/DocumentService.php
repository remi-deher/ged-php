<?php
// src/Services/DocumentService.php

namespace App\Services;

use App\Repositories\DocumentRepository;
use App\Repositories\FolderRepository;

class DocumentService
{
    private DocumentRepository $documentRepository;
    private FolderRepository $folderRepository;

    public function __construct()
    {
        $this->documentRepository = new DocumentRepository();
        $this->folderRepository = new FolderRepository();
    }

    /**
     * Retrieves folders and documents for a given parent folder.
     */
    public function getDocumentsAndFolders(?int $folderId, array $filters = []): array
    {
        $folders = $this->folderRepository->findByParent($folderId);
        foreach ($folders as &$folder) {
            $folder['type'] = 'folder';
        }

        // Le filtre 'type' a été retiré du Repository,
        // mais l'ajout de la clé 'type' ici est correct pour le front-end.
        $documents = $this->documentRepository->findByFolder($folderId, $filters);
        foreach ($documents as &$doc) {
            $doc['type'] = 'document';
        }

        return array_merge($folders, $documents);
    }

    public function renameDocument(int $id, string $newName): bool
    {
        if (empty(trim($newName))) {
            throw new \Exception("Le nouveau nom ne peut pas être vide.");
        }
        // ** CORRECTION : 'filename' -> 'original_filename' pour correspondre à ged.sql **
        return $this->documentRepository->update($id, ['original_filename' => $newName]);
    }

    public function moveDocument(int $documentId, ?int $targetFolderId): bool
    {
        // C'était déjà correct.
        return $this->documentRepository->update($documentId, ['folder_id' => $targetFolderId]);
    }

    public function downloadDocument(int $id): void
    {
        $document = $this->documentRepository->find($id);

        // ** CORRECTION : Vérification des clés 'storage_path', 'stored_filename' et 'original_filename' **
        if (!$document || !isset($document['storage_path']) || !isset($document['stored_filename']) || !isset($document['original_filename'])) {
            http_response_code(404);
            die('Document non valide ou introuvable.');
        }

        // ** CORRECTION : Construction du chemin de fichier basée sur les colonnes de ged.sql **
        $filePath = __DIR__ . '/../../' . $document['storage_path'] . $document['stored_filename'];

        if (!file_exists($filePath) || is_dir($filePath)) {
            http_response_code(404);
            die('Fichier non trouvé sur le serveur ou est un dossier.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($document['mime_type'] ?? 'application/octet-stream'));
        // ** CORRECTION : Utilisation de 'original_filename' pour le nom de téléchargement **
        header('Content-Disposition: attachment; filename="' . basename($document['original_filename']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        flush();
        readfile($filePath);
        exit;
    }
}
