<?php
// src/Repositories/DocumentRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class DocumentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM documents WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findManyById(array $ids): array
    {
        if (empty($ids)) return [];
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM documents WHERE id IN ($inQuery)");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByFolderAndQuery(?int $folderId, ?string $searchQuery, string $sort = 'created_at', string $order = 'desc', array $filters = []): array
    {
        // Valider les colonnes de tri autorisées pour éviter l'injection SQL
        $allowedSortColumns = ['name', 'size', 'created_at'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 'created_at';
        }

        // Valider l'ordre
        $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';

        $sql = 'SELECT d.id, d.original_filename as name, d.status, d.created_at, d.parent_document_id, d.folder_id, d.source_account_id, d.size, d.mime_type 
                FROM documents d 
                WHERE d.deleted_at IS NULL';
        
        $params = [];
        if ($searchQuery) {
            $sql .= ' AND d.original_filename LIKE :search_query';
            $params[':search_query'] = '%' . $searchQuery . '%';
        } else {
            $sql .= ' AND d.folder_id ' . ($folderId ? '= :folder_id' : 'IS NULL');
            if ($folderId) {
                $params[':folder_id'] = $folderId;
            }
        }
        
        // Ajout des filtres
        if (!empty($filters['mime_type'])) {
            if ($filters['mime_type'] === 'image') {
                $sql .= ' AND d.mime_type LIKE :mime_type';
                $params[':mime_type'] = 'image/%';
            } else if ($filters['mime_type'] === 'word') {
                 $sql .= ' AND (d.mime_type = :mime_word1 OR d.mime_type = :mime_word2)';
                 $params[':mime_word1'] = 'application/msword';
                 $params[':mime_word2'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            } else {
                $sql .= ' AND d.mime_type = :mime_type';
                $params[':mime_type'] = $filters['mime_type'];
            }
        }
        if (!empty($filters['source'])) {
            if ($filters['source'] === 'email') {
                $sql .= ' AND d.source_account_id IS NOT NULL';
            } else if ($filters['source'] === 'manual') {
                $sql .= ' AND d.source_account_id IS NULL';
            }
        }

        // Appliquer le tri
        $sortColumn = ($sort === 'name') ? 'd.original_filename' : 'd.' . $sort;
        $sql .= " ORDER BY $sortColumn $order";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAttachments(int $parentId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, original_filename FROM documents WHERE parent_document_id = ? AND deleted_at IS NULL");
        $stmt->execute([$parentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): void
    {
        $sql = "INSERT INTO documents (original_filename, stored_filename, storage_path, mime_type, size, status, folder_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'received', ?, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['original_filename'], 
            $data['stored_filename'], 
            'storage/', 
            $data['mime_type'], 
            $data['size'], 
            $data['folder_id'] ?? null
        ]);
    }

    public function move(array $docIds, ?int $folderId): void
    {
        if (empty($docIds)) return;
        $inQuery = implode(',', array_fill(0, count($docIds), '?'));
        $stmt = $this->pdo->prepare("UPDATE documents SET folder_id = ? WHERE id IN ($inQuery)");
        $params = array_merge([$folderId], $docIds);
        $stmt->execute($params);
    }

    public function moveToTrash(array $docIds): void
    {
        if (empty($docIds)) return;
        $inQuery = implode(',', array_fill(0, count($docIds), '?'));
        $stmt = $this->pdo->prepare("UPDATE documents SET deleted_at = NOW() WHERE id IN ($inQuery)");
        $stmt->execute($docIds);
    }

    public function findTrashed(): array
    {
        $stmt = $this->pdo->query('SELECT id, original_filename, deleted_at FROM documents WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function restore(array $docIds): void
    {
        if (empty($docIds)) return;
        $inQuery = implode(',', array_fill(0, count($docIds), '?'));
        $stmt = $this->pdo->prepare("UPDATE documents SET deleted_at = NULL WHERE id IN ($inQuery)");
        $stmt->execute($docIds);
    }

    public function forceDelete(array $docIds): void
    {
        if (empty($docIds)) return;
        $inQuery = implode(',', array_fill(0, count($docIds), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM documents WHERE id IN ($inQuery)");
        $stmt->execute($docIds);
    }

    public function updateStatus(int $docId, string $status): void
    {
        $stmt = $this->pdo->prepare("UPDATE documents SET status = ? WHERE id = ?");
        $stmt->execute([$status, $docId]);
    }

    public function updatePrintJob(int $docId, string $jobId): void
    {
        $stmt = $this->pdo->prepare("UPDATE documents SET status = 'to_print', print_job_id = ? WHERE id = ?");
        $stmt->execute([$jobId, $docId]);
    }

    public function setPrintError(int $docId, string $errorMessage): void
    {
        $stmt = $this->pdo->prepare("UPDATE documents SET status = 'print_error', print_error_message = ? WHERE id = ?");
        $stmt->execute([$errorMessage, $docId]);
    }
    
    public function findPrintJobId(int $docId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT print_job_id FROM documents WHERE id = ?");
        $stmt->execute([$docId]);
        return $stmt->fetchColumn() ?: null;
    }

    public function clearPrintJob(int $docId, ?string $message): void
    {
        $stmt = $this->pdo->prepare("UPDATE documents SET status = 'received', print_job_id = NULL, print_error_message = ? WHERE id = ?");
        $stmt->execute([$message, $docId]);
    }

    public function findPrintingOrErrorDocs(): array
    {
        $stmt = $this->pdo->query("SELECT id, original_filename, print_job_id, print_error_message, status FROM documents WHERE status = 'to_print' OR status = 'print_error'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
