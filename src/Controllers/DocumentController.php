<?php
// src/Controllers/DocumentController.php

namespace App\Controllers;

use App\Core\Database;
use App\Services\FileUploaderService; // Import du service
use PDO;
use WebSocket\Client;

class DocumentController
{
    /**
     * Affiche la page d'accueil avec les documents actifs et les dossiers.
     */
    public function listDocuments(): void
    {
        $pdo = Database::getInstance();

        // Récupérer le dossier actuel (ou racine si non spécifié)
        $currentFolderId = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;

        // Récupérer tous les dossiers pour la navigation
        $foldersStmt = $pdo->query('SELECT id, name FROM folders ORDER BY name ASC');
        $folders = $foldersStmt->fetchAll();

        // Récupérer les documents du dossier actuel
        $sql = 'SELECT id, original_filename, status, created_at, parent_document_id FROM documents WHERE deleted_at IS NULL AND folder_id ' . ($currentFolderId ? '= ?' : 'IS NULL') . ' ORDER BY created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($currentFolderId ? [$currentFolderId] : []);
        $allDocuments = $stmt->fetchAll();

        // Organiser les documents en une structure parent-enfant
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

    /**
     * Gère l'envoi d'un nouveau document en utilisant le FileUploaderService.
     */
    public function uploadDocument(): void
    {
        if (!isset($_FILES['document'])) {
            header('Location: /?error=nofile');
            exit();
        }

        $uploader = new FileUploaderService();

        try {
            $uploadedFile = $uploader->handleUpload($_FILES['document']);

            $pdo = Database::getInstance();
            $sql = "INSERT INTO documents (original_filename, stored_filename, storage_path, mime_type, size, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, 'received', NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $uploadedFile['original_filename'],
                $uploadedFile['stored_filename'],
                'storage/',
                $uploadedFile['mime_type'],
                $uploadedFile['size']
            ]);
            
            $this->notifyClients('new_document', ['filename' => $uploadedFile['original_filename'], 'timestamp' => date('Y-m-d H:i:s')]);

        } catch (\RuntimeException $e) {
            error_log('Upload Error: ' . $e->getMessage());
            header('Location: /?error=' . urlencode($e->getMessage()));
            exit();
        } catch (\PDOException $e) {
            if (isset($uploadedFile['full_path']) && file_exists($uploadedFile['full_path'])) {
                unlink($uploadedFile['full_path']);
            }
            error_log('Database Error after upload: ' . $e->getMessage());
            header('Location: /?error=db_error');
            exit();
        }
        
