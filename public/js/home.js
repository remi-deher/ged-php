document.addEventListener('DOMContentLoaded', () => {
    // --- Gestion des cases à cocher et des actions groupées ---
    const mainCheckbox = document.getElementById('select-all-checkbox');
    const docCheckboxes = document.querySelectorAll('.doc-checkbox');
    const bulkDeleteButton = document.getElementById('bulk-delete-button');
    const bulkPrintButton = document.getElementById('bulk-print-button');

    function toggleBulkActionButtons() {
        const anyChecked = document.querySelectorAll('.doc-checkbox:checked').length > 0;
        if (bulkDeleteButton) bulkDeleteButton.style.display = anyChecked ? 'inline-flex' : 'none';
        if (bulkPrintButton) bulkPrintButton.style.display = anyChecked ? 'inline-flex' : 'none';
    }

    if (mainCheckbox) {
        mainCheckbox.addEventListener('change', (e) => {
            docCheckboxes.forEach(checkbox => checkbox.checked = e.target.checked);
            toggleBulkActionButtons();
        });
    }

    docCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            if (!checkbox.classList.contains('attachment-checkbox')) {
                const parentId = checkbox.closest('tr').dataset.docId;
                document.querySelectorAll(`.attachment-row[data-parent-id="${parentId}"] .doc-checkbox`).forEach(child => {
                    child.checked = checkbox.checked;
                });
            }
            toggleBulkActionButtons();
        });
    });

    // --- Gestion de la modale ---
    // ... (code de la modale identique à avant) ...

    // --- NOUVEAU : Gestion des notifications Toast via WebSocket ---
    function showToast(message, icon = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `<i data-lucide="${icon}"></i> ${message}`;
        container.appendChild(toast);
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Animer l'apparition
        setTimeout(() => toast.classList.add('show'), 100);

        // Faire disparaître après 5 secondes
        setTimeout(() => {
            toast.classList.remove('show');
            toast.addEventListener('transitionend', () => toast.remove());
        }, 5000);
    }

    function connectWebSocket() {
        try {
            const socket = new WebSocket('ws://127.0.0.1:8082');

            socket.onopen = () => console.log('WebSocket connecté.');

            socket.onmessage = (event) => {
                const payload = JSON.parse(event.data);
                if (payload.action === 'print_sent') {
                    showToast(payload.data.message, 'printer');
                    
                    // Mettre à jour visuellement le statut du document
                    const docRow = document.querySelector(`tr[data-doc-id="${payload.data.doc_id}"]`);
                    if (docRow) {
                        const statusDot = docRow.querySelector('.status-dot');
                        if (statusDot) {
                            statusDot.style.backgroundColor = '#ffc107'; // Couleur "À imprimer"
                            statusDot.title = 'À imprimer';
                        }
                    }
                }
            };

            socket.onclose = () => {
                console.log('WebSocket déconnecté. Tentative de reconnexion dans 5s...');
                setTimeout(connectWebSocket, 5000);
            };

            socket.onerror = (error) => {
                console.error('Erreur WebSocket:', error);
                socket.close();
            };
        } catch(e) {
            console.error("Impossible de se connecter au serveur WebSocket. Les notifications temps réel sont désactivées.");
        }
    }

    // Lancer la connexion WebSocket
    connectWebSocket();

    // Initialisation des icônes Lucide
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
