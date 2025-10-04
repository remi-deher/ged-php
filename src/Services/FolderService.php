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

    public function getBreadcrumbs(?int $folderId): array
    {
        if ($folderId === null) {
            return [];
        }
        $breadcrumbs = [];
        $currentId = $folderId;
        while ($currentId !== null) {
            $folder = $this->folderRepository->find($currentId);
            if ($folder) {
                array_unshift($breadcrumbs, $folder);
                $currentId = $folder['parent_id'];
            } else {
                $currentId = null;
            }
        }
        return $breadcrumbs;
    }
}
