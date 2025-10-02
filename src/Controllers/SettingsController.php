<?php
// src/Controllers/SettingsController.php

namespace App\Controllers;

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
     * Affiche la liste des comptes et le formulaire d'ajout.
     */
    public function listAccounts(): void
    {
        $accounts = $this->loadAccounts();
        require_once dirname(__DIR__, 2) . '/templates/settings_multi.php';
    }

    /**
     * Ajoute ou met à jour un compte de messagerie.
     */
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
    
    /**
     * Supprime un compte de messagerie.
     */
    public function deleteAccount(): void
    {
        $accounts = $this->loadAccounts();
        $accountId = $_POST['account_id'] ?? null;

        if ($accountId) {
            $accounts = array_filter($accounts, function($acc) use ($accountId) {
                return $acc['id'] !== $accountId;
            });
            $this->saveAccounts(array_values($accounts)); // re-index array
        }
        
        header('Location: /settings');
        exit();
    }
    
    /**
     * Récupère les dossiers via AJAX pour un compte donné.
     */
    public function ajaxListFolders(): void
    {
        header('Content-Type: application/json');
        
        $credentials = [
            'tenant_id' => $_POST['tenant_id'] ?? '',
            'client_id' => $_POST['client_id'] ?? '',
            'client_secret' => $_POST['client_secret'] ?? '',
            'user_email' => $_POST['user_email'] ?? ''
        ];

        if (empty(array_filter($credentials))) {
            echo json_encode(['error' => 'Identifiants manquants.']);
            return;
        }

        try {
            $tokenRequestContext = new ClientCredentialContext($credentials['tenant_id'], $credentials['client_id'], $credentials['client_secret']);
            $graph = new GraphServiceClient($tokenRequestContext);
            $mailFolders = $graph->users()->byUserId($credentials['user_email'])->mailFolders()->get()->wait();
            
            $folders = [];
            foreach ($mailFolders->getValue() as $folder) {
                $folders[] = ['id' => $folder->getId(), 'name' => $folder->getDisplayName()];
            }
            echo json_encode(['folders' => $folders]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'La connexion a échoué: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Charge les comptes depuis le fichier de configuration.
     * Détecte et migre automatiquement l'ancien format.
     */
    private function loadAccounts(): array
    {
        if (!file_exists($this->settingsFile)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($this->settingsFile), true) ?: [];

        // Si le JSON est vide ou n'est pas un tableau, on retourne un tableau vide.
        if (!is_array($data)) {
            return [];
        }

        // Détection de l'ancien format : il a une clé 'service' au premier niveau,
        // alors que le nouveau format est un tableau d'objets (qui n'a pas de clés numériques).
        if (isset($data['service']) && !isset($data[0])) {
            echo "";
            $migratedAccount = [
                'id' => 'migrated_' . time(),
                'account_name' => 'Compte Principal (migré)',
                'service' => $data['service'],
                'graph' => $data['graph'] ?? [],
                'folders' => $data['folders'] ?? []
            ];
            $newData = [$migratedAccount];
            
            // On sauvegarde le nouveau format et on le retourne
            $this->saveAccounts($newData);
            return $newData;
        }

        return $data;
    }

    private function saveAccounts(array $accounts): void
    {
        file_put_contents($this->settingsFile, json_encode($accounts, JSON_PRETTY_PRINT));
    }
}
