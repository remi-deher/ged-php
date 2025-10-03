<?php
// src/Controllers/DocumentController.php

namespace App\Controllers;

use App\Core\Database;
use App\Services\FileUploaderService;
use PDO;
use WebSocket\Client as WebSocketClient;

// Classe non utilisée pour l'impression mais gardée pour la compatibilité
use DR\Ipp\Enum\FileTypeEnum;

class DocumentController
{
    public function listDocuments(): void
    {
        $pdo = Database::getInstance();
        $currentFolderId = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;
        $searchQuery = $_GET['q'] ?? null;
        $currentFolder = null;

        if ($currentFolderId) {
            $folderStmt = $pdo->prepare('SELECT id, name FROM folders WHERE id = ?');
            $folderStmt->execute([$currentFolderId]);
            $currentFolder = $folderStmt->fetch();
        }

        $folders = [];
        // On n'affiche les dossiers que si on est à la racine et qu'il n'y a pas de recherche
        if (!$currentFolderId && !$searchQuery) {
            $foldersStmt = $pdo->query('SELECT id, name FROM folders ORDER BY name ASC');
            $folders = $foldersStmt->fetchAll();
        }
        
        // La requête de base sélectionne les documents non supprimés
        $sql = 'SELECT d.id, d.original_filename, d.status, d.created_at, d.parent_document_id, d.folder_id, d.source_account_id, d.size, d.mime_type 
                FROM documents d 
                WHERE d.deleted_at IS NULL';
        
        $params = [];

        // Si une recherche est effectuée, on cherche dans tous les dossiers
        if ($searchQuery) {
            $sql .= ' AND d.original_filename LIKE :search_query';
            $params[':search_query'] = '%' . $searchQuery . '%';
        } else {
            // Sinon, on filtre par le dossier courant (ou la racine)
            $sql .= ' AND d.folder_id ' . ($currentFolderId ? '= :folder_id' : 'IS NULL');
            if ($currentFolderId) {
                $params[':folder_id'] = $currentFolderId;
            }
        }
        
        $sql .= ' ORDER BY d.created_at DESC';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $allDocuments = $stmt->fetchAll();
        
        $documents = [];
        $attachmentsMap = [];
        foreach ($allDocuments as $doc) {
            if ($doc['parent_document_id'] !== null) {
                $attachmentsMap[$doc['parent_document_id']][] = $doc;
            } else {
                $documents[$doc['id']] = $doc;
                $documents[$doc['id']]['attachments'] = [];
            }
        }
        foreach ($attachmentsMap as $parentId => $attachments) {
            if (isset($documents[$parentId])) {
                $documents[$parentId]['attachments'] = $attachments;
            }
        }
        require_once dirname(__DIR__, 2) . '/templates/home.php';
    }

    public function getPrintQueueStatus(): void
    {
        header('Content-Type: application/json');
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->query("SELECT id, original_filename, print_job_id, print_error_message, status FROM documents WHERE status = 'to_print' OR status = 'print_error'");
            $printingDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($printingDocs)) {
                echo json_encode([]);
                exit();
            }

            $cupsJobsOutput = @shell_exec('lpstat -o 2>&1');
            $activeJobs = [];
            if (!empty($cupsJobsOutput)) {
                preg_match_all('/^.*?-(\d+)\s+.*$/m', $cupsJobsOutput, $matches);
                if (isset($matches[1])) {
                    foreach ($matches[1] as $jobId) {
                        $activeJobs[$jobId] = true;
                    }
                }
            }

