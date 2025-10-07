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
        // Passer les variables nécessaires à la vue
        $folderService = $this->folderService;
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

    // --------------------------------------------------------------------
    // SECTION API - TOUTES LES MÉTHODES CI-DESSOUS SONT POUR LE JAVASCRIPT
    // --------------------------------------------------------------------

    // Envoie la liste des documents et dossiers en JSON
    public function apiGetDocuments()
    {
        header('Content-Type: application/json');
        try {
            $folderId = $_GET['folder_id'] ?? null;
            $filters = [
                'type' => $_GET['type'] ?? null,
                'status' => $_GET['status'] ?? null,
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null,
            ];
            $documents = $this->documentService->getDocumentsAndFolders($folderId, $filters);
            echo json_encode(['success' => true, 'documents' => $documents]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Gère le téléversement de fichiers via l'API
    public function apiUpload()
    {
        header('Content-Type: application/json');
        try {
            $folderId = $_POST['folder_id'] ?? null;
            $files = $_FILES['documents'] ?? [];
            if (empty($files)) {
                throw new \Exception("Aucun fichier n'a été téléversé.");
            }
            $result = $this->uploaderService->handleUpload($files, $folderId);
            echo json_encode(['success' => true, 'files' => $result]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Crée un dossier via l'API
    public function apiCreateFolder($data)
    {
        header('Content-Type: application/json');
        try {
            $folderName = $data['folderName'] ?? null;
            $parentId = $data['parentId'] ?? null;
            $folder = $this->folderService->createFolder($folderName, $parentId);
            echo json_encode(['success' => true, 'folder' => $folder]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Renomme un élément (dossier ou document) via l'API
    public function apiRenameItem($data)
    {
        header('Content-Type: application/json');
        try {
            $id = $data['id'] ?? null;
            $type = $data['type'] ?? null;
            $newName = $data['newName'] ?? null;

            if ($type === 'folder') {
                $this->folderService->renameFolder($id, $newName);
            } else {
                $this->documentService->renameDocument($id, $newName);
            }
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // Supprime un élément via l'API (le met à la corbeille)
    public function apiDeleteItem($data)
    {
        header('Content-Type: application/json');
        try {
            $id = $data['id'] ?? null;
            $this->trashService->moveToTrash($id);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Déplace un élément vers un autre dossier via l'API
    public function apiMoveItem($data)
    {
        header('Content-Type: application/json');
        try {
            $itemId = $data['itemId'] ?? null;
            $targetFolderId = $data['targetFolderId'] ?? null;
            $this->documentService->moveDocument($itemId, $targetFolderId);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
