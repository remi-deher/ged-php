<?php
// src/Services/ConfigurationService.php

namespace App\Services;

use Exception;

class ConfigurationService
{
    private string $mailSettingsFile;
    private string $printSettingsFile;

    public function __construct()
    {
        $configDir = dirname(__DIR__, 2) . '/config/';
        $this->mailSettingsFile = $configDir . 'mail_settings.json';
        $this->printSettingsFile = $configDir . 'print_settings.json';
    }

    // --- Mail Settings ---

    public function loadMailSettings(): array
    {
        return $this->loadJsonFile($this->mailSettingsFile);
    }

    public function saveMailSettings(array $tenants): void
    {
        $this->saveJsonFile($this->mailSettingsFile, $tenants);
    }
    
    public function findTenantById(string $tenantId): ?array
    {
        foreach ($this->loadMailSettings() as $tenant) {
            if ($tenant['tenant_id'] === $tenantId) {
                return $tenant;
            }
        }
        return null;
    }

    // --- Print Settings ---

    public function loadPrintSettings(): array
    {
        return $this->loadJsonFile($this->printSettingsFile);
    }

    public function savePrintSettings(array $printers): void
    {
        $this->saveJsonFile($this->printSettingsFile, $printers);
    }

    public function findPrinterById(string $printerId): ?array
    {
        foreach ($this->loadPrintSettings() as $printer) {
            if ($printer['id'] === $printerId) {
                return $printer;
            }
        }
        return null;
    }

    // --- Helpers ---

    private function loadJsonFile(string $filePath): array
    {
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            return [];
        }
        $data = json_decode(file_get_contents($filePath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Fichier JSON mal formaté : " . basename($filePath));
        }
        return is_array($data) ? $data : [];
    }

    private function saveJsonFile(string $filePath, array $data): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
