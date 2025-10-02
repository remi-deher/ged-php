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

    public function showSettings(): void
    {
        $tenants = $this->loadSettings();
        // Le renommage de la vue est important pour la clarté
        require_once dirname(__DIR__, 2) . '/templates/settings_tenant.php';
    }

    public function saveTenant(): void
    {
        $tenants = $this->loadSettings();
        $tenantId = $_POST['tenant_id'] ?: 'tenant_' . time();
        $isEditing = !empty($_POST['tenant_id']);
        $newSecret = $_POST['graph_client_secret'] ?? '';
        $finalSecret = $newSecret;

        $existingTenant = $isEditing ? $this->findTenantById($tenantId) : null;

        if ($isEditing && empty($newSecret) && $existingTenant) {
            $finalSecret = $existingTenant['graph']['client_secret'] ?? '';
        }

        $tenantData = [
            'tenant_id' => $tenantId,
            'tenant_name' => $_POST['tenant_name'] ?? 'Nouveau Tenant',
            'graph' => [
                'tenant_id' => $_POST['graph_tenant_id'] ?? '',
                'client_id' => $_POST['graph_client_id'] ?? '',
                'client_secret' => $finalSecret,
            ],
            'accounts' => $existingTenant['accounts'] ?? []
        ];

        $found = false;
        foreach ($tenants as $key => $tenant) {
            if ($tenant['tenant_id'] === $tenantId) {
                $tenants[$key] = $tenantData;
                $found = true;
                break;
            }
        }
        if (!$found) $tenants[] = $tenantData;

        $this->saveSettings($tenants);
        header('Location: /settings');
        exit();
    }
    
    public function deleteTenant(): void
    {
        $tenants = $this->loadSettings();
        $tenantId = $_POST['tenant_id'] ?? null;
        if ($tenantId) {
            $tenants = array_filter($tenants, fn($t) => $t['tenant_id'] !== $tenantId);
            $this->saveSettings(array_values($tenants));
        }
        header('Location: /settings');
        exit();
    }
    
    public function saveAccount(): void
    {
        $tenants = $this->loadSettings();
        $tenantId = $_POST['tenant_id'] ?? null;
        $accountId = $_POST['account_id'] ?: 'acc_' . time();
        
        $accountData = [
            'id' => $accountId,
            'account_name' => $_POST['account_name'] ?? 'Nouveau Compte',
            'user_email' => $_POST['user_email'] ?? '',
            'folders' => $_POST['folders'] ?? []
        ];

        foreach ($tenants as $key => &$tenant) { // Pass by reference
            if ($tenant['tenant_id'] === $tenantId) {
                $accountFound = false;
                if (!isset($tenant['accounts'])) $tenant['accounts'] = [];
                foreach ($tenant['accounts'] as $accKey => &$account) {
                    if ($account['id'] === $accountId) {
                        $account = $accountData;
                        $accountFound = true;
                        break;
                    }
                }
                if (!$accountFound) $tenant['accounts'][] = $accountData;
                break;
            }
        }

        $this->saveSettings($tenants);
        header('Location: /settings');
        exit();
    }
    
    public function deleteAccount(): void
    {
        $tenants = $this->loadSettings();
        $tenantId = $_POST['tenant_id'] ?? null;
        $accountId = $_POST['account_id'] ?? null;

        foreach ($tenants as $key => &$tenant) {
            if ($tenant['tenant_id'] === $tenantId) {
                $tenant['accounts'] = array_values(array_filter($tenant['accounts'] ?? [], fn($acc) => $acc['id'] !== $accountId));
                break;
            }
        }
        $this->saveSettings($tenants);
        header('Location: /settings');
        exit();
    }
    
    public function ajaxListFolders(): void
    {
        header('Content-Type: application/json');
        try {
            $tenantId = $_POST['tenant_id'] ?? null;
            $userEmail = $_POST['user_email'] ?? null;
            if (!$tenantId || !$userEmail) throw new \Exception("ID du tenant ou e-mail manquant.");

            $tenant = $this->findTenantById($tenantId);
            if (!$tenant) throw new \Exception("Tenant non trouvé.");
            
            $credentials = $tenant['graph'];
            if (empty($credentials['client_secret'])) throw new \Exception("Le Secret Client n'est pas configuré pour ce tenant.");

            $graphService = new MicrosoftGraphService($credentials);
            $folders = $graphService->listMailFolders($userEmail);
            
            echo json_encode(['folders' => $folders]);
        } catch (\Exception $e) {
            http_response_code(500);
            error_log("Graph API Error: " . $e->getMessage());
            echo json_encode(['error' => 'La connexion a échoué: ' . $e->getMessage()]);
        }
    }

    private function loadSettings(): array
    {
        if (!file_exists($this->settingsFile) || filesize($this->settingsFile) === 0) return [];
        $data = json_decode(file_get_contents($this->settingsFile), true);
        if (!is_array($data)) return [];

        // --- LOGIQUE DE MIGRATION AUTOMATIQUE ---
        // Détecte l'ancien format (un tableau de comptes) et le convertit.
        if (isset($data[0]) && isset($data[0]['graph']['user_email'])) {
            $migratedTenant = [
                'tenant_id' => 'tenant_' . time(),
                'tenant_name' => 'Tenant Principal (Migré)',
                'graph' => [
                    'tenant_id' => $data[0]['graph']['tenant_id'] ?? '',
                    'client_id' => $data[0]['graph']['client_id'] ?? '',
                    'client_secret' => $data[0]['graph']['client_secret'] ?? '',
                ],
                'accounts' => []
            ];

            foreach ($data as $oldAccount) {
                $migratedTenant['accounts'][] = [
                    'id' => $oldAccount['id'] ?? 'acc_' . uniqid(),
                    'account_name' => $oldAccount['account_name'] ?? 'Compte Migré',
                    'user_email' => $oldAccount['graph']['user_email'] ?? '',
                    'folders' => $oldAccount['folders'] ?? []
                ];
            }
            
            $newData = [$migratedTenant];
            $this->saveSettings($newData);
            return $newData;
        }

        return $data;
    }

    private function saveSettings(array $tenants): void
    {
        if (!is_dir(dirname($this->settingsFile))) mkdir(dirname($this->settingsFile), 0755, true);
        file_put_contents($this->settingsFile, json_encode($tenants, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function findTenantById(string $tenantId): ?array
    {
        foreach ($this->loadSettings() as $tenant) {
            if ($tenant['tenant_id'] === $tenantId) return $tenant;
        }
        return null;
    }
}
