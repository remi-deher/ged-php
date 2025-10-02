<?php
// src/Controllers/SettingsController.php

namespace App\Controllers;

use App\Core\Database;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;

class SettingsController
{
    private $settingsFile;

    public function __construct()
    {
        $this->settingsFile = dirname(__DIR__, 2) . '/config/mail_settings.json';
    }

    /**
     * Affiche le formulaire de configuration de la messagerie.
     */
    public function showSettingsForm(): void
    {
        $settings = $this->loadSettings();
        $folders = [];
        
        // CORRECTION : On prépare la variable pour la vue
        $isConfigured = $this->isConfigured($settings);

        if ($isConfigured) {
            $folders = $this->listFolders();
        }

        require_once dirname(__DIR__, 2) . '/templates/settings.php';
    }

    /**
     * Sauvegarde les paramètres de messagerie.
     */
    public function saveSettings(): void
    {
        $settings = [
            'service' => $_POST['service'] ?? 'graph',
            'graph' => [
                'tenant_id' => $_POST['graph_tenant_id'] ?? '',
                'client_id' => $_POST['graph_client_id'] ?? '',
                'client_secret' => $_POST['graph_client_secret'] ?? '',
                'user_email' => $_POST['graph_user_email'] ?? '',
            ],
            'imap' => [
                'host' => $_POST['imap_host'] ?? '',
                'port' => $_POST['imap_port'] ?? '993',
                'encryption' => $_POST['imap_encryption'] ?? 'ssl',
                'username' => $_POST['imap_username'] ?? '',
                'password' => $_POST['imap_password'] ?? '',
            ],
            'folders' => $_POST['folders'] ?? []
        ];

        file_put_contents($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT));

        header('Location: /settings');
        exit();
    }
    
    /**
     * Charge les paramètres depuis le fichier de configuration.
     */
    private function loadSettings(): array
    {
        if (!file_exists($this->settingsFile)) {
            return [];
        }
        $json = file_get_contents($this->settingsFile);
        return json_decode($json, true) ?: [];
    }

    /**
     * Vérifie si les paramètres sont suffisamment complets pour une connexion.
     */
    private function isConfigured(array $settings): bool
    {
        if (($settings['service'] ?? '') === 'graph') {
            return !empty($settings['graph']['tenant_id']) && !empty($settings['graph']['client_id']) && !empty($settings['graph']['client_secret']) && !empty($settings['graph']['user_email']);
        }
        // Logique IMAP à implémenter
        return false;
    }

    /**
     * Liste les dossiers de la boîte mail (uniquement pour Microsoft Graph pour l'instant).
     */
    public function listFolders(): array
    {
        $settings = $this->loadSettings();
        if (!$this->isConfigured($settings) || $settings['service'] !== 'graph') {
            return [];
        }

        try {
            $tokenRequestContext = new ClientCredentialContext(
                $settings['graph']['tenant_id'],
                $settings['graph']['client_id'],
                $settings['graph']['client_secret']
            );
            $graph = new GraphServiceClient($tokenRequestContext);

            $mailFolders = $graph->users()->byUserId($settings['graph']['user_email'])->mailFolders()->get()->wait();
            
            $folders = [];
            foreach ($mailFolders->getValue() as $folder) {
                $folders[] = [
                    'id' => $folder->getId(),
                    'name' => $folder->getDisplayName()
                ];
            }
            return $folders;

        } catch (\Exception $e) {
            // Gérer l'erreur de connexion, par exemple en affichant un message
            error_log("Erreur lors de la récupération des dossiers Graph: " . $e->getMessage());
            return [];
        }
    }
}
