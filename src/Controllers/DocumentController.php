<?php
// src/Controllers/DocumentController.php

namespace App\Controllers;

use App\Core\Database;
use PDO;
use WebSocket\Client;

class DocumentController
{
    /**
     * Affiche la page d'accueil avec les documents actifs (non supprimés).
     */
    public function listDocuments(): void
    {
        $pdo = Database::getInstance();
        // MODIFICATION : On ne sélectionne que les documents où deleted_at est NULL
        $stmt = $pdo->query('SELECT id, original_filename, status, created_at FROM documents WHERE deleted_at IS NULL ORDER BY created_at DESC');
        $documents = $stmt->fetchAll();

        require_once dirname(__DIR__, 2) . '/templates/home.php';
    }

    /**
     * Gère l'envoi d'un nouveau document.
     */
    public function uploadDocument(): void
    {
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            die("Erreur lors de l'envoi du fichier.");
        }

        $file = $_FILES['document'];
        $storagePath = dirname(__DIR__, 2) . '/storage/';
        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        if (!in_array($file['type'], $allowedMimeTypes)) {
            die("Type de fichier non autorisé.");
        }

        $originalFilename = basename($file['name']);
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $storedFilename = 'doc_' . uniqid('', true) . '.' . $extension;
        $destination = $storagePath . $storedFilename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            try {
                $pdo = Database::getInstance();
                $sql = "INSERT INTO documents (original_filename, stored_filename, storage_path, mime_type, size, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, 'received', NOW(), NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$originalFilename, $storedFilename, 'storage/', $file['type'], $file['size']]);
                
                $this->notifyClients('new_document', ['filename' => $originalFilename, 'timestamp' => date('Y-m-d H:i:s')]);

            } catch (\PDOException $e) {
                unlink($destination);
                die("Erreur de base de données : " . $e->getMessage());
            }
        }
        
        header('Location: /');
        exit();
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

            // Si le nouveau statut est "À imprimer", on lance l'impression
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
     * [NOUVEAU] Met un document dans la corbeille (soft delete).
     */
    public function moveToTrash(): void
    {
        if (!isset($_POST['doc_id'])) {
            die("ID de document manquant.");
        }
        $docId = (int)$_POST['doc_id'];

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE documents SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$docId]);

        $this->notifyClients('document_deleted', ['doc_id' => $docId]);
        
        header('Location: /');
        exit();
    }

    /**
     * [NOUVEAU] Restaure un document depuis la corbeille.
     */
    public function restoreDocument(): void
    {
        if (!isset($_POST['doc_id'])) {
            die("ID de document manquant.");
        }
        $docId = (int)$_POST['doc_id'];

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE documents SET deleted_at = NULL WHERE id = ?");
        $stmt->execute([$docId]);
        
        // On pourrait notifier les clients de la restauration
        
        header('Location: /trash'); // Redirige vers la page de la corbeille
        exit();
    }

    /**
     * [NOUVEAU] Supprime définitivement un document.
     */
    public function forceDelete(): void
    {
        if (!isset($_POST['doc_id'])) {
            die("ID de document manquant.");
        }
        $docId = (int)$_POST['doc_id'];

        $pdo = Database::getInstance();
        
        // 1. Récupérer le nom du fichier pour le supprimer du disque
        $stmt = $pdo->prepare("SELECT stored_filename FROM documents WHERE id = ?");
        $stmt->execute([$docId]);
        $document = $stmt->fetch();

        if ($document) {
            $filePath = dirname(__DIR__, 2) . '/storage/' . $document['stored_filename'];
            if (file_exists($filePath)) {
                unlink($filePath); // Suppression du fichier physique
            }
        }

        // 2. Supprimer l'entrée de la base de données
        $deleteStmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
        $deleteStmt->execute([$docId]);

        header('Location: /trash');
        exit();
    }
    
    /**
     * [NOUVEAU] Affiche la liste des documents dans la corbeille.
     */
    public function listTrash(): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT id, original_filename, deleted_at FROM documents WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');
        $documents = $stmt->fetchAll();

        // Il faudra créer ce nouveau fichier de template
        require_once dirname(__DIR__, 2) . '/templates/trash.php';
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

