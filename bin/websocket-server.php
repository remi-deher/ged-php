#!/usr/bin/env php
<?php
// bin/websocket-server.php

ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "1. Démarrage du script serveur...\n";

chdir(dirname(__DIR__));

echo "2. Inclusion de l'autoloader de Composer...\n";
require 'vendor/autoload.php';
echo "   -> Autoloader chargé avec succès.\n";

// Importation des classes nécessaires
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Core\CollaborationServer;

try {
    echo "3. Préparation du serveur WebSocket (mode non-sécurisé pour reverse proxy)...\n";
    
    // Instanciation de notre logique de collaboration
    $collaborationServer = new CollaborationServer();

    // Création du serveur WebSocket qui écoute sur le port 8082
    // Il sera accessible uniquement par le reverse proxy
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                $collaborationServer
            )
        ),
        8082
    );

    echo "4. Serveur prêt. Lancement et écoute des connexions WS (non-sécurisées) sur le port 8082...\n";
    echo "--------------------------------------------------------\n";
    $server->run();

} catch (\Exception $e) {
    echo "\n--------------------------------------------------------\n";
    echo "   ERREUR FATALE LORS DU DÉMARRAGE DU SERVEUR\n";
    echo "--------------------------------------------------------\n";
    echo "Message : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne   : " . $e->getLine() . "\n\n";
    echo "Causes possibles :\n";
    echo "  -> Le port 8082 est déjà utilisé par une autre application.\n";
    echo "--------------------------------------------------------\n";
}
