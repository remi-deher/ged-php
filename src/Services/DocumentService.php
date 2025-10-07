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
        return $this->documentRepository->update($id, ['filename' => $newName]);
    }

    public function moveDocument(int $documentId, ?int $targetFolderId): bool
    {
        return $this->documentRepository->update($documentId, ['folder_id' => $targetFolderId]);
    }

    public function downloadDocument(int $id): void
    {
        $document = $this->documentRepository->find($id);

        if (!$document || !isset($document['file_path']) || !isset($document['filename'])) {
            http_response_code(404);
            die('Document non valide ou introuvable.');
        }

        $filePath = __DIR__ . '/../../' . $document['file_path'];

        if (!file_exists($filePath) || is_dir($filePath)) {
            http_response_code(404);
            die('Fichier non trouvé sur le serveur ou est un dossier.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($document['mime_type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($document['filename']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        flush();
        readfile($filePath);
        exit;
    }
}
