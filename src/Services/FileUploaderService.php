<?php
// src/Services/FileUploaderService.php

namespace App\Services;

class FileUploaderService
{
    private string $storagePath;

    public function __construct(string $storagePath = null)
    {
        $this->storagePath = $storagePath ?: dirname(__DIR__, 2) . '/storage/';
    }

    /**
     * Gère la validation et le déplacement d'un fichier uploadé.
     * @param array $file Le tableau de fichier de $_FILES.
     * @return array Les informations sur le fichier stocké.
     * @throws \RuntimeException En cas d'erreur.
     */
    public function handleUpload(array $file): array
    {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("Une erreur est survenue lors de l'envoi du fichier.");
        }

        $allowedMimeTypes = [
            'application/pdf', 
            'image/jpeg', 
            'image/png', 
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($file['type'], $allowedMimeTypes)) {
            throw new \RuntimeException("Type de fichier non autorisé : " . htmlspecialchars($file['type']));
        }

        $originalFilename = basename($file['name']);
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $storedFilename = 'doc_' . uniqid('', true) . '.' . $extension;
        $destination = $this->storagePath . $storedFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException("Impossible de déplacer le fichier uploadé vers le stockage.");
        }

        return [
            'original_filename' => $originalFilename,
            'stored_filename'   => $storedFilename,
            'mime_type'         => $file['type'],
            'size'              => $file['size'],
            'full_path'         => $destination
        ];
    }
}
