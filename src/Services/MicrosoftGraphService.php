<?php
// src/Services/MicrosoftGraphService.php

namespace App\Services;

use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Exception;

class MicrosoftGraphService
{
    private GraphServiceClient $graph;

    /**
     * Initialise le client Graph avec les identifiants fournis.
     * @param array $credentials ['tenant_id', 'client_id', 'client_secret']
     * @throws Exception Si les identifiants sont invalides.
     */
    public function __construct(array $credentials)
    {
        if (empty($credentials['tenant_id']) || empty($credentials['client_id']) || empty($credentials['client_secret'])) {
            throw new Exception("Les informations d'identification pour l'API Graph sont incomplètes.");
        }

        $tokenRequestContext = new ClientCredentialContext(
            $credentials['tenant_id'],
            $credentials['client_id'],
            $credentials['client_secret']
        );
        
        $this->graph = new GraphServiceClient($tokenRequestContext);
    }

    /**
     * Récupère la liste des dossiers de messagerie pour un utilisateur donné.
     * @param string $userEmail L'adresse e-mail de la boîte à lire.
     * @return array La liste des dossiers avec leur ID et leur nom.
     * @throws Exception En cas d'échec de la connexion ou de la récupération.
     */
    public function listMailFolders(string $userEmail): array
    {
        if (empty($userEmail)) {
            throw new Exception("L'adresse e-mail de l'utilisateur est requise.");
        }

        try {
            $mailFoldersResponse = $this->graph->users()->byUserId($userEmail)->mailFolders()->get()->wait();
            
            $folders = [];
            foreach ($mailFoldersResponse->getValue() as $folder) {
                $folders[] = ['id' => $folder->getId(), 'name' => $folder->getDisplayName()];
            }
            return $folders;
        } catch (Exception $e) {
            // Renvoyer une exception plus générique pour ne pas fuiter de détails techniques
            throw new Exception("La connexion à l'API Microsoft Graph a échoué. Vérifiez les identifiants. Erreur originale : " . $e->getMessage());
        }
    }
}
