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
     * Récupère les dossiers et les documents pour un dossier parent donné.
     * C'est la fonction principale appelée par l'API.
     */
    public function getDocumentsAndFolders(?int $folderId, array $filters = []): array
    {
        // 1. Récupérer les sous-dossiers
        $folders = $this->folderRepository->findByParent($folderId);
        // Ajoute un type pour que le JavaScript puisse les différencier
        foreach ($folders as &$folder) {
            $folder['type'] = 'folder';
        }

        // 2. Récupérer les documents dans le dossier
        $documents = $this->documentRepository->findByFolder($folderId, $filters);
        // Ajoute un type pour le JS
        foreach ($documents as &$doc) {
            $doc['type'] = 'document';
        }

        // 3. Fusionner les deux listes
        $allItems = array_merge($folders, $documents);

        return $allItems;
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
        if (!$document) {
            http_response_code(404);
            die('Document non trouvé.');
        }

        $filePath = __DIR__ . '/../../' . $document['file_path'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            die('Fichier non trouvé sur le serveur.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $document['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($document['filename']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
