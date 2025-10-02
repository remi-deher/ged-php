<?php
// public/index.php

/**
 * Ce fichier est le point d'entrée unique de l'application (Front Controller).
 * Son rôle est d'analyser l'URL demandée par l'utilisateur et d'appeler
 * la méthode du contrôleur correspondante.
 */

// Affiche les erreurs PHP pour faciliter le débogage (à désactiver en production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inclusion de l'autoloader de Composer, qui gère le chargement de toutes nos classes
require_once __DIR__ . '/../vendor/autoload.php';

// Importation des classes des contrôleurs
use App\Controllers\DocumentController;
use App\Controllers\SettingsController;

// Récupération de l'URI de la requête sans les paramètres GET
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Création des instances de nos contrôleurs
$documentController = new DocumentController();
$settingsController = new SettingsController();

// Routage simple basé sur l'URI demandée
switch ($requestUri) {
    // --- Pages principales ---
    case '/':
        $documentController->listDocuments();
        break;

    case '/trash':
        $documentController->listTrash();
        break;

    // --- Routes pour les réglages multi-tenants ---
    case '/settings':
        $settingsController->showSettings();
        break;

    case '/settings/tenant/save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingsController->saveTenant();
        }
        break;

    case '/settings/tenant/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingsController->deleteTenant();
        }
        break;

    case '/settings/account/save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingsController->saveAccount();
        }
        break;

    case '/settings/account/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingsController->deleteAccount();
        }
        break;

    case '/settings/ajax/list-folders':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingsController->ajaxListFolders();
        }
        break;

    // --- Actions pour les documents (traitées via POST) ---
    case '/upload':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->uploadDocument();
        } else {
            header('Location: /');
            exit();
        }
        break;

    // --- Routes pour la visualisation et le téléchargement ---
    case '/document/details':
        if (isset($_GET['id'])) {
            $documentController->getDocumentDetails((int)$_GET['id']);
        }
        break;

    case '/document/download':
        if (isset($_GET['id'])) {
            $documentController->downloadDocument((int)$_GET['id']);
        }
        break;

    case '/document/update-status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->updateDocumentStatus();
        } else {
            header('Location: /');
            exit();
        }
        break;

    case '/document/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->moveToTrash();
        } else {
            header('Location: /');
            exit();
        }
        break;

    case '/document/restore':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->restoreDocument();
        } else {
            header('Location: /trash');
            exit();
        }
        break;

    case '/document/force-delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->forceDelete();
        } else {
            header('Location: /trash');
            exit();
        }
        break;
        
    case '/folder/create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->createFolder();
        } else {
            header('Location: /');
            exit();
        }
        break;

    case '/document/move':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->moveDocument();
        } else {
            header('Location: /');
            exit();
        }
        break;

    // Si l'URL ne correspond à aucune route connue
    default:
        http_response_code(404);
        echo "<h1>404 - Page non trouvée</h1>";
        break;
}
