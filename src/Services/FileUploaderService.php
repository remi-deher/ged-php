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
        // GESTION DES ERREURS AMÉLIORÉE
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = $this->getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
            throw new \RuntimeException($errorMessage);
        }

        // LISTE DES TYPES MIME ÉTENDUE
        $allowedMimeTypes = [
            // PDF
            'application/pdf', 
            // Images
            'image/jpeg', 
            'image/png',
            'image/gif',
            // Microsoft Word
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            // Microsoft Excel
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            // Microsoft PowerPoint
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            // Fichiers texte
            'text/plain',
            'text/csv'
        ];

        if (!in_array($file['type'], $allowedMimeTypes)) {
            throw new \RuntimeException("Type de fichier non autorisé : " . htmlspecialchars($file['type']));
        }

        $originalFilename = basename($file['name']);
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $storedFilename = 'doc_' . uniqid('', true) . '.' . $extension;
        $destination = $this->storagePath . $storedFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException("Impossible de déplacer le fichier uploadé vers le stockage. Vérifiez les permissions du dossier 'storage'.");
        }

        return [
            'original_filename' => $originalFilename,
            'stored_filename'   => $storedFilename,
            'mime_type'         => $file['type'],
            'size'              => $file['size'],
            'full_path'         => $destination
        ];
    }

    /**
     * Traduit un code d'erreur d'upload PHP en un message clair.
     * @param int $errorCode Le code d'erreur de $_FILES['error'].
     * @return string Le message d'erreur.
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return "Le fichier dépasse la taille maximale autorisée par le serveur (upload_max_filesize).";
            case UPLOAD_ERR_FORM_SIZE:
                return "Le fichier dépasse la taille maximale autorisée par le formulaire.";
            case UPLOAD_ERR_PARTIAL:
                return "Le fichier n'a été que partiellement téléversé.";
            case UPLOAD_ERR_NO_FILE:
                return "Aucun fichier n'a été téléversé.";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Erreur serveur : dossier temporaire manquant.";
            case UPLOAD_ERR_CANT_WRITE:
                return "Erreur serveur : impossible d'écrire le fichier sur le disque.";
            case UPLOAD_ERR_EXTENSION:
                return "Une extension PHP a arrêté le téléversement du fichier.";
            default:
                return "Une erreur inconnue est survenue lors de l'envoi du fichier.";
        }
    }
}
