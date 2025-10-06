<?php
// src/Services/PrintService.php

namespace App\Services;

use App\Repositories\DocumentRepository;
use App\Repositories\FolderRepository;
use App\Services\ConfigurationService;
use Exception;

class PrintService
{
    private DocumentRepository $documentRepository;
    private ConfigurationService $configService;

    public function __construct()
    {
        $this->documentRepository = new DocumentRepository();
        $this->configService = new ConfigurationService();
    }

    public function sendToPrinter(int $docId): void
    {
        try {
            $document = $this->documentRepository->find($docId);
            if (!$document) {
                throw new Exception("Document non trouvé.");
            }

            $printers = $this->configService->loadPrintSettings();
            if (empty($printers)) {
                throw new Exception("Aucune imprimante n'est configurée dans l'application.");
            }

            // --- NOUVELLE LOGIQUE DE SÉLECTION D'IMPRIMANTE ---
            $printerToUse = null;

            // 1. Chercher une imprimante par défaut pour le dossier du document
            if ($document['folder_id']) {
                $folderRepo = new FolderRepository();
                $folder = $folderRepo->find($document['folder_id']);
                if ($folder && $folder['default_printer_id']) {
                    foreach ($printers as $p) {
                        if ($p['id'] === $folder['default_printer_id']) {
                            $printerToUse = $p;
                            break;
                        }
                    }
                }
            }

            // 2. Si pas trouvée, chercher une imprimante par défaut pour le compte source
            if (!$printerToUse && $document['source_account_id']) {
                $mailSettings = $this->configService->loadMailSettings();
                foreach ($mailSettings as $tenant) {
                    foreach ($tenant['accounts'] as $account) {
                        if ($account['id'] === $document['source_account_id'] && !empty($account['default_printer_id'])) {
                            foreach ($printers as $p) {
                                if ($p['id'] === $account['default_printer_id']) {
                                    $printerToUse = $p;
                                    break 2; // Sort des deux boucles
                                }
                            }
                        }
                    }
                }
            }
            
            // 3. Sinon, utiliser la première imprimante comme défaut global
            if (!$printerToUse) {
                $printerToUse = $printers[0];
            }
            // --- FIN DE LA NOUVELLE LOGIQUE ---

            $filePath = dirname(__DIR__, 2) . '/storage/' . $document['stored_filename'];
            if (!file_exists($filePath)) {
                throw new Exception("Fichier physique introuvable sur le serveur.");
            }

            $command = sprintf(
                'lp -d %s %s 2>&1',
                escapeshellarg($printerToUse['name']), // Utilise l'imprimante sélectionnée
                escapeshellarg($filePath)
            );

            $output = shell_exec($command);

            if (preg_match('/request id is .*?-(\d+)/', $output, $matches)) {
                $jobId = $matches[1];
                $this->documentRepository->updatePrintJob($docId, $printerToUse['name'] . '-' . $jobId);
            } else {
                throw new Exception("Échec de l'envoi à CUPS. Réponse: " . $output);
            }
        } catch (Exception $e) {
            $this->documentRepository->setPrintError($docId, $e->getMessage());
            error_log("Erreur d'impression (doc ID: $docId): " . $e->getMessage());
        }
    }

    public function cancelPrintJob(int $docId): void
    {
        $jobId = $this->documentRepository->findPrintJobId($docId);
        if (!$jobId) {
            throw new Exception("Aucun travail d'impression actif pour ce document.");
        }
        
        shell_exec("cancel " . escapeshellarg($jobId) . " 2>&1");

        $this->documentRepository->clearPrintJob($docId, 'Impression annulée.');
    }

    public function clearPrintJobError(int $docId): void
    {
        $this->documentRepository->clearPrintJob($docId, null);
    }

    public function getPrintQueueStatus(): array
    {
        $printingDocs = $this->documentRepository->findPrintingOrErrorDocs();
        if (empty($printingDocs)) {
            return [];
        }

        $cupsJobsOutput = @shell_exec('lpstat -o 2>&1');
        $activeJobs = [];
        if (!empty($cupsJobsOutput)) {
            preg_match_all('/^.*?-(\d+)\s+.*$/m', $cupsJobsOutput, $matches);
            if (isset($matches[1])) {
                foreach ($matches[1] as $jobId) {
                    $activeJobs[$jobId] = true;
                }
            }
        }

        $queueStatus = [];
        foreach ($printingDocs as $doc) {
            $jobId = $doc['print_job_id'];
            $status = 'Terminé';

            if ($jobId && isset($activeJobs[explode('-', $jobId)[1] ?? ''])) {
                $status = "En cours d'impression";
            } elseif ($doc['status'] !== 'print_error') {
                $this->documentRepository->updateStatus($doc['id'], 'printed');
            }

            if ($doc['status'] === 'print_error') {
                $status = 'Erreur';
            }

            $queueStatus[] = [
                'id' => $doc['id'],
                'filename' => $doc['original_filename'],
                'status' => $status,
                'error' => $doc['print_error_message'],
                'job_id' => $jobId
            ];
        }
        return $queueStatus;
    }
}
