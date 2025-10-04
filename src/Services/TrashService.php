<?php
// src/Services/TrashService.php

namespace App\Services;

use App\Repositories\DocumentRepository;

class TrashService
{
    private DocumentRepository $documentRepository;
    private string $storagePath;

    public function __construct()
    {
        $this->documentRepository = new DocumentRepository();
        $this->storagePath = dirname(__DIR__, 2) . '/storage/';
    }

    public function getTrashedDocuments(): array
    {
        return $this->documentRepository->findTrashed();
    }

    public function restoreDocuments(array $docIds): void
    {
        if (empty($docIds)) {
            return;
        }
        $this->documentRepository->restore($docIds);
    }

    public function forceDeleteDocuments(array $docIds): void
    {
        if (empty($docIds)) {
            return;
        }
        
        $documents = $this->documentRepository->findManyById($docIds);
        foreach ($documents as $document) {
            $filePath = $this->storagePath . $document['stored_filename'];
            if (file_exists($filePath) && is_file($filePath)) {
                unlink($filePath);
            }
        }

        $this->documentRepository->forceDelete($docIds);
    }
}
