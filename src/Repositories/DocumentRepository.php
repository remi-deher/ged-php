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
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * CORRECTION MAJEURE
     * Trouve les documents dans un dossier en appliquant des filtres.
     * Gère le cas où folderId est null (racine).
     *
     * @param int|null $folderId
     * @param array $filters
     * @return array
     */
    public function findByFolder(?int $folderId, array $filters = []): array
    {
        // Base de la requête SQL
        $sql = "SELECT * FROM documents WHERE deleted_at IS NULL";
        $params = [];

        // Ajoute la condition sur le dossier parent
        if ($folderId === null) {
            $sql .= " AND folder_id IS NULL";
        } else {
            $sql .= " AND folder_id = :folder_id";
            $params[':folder_id'] = $folderId;
        }

        // --- Application des filtres ---
        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND created_at >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND created_at <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        // Ajoute le tri
        $sql .= " ORDER BY created_at DESC";

        // Exécution de la requête
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
        // Construit la requête dynamiquement pour ne mettre à jour que les champs fournis
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
