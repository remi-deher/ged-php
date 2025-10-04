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
    /**
     * Construit une structure arborescente (imbriquée) à partir d'une liste de dossiers.
     * @param array $elements La liste de tous les dossiers.
     * @param int|null $parentId L'ID du parent pour lequel construire la branche.
     * @return array La branche de l'arbre.
     */
    private function buildFolderTree(array &$elements, ?int $parentId = null): array
    {
        $branch = [];
        foreach ($elements as $key => $element) {
            // Utilise une comparaison non stricte car les données de la BDD peuvent être des chaînes
            if ($element['parent_id'] == $parentId) {
                // Copie l'élément pour ne pas modifier l'original dans la boucle
                $child = $element;
                unset($elements[$key]); // Optimisation: retire l'élément traité
                $children = $this->buildFolderTree($elements, $child['id']);
                if ($children) {
                    $child['children'] = $children;
                }
                $branch[] = $child;
            }
        }
        return $branch;
    }

    /**
     * Récupère tous les dossiers et les retourne sous forme d'arbre hiérarchique.
     * @return array
     */
    private function getFolderTree(): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT id, name, parent_id FROM folders ORDER BY name ASC');
        $allFolders = $stmt->fetchAll();
        return $this->buildFolderTree($allFolders);
    }
    
    /**
     * Récupère la chaîne de parents (fil d'Ariane) pour un dossier donné.
     * @param int|null $folderId L'ID du dossier de départ.
     * @return array Le fil d'Ariane.
     */
    private function getBreadcrumbs(?int $folderId): array
    {
        if ($folderId === null) {
            return [];
        }
        $pdo = Database::getInstance();
        $breadcrumbs = [];
        $currentId = $folderId;
        while ($currentId !== null) {
            $stmt = $pdo->prepare('SELECT id, name, parent_id FROM folders WHERE id = ?');
            $stmt->execute([$currentId]);
            $folder = $stmt->fetch();
            if ($folder) {
                array_unshift($breadcrumbs, $folder);
                $currentId = $folder['parent_id'];
            } else {
                $currentId = null; // Stoppe la boucle si un dossier est introuvable
            }
        }
        return $breadcrumbs;
    }

    /**
     * Affiche la liste des dossiers et documents.
     */
    public function listDocuments(): void
    {
        $pdo = Database::getInstance();
        $currentFolderId = isset($_GET['folder_id']) && $_GET['folder_id'] !== 'root' ? (int)$_GET['folder_id'] : null;
        $searchQuery = $_GET['q'] ?? null;
        
        $folderTree = $this->getFolderTree(); // Arbre pour la sidebar de gauche
        $breadcrumbs = $this->getBreadcrumbs($currentFolderId); // Fil d'Ariane
        $currentFolder = !empty($breadcrumbs) ? end($breadcrumbs) : null;

        $items = [];

        // On n'affiche les sous-dossiers que s'il n'y a pas de recherche active
        if (!$searchQuery) {
            $folderSql = 'SELECT id, name, parent_id, NULL as status, NULL as created_at, NULL as parent_document_id, NULL as source_account_id, NULL as size, NULL as mime_type FROM folders WHERE ';
            if ($currentFolderId) {
                $folderSql .= 'parent_id = :folder_id';
                $folderParams = [':folder_id' => $currentFolderId];
            } else {
                $folderSql .= 'parent_id IS NULL';
                $folderParams = [];
            }
            $folderSql .= ' ORDER BY name ASC';
            
            $foldersStmt = $pdo->prepare($folderSql);
            $foldersStmt->execute($folderParams);
            foreach($foldersStmt->fetchAll() as $folder) {
                $folder['type'] = 'folder';
                $items[] = $folder;
            }
        }
        
        // Requête pour les documents
        $docSql = 'SELECT d.id, d.original_filename as name, d.status, d.created_at, d.parent_document_id, d.folder_id, d.source_account_id, d.size, d.mime_type 
                   FROM documents d 
                   WHERE d.deleted_at IS NULL';
        
        $params = [];

        if ($searchQuery) {
            $docSql .= ' AND d.original_filename LIKE :search_query';
            $params[':search_query'] = '%' . $searchQuery . '%';
        } else {
            $docSql .= ' AND d.folder_id ' . ($currentFolderId ? '= :folder_id' : 'IS NULL');
            if ($currentFolderId) {
                $params[':folder_id'] = $currentFolderId;
            }
        }
        
        $docSql .= ' ORDER BY d.created_at DESC';
        
        $stmt = $pdo->prepare($docSql);
        $stmt->execute($params);
        $allDocuments = $stmt->fetchAll();
        
        // Logique pour grouper les pièces jointes avec leur e-mail parent
        $documents = [];
        $attachmentsMap = [];
        foreach ($allDocuments as $doc) {
            if ($doc['parent_document_id'] !== null) {
                $attachmentsMap[$doc['parent_document_id']][] = $doc;
            } else {
                $doc['type'] = 'document';
                $doc['attachments'] = [];
                $documents[$doc['id']] = $doc;
            }
        }
        foreach ($attachmentsMap as $parentId => $attachments) {
            if (isset($documents[$parentId])) {
                $documents[$parentId]['attachments'] = $attachments;
            }
        }
        
        // Fusionne les dossiers et les documents dans la liste finale
        $items = array_merge($items, array_values($documents));
        
        require_once dirname(__DIR__, 2) . '/templates/home.php';
    }

    /**
     * Gère la création d'un dossier, potentiellement dans un dossier parent.
     */
    public function createFolder(): void
    {
        if (isset($_POST['folder_name']) && !empty(trim($_POST['folder_name']))) {
            $folderName = trim($_POST['folder_name']);
            // Gère le cas où 'parent_id' est une chaîne vide pour la racine
            $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;
            
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("INSERT INTO folders (name, parent_id) VALUES (?, ?)");
            $stmt->execute([$folderName, $parentId]);
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
    }
    
    // ... Le reste des méthodes (getPrintQueueStatus, uploadDocument, downloadDocument, etc.) reste inchangé ...
    // ... Vous pouvez les copier depuis votre fichier existant ou la réponse précédente.
    
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
        $currentFolderId = isset($_POST['folder_id']) && !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
        $uploader = new FileUploaderService();
        try {
            $uploadedFile = $uploader->handleUpload($_FILES['document']);
            $pdo = Database::getInstance();
            $sql = "INSERT INTO documents (original_filename, stored_filename, storage_path, mime_type, size, status, folder_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'received', ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uploadedFile['original_filename'], $uploadedFile['stored_filename'], 'storage/', $uploadedFile['mime_type'], $uploadedFile['size'], $currentFolderId]);
            $this->notifyClients('new_document', ['filename' => $uploadedFile['original_filename']]);
        } catch (\Exception $e) {
            error_log('Upload/DB Error: ' . $e->getMessage());
            header('Location: /?error=' . urlencode($e->getMessage()));
            exit();
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
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

    public function moveDocument(): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        $docIds = [];
        if (isset($_POST['doc_id'])) {
            $docIds[] = (int)$_POST['doc_id'];
        } elseif (isset($_POST['doc_ids']) && is_array($_POST['doc_ids'])) {
            $docIds = array_map('intval', $_POST['doc_ids']);
        }

        if (!empty($docIds) && isset($_POST['folder_id'])) {
            try {
                $folderId = $_POST['folder_id'] === 'root' ? null : (int)$_POST['folder_id'];
                
                $pdo = Database::getInstance();
                
                $inQuery = implode(',', array_fill(0, count($docIds), '?'));
                $stmt = $pdo->prepare("UPDATE documents SET folder_id = ? WHERE id IN ($inQuery)");
                
                $params = array_merge([$folderId], $docIds);
                $stmt->execute($params);

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
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/') . '?error=' . urlencode('Erreur lors du déplacement'));
                exit();
            }
        }

        if (!$isAjax) {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit();
        }
    }

    public function moveFolder(): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Le déplacement de dossier n\'est pas encore implémenté.']);
            exit();
        }
        
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
        exit();
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
        if ($bytes === null || $bytes <= 0) return '';
        $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
        $pow = floor(log($bytes) / log(1024)); 
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow]; 
    }
}
