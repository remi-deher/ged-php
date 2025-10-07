<?php
// src/Repositories/DocumentRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class DocumentRepository
{
    private ?PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Finds documents in a folder, applying filters.
     */
    public function findByFolder(?int $folderId, array $filters = []): array
    {
        // Base of the SQL query
        $sql = "SELECT * FROM documents";
        $params = [];
        $conditions = [];

        // Add condition for the parent folder
        if ($folderId === null) {
            $conditions[] = "folder_id IS NULL";
        } else {
            $conditions[] = "folder_id = :folder_id";
            $params[':folder_id'] = $folderId;
        }

        // --- Apply Filters ---
        if (!empty($filters['type'])) {
            $conditions[] = "type = :type";
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = "status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $conditions[] = "created_at >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $conditions[] = "created_at <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        // Add conditions to the main query if they exist
        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // Add sorting
        $sql .= " ORDER BY created_at DESC";

        // Execute the query
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): array
    {
        $stmt = $this->db->prepare(
            "INSERT INTO documents (filename, file_path, mime_type, size, folder_id, created_at, updated_at) 
             VALUES (:filename, :file_path, :mime_type, :size, :folder_id, NOW(), NOW())"
        );
        $stmt->execute([
            ':filename' => $data['filename'],
            ':file_path' => $data['file_path'],
            ':mime_type' => $data['mime_type'],
            ':size' => $data['size'],
            ':folder_id' => $data['folder_id'],
        ]);
        $id = $this->db->lastInsertId();
        return $this->find($id);
    }
    
    public function update(int $id, array $data): bool
    {
        // Dynamically build the query to update only the provided fields
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        $fieldList = implode(', ', $fields);

        $stmt = $this->db->prepare("UPDATE documents SET {$fieldList}, updated_at = NOW() WHERE id = :id");
        return $stmt->execute($params);
    }
}
