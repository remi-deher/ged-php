<?php
// src/Controllers/SettingsController.php

namespace App\Controllers;

use App\Services\ConfigurationService;

class SettingsController
{
    private $configService;

    public function __construct()
    {
        $this->configService = new ConfigurationService();
    }

    /**
     * --- START OF CORRECTION ---
     * This 'index' method was missing. It is required by the router
     * to display the main settings page.
     */
    public function index()
    {
        // This line loads the HTML template for the settings page.
        require_once __DIR__ . '/../../templates/settings_tenant.php';
    }
    // --- END OF CORRECTION ---

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
