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
    // Page d'accueil : affiche la liste des documents actifs
    case '/':
        $documentController->listDocuments();
        break;

    // Affiche le contenu de la corbeille
    case '/trash':
        $documentController->listTrash();
        break;
        
    // Affiche la page des réglages
    case '/settings':
        $settingsController->showSettingsForm();
        break;

    // --- ACTIONS (traitées uniquement via la méthode POST) ---

    // Action pour sauvegarder les réglages
    case '/settings/save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingsController->saveSettings();
        } else {
            header('Location: /settings');
            exit();
        }
        break;

    // Action pour envoyer un nouveau fichier
    case '/upload':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->uploadDocument();
        } else {
            header('Location: /');
            exit();
        }
        break;

    // Action pour mettre à jour le statut d'un document
    case '/document/update-status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->updateDocumentStatus();
        } else {
            header('Location: /');
            exit();
        }
        break;
    
    // Action pour envoyer un document à la corbeille (soft delete)
    case '/document/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->moveToTrash();
        } else {
            header('Location: /');
            exit();
        }
        break;

    // Action pour restaurer un document depuis la corbeille
    case '/document/restore':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->restoreDocument();
        } else {
            header('Location: /trash');
            exit();
        }
        break;

    // Action pour supprimer définitivement un document
    case '/document/force-delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $documentController->forceDelete();
        } else {
            header('Location: /trash');
            exit();
        }
        break;

    // Si l'URL ne correspond à aucune route connue
    default:
        http_response_code(404);
        echo "<h1>404 - Page non trouvée</h1>";
        break;
}