        header('Location: /');
        exit();
    }

    /**
     * Récupère les détails d'un document et ses pièces jointes en JSON.
     */
    public function getDocumentDetails(int $docId): void
    {
        header('Content-Type: application/json');
        $pdo = Database::getInstance();

        try {
            // Récupérer le document principal (l'e-mail)
            $mainDocStmt = $pdo->prepare("SELECT id, original_filename, stored_filename FROM documents WHERE id = ? AND deleted_at IS NULL");
            $mainDocStmt->execute([$docId]);
            $mainDocument = $mainDocStmt->fetch(PDO::FETCH_ASSOC);

            if (!$mainDocument) {
                http_response_code(404);
                echo json_encode(['error' => 'Document not found']);
                return;
            }

            // Récupérer les pièces jointes
            $attachmentsStmt = $pdo->prepare("SELECT id, original_filename FROM documents WHERE parent_document_id = ? AND deleted_at IS NULL");
            $attachmentsStmt->execute([$docId]);
            $attachments = $attachmentsStmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'main_document' => $mainDocument,
                'attachments' => $attachments
            ];

            echo json_encode($response);

        } catch (\PDOException $e) {
            http_response_code(500);
            error_log('Error fetching document details: ' . $e->getMessage());
            echo json_encode(['error' => 'Database error']);
        }
        exit();
    }

    /**
     * Gère le téléchargement d'un fichier.
     */
    public function downloadDocument(int $docId): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT original_filename, stored_filename, mime_type FROM documents WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$docId]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            http_response_code(404);
            die('Document not found.');
        }

        $filePath = dirname(__DIR__, 2) . '/storage/' . $doc['stored_filename'];

        if (!file_exists($filePath)) {
            http_response_code(404);
            die('File not found on server.');
        }

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


    /**
     * Met à jour le statut d'un document et déclenche l'impression si nécessaire.
     */
    public function updateDocumentStatus(): void
    {
        if (!isset($_POST['doc_id']) || !isset($_POST['status'])) {
            die("Données manquantes.");
        }

        $docId = (int)$_POST['doc_id'];
        $newStatus = $_POST['status'];
        $allowedStatus = ['received', 'to_print', 'printed'];

        if (!in_array($newStatus, $allowedStatus)) {
            die("Statut non valide.");
        }
        
        try {
            $pdo = Database::getInstance();
            $sql = "UPDATE documents SET status = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newStatus, $docId]);

            if ($newStatus === 'to_print') {
                $this->printDocument($docId);
            }

            $this->notifyClients('status_update', ['doc_id' => $docId, 'new_status' => $newStatus]);

        } catch (\PDOException $e) {
            die("Erreur de base de données : " . $e->getMessage());
        }
        
        header('Location: /');
        exit();
    }

    /**
     * Envoie un document au serveur d'impression CUPS.
     */
    public function printDocument(int $docId): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT stored_filename FROM documents WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$docId]);
        $document = $stmt->fetch();

        if (!$document) {
            error_log("Tentative d'impression d'un document non trouvé ou supprimé (ID: $docId)");
            return;
        }

        $filePath = dirname(__DIR__, 2) . '/storage/' . $document['stored_filename'];
        $printerName = 'GedPrinter';

        $command = "sudo lp -d " . escapeshellarg($printerName) . " " . escapeshellarg($filePath);
        $output = shell_exec($command . " 2>&1");

        preg_match('/request id is\s(.*?)\s/', $output, $matches);
        
        if (isset($matches[1])) {
            $jobId = $matches[1];
            $updateStmt = $pdo->prepare("UPDATE documents SET print_job_id = ? WHERE id = ?");
            $updateStmt->execute([$jobId, $docId]);
        } else {
            error_log("Erreur d'impression CUPS pour doc ID $docId: " . $output);
        }
    }

    /**
     * Met plusieurs documents dans la corbeille (soft delete).
     */
    public function moveToTrash(): void
    {
        if (empty($_POST['doc_ids'])) {
            die("Aucun document sélectionné.");
        }
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

    /**
     * Restaure plusieurs documents depuis la corbeille.
     */
    public function restoreDocument(): void
    {
        if (empty($_POST['doc_ids'])) {
            die("Aucun document sélectionné.");
        }
        $docIds = $_POST['doc_ids'];

        $pdo = Database::getInstance();
        $inQuery = implode(',', array_fill(0, count($docIds), '?'));
        
        $stmt = $pdo->prepare("UPDATE documents SET deleted_at = NULL WHERE id IN ($inQuery)");
        $stmt->execute($docIds);
        
        header('Location: /trash');
        exit();
    }

    /**
     * Supprime définitivement plusieurs documents.
     */
    public function forceDelete(): void
    {
        if (empty($_POST['doc_ids'])) {
            die("Aucun document sélectionné.");
        }
        $docIds = $_POST['doc_ids'];
        $inQuery = implode(',', array_fill(0, count($docIds), '?'));

        $pdo = Database::getInstance();
        
        $stmt = $pdo->prepare("SELECT stored_filename FROM documents WHERE id IN ($inQuery)");
        $stmt->execute($docIds);
        $documents = $stmt->fetchAll();

        foreach ($documents as $document) {
            $filePath = dirname(__DIR__, 2) . '/storage/' . $document['stored_filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $deleteStmt = $pdo->prepare("DELETE FROM documents WHERE id IN ($inQuery)");
        $deleteStmt->execute($docIds);

        header('Location: /trash');
        exit();
    }
    
    /**
     * Affiche la liste des documents dans la corbeille.
     */
    public function listTrash(): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT id, original_filename, deleted_at FROM documents WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');
        $documents = $stmt->fetchAll();

        require_once dirname(__DIR__, 2) . '/templates/trash.php';
    }

    /**
     * Crée un nouveau dossier.
     */
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

    /**
     * Déplace un document vers un dossier.
     */
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

    /**
     * Fonction générique pour envoyer des notifications au serveur WebSocket.
     */
    private function notifyClients(string $action, array $data): void
    {
        try {
            $client = new Client("ws://127.0.0.1:8082");
            $payload = json_encode(['action' => $action, 'data' => $data]);
            $client->send($payload);
            $client->close();
        } catch (\Exception $e) {
            error_log("Impossible de se connecter au serveur WebSocket : " . $e->getMessage());
        }
    }
}
