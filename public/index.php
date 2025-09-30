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

// Importation de la classe DocumentController pour la rendre plus facile à utiliser
use App\Controllers\DocumentController;

// Récupération de l'URI de la requête sans les paramètres GET (ex: /upload au lieu de /upload?id=1)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Création d'une instance de notre contrôleur
$controller = new DocumentController();

// Routage simple basé sur l'URI demandée
switch ($requestUri) {
    // Page d'accueil : affiche la liste des documents actifs
    case '/':
        $controller->listDocuments();
        break;

    // Nouvelle route : affiche le contenu de la corbeille
    case '/trash':
        $controller->listTrash();
        break;

    // --- ACTIONS (traitées uniquement via la méthode POST) ---

    // Action pour envoyer un nouveau fichier
    case '/upload':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->uploadDocument();
        } else {
            // Si la méthode n'est pas POST, on redirige vers l'accueil
            header('Location: /');
            exit();
        }
        break;

    // Action pour mettre à jour le statut d'un document
    case '/document/update-status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->updateDocumentStatus();
        } else {
            header('Location: /');
            exit();
        }
        break;
    
    // Action pour envoyer un document à la corbeille (soft delete)
    case '/document/delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->moveToTrash();
        } else {
            header('Location: /');
            exit();
        }
        break;

    // Action pour restaurer un document depuis la corbeille
    case '/document/restore':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->restoreDocument();
        } else {
            // On redirige vers la corbeille si la méthode n'est pas bonne
            header('Location: /trash');
            exit();
        }
        break;

    // Action pour supprimer définitivement un document
    case '/document/force-delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->forceDelete();
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
