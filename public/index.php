<?php
// public/index.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\DocumentController;
use App\Controllers\SettingsController;

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$documentController = new DocumentController();
$settingsController = new SettingsController();

switch ($requestUri) {
    // Pages principales
    case '/': $documentController->listDocuments(); break;
    case '/trash': $documentController->listTrash(); break;
    
    // Réglages
    case '/settings': $settingsController->showSettings(); break;
    
    // Nouvelles routes pour les imprimantes
    case '/settings/printer/save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $settingsController->savePrinter();
        break;
    case '/settings/printer/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $settingsController->deletePrinter();
        break;
    case '/settings/printer/test':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $settingsController->testPrinter();
        break;

    // DASHBOARD D'IMPRESSION
    case '/print-queue/status':
        $documentController->getPrintQueueStatus();
        break;
    case '/document/cancel-print':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->cancelPrintJob();
        break;
    // NOUVELLE ROUTE POUR NETTOYER LES ERREURS
    case '/document/clear-print-error':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->clearPrintJobError();
        break;

    // Routes existantes
    case '/settings/tenant/save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $settingsController->saveTenant();
        break;
    case '/settings/tenant/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $settingsController->deleteTenant();
        break;
    case '/settings/account/save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $settingsController->saveAccount();
        break;
    case '/settings/account/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $settingsController->deleteAccount();
        break;
    case '/settings/ajax/list-folders':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $settingsController->ajaxListFolders();
        break;
    case '/settings/ajax/create-folder':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $settingsController->ajaxCreateFolder();
        break;
        
    // Actions sur les documents
    case '/upload':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->uploadDocument();
        break;
    case '/document/details':
        if (isset($_GET['id'])) $documentController->getDocumentDetails((int)$_GET['id']);
        break;
    case '/document/download':
        if (isset($_GET['id'])) $documentController->downloadDocument((int)$_GET['id']);
        break;
    case '/document/update-status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->updateDocumentStatus();
        break;
    case '/document/print':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->printSingleDocument();
        break;
    case '/document/bulk-print':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->printBulkDocuments();
        break;
    case '/document/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->moveToTrash();
        break;
    case '/document/restore':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->restoreDocument();
        break;
    case '/document/force-delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->forceDelete();
        break;
    case '/folder/create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->createFolder();
        break;
    case '/document/move':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->moveDocument();
        break;
    case '/folder/move':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $documentController->moveFolder();
        break;
        
    default:
        http_response_code(404);
        echo "<h1>404 - Page non trouvée</h1>";
        break;
}
