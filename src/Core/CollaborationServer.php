<?php
// src/Core/CollaborationServer.php

namespace App\Core;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class CollaborationServer implements MessageComponentInterface 
{
    /** @var \SplObjectStorage Pour stocker toutes les connexions des clients (navigateurs) */
    protected $clients;

    public function __construct() 
    {
        $this->clients = new \SplObjectStorage;
        echo "   [OK] La classe CollaborationServer a été chargée correctement.\n";
    }

    public function onOpen(ConnectionInterface $conn) 
    {
        // Ajoute le nouveau client à la liste des connexions
        $this->clients->attach($conn);
        echo "Nouvelle connexion ! ({$conn->resourceId})\n";
    }

    /**
     * C'est la méthode la plus importante. Elle est appelée quand un message arrive.
     * @param ConnectionInterface $from La connexion d'où vient le message (notre script PHP)
     * @param string $msg Le message lui-même (les données JSON)
     */
    public function onMessage(ConnectionInterface $from, $msg) 
    {
        echo "Message reçu de {$from->resourceId}: $msg\n";

        // On parcourt tous les clients connectés
        foreach ($this->clients as $client) {
            // On envoie le message à tout le monde, SAUF à celui qui vient de l'envoyer.
            // Dans notre cas, on pourrait même l'envoyer à tout le monde sans distinction.
            // if ($from !== $client) { 
                echo " -> Diffusion du message au client {$client->resourceId}\n";
                $client->send($msg);
            // }
        }
    }

    public function onClose(ConnectionInterface $conn) 
    {
        // Retire le client de la liste quand il se déconnecte
        $this->clients->detach($conn);
        echo "Connexion {$conn->resourceId} fermée.\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) 
    {
        echo "Une erreur est survenue sur la connexion {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }
}
