// public/js/home.js

document.addEventListener('DOMContentLoaded', () => {
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
            // Si la case cochée n'est pas une pièce jointe, cochez/décochez les pièces jointes associées
            if (!checkbox.classList.contains('attachment-checkbox')) {
                const parentId = checkbox.closest('tr').dataset.docId;
                document.querySelectorAll(`.attachment-row[data-parent-id="${parentId}"] .doc-checkbox`).forEach(child => {
                    child.checked = checkbox.checked;
                });
            }
            toggleBulkActionButtons();
        });
    });

    // --- Gestion des notifications Toast via WebSocket ---
    function showToast(message, icon = 'ℹ️') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `<span style="margin-right: 8px;">${icon}</span> ${message}`; // Remplacement de l'icône par un emoji/texte
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);
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
                    showToast(payload.data.message, '🖨️'); // Emoji pour l'impression
                    const docRow = document.querySelector(`tr[data-doc-id="${payload.data.doc_id}"]`);
                    if (docRow) {
                        const statusDot = docRow.querySelector('.status-dot');
                        if (statusDot) {
                            statusDot.style.backgroundColor = '#ffc107'; // Jaune "À imprimer"
                            statusDot.title = 'À imprimer';
                        }
                    }
                }
            };
            socket.onclose = () => { setTimeout(connectWebSocket, 5000); }; // Tente de se reconnecter après 5s
            socket.onerror = (error) => { console.error('Erreur WebSocket:', error); socket.close(); };
        } catch(e) {
            console.error("Impossible de se connecter au serveur WebSocket.");
        }
    }

    connectWebSocket();
});
