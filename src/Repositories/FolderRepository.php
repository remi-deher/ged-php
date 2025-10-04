<?php
// src/Repositories/FolderRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class FolderRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, parent_id, default_printer_id FROM folders ORDER BY name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, parent_id, default_printer_id FROM folders WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findChildren(?int $parentId): array
    {
        $sql = 'SELECT id, name, parent_id, NULL as status, NULL as created_at, NULL as parent_document_id, NULL as source_account_id, NULL as size, NULL as mime_type FROM folders WHERE parent_id';
        if ($parentId) {
            $sql .= ' = :folder_id';
            $params = [':folder_id' => $parentId];
        } else {
            $sql .= ' IS NULL';
            $params = [];
        }
        $sql .= ' ORDER BY name ASC';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $name, ?int $parentId): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO folders (name, parent_id) VALUES (?, ?)");
        $stmt->execute([$name, $parentId]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updatePrinter(int $folderId, ?string $printerId): void
    {
        $stmt = $this->pdo->prepare("UPDATE folders SET default_printer_id = ? WHERE id = ?");
        $stmt->execute([$printerId, $folderId]);
    }
}