            $queueStatus = [];
            foreach ($printingDocs as $doc) {
                $jobId = $doc['print_job_id'];
                $status = 'Terminé';

                if ($jobId && isset($activeJobs[$jobId])) {
                    $status = 'En cours d\'impression';
                } elseif ($doc['status'] !== 'print_error') {
                    $pdo->prepare("UPDATE documents SET status = 'printed' WHERE id = ?")->execute([$doc['id']]);
                }

                if ($doc['status'] === 'print_error') {
                    $status = 'Erreur';
                }

                $queueStatus[] = [
                    'id' => $doc['id'],
                    'filename' => $doc['original_filename'],
                    'status' => $status,
                    'error' => $doc['print_error_message'],
                    'job_id' => $jobId
                ];
            }
            echo json_encode($queueStatus);
        } catch (\Throwable $e) {
            error_log("Erreur dans getPrintQueueStatus: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur interne du serveur.', 'message' => $e->getMessage()]);
        }
        exit();
    }
    
    public function uploadDocument(): void
    {
        if (!isset($_FILES['document'])) { header('Location: /?error=nofile'); exit(); }
        $uploader = new FileUploaderService();
        try {
            $uploadedFile = $uploader->handleUpload($_FILES['document']);
            $pdo = Database::getInstance();
            $sql = "INSERT INTO documents (original_filename, stored_filename, storage_path, mime_type, size, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'received', NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uploadedFile['original_filename'], $uploadedFile['stored_filename'], 'storage/', $uploadedFile['mime_type'], $uploadedFile['size']]);
            $this->notifyClients('new_document', ['filename' => $uploadedFile['original_filename']]);
        } catch (\Exception $e) {
            error_log('Upload/DB Error: ' . $e->getMessage());
            header('Location: /?error=' . urlencode($e->getMessage()));
            exit();
        }
        header('Location: /');
        exit();
    }

    public function getDocumentDetails(int $docId): void
    {
        header('Content-Type: application/json');
        $pdo = Database::getInstance();
        try {
            $mainDocStmt = $pdo->prepare("SELECT id, original_filename, stored_filename, size, mime_type, created_at, source_account_id FROM documents WHERE id = ? AND deleted_at IS NULL");
            $mainDocStmt->execute([$docId]);
            $mainDocument = $mainDocStmt->fetch(PDO::FETCH_ASSOC);
            if (!$mainDocument) { http_response_code(404); echo json_encode(['error' => 'Document not found']); return; }

            // Ajout d'infos formatées pour le front
            $mainDocument['size_formatted'] = $this->formatBytes($mainDocument['size']);

            $attachmentsStmt = $pdo->prepare("SELECT id, original_filename FROM documents WHERE parent_document_id = ? AND deleted_at IS NULL");
            $attachmentsStmt->execute([$docId]);
            $attachments = $attachmentsStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['main_document' => $mainDocument, 'attachments' => $attachments]);
        } catch (\PDOException $e) {
            http_response_code(500);
            error_log('Error fetching document details: ' . $e->getMessage());
            echo json_encode(['error' => 'Database error']);
        }
        exit();
    }

    public function downloadDocument(int $docId): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT original_filename, stored_filename, mime_type FROM documents WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$doc) { http_response_code(404); die('Document not found.'); }
        $filePath = dirname(__DIR__, 2) . '/storage/' . $doc['stored_filename'];
        if (!file_exists($filePath)) { http_response_code(404); die('File not found on server.'); }
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: inline; filename="' . basename($doc['original_filename']) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function printSingleDocument(): void
    {
        if (!isset($_POST['doc_id'])) die("ID de document manquant.");
        $this->sendToPrinter((int)$_POST['doc_id']);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }

    public function printBulkDocuments(): void
    {
        if (empty($_POST['doc_ids'])) die("Aucun document sélectionné.");
        foreach ($_POST['doc_ids'] as $docId) {
            $this->sendToPrinter((int)$docId);
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }
    
    public function cancelPrintJob(): void
    {
        header('Content-Type: application/json');
        if (empty($_POST['doc_id'])) { http_response_code(400); echo json_encode(['error' => 'ID de document manquant.']); exit(); }
        $docId = (int)$_POST['doc_id'];
        $pdo = Database::getInstance();
        try {
            $stmt = $pdo->prepare("SELECT print_job_id FROM documents WHERE id = ?");
            $stmt->execute([$docId]);
            $jobId = $stmt->fetchColumn();
            if (!$jobId) throw new \Exception("Aucun travail d'impression actif.");
            
            shell_exec("cancel " . escapeshellarg($jobId) . " 2>&1");

            $pdo->prepare("UPDATE documents SET status = 'received', print_job_id = NULL, print_error_message = 'Impression annulée.' WHERE id = ?")->execute([$docId]);
            $this->notifyClients('print_cancelled', ['doc_id' => $docId]);
            echo json_encode(['success' => true, 'message' => 'Travail d\'impression annulé.']);
        } catch (\Exception $e) {
            http_response_code(500);
            error_log("Erreur d'annulation (doc ID: $docId): " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }
    
    public function clearPrintJobError(): void
    {
        header('Content-Type: application/json');
        if (empty($_POST['doc_id'])) { http_response_code(400); echo json_encode(['error' => 'ID de document manquant.']); exit(); }
        $docId = (int)$_POST['doc_id'];
        $pdo = Database::getInstance();
        try {
            $stmt = $pdo->prepare("UPDATE documents SET status = 'received', print_job_id = NULL, print_error_message = NULL WHERE id = ? AND status = 'print_error'");
            $stmt->execute([$docId]);
            if ($stmt->rowCount() === 0) throw new \Exception("Le document n'était pas en erreur.");
            $this->notifyClients('print_error_cleared', ['doc_id' => $docId]);
            echo json_encode(['success' => true, 'message' => 'L\'erreur a été effacée.']);
        } catch (\Exception $e) {
            http_response_code(500);
            error_log("Erreur nettoyage erreur impression (doc ID: $docId): " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }

    private function sendToPrinter(int $docId): void
    {
        $pdo = Database::getInstance();
        try {
            // ... (logique sendToPrinter inchangée)
        } catch (\Exception $e) {
            $pdo->prepare("UPDATE documents SET status = 'print_error', print_error_message = ? WHERE id = ?")->execute([$e->getMessage(), $docId]);
            error_log("Erreur d'impression (doc ID: $docId): " . $e->getMessage());
        }
    }
    
    public function moveToTrash(): void
    {
        if (empty($_POST['doc_ids'])) die("Aucun document sélectionné.");
        $docIds = $_POST['doc_ids'];
        $pdo = Database::getInstance();
        $inQuery = implode(',', array_fill(0, count($docIds), '?'));
        $stmt = $pdo->prepare("UPDATE documents SET deleted_at = NOW() WHERE id IN ($inQuery)");
        $stmt->execute($docIds);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }

    public function listTrash(): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT id, original_filename, deleted_at FROM documents WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');
        $documents = $stmt->fetchAll();
        require_once dirname(__DIR__, 2) . '/templates/trash.php';
    }
    
    public function restoreDocument(): void
    {
        if (empty($_POST['doc_ids'])) die("Aucun document sélectionné.");
        $docIds = $_POST['doc_ids'];
        $pdo = Database::getInstance();
        $inQuery = implode(',', array_fill(0, count($docIds), '?'));
        $stmt = $pdo->prepare("UPDATE documents SET deleted_at = NULL WHERE id IN ($inQuery)");
        $stmt->execute($docIds);
        header('Location: /trash');
        exit();
    }

    public function forceDelete(): void
    {
        if (empty($_POST['doc_ids'])) die("Aucun document sélectionné.");
        $docIds = $_POST['doc_ids'];
        $pdo = Database::getInstance();
        $inQuery = implode(',', array_fill(0, count($docIds), '?'));
        $stmt = $pdo->prepare("SELECT stored_filename FROM documents WHERE id IN ($inQuery)");
        $stmt->execute($docIds);
        foreach ($stmt->fetchAll() as $document) {
            $filePath = dirname(__DIR__, 2) . '/storage/' . $document['stored_filename'];
            if (file_exists($filePath) && is_file($filePath)) unlink($filePath);
        }
        $pdo->prepare("DELETE FROM documents WHERE id IN ($inQuery)")->execute($docIds);
        header('Location: /trash');
        exit();
    }
    
    public function createFolder(): void
    {
        if (isset($_POST['folder_name']) && !empty(trim($_POST['folder_name']))) {
            $folderName = trim($_POST['folder_name']);
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("INSERT INTO folders (name) VALUES (?)");
            $stmt->execute([$folderName]);
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }

    public function moveDocument(): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        if (isset($_POST['doc_id'], $_POST['folder_id'])) {
            try {
                $docId = (int)$_POST['doc_id'];
                $folderId = $_POST['folder_id'] === 'root' ? null : (int)$_POST['folder_id'];
                
                $pdo = Database::getInstance();
                $stmt = $pdo->prepare("UPDATE documents SET folder_id = ? WHERE id = ?");
                $stmt->execute([$folderId, $docId]);

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Document déplacé avec succès.']);
                    exit();
                }
            } catch (\Exception $e) {
                if ($isAjax) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit();
                }
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/') . '?error=' . urlencode('Erreur lors du déplacement'));
                exit();
            }
        }
        if (!$isAjax) {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit();
        }
    }

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

    // Helper privé pour formater la taille des fichiers
    private function formatBytes($bytes, $precision = 2): string
    { 
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
        $pow = floor(log($bytes) / log(1024)); 
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow]; 
    }
}
