<?php
// src/Controllers/SettingsController.php

namespace App\Controllers;

use App\Core\Database;
use App\Services\MicrosoftGraphService;
use App\Services\FolderService; // AJOUTER CETTE LIGNE

// ... (use statements pour la bibliothèque d'impression) ...
use DR\Ipp\Ipp;
use DR\Ipp\Entity\IppServer;
use DR\Ipp\Entity\IppPrinter;
use DR\Ipp\Entity\IppPrintFile;
use DR\Ipp\Enum\FileTypeEnum;
use GuzzleHttp\Client as GuzzleClient;

class SettingsController
{
    private $settingsFile;
    private $printSettingsFile;
    private FolderService $folderService; // AJOUTER CETTE LIGNE

    public function __construct()
    {
        $this->settingsFile = dirname(__DIR__, 2) . '/config/mail_settings.json';
        $this->printSettingsFile = dirname(__DIR__, 2) . '/config/print_settings.json';
        $this->folderService = new FolderService(); // AJOUTER CETTE LIGNE
    }

    public function showSettings(): void
    {
        $tenants = $this->loadSettings();
        $printers = $this->loadPrintSettings();
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT id, name, default_printer_id FROM folders ORDER BY name ASC');
        $appFolders = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // AJOUT: Charger l'arborescence des dossiers et l'ID du dossier courant
        $folderTree = $this->folderService->getFolderTree();
        $currentFolderId = null; // Aucun dossier n'est sélectionné dans les réglages

        require_once dirname(__DIR__, 2) . '/templates/settings_tenant.php';
    }

    // ... (Le reste de la classe reste inchangé) ...
    public function savePrinter(): void
    {
        $printers = $this->loadPrintSettings();
        $printerId = $_POST['printer_id'] ?: 'printer_' . time();
        
        $printerData = [
            'id' => $printerId,
            'name' => $_POST['printer_name'] ?? 'Nouvelle Imprimante',
            'uri' => $_POST['printer_uri'] ?? 'ipp://localhost/printers/MyPrinter',
        ];

        $command = sprintf(
            'sudo lpadmin -p %s -E -v %s -m everywhere',
            escapeshellarg($printerData['name']),
            escapeshellarg($printerData['uri'])
        );
        
        @exec($command . ' 2>&1', $output, $return_var);

        if ($return_var !== 0) {
            error_log("CUPS lpadmin command failed for printer {$printerData['name']}: " . implode("\n", $output));
        }

        $found = false;
        foreach ($printers as $key => $printer) {
            if ($printer['id'] === $printerId) {
                $printers[$key] = $printerData;
                $found = true;
                break;
            }
        }
        if (!$found) $printers[] = $printerData;
        
        $this->savePrintSettings($printers);
        header('Location: /settings');
        exit();
    }

    public function deletePrinter(): void
    {
        $printers = $this->loadPrintSettings();
        $printerId = $_POST['printer_id'] ?? null;
        $printerToDelete = null;

        if ($printerId) {
            foreach ($printers as $printer) {
                if ($printer['id'] === $printerId) {
                    $printerToDelete = $printer;
                    break;
                }
            }
            
            if ($printerToDelete) {
                $command = sprintf('sudo lpadmin -x %s', escapeshellarg($printerToDelete['name']));
                @exec($command . ' 2>&1', $output, $return_var);
                 if ($return_var !== 0) {
                    error_log("CUPS lpadmin delete command failed for printer {$printerToDelete['name']}: " . implode("\n", $output));
                }
            }
            
            $printers = array_filter($printers, fn($p) => $p['id'] !== $printerId);
            $this->savePrintSettings(array_values($printers));
        }
        header('Location: /settings');
        exit();
    }
    
