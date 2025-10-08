<?php
// src/Controllers/DocumentController.php

namespace App\Controllers;

use App\Services\DocumentService;
use App\Services\FolderService;
use App\Services\FileUploaderService;
use App\Services\PreviewService;
use App\Services\PrintService;
use App\Services\TrashService;

class DocumentController
{
    private $documentService;
    private $folderService;
    private $uploaderService;
    private $previewService;
    private $printService;
    private $trashService;

    public function __construct()
    {
        $this->documentService = new DocumentService();
        $this->folderService = new FolderService();
        $this->uploaderService = new FileUploaderService();
        $this->previewService = new PreviewService();
        $this->printService = new PrintService();
        $this->trashService = new TrashService();
    }

    // Affiche la page d'accueil
    public function home()
    {
        $folderId = $_GET['folder_id'] ?? null;
        $currentFolder = $this->folderService->getFolderById($folderId);
        $folderService = $this->folderService; // Pour la sidebar
        require_once __DIR__ . '/../../templates/home.php';
    }
    
    // Affiche la corbeille
    public function trash()
    {
        $documents = $this->trashService->getTrashedItems();
        require_once __DIR__ . '/../../templates/trash.php';
    }

    // Gère le téléchargement d'un document
    public function download()
    {
        $docId = $_GET['id'] ?? null;
        if (!$docId) {
            http_response_code(400);
            echo "ID de document manquant.";
            return;
        }
        $this->documentService->downloadDocument($docId);
    }
    
    // Génère un aperçu de document
    public function preview()
    {
        $docId = $_GET['id'] ?? null;
        if (!$docId) {
            http_response_code(400);
            echo "ID de document manquant.";
            return;
        }
        $this->previewService->generatePreview($docId);
    }

    // --- API METHODS ---

    // Récupère les documents et dossiers pour l'affichage principal
    public function apiGetDocuments()
    {
        header('Content-Type: application/json');
        try {
            $folderId = $_GET['folder_id'] ?? null;
            $filters = $_GET;
            $items = $this->documentService->getDocumentsAndFolders($folderId, $filters);
            echo json_encode(['documents' => $items]); // Assurez-vous que la clé est 'documents' comme attendu par le JS
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Gère l'upload de fichiers
    public function apiUpload()
    {
        header('Content-Type: application/json');
        try {
            $folderId = $_POST['folder_id'] ?? null;
            if ($folderId === 'null') $folderId = null;
            $result = $this->uploaderService->handleUpload($_FILES['files'], $folderId);
            echo json_encode(['success' => true, 'files' => $result]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Crée un dossier
    public function apiCreateFolder($data)
    {
        header('Content-Type: application/json');
        try {
            $folderName = $data['name'] ?? null;
            $parentId = $data['parent_id'] ?? null;
            $folder = $this->folderService->createFolder($folderName, $parentId);
            echo json_encode(['success' => true, 'folder' => $folder]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Renomme un item
    public function apiRenameItem($data)
    {
        header('Content-Type: application/json');
        try {
            $id = $data['id'] ?? null;
            $type = $data['type'] ?? null;
            $newName = $data['new_name'] ?? null;

            if ($type === 'folder') {
                $this->folderService->renameFolder($id, $newName);
            } else {
                $this->documentService->renameDocument($id, $newName);
            }
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Supprime un item (déplace vers la corbeille)
    public function apiDeleteItem($data)
    {
        header('Content-Type: application/json');
        try {
            $id = $data['id'] ?? null;
            $type = $data['type'] ?? null;
            $this->trashService->moveToTrash($id, $type);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Déplace un item
    public function apiMoveItem($data)
    {
        header('Content-Type: application/json');
        try {
            $itemId = $data['itemId'] ?? null;
            $itemType = $data['itemType'] ?? 'document';
            $targetFolderId = $data['targetFolderId'] ?? null;

            if ($itemType === 'document') {
                 $this->documentService->moveDocument($itemId, $targetFolderId);
            } else {
                $this->folderService->moveFolder($itemId, $targetFolderId);
            }

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
