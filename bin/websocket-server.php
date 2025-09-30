#!/usr/bin/env php
<?php
// bin/websocket-server.php

// --- DÉBUT BLOC DE DÉBOGAGE ---
// Force l'affichage de toutes les erreurs PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "1. Démarrage du script serveur...\n";
// --- FIN BLOC DE DÉBOGAGE ---

// On se place à la racine du projet pour être sûr que les chemins sont corrects
chdir(dirname(__DIR__));

echo "2. Inclusion de l'autoloader de Composer...\n";
// Inclusion de l'autoloader. Si ce fichier n'existe pas, une erreur s'affichera ici.
require 'vendor/autoload.php';
echo "   -> Autoloader chargé avec succès.\n";

// Importation des classes nécessaires
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Core\CollaborationServer;

try {
    echo "3. Préparation du serveur WebSocket...\n";
    
    // Instanciation de notre logique de collaboration
    echo "   -> Instanciation de CollaborationServer...\n";
    $collaborationServer = new CollaborationServer();

    // Création du serveur WebSocket qui encapsule notre logique
    echo "   -> Instanciation de WsServer...\n";
    $wsServer = new WsServer($collaborationServer);

    // Création du serveur HTTP qui encapsule le serveur WebSocket
    echo "   -> Instanciation de HttpServer...\n";
    $httpServer = new HttpServer($wsServer);

    // Création du serveur d'entrées/sorties (I/O) qui écoute sur un port
    $port = 8082;
    echo "   -> Instanciation de IoServer sur le port $port...\n";
    $server = IoServer::factory($httpServer, $port);

    echo "4. Serveur prêt. Lancement et écoute des connexions...\n";
    echo "--------------------------------------------------------\n";
    // Lancement du serveur. Ce processus est bloquant et tournera en continu.
    $server->run();

} catch (\Exception $e) {
    // Si une erreur se produit PENDANT le démarrage, elle sera capturée ici.
    echo "\n--------------------------------------------------------\n";
    echo "   ERREUR FATALE LORS DU DÉMARRAGE DU SERVEUR\n";
    echo "--------------------------------------------------------\n";
    echo "Message : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne   : " . $e->getLine() . "\n\n";
    echo "Causes possibles :\n";
    echo "  -> Le port $port est déjà utilisé par une autre application (Apache, Skype, etc.).\n";
    echo "  -> Une erreur s'est produite dans le constructeur de la classe 'CollaborationServer'.\n";
    echo "--------------------------------------------------------\n";
}
