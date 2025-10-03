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
        $currentFolder = null;

        $initialPrintQueue = $this->getInitialPrintQueue();

        if ($currentFolderId) {
            $folderStmt = $pdo->prepare('SELECT id, name FROM folders WHERE id = ?');
            $folderStmt->execute([$currentFolderId]);
            $currentFolder = $folderStmt->fetch();
        }

        $folders = [];
        if (!$currentFolderId) {
            $foldersStmt = $pdo->query('SELECT id, name FROM folders ORDER BY name ASC');
            $folders = $foldersStmt->fetchAll();
        }
        
        $sql = 'SELECT d.id, d.original_filename, d.status, d.created_at, d.parent_document_id, d.folder_id, d.source_account_id FROM documents d WHERE d.deleted_at IS NULL AND d.folder_id ' . ($currentFolderId ? '= ?' : 'IS NULL') . ' ORDER BY d.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($currentFolderId ? [$currentFolderId] : []);
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
                $lines = explode("\n", trim($cupsJobsOutput));
                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;
                    $parts = preg_split('/\s+/', $line);
                    if (isset($parts[0])) {
                        $jobIdParts = explode('-', $parts[0]);
                        $jobId = end($jobIdParts);
                        if (is_numeric($jobId)) {
                            $activeJobs[$jobId] = ['status' => 'pending/printing', 'full_line' => $line];
                        }
                    }
                }
            }
            $printerStatusOutput = @shell_exec('lpstat -p 2>&1');
            $printerErrors = [];
            if (!empty($printerStatusOutput)) {
                $lines = explode("\n", trim($printerStatusOutput));
                foreach ($lines as $line) {
                    if (stripos($line, 'disabled') !== false || stripos($line, 'Paused') !== false || stripos($line, 'stopped') !== false) {
                        $parts = preg_split('/\s+/', $line);
                        $printerName = $parts[1] ?? 'unknown';
                        $printerErrors[] = "L'imprimante '$printerName' semble en pause ou arrêtée.";
                    }
                }
            }
            $globalError = empty($printerErrors) ? null : implode(' ', $printerErrors);

            $queueStatus = [];
            foreach ($printingDocs as $doc) {
                $jobId = $doc['print_job_id'];
                $status = 'Terminé';
                $error = $globalError;

                if ($jobId && isset($activeJobs[$jobId])) {
                    $status = 'En cours d\'impression';
                } elseif ($doc['status'] !== 'print_error') {
                    $pdo->prepare("UPDATE documents SET status = 'printed' WHERE id = ?")->execute([$doc['id']]);
                }

                if ($doc['status'] === 'print_error') {
                    $status = 'Erreur';
                    $error = $doc['print_error_message'];
                }

                $queueStatus[] = [
                    'id' => $doc['id'],
                    'filename' => $doc['original_filename'],
                    'status' => $status,
                    'error' => $error,
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
    
    private function getInitialPrintQueue(): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT id, original_filename, print_job_id, status FROM documents WHERE status = 'to_print' AND print_job_id IS NOT NULL");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $this->notifyClients('new_document', ['filename' => $uploadedFile['original_filename'], 'timestamp' => date('Y-m-d H:i:s')]);
        } catch (\Exception $e) {
            error_log('Upload/DB Error: ' . $e->getMessage());
            if (isset($uploadedFile['full_path']) && file_exists($uploadedFile['full_path'])) unlink($uploadedFile['full_path']);
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
            $mainDocStmt = $pdo->prepare("SELECT id, original_filename, stored_filename FROM documents WHERE id = ? AND deleted_at IS NULL");
            $mainDocStmt->execute([$docId]);
            $mainDocument = $mainDocStmt->fetch(PDO::FETCH_ASSOC);
            if (!$mainDocument) { http_response_code(404); echo json_encode(['error' => 'Document not found']); return; }
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
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: inline; filename="' . basename($doc['original_filename']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function updateDocumentStatus(): void
    {
        if (!isset($_POST['doc_id']) || !isset($_POST['status'])) die("Données manquantes.");
        $docId = (int)$_POST['doc_id'];
        $newStatus = $_POST['status'];
        $allowedStatus = ['received', 'to_print', 'printed', 'print_error'];
        if (!in_array($newStatus, $allowedStatus)) die("Statut non valide.");
        
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("UPDATE documents SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $docId]);
            if ($newStatus === 'to_print') {
                $this->sendToPrinter($docId);
            }
            $this->notifyClients('status_update', ['doc_id' => $docId, 'new_status' => $newStatus]);
        } catch (\PDOException $e) {
            die("Erreur de base de données : " . $e->getMessage());
        }
        
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
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

    private function sendToPrinter(int $docId): void
    {
        $pdo = Database::getInstance();
        try {
            $printSettingsFile = dirname(__DIR__, 2) . '/config/print_settings.json';
            if (!file_exists($printSettingsFile)) throw new \Exception("Fichier de configuration d'impression manquant.");
            $printers = json_decode(file_get_contents($printSettingsFile), true);
            if (empty($printers)) throw new \Exception("Aucune imprimante configurée.");

            $stmt = $pdo->prepare("SELECT d.original_filename, d.stored_filename, d.folder_id, d.source_account_id, f.default_printer_id as folder_printer_id FROM documents d LEFT JOIN folders f ON d.folder_id = f.id WHERE d.id = ? AND d.deleted_at IS NULL");
            $stmt->execute([$docId]);
            $document = $stmt->fetch();
            if (!$document) throw new \Exception("Document ID $docId non trouvé.");
            
            $pdo->prepare("UPDATE documents SET status = 'to_print', print_error_message = NULL WHERE id = ?")->execute([$docId]);

            $printerIdToUse = null;
            if ($document['folder_printer_id']) $printerIdToUse = $document['folder_printer_id'];
            elseif ($document['source_account_id']) {
                $mailSettings = json_decode(file_get_contents(dirname(__DIR__, 2) . '/config/mail_settings.json'), true);
                foreach ($mailSettings as $tenant) {
                    foreach ($tenant['accounts'] as $account) {
                        if ($account['id'] === $document['source_account_id'] && !empty($account['default_printer_id'])) {
                            $printerIdToUse = $account['default_printer_id']; break 2;
                        }
                    }
                }
            }

            $printerConfig = null;
            if ($printerIdToUse) {
                foreach ($printers as $printer) if ($printer['id'] === $printerIdToUse) $printerConfig = $printer;
            }
            if (!$printerConfig && !empty($printers[0])) $printerConfig = $printers[0];
            if (!$printerConfig) throw new \Exception("Impossible de déterminer une imprimante.");
            
            $filePath = dirname(__DIR__, 2) . '/storage/' . $document['stored_filename'];
            if (!file_exists($filePath)) throw new \Exception("Fichier à imprimer non trouvé : " . $filePath);

            // --- CORRECTION DÉFINITIVE APPLIQUÉE ICI ---
            // On extrait le nom système de l'imprimante depuis l'URI.
            $printerSystemName = basename(parse_url($printerConfig['uri'], PHP_URL_PATH));
            
            $escapedPrinterName = escapeshellarg($printerSystemName);
            $escapedFilePath = escapeshellarg($filePath);
            $escapedTitle = escapeshellarg($document['original_filename']);
            
            // Construit la commande `lp` avec le nom système correct.
            $command = "lp -d {$escapedPrinterName} -t {$escapedTitle} {$escapedFilePath} 2>&1";
            
            $output = shell_exec($command);

            if (preg_match('/request id is .*?-(\d+)/', $output, $matches)) {
                $jobId = $matches[1];
                $pdo->prepare("UPDATE documents SET print_job_id = ? WHERE id = ?")->execute([$jobId, $docId]);
                $this->notifyClients('print_sent', [
                    'doc_id' => $docId, 
                    'filename' => $document['original_filename'],
                    'message' => "Document '" . htmlspecialchars($document['original_filename']) . "' envoyé à l'imprimante."
                ]);
            } else {
                throw new \Exception("Échec de la commande d'impression : " . ($output ?: "Aucune sortie. Vérifiez les permissions de l'utilisateur www-data."));
            }

        } catch (\Exception $e) {
            $pdo->prepare("UPDATE documents SET status = 'print_error', print_error_message = ? WHERE id = ?")
                ->execute([$e->getMessage(), $docId]);
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
        foreach ($docIds as $docId) {
            $this->notifyClients('document_deleted', ['doc_id' => $docId]);
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
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
            if (file_exists($filePath)) unlink($filePath);
        }
        $pdo->prepare("DELETE FROM documents WHERE id IN ($inQuery)")->execute($docIds);
        header('Location: /trash');
        exit();
    }
    
    public function listTrash(): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT id, original_filename, deleted_at FROM documents WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');
        $documents = $stmt->fetchAll();
        require_once dirname(__DIR__, 2) . '/templates/trash.php';
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
        if (isset($_POST['doc_id'], $_POST['folder_id'])) {
            $docId = (int)$_POST['doc_id'];
            $folderId = $_POST['folder_id'] === 'root' ? null : (int)$_POST['folder_id'];
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("UPDATE documents SET folder_id = ? WHERE id = ?");
            $stmt->execute([$folderId, $docId]);
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }

    private function notifyClients(string $action, array $data): void
    {
        try {
            // L'URL du WebSocket est maintenant gérée par le reverse proxy
            $client = new WebSocketClient("ws://127.0.0.1:8082");
            $client->send(json_encode(['action' => $action, 'data' => $data]));
            $client->close();
        } catch (\Exception $e) {
            error_log("Impossible de se connecter au serveur WebSocket : " . $e->getMessage());
        }
    }
}
