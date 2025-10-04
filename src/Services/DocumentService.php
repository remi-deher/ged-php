<?php
// src/Services/DocumentService.php

namespace App\Services;

use App\Repositories\DocumentRepository;
use App\Repositories\FolderRepository;
use App\Services\ConfigurationService;

class DocumentService
{
    private DocumentRepository $documentRepository;
    private FolderRepository $folderRepository;
    private ConfigurationService $configService;

    public function __construct()
    {
        $this->documentRepository = new DocumentRepository();
        $this->folderRepository = new FolderRepository();
        $this->configService = new ConfigurationService();
    }

    public function getItemsForFolder(?int $folderId, ?string $searchQuery = null, string $sort = 'created_at', string $order = 'desc', array $filters = []): array
    {
        $items = [];
        if (!$searchQuery) {
            $folders = $this->folderRepository->findChildren($folderId);
            foreach ($folders as $folder) {
                $folder['type'] = 'folder';
                $items[] = $folder;
            }
        }

        $allDocuments = $this->documentRepository->findByFolderAndQuery($folderId, $searchQuery, $sort, $order, $filters);
        
        // Mapper les comptes pour un accès facile
        $allMailSettings = $this->configService->loadMailSettings();
        $accountsMap = [];
        foreach ($allMailSettings as $tenant) {
            foreach ($tenant['accounts'] as $account) {
                $accountsMap[$account['id']] = $account;
            }
        }
        
        $documents = [];
        $attachmentsMap = [];
        foreach ($allDocuments as $doc) {
            // Ajouter l'information de la source
            if ($doc['source_account_id'] && isset($accountsMap[$doc['source_account_id']])) {
                $doc['source_details'] = 'Email: ' . $accountsMap[$doc['source_account_id']]['user_email'];
            } else if ($doc['source_account_id']) {
                $doc['source_details'] = 'Source inconnue';
            } else {
                $doc['source_details'] = 'Manuel';
            }

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

        // Formater la taille et récupérer le mime_type pour chaque pièce jointe
        foreach ($attachments as &$attachment) {
            $attachmentData = $this->documentRepository->find($attachment['id']);
            $attachment['size_formatted'] = $this->formatBytes($attachmentData['size'] ?? 0);
            $attachment['mime_type'] = $attachmentData['mime_type'] ?? 'application/octet-stream';
        }

        return ['main_document' => $mainDocument, 'attachments' => $attachments];
    }
    
    private function formatBytes($bytes, $precision = 2): string
    { 
        if ($bytes === null || $bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
        $base = 1024;
        $pow = floor(log($bytes) / log($base)); 
        return round($bytes / ($base ** $pow), $precision) . ' ' . $units[$pow]; 
    }
}
