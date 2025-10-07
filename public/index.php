<?php
// public/index.php

// Affiche toutes les erreurs, y compris les erreurs de démarrage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tente de charger l'autoloader et intercepte l'erreur si le fichier est introuvable
try {
    // Cette ligne est cruciale
    require_once __DIR__ . '/../vendor/autoload.php';
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Erreur critique : Impossible de charger l'autoloader de Composer. Vérifiez les permissions du dossier 'vendor'.",
        'error' => $e->getMessage()
    ]);
    exit;
}


// --- BLOC DE DÉBOGAGE PRINCIPAL ---
// Ce bloc va attraper n'importe quelle erreur fatale (comme "Classe non trouvée")
// et renvoyer un message JSON propre au lieu d'une page HTML d'erreur.
try {
    // Démarre la session
    session_start();

    // Routeur
    $requestUri = strtok($_SERVER['REQUEST_URI'], '?');
    $requestMethod = $_SERVER['REQUEST_METHOD'];

    // Point d'entrée pour la seule route qui nous pose problème
    if ($requestMethod === 'GET' && $requestUri === '/api/documents') {
        // On instancie le contrôleur. C'est ici que l'erreur se produit probablement.
        $controller = new \App\Controllers\DocumentController();
        $controller->apiGetDocuments();

    } elseif ($requestMethod === 'GET' && $requestUri === '/') {
        $controller = new \App\Controllers\DocumentController();
        $controller->home();

    } else {
        // Gérer les autres routes si nécessaire, sinon renvoyer 404
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => "Route non trouvée : {$requestMethod} {$requestUri}"
        ]);
    }

} catch (\Throwable $e) {
    // Si une erreur fatale se produit (ex: classe non trouvée), ce bloc l'attrapera
    http_response_code(500);
    header('Content-Type: application/json');
    // On log l'erreur pour pouvoir la consulter sur le serveur
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    // On renvoie un message JSON clair au navigateur
    echo json_encode([
        'success' => false,
        'message' => "Une erreur fatale est survenue sur le serveur.",
        'error_type' => get_class($e),
        'error_message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}
