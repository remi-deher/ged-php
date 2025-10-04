// public/js/home/websocket.js

GED.home = GED.home || {};

GED.home.websocket = {
    init() {
        const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws';
        const wsUrl = `${protocol}://${window.location.host}/ws`;
        
        try {
            const ws = new WebSocket(wsUrl);
            ws.onopen = () => console.log('WebSocket connecté.');
            ws.onmessage = (event) => {
                try {
                    const message = JSON.parse(event.data);
                    if (['new_document', 'document_deleted', 'print_cancelled', 'print_error_cleared'].includes(message.action)) {
                        GED.utils.showToast('Mise à jour du serveur, actualisation...', '🔄');
                        setTimeout(() => window.location.reload(), 1500);
                    } else if (message.action === 'print_sent') {
                        GED.utils.showToast(message.data.message, '🖨️');
                        GED.home.printQueue.updateQueueStatus();
                    }
                } catch (e) {
                    console.error('Erreur lors du parsing du message WebSocket', e);
                }
            };
            ws.onclose = () => {
                console.log('WebSocket déconnecté. Tentative de reconnexion dans 5s...');
                setTimeout(() => this.init(), 5000);
            };
            ws.onerror = (error) => {
                console.error('Erreur WebSocket:', error);
                ws.close();
            };
        } catch(e) {
            console.error('Impossible de créer la connexion WebSocket.', e);
        }
    }
};
