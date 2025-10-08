<?php
// public/index.php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\DocumentController;
use App\Controllers\SettingsController;

$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$method = $_SERVER['REQUEST_METHOD'];

// Routeur principal
switch ($requestUri) {
    // --- Pages HTML ---
    case '/':
        (new DocumentController())->home();
        break;
    case '/trash':
        (new DocumentController())->trash();
        break;
    case '/settings':
        (new SettingsController())->index();
        break;
    case '/download': // Assumant que le téléchargement est via /download?id=...
        (new DocumentController())->download();
        break;
    case '/preview': // Assumant que l'aperçu est via /preview?id=...
        (new DocumentController())->preview();
        break;

    // --- Routes API ---
    case '/api/documents':
        if ($method === 'GET') {
            (new DocumentController())->apiGetDocuments();
        }
        break;
    case '/api/upload':
        if ($method === 'POST') {
            (new DocumentController())->apiUpload();
        }
        break;
    case '/api/folders': // Corresponds à l'appel JS pour créer un dossier
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            (new DocumentController())->apiCreateFolder($data);
        }
        break;
    case '/api/rename-item': // Corresponds à l'appel JS
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            (new DocumentController())->apiRenameItem($data);
        }
        break;
    case '/api/delete-item': // Corresponds à l'appel JS
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            (new DocumentController())->apiDeleteItem($data);
        }
        break;
    case '/api/move-item': // Corresponds à l'appel JS pour le glisser-déposer
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            (new DocumentController())->apiMoveItem($data);
        }
        break;

    // --- Route par défaut (404) ---
    default:
        // Si la route commence par /api/, renvoyer une erreur JSON
        if (strpos($requestUri, '/api/') === 0) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Route API non trouvée : $method $requestUri"]);
        } else {
            // Sinon, afficher une page 404 HTML
            http_response_code(404);
            echo "<h1>404 Not Found</h1><p>La page que vous avez demandée n'a pas pu être trouvée.</p>";
        }
        break;
}
