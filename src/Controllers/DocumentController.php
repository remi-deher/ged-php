<?php
// src/Controllers/DocumentController.php

namespace App\Controllers;

use App\Services\DocumentService;
use App\Services\FolderService;
use App\Services\FileUploaderService;
use App\Services\PrintService;
use App\Services\TrashService;
use App\Repositories\DocumentRepository;
use App\Repositories\FolderRepository;
use WebSocket\Client as WebSocketClient;
use PDO;

class DocumentController
{
    private DocumentService $documentService;
    private FolderService $folderService;
    private PrintService $printService;
    private TrashService $trashService;
    private DocumentRepository $documentRepository; // Gardé pour les actions simples
    private FolderRepository $folderRepository;

    public function __construct()
    {
        $this->documentService = new DocumentService();
        $this->folderService = new FolderService();
        $this->printService = new PrintService();
        $this->trashService = new TrashService();
        $this->documentRepository = new DocumentRepository();
        $this->folderRepository = new FolderRepository();
    }

    // --- Logique d'affichage ---

    public function listDocuments(): void
    {
        $currentFolderId = isset($_GET['folder_id']) && $_GET['folder_id'] !== 'root' ? (int)$_GET['folder_id'] : null;
        $searchQuery = $_GET['q'] ?? null;
        
        $folderTree = $this->folderService->getFolderTree();
        $breadcrumbs = $this->folderService->getBreadcrumbs($currentFolderId);
        $currentFolder = !empty($breadcrumbs) ? end($breadcrumbs) : null;
        $items = $this->documentService->getItemsForFolder($currentFolderId, $searchQuery);
        
        require_once dirname(__DIR__, 2) . '/templates/home.php';
    }

    public function getDocumentDetails(int $docId): void
    {
        header('Content-Type: application/json');
        $details = $this->documentService->getDocumentDetails($docId);
        if (!$details) {
            http_response_code(404);
            echo json_encode(['error' => 'Document not found']);
            return;
        }
        echo json_encode($details);
    }

    // --- Actions sur les dossiers et documents ---

    public function createFolder(): void
    {
        if (isset($_POST['folder_name']) && !empty(trim($_POST['folder_name']))) {
            $folderName = trim($_POST['folder_name']);
            $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
            $this->folderRepository->create($folderName, $parentId);
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }

    public function uploadDocument(): void
    {
        if (!isset($_FILES['document'])) { 
            header('Location: /?error=nofile'); 
            exit(); 
        }
        
        $currentFolderId = isset($_POST['folder_id']) && !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
        $uploader = new FileUploaderService();
        
        try {
            $uploadedFile = $uploader->handleUpload($_FILES['document']);
            $uploadedFile['folder_id'] = $currentFolderId;
            $this->documentRepository->create($uploadedFile);
            $this->notifyClients('new_document', ['filename' => $uploadedFile['original_filename']]);
        } catch (\Exception $e) {
            error_log('Upload/DB Error: ' . $e->getMessage());
            header('Location: /?error=' . urlencode($e->getMessage()));
            exit();
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }

    public function moveDocument(): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        $docIds = isset($_POST['doc_ids']) && is_array($_POST['doc_ids']) ? array_map('intval', $_POST['doc_ids']) : [];

        if (!empty($docIds) && isset($_POST['folder_id'])) {
            try {
                $folderId = $_POST['folder_id'] === 'root' ? null : (int)$_POST['folder_id'];
                $this->documentRepository->move($docIds, $folderId);
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Document(s) déplacé(s) avec succès.']);
                    exit();
                }
            } catch (\Exception $e) {
                if ($isAjax) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit();
                }
            }
        }
        if (!$isAjax) {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit();
        }
    }

    public function downloadDocument(int $docId): void
    {
        $doc = $this->documentRepository->find($docId);
        if (!$doc) { 
            http_response_code(404); 
            die('Document not found.'); 
        }
        $filePath = dirname(__DIR__, 2) . '/storage/' . $doc['stored_filename'];
        if (!file_exists($filePath)) { 
            http_response_code(404); 
            die('File not found on server.'); 
        }
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: inline; filename="' . basename($doc['original_filename']) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    
    // --- Logique d'impression (wrapper pour PrintService) ---

    public function getPrintQueueStatus(): void
    {
        header('Content-Type: application/json');
        try {
            $queueStatus = $this->printService->getPrintQueueStatus();
            echo json_encode($queueStatus);
        } catch (\Throwable $e) {
            error_log("Erreur dans getPrintQueueStatus: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur interne du serveur.', 'message' => $e->getMessage()]);
        }
        exit();
    }

    public function printSingleDocument(): void
    {
        if (!isset($_POST['doc_id'])) die("ID de document manquant.");
        $this->printService->sendToPrinter((int)$_POST['doc_id']);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }

    public function printBulkDocuments(): void
    {
        if (empty($_POST['doc_ids'])) die("Aucun document sélectionné.");
        foreach ($_POST['doc_ids'] as $docId) {
            $this->printService->sendToPrinter((int)$docId);
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }
    
    public function cancelPrintJob(): void
    {
        header('Content-Type: application/json');
        if (empty($_POST['doc_id'])) { http_response_code(400); echo json_encode(['error' => 'ID de document manquant.']); exit(); }
        
        try {
            $this->printService->cancelPrintJob((int)$_POST['doc_id']);
            $this->notifyClients('print_cancelled', ['doc_id' => (int)$_POST['doc_id']]);
            echo json_encode(['success' => true, 'message' => 'Travail d\'impression annulé.']);
        } catch (\Exception $e) {
            http_response_code(500);
            error_log("Erreur d'annulation : " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }
    
    public function clearPrintJobError(): void
    {
        header('Content-Type: application/json');
        if (empty($_POST['doc_id'])) { http_response_code(400); echo json_encode(['error' => 'ID de document manquant.']); exit(); }
        
        try {
            $this->printService->clearPrintJobError((int)$_POST['doc_id']);
            $this->notifyClients('print_error_cleared', ['doc_id' => (int)$_POST['doc_id']]);
            echo json_encode(['success' => true, 'message' => 'L\'erreur a été effacée.']);
        } catch (\Exception $e) {
            http_response_code(500);
            error_log("Erreur nettoyage erreur impression : " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }
    
    // --- Logique de la corbeille (wrapper pour TrashService) ---

    public function moveToTrash(): void
    {
        if (!empty($_POST['doc_ids'])) {
            $this->trashService->moveToTrash($_POST['doc_ids']);
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }

    public function listTrash(): void
    {
        $documents = $this->trashService->getTrashedDocuments();
        require_once dirname(__DIR__, 2) . '/templates/trash.php';
    }
    
    public function restoreDocument(): void
    {
        if (!empty($_POST['doc_ids'])) {
            $this->trashService->restoreDocuments($_POST['doc_ids']);
        }
        header('Location: /trash');
        exit();
    }

    public function forceDelete(): void
    {
        if (!empty($_POST['doc_ids'])) {
            $this->trashService->forceDeleteDocuments($_POST['doc_ids']);
        }
        header('Location: /trash');
        exit();
    }

    // --- Helper ---

    private function notifyClients(string $action, array $data): void
    {
        try {
            $client = new WebSocketClient("ws://127.0.0.1:8082");
            $client->send(json_encode(['action' => $action, 'data' => $data]));
            $client->close();
        } catch (\Exception $e) {
            error_log("Impossible de se connecter au serveur WebSocket : " . $e->getMessage());
        }
    }
}
