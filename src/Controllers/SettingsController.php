<?php
// src/Controllers/SettingsController.php

namespace App\Controllers;

use App\Services\ConfigurationService;
use App\Services\FolderService; // AJOUTÉ

class SettingsController
{
    private $configService;
    private $folderService; // AJOUTÉ

    public function __construct()
    {
        $this->configService = new ConfigurationService();
        $this->folderService = new FolderService(); // AJOUTÉ
    }

    /**
     * --- CORRIGÉ ---
     * La méthode 'index' charge désormais les données nécessaires pour le template,
     * ce qui empêche les erreurs fatales lors du rendu de la page.
     */
    public function index()
    {
        // Charger toutes les données nécessaires pour la vue
        $tenants = $this->configService->loadMailSettings();
        $printers = $this->configService->loadPrintSettings();
        $folderTree = $this->folderService->getFolderTree();
        $appFolders = $this->folderService->getAllFoldersFlat();

        // Rendre le template en incluant les variables nécessaires
        require_once __DIR__ . '/../../templates/settings_tenant.php';
    }

    public function getMailSettings()
    {
        header('Content-Type: application/json');
        try {
            // CORRIGÉ : Utilisation de la méthode existante loadMailSettings()
            $settings = $this->configService->loadMailSettings();
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
            // CORRIGÉ : Utilisation de la méthode existante loadPrintSettings()
            $settings = $this->configService->loadPrintSettings();
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
