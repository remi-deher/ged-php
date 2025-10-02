<?php
// src/Controllers/SettingsController.php

namespace App\Controllers;

use App\Services\MicrosoftGraphService;

class SettingsController
{
    private $settingsFile;

    public function __construct()
    {
        $this->settingsFile = dirname(__DIR__, 2) . '/config/mail_settings.json';
    }

    /**
     * Affiche la liste des comptes et le formulaire d'ajout.
     */
    public function listAccounts(): void
    {
        $accounts = $this->loadAccounts();
        require_once dirname(__DIR__, 2) . '/templates/settings_multi.php';
    }
    
    // ... les autres méthodes (saveAccount, deleteAccount, ajaxListFolders) restent identiques ...

    /**
     * Charge les comptes depuis le fichier de configuration de manière robuste.
     * Détecte et migre automatiquement l'ancien format.
     */
    private function loadAccounts(): array
    {
        if (!file_exists($this->settingsFile) || filesize($this->settingsFile) === 0) {
            return [];
        }
        
        $data = json_decode(file_get_contents($this->settingsFile), true);

        // Si le JSON est invalide ou n'est pas un tableau, on retourne un tableau vide.
        if (!is_array($data)) {
            return [];
        }

        // Détection et migration de l'ancien format
        if (isset($data['service']) && !isset($data[0])) {
            $migratedAccount = [
                'id' => 'migrated_' . time(),
                'account_name' => 'Compte Principal (migré)',
                'service' => $data['service'],
                'graph' => $data['graph'] ?? [],
                'folders' => $data['folders'] ?? []
            ];
            $newData = [$migratedAccount];
            
            $this->saveAccounts($newData);
            return $newData;
        }

        return $data;
    }

    private function saveAccounts(array $accounts): void
    {
        // On s'assure que le répertoire existe
        if (!is_dir(dirname($this->settingsFile))) {
            mkdir(dirname($this->settingsFile), 0755, true);
        }
        file_put_contents($this->settingsFile, json_encode($accounts, JSON_PRETTY_PRINT));
    }

    // --- Les autres méthodes complètes pour référence ---

    public function saveAccount(): void
    {
        $accounts = $this->loadAccounts();
        $accountId = $_POST['account_id'] ?: 'acc_' . time();

        $accountData = [
            'id' => $accountId,
            'account_name' => $_POST['account_name'] ?? 'Nouveau compte',
            'service' => $_POST['service'] ?? 'graph',
            'graph' => [
                'tenant_id' => $_POST['graph_tenant_id'] ?? '',
                'client_id' => $_POST['graph_client_id'] ?? '',
                'client_secret' => $_POST['graph_client_secret'] ?? '',
                'user_email' => $_POST['graph_user_email'] ?? '',
            ],
            'folders' => $_POST['folders'] ?? []
        ];

        $found = false;
        foreach ($accounts as $key => $account) {
            if ($account['id'] === $accountId) {
                $accounts[$key] = $accountData;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $accounts[] = $accountData;
        }

        $this->saveAccounts($accounts);
        header('Location: /settings');
        exit();
    }
    
    public function deleteAccount(): void
    {
        $accounts = $this->loadAccounts();
        $accountId = $_POST['account_id'] ?? null;

        if ($accountId) {
            $accounts = array_filter($accounts, function($acc) use ($accountId) {
                return $acc['id'] !== $accountId;
            });
            $this->saveAccounts(array_values($accounts));
        }
        
        header('Location: /settings');
        exit();
    }
    
    public function ajaxListFolders(): void
    {
        header('Content-Type: application/json');
        
        try {
            $required_keys = ['tenant_id', 'client_id', 'client_secret', 'user_email'];
            foreach($required_keys as $key) {
                if (empty($_POST[$key])) {
                    throw new \Exception("Paramètre manquant : $key");
                }
            }

            $credentials = [
                'tenant_id' => $_POST['tenant_id'],
                'client_id' => $_POST['client_id'],
                'client_secret' => $_POST['client_secret']
            ];
            $userEmail = $_POST['user_email'];

            $graphService = new MicrosoftGraphService($credentials);
            $folders = $graphService->listMailFolders($userEmail);
            
            echo json_encode(['folders' => $folders]);

        } catch (\Exception $e) {
            http_response_code(500);
            error_log("Graph API Error: " . $e->getMessage());
            echo json_encode(['error' => 'La connexion a échoué: ' . $e->getMessage()]);
        }
    }
}
