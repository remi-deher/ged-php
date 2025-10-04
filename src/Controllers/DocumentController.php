<?php
// src/Controllers/DocumentController.php

namespace App\Controllers;

use App\Services\DocumentService;
use App\Services\FolderService;
use App\Services\FileUploaderService;
use App\Services\PrintService;
use App\Services\TrashService;
use App\Services\PreviewService; // Ajout de l'import
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
    private PreviewService $previewService; // Ajout de la propriété
    private DocumentRepository $documentRepository;
    private FolderRepository $folderRepository;

    public function __construct()
    {
        $this->documentService = new DocumentService();
        $this->folderService = new FolderService();
        $this->printService = new PrintService();
        $this->trashService = new TrashService();
        $this->previewService = new PreviewService(); // Instanciation du service
        $this->documentRepository = new DocumentRepository();
        $this->folderRepository = new FolderRepository();
    }

    // --- Logique d'affichage ---

    public function listDocuments(): void
    {
        $currentFolderId = isset($_GET['folder_id']) && $_GET['folder_id'] !== 'root' ? (int)$_GET['folder_id'] : null;
        $searchQuery = $_GET['q'] ?? null;
        
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'desc';
        
        $filters = [
            'mime_type' => $_GET['mime_type'] ?? null,
            'source' => $_GET['source'] ?? null,
        ];

        $folderTree = $this->folderService->getFolderTree();
        $breadcrumbs = $this->folderService->getBreadcrumbs($currentFolderId);
        $currentFolder = !empty($breadcrumbs) ? end($breadcrumbs) : null;
        $items = $this->documentService->getItemsForFolder($currentFolderId, $searchQuery, $sort, $order, $filters);
        
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

    /**
     * Gère la prévisualisation des documents, avec conversion à la volée.
     */
    public function previewDocument(int $docId): void
    {
        try {
            $preview = $this->previewService->getPreview($docId);

            // Supprime l'en-tête X-Frame-Options ajoutée par le reverse proxy
            header_remove('X-Frame-Options');

            header('Content-Type: ' . $preview['mimeType']);
            header('Content-Disposition: inline; filename="' . basename($preview['fileName']) . '"');
            header('Content-Length: ' . filesize($preview['filePath']));
            readfile($preview['filePath']);
            exit;
        } catch (\RuntimeException $e) {
            // Afficher un message d'erreur clair en cas de problème
            http_response_code($e->getCode() ?: 500);
            echo "<h1>Erreur de prévisualisation</h1>";
            echo "<p>Impossible de générer un aperçu pour ce document.</p>";
            echo "<p><strong>Détail :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            if ($e->getCode() === 500) {
                 echo "<p><strong>Note :</strong> Pour les documents Word, Excel, etc., assurez-vous que LibreOffice est bien installé sur le serveur et accessible par l'utilisateur du serveur web (`www-data`).</p>";
            }
            exit;
        }
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
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        $filesKey = isset($_FILES['documents']) ? 'documents' : 'document';

        if (empty($_FILES[$filesKey])) {
            if ($isAjax) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu.']);
            } else {
                header('Location: /?error=nofile');
            }
            exit();
        }

        $files = $this->reformatFilesArray($_FILES[$filesKey]);
        $currentFolderId = isset($_POST['folder_id']) && !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
        $uploader = new FileUploaderService();
        $errors = [];
        $successCount = 0;

        foreach ($files as $file) {
            try {
                $uploadedFile = $uploader->handleUpload($file);
                $uploadedFile['folder_id'] = $currentFolderId;
                $this->documentRepository->create($uploadedFile);
                $this->notifyClients('new_document', ['filename' => $uploadedFile['original_filename']]);
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = $file['name'] . ': ' . $e->getMessage();
                error_log('Upload Error: ' . $e->getMessage());
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            if ($successCount > 0 && empty($errors)) {
                echo json_encode(['success' => true, 'message' => "$successCount fichier(s) téléversé(s) avec succès !"]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Erreur lors du téléversement : " . implode(', ', $errors)]);
            }
        } else {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        }
        exit();
    }

    /**
     * Reformate le tableau $_FILES pour le rendre itérable.
     */
    private function reformatFilesArray(array $files): array
    {
        if (!is_array($files['name'])) {
            return [$files];
        }

        $fileArray = [];
        $fileCount = count($files['name']);
        $fileKeys = array_keys($files);

        for ($i = 0; $i < $fileCount; $i++) {
            foreach ($fileKeys as $key) {
                $fileArray[$i][$key] = $files[$key][$i];
            }
        }
        return $fileArray;
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
        // Force le téléchargement au lieu de l'affichage
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($doc['original_filename']) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    
    // --- Logique d'impression ---

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
    
    // --- Logique de la corbeille ---

    public function moveToTrash(): void
    {
        if (!empty($_POST['doc_ids'])) {
            $this->trashService->moveToTrash($_POST['doc_ids']);
            $this->notifyClients('document_deleted', ['doc_ids' => $_POST['doc_ids']]);
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }

    public function listTrash(): void
    {
        $folderTree = $this->folderService->getFolderTree();
        $currentFolderId = null;

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
