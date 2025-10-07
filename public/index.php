<?php
// public/index.php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\DocumentController;
use App\Controllers\SettingsController;

// Basic routing
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$method = $_SERVER['REQUEST_METHOD'];

$controller = new DocumentController();

// This router handles all page loads and API calls.
// Non-API GET requests for pages
if ($method === 'GET' && strpos($requestUri, '/api/') === false) {
    switch ($requestUri) {
        case '/':
            $controller->home();
            break;
        case '/trash':
            $controller->trash();
            break;
        case '/settings':
            $settingsController = new SettingsController();
            $settingsController->index();
            break;
        case '/document/download':
            $controller->download();
            break;
        case '/document/preview':
            $controller->preview();
            break;
        default:
            // Optional: A simple 404 page for non-API routes if needed
            http_response_code(404);
            echo "<h1>404 Not Found</h1><p>The page you requested could not be found.</p>";
            break;
    }
}

// API routes (typically start with /api/)
if (strpos($requestUri, '/api/') === 0) {
    // Get the part of the URI after /api/
    $apiRoute = substr($requestUri, 5);
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    switch ($apiRoute) {
        case 'documents':
            $controller->apiGetDocuments();
            break;
        case 'upload':
            if ($method === 'POST') {
                $controller->apiUpload();
            }
            break;
        case 'folder/create':
            if ($method === 'POST') {
                $controller->apiCreateFolder($data);
            }
            break;
        case 'item/rename':
            if ($method === 'POST') {
                $controller->apiRenameItem($data);
            }
            break;
        case 'item/delete':
            if ($method === 'POST') {
                $controller->apiDeleteItem($data);
            }
            break;
        case 'item/move':
            if ($method === 'POST') {
                $controller->apiMoveItem($data);
            }
            break;
        default:
            // If no API route is matched, send a JSON 404 response
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Route non trouvée : $method $requestUri"]);
            break;
    }
}
