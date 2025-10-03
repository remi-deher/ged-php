// public/js/home.js

document.addEventListener('DOMContentLoaded', () => {
    // --- Gestion de la sélection multiple ---
    const mainCheckbox = document.getElementById('select-all-checkbox');
    const docCheckboxes = document.querySelectorAll('.doc-checkbox');
    const bulkDeleteButton = document.getElementById('bulk-delete-button');
    const bulkPrintButton = document.getElementById('bulk-print-button');
    const bulkActionForm = document.getElementById('bulk-action-form');

    function toggleBulkActionButtons() {
        const anyChecked = Array.from(docCheckboxes).some(cb => cb.checked);
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
        checkbox.addEventListener('change', toggleBulkActionButtons);
    });
    
    // --- Gestion de la modale de visualisation ---
    const modal = document.getElementById('document-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalAttachmentsList = document.getElementById('modal-attachments-list');
    const modalPreviewIframe = document.getElementById('modal-preview-iframe');
    
    if (modal) {
        document.querySelectorAll('.email-row').forEach(row => {
            row.addEventListener('click', (e) => {
                // Empêche l'ouverture de la modale si on clique sur un bouton, un lien ou une checkbox
                if (e.target.closest('button, a, input[type="checkbox"]')) {
                    return;
                }
                
                const docId = row.dataset.docId;
                openModalForDocument(docId);
            });
        });

        const openModalForDocument = async (docId) => {
            if (!docId) return;

            // Afficher un état de chargement
            modalTitle.textContent = 'Chargement...';
            modalAttachmentsList.innerHTML = '<li>Chargement des pièces jointes...</li>';
            modalPreviewIframe.src = 'about:blank';
            modal.style.display = 'flex';

            try {
                const response = await fetch(`/document/details?id=${docId}`);
                if (!response.ok) {
                    throw new Error('Document non trouvé ou erreur serveur.');
                }
                const data = await response.json();
                
                modalTitle.textContent = data.main_document.original_filename;
                
                // Afficher le document principal dans l'iframe
                modalPreviewIframe.src = `/document/download?id=${data.main_document.id}`;

                // Lister les pièces jointes
                modalAttachmentsList.innerHTML = ''; // Vider la liste
                if (data.attachments && data.attachments.length > 0) {
                    data.attachments.forEach(attachment => {
                        const li = document.createElement('li');
                        li.innerHTML = `📄 <a href="/document/download?id=${attachment.id}" target="_blank">${attachment.original_filename}</a>`;
                        modalAttachmentsList.appendChild(li);
                    });
                } else {
                    modalAttachmentsList.innerHTML = '<li>Aucune pièce jointe.</li>';
                }

            } catch (error) {
                console.error('Erreur lors de la récupération des détails du document:', error);
                modalTitle.textContent = 'Erreur';
                modalAttachmentsList.innerHTML = `<li>Impossible de charger les informations.</li>`;
            }
        };

        // Fermeture de la modale
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.classList.contains('modal-close')) {
                modal.style.display = 'none';
                modalPreviewIframe.src = 'about:blank'; // Important pour arrêter le chargement de PDF, etc.
            }
        });
    }


    // --- Gestion des notifications Toast via WebSocket ---
    function showToast(message, icon = 'ℹ️') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `<span style="margin-right: 8px;">${icon}</span> ${message}`;
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
                    showToast(payload.data.message, '🖨️');
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
            socket.onclose = () => { setTimeout(connectWebSocket, 5000); };
            socket.onerror = (error) => { console.error('Erreur WebSocket:', error); socket.close(); };
        } catch(e) {
            console.error("Impossible de se connecter au serveur WebSocket.");
        }
    }

    connectWebSocket();
});
