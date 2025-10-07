<?php
// src/Services/FolderService.php

namespace App\Services;

use App\Repositories\FolderRepository;

class FolderService
{
    private FolderRepository $folderRepository;

    public function __construct()
    {
        $this->folderRepository = new FolderRepository();
    }

    /**
     * NOUVELLE MÉTHODE
     * Récupère un dossier par son ID.
     * Si l'ID est null, retourne les informations pour la racine.
     *
     * @param int|null $folderId
     * @return array|null
     */
    public function getFolderById(?int $folderId): ?array
    {
        if ($folderId === null) {
            // C'est la racine, on retourne une structure par défaut
            return ['id' => null, 'name' => 'Mes documents', 'parent_id' => null];
        }
        return $this->folderRepository->find($folderId);
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
                unset($elements[$key]); // Optimisation pour réduire les recherches futures
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
     * Renommé de getBreadcrumbs pour plus de clarté.
     *
     * @param int|null $folderId
     * @return array
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
                $currentId = null; // Stoppe la boucle si un parent n'est pas trouvé
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
