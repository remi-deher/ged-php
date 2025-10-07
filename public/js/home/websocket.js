// public/js/home/websocket.js

import { showToast } from '../utils.js';

// On exporte directement la fonction pour qu'elle soit importable
export function connectWebSocket() {
    const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws';
    const wsUrl = `${protocol}://${window.location.host}/ws`;
    
    try {
        const ws = new WebSocket(wsUrl);

        ws.onopen = () => console.log('WebSocket connecté.');

        ws.onmessage = (event) => {
            try {
                const message = JSON.parse(event.data);
                
                // Logique pour rafraîchir la page si des changements majeurs ont lieu
                if (['new_document', 'document_deleted', 'document_updated'].includes(message.action)) {
                    showToast('Mise à jour du serveur, actualisation...', 'info');
                    // On attend un court instant pour que l'utilisateur voie le message
                    setTimeout(() => {
                        // On demande à l'application principale de se rafraîchir
                        if (window.GED && window.GED.App) {
                            window.GED.App.fetchAndDisplayDocuments();
                        }
                    }, 1500);
                } else if (message.action === 'toast') {
                    // Pour les notifications simples
                    showToast(message.data.message, message.data.type || 'info');
                }

            } catch (e) {
                console.error('Erreur lors du parsing du message WebSocket', e);
            }
        };

        ws.onclose = () => {
            console.log('WebSocket déconnecté. Tentative de reconnexion dans 5s...');
            // On essaie de se reconnecter après 5 secondes
            setTimeout(connectWebSocket, 5000);
        };

        ws.onerror = (error) => {
            console.error('Erreur WebSocket:', error);
            // L'événement onclose sera appelé automatiquement après une erreur
            ws.close();
        };

    } catch(e) {
        console.error('Impossible de créer la connexion WebSocket.', e);
    }
}
