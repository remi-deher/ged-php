<?php
// src/Services/FolderService.php

namespace App\Services;

use App\Repositories\FolderRepository;

class FolderService
{
    private FolderRepository $folderRepository;

    /**
     * Constructeur modifié pour l'injection de dépendances.
     */
    public function __construct(FolderRepository $folderRepository = null)
    {
        $this->folderRepository = $folderRepository ?: new FolderRepository();
    }

    /**
     * Récupère un dossier par son ID.
     */
    public function getFolderById(?int $folderId): ?array
    {
        if ($folderId === null) {
            return ['id' => null, 'name' => 'Mes documents', 'parent_id' => null];
        }
        return $this->folderRepository->find($folderId);
    }

    /**
     * --- NOUVELLE MÉTHODE AJOUTÉE ---
     * Récupère la liste de tous les dossiers à plat.
     * C'est la méthode qui manquait et causait l'erreur fatale.
     *
     * @return array
     */
    public function getAllFoldersFlat(): array
    {
        return $this->folderRepository->findAll();
    }

    public function getFolderTree(): array
    {
        $allFolders = $this->folderRepository->findAll();
        return $this->buildFolderTree($allFolders);
    }

    private function buildFolderTree(array &$elements, ?int $parentId = null): array
    {
        $branch = [];
        foreach ($elements as $key => $element) {
            if ($element['parent_id'] == $parentId) {
                $child = $element;
                unset($elements[$key]);
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
     * Récupère le chemin complet (parents) d'un dossier.
     */
    public function getFolderPath(?int $folderId): array
    {
        if ($folderId === null) {
            return [];
        }
        $path = [];
        $currentId = $folderId;
        while ($currentId !== null) {
            $folder = $this->folderRepository->find($currentId);
            if ($folder) {
                array_unshift($path, $folder);
                $currentId = $folder['parent_id'];
            } else {
                $currentId = null;
            }
        }
        return $path;
    }

    // --- Fonctions pour les actions API ---

    public function createFolder(string $name, ?int $parentId = null): array
    {
        if (empty(trim($name))) {
            throw new \Exception("Le nom du dossier ne peut pas être vide.");
        }
        return $this->folderRepository->create($name, $parentId);
    }

    public function renameFolder(int $id, string $newName): bool
    {
         if (empty(trim($newName))) {
            throw new \Exception("Le nouveau nom ne peut pas être vide.");
        }
        return $this->folderRepository->update($id, ['name' => $newName]);
    }
}
