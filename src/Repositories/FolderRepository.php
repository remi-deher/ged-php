<?php
// src/Repositories/FolderRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class FolderRepository
{
    private ?PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM folders WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM folders WHERE deleted_at IS NULL ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * NOUVELLE MÉTHODE
     * Trouve tous les dossiers enfants directs d'un dossier parent.
     */
    public function findByParent(?int $parentId): array
    {
        if ($parentId === null) {
            // Dossiers à la racine
            $stmt = $this->db->prepare("SELECT * FROM folders WHERE parent_id IS NULL AND deleted_at IS NULL ORDER BY name ASC");
            $stmt->execute();
        } else {
            // Dossiers dans un dossier spécifique
            $stmt = $this->db->prepare("SELECT * FROM folders WHERE parent_id = :parent_id AND deleted_at IS NULL ORDER BY name ASC");
            $stmt->execute([':parent_id' => $parentId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create(string $name, ?int $parentId = null): array
    {
        $stmt = $this->db->prepare(
            "INSERT INTO folders (name, parent_id, created_at, updated_at) VALUES (:name, :parent_id, NOW(), NOW())"
        );
        $stmt->execute([':name' => $name, ':parent_id' => $parentId]);
        $id = $this->db->lastInsertId();
        return $this->find($id);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE folders SET name = :name, updated_at = NOW() WHERE id = :id");
        return $stmt->execute([':name' => $data['name'], ':id' => $id]);
    }
}
