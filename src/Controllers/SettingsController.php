<?php
// src/Controllers/SettingsController.php

namespace App\Controllers;

use App\Services\ConfigurationService;
use App\Repositories\FolderRepository; // Ajout important

class SettingsController
{
    private $configService;
    private $folderRepository; // Ajout important

    public function __construct()
    {
        $this->configService = new ConfigurationService();
        $this->folderRepository = new FolderRepository(); // Ajout important
    }

    public function index()
    {
        // --- DÉBUT DE LA CORRECTION ---
        // On charge toutes les données nécessaires pour la page des réglages
        $tenantsData = $this->configService->getMailSettings();
        $printers = $this->configService->getPrintSettings();
        $appFolders = $this->folderRepository->findAll(); // Récupère tous les dossiers pour les menus déroulants

        // On passe les variables au template
        $tenants = $tenantsData['tenants'] ?? [];
        // --- FIN DE LA CORRECTION ---

        require_once __DIR__ . '/../../templates/settings_tenant.php';
    }

    public function getMailSettings()
    {
        header('Content-Type: application/json');
        try {
            $settings = $this->configService->getMailSettings();
            echo json_encode(['success' => true, 'settings' => $settings]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function saveMailSettings($data)
    {
        header('Content-Type: application/json');
        try {
            $this->configService->saveMailSettings($data);
            echo json_encode(['success' => true, 'message' => 'Paramètres de messagerie enregistrés.']);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getPrintSettings()
    {
        header('Content-Type: application/json');
        try {
            $settings = $this->configService->getPrintSettings();
            echo json_encode(['success' => true, 'settings' => $settings]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function savePrintSettings($data)
    {
        header('Content-Type: application/json');
        try {
            $this->configService->savePrintSettings($data);
            echo json_encode(['success' => true, 'message' => 'Paramètres d\'impression enregistrés.']);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
