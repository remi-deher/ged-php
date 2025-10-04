<?php
// src/Services/DocumentService.php

namespace App\Services;

use App\Repositories\DocumentRepository;
use App\Repositories\FolderRepository;

class DocumentService
{
    private DocumentRepository $documentRepository;
    private FolderRepository $folderRepository;

    public function __construct()
    {
        $this->documentRepository = new DocumentRepository();
        $this->folderRepository = new FolderRepository();
    }

    public function getItemsForFolder(?int $folderId, ?string $searchQuery = null): array
    {
        $items = [];
        if (!$searchQuery) {
            $folders = $this->folderRepository->findChildren($folderId);
            foreach ($folders as $folder) {
                $folder['type'] = 'folder';
                $items[] = $folder;
            }
        }

        $allDocuments = $this->documentRepository->findByFolderAndQuery($folderId, $searchQuery);
        
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
        
        return array_merge($items, array_values($documents));
    }

    public function getDocumentDetails(int $docId): ?array
    {
        $mainDocument = $this->documentRepository->find($docId);
        if (!$mainDocument) {
            return null;
        }

        $mainDocument['size_formatted'] = $this->formatBytes($mainDocument['size']);
        $attachments = $this->documentRepository->findAttachments($docId);

        return ['main_document' => $mainDocument, 'attachments' => $attachments];
    }
    
    private function formatBytes($bytes, $precision = 2): string
    { 
        if ($bytes === null || $bytes <= 0) return '';
        $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
        $pow = floor(log($bytes) / log(1024)); 
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow]; 
    }
}