    public function testPrinter(): void
    {
        header('Content-Type: application/json');
        $printerId = $_POST['printer_id'] ?? null;

        if (!$printerId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de l\'imprimante manquant.']);
            exit();
        }

        try {
            $printers = $this->loadPrintSettings();
            $printerConfig = null;
            foreach ($printers as $p) {
                if ($p['id'] === $printerId) {
                    $printerConfig = $p;
                    break;
                }
            }

            if (!$printerConfig) throw new \Exception("Imprimante non trouvée.");

            $uriParts = parse_url($printerConfig['uri']);
            $serverUri = ($uriParts['scheme'] ?? 'http') . '://' . ($uriParts['host'] ?? 'localhost') . ':' . ($uriParts['port'] ?? 631);
            $printerName = basename($uriParts['path'] ?? 'printer');
            
            $server = new IppServer();
            $server->setUri($serverUri);
            $ipp = new Ipp($server, new GuzzleClient(['timeout' => 30, 'verify' => false]));

            $printer = new IppPrinter();
            $printer->setHostname($printerName);
            
            $content = "Ceci est une page de test pour l'imprimante '{$printerConfig['name']}' depuis la GED.\n\nDate: " . date('Y-m-d H:i:s');
            $ippFile = new IppPrintFile($content, FileTypeEnum::PS);
            $ippFile->setFileName('Test_Page_GED.txt');

            $response = $ipp->print($printer, $ippFile);
            
            $attributes = $response->getAttributes();
            $jobIdAttribute = $attributes['job-id'] ?? null;

            if ($jobIdAttribute) {
                $jobId = $jobIdAttribute->getValue();
                echo json_encode(['success' => true, 'message' => "Page de test envoyée à '{$printerConfig['name']}'. Job ID: " . $jobId]);
            } else {
                throw new \Exception("L'envoi du travail de test à CUPS a échoué. " . ($response->getStatusMessage() ?? ''));
            }

        } catch (\Exception $e) {
            http_response_code(500);
            error_log("Erreur de test d'impression : " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
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
            'graph' => ['tenant_id' => $_POST['graph_tenant_id'] ?? '', 'client_id' => $_POST['graph_client_id'] ?? '', 'client_secret' => $finalSecret],
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
        
        if (isset($_POST['folder_printers']) && is_array($_POST['folder_printers'])) {
            $pdo = Database::getInstance();
            foreach ($_POST['folder_printers'] as $folderId => $printerId) {
                $stmt = $pdo->prepare("UPDATE folders SET default_printer_id = ? WHERE id = ?");
                $stmt->execute([$printerId ?: null, $folderId]);
            }
        }

        $foldersData = [];
        if (isset($_POST['folders']) && is_array($_POST['folders'])) {
            foreach ($_POST['folders'] as $folderMapping) {
                $mapping = json_decode($folderMapping, true);
                if ($mapping && isset($mapping['id']) && isset($mapping['destination_folder_id'])) {
                    $foldersData[] = $mapping;
                }
            }
        }
        $rulesData = [];
        if (isset($_POST['automation_rules'])) {
            $rulesData = json_decode($_POST['automation_rules'], true);
            if (!is_array($rulesData)) $rulesData = [];
        }
        $accountData = [
            'id' => $accountId,
            'account_name' => $_POST['account_name'] ?? 'Nouveau Compte',
            'user_email' => $_POST['user_email'] ?? '',
            'default_printer_id' => $_POST['default_printer_id'] ?? null,
            'folders' => $foldersData,
            'automation_rules' => $rulesData
        ];
        foreach ($tenants as $key => &$tenant) {
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

    public function ajaxCreateFolder(): void
    {
        header('Content-Type: application/json');
        try {
            $folderName = $_POST['folder_name'] ?? null;
            if (empty(trim($folderName))) {
                throw new \Exception("Le nom du dossier ne peut pas être vide.");
            }
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("INSERT INTO folders (name) VALUES (?)");
            $stmt->execute([trim($folderName)]);
            $newFolderId = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'id' => $newFolderId, 'name' => trim($folderName)]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function loadSettings(): array
    {
        if (!file_exists($this->settingsFile) || filesize($this->settingsFile) === 0) return [];
        $data = json_decode(file_get_contents($this->settingsFile), true);
        if (!is_array($data)) return [];
        if (isset($data[0]) && isset($data[0]['graph']['user_email'])) {
            $migratedTenant = [
                'tenant_id' => 'tenant_' . time(),
                'tenant_name' => 'Tenant Principal (Migré)',
                'graph' => ['tenant_id' => $data[0]['graph']['tenant_id'] ?? '', 'client_id' => $data[0]['graph']['client_id'] ?? '', 'client_secret' => $data[0]['graph']['client_secret'] ?? ''],
                'accounts' => []
            ];
            foreach ($data as $oldAccount) {
                $folders = is_array($oldAccount['folders']) ? array_map(fn($id) => ['id' => $id, 'name' => 'Unknown', 'destination_folder_id' => 'root'], $oldAccount['folders']) : [];
                $migratedTenant['accounts'][] = ['id' => $oldAccount['id'] ?? 'acc_' . uniqid(), 'account_name' => $oldAccount['account_name'] ?? 'Compte Migré', 'user_email' => $oldAccount['graph']['user_email'] ?? '', 'folders' => $folders, 'automation_rules' => []];
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

    private function loadPrintSettings(): array
    {
        if (!file_exists($this->printSettingsFile) || filesize($this->printSettingsFile) === 0) {
            return [];
        }
        $settings = json_decode(file_get_contents($this->printSettingsFile), true);
        if (isset($settings['printer_uri'])) {
            return [
                [
                    'id' => 'printer_default',
                    'name' => 'Imprimante par défaut (Migrée)',
                    'uri' => $settings['printer_uri']
                ]
            ];
        }
        return is_array($settings) ? $settings : [];
    }

    private function savePrintSettings(array $printers): void
    {
        if (!is_dir(dirname($this->printSettingsFile))) {
            mkdir(dirname($this->printSettingsFile), 0755, true);
        }
        file_put_contents($this->printSettingsFile, json_encode($printers, JSON_PRETTY_PRINT));
    }
}
