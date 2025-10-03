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
                if (e.target.closest('button, a, input[type="checkbox"]')) {
                    return;
                }
                const docId = row.dataset.docId;
                openModalForDocument(docId);
            });
        });

        const openModalForDocument = async (docId) => {
            if (!docId) return;
            modalTitle.textContent = 'Chargement...';
            modalAttachmentsList.innerHTML = '<li>Chargement des pièces jointes...</li>';
            modalPreviewIframe.src = 'about:blank';
            modal.style.display = 'flex';

            try {
                const response = await fetch(`/document/details?id=${docId}`);
                if (!response.ok) throw new Error('Document non trouvé ou erreur serveur.');
                const data = await response.json();
                
                modalTitle.textContent = data.main_document.original_filename;
                modalPreviewIframe.src = `/document/download?id=${data.main_document.id}`;

                modalAttachmentsList.innerHTML = '';
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

        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.classList.contains('modal-close')) {
                modal.style.display = 'none';
                modalPreviewIframe.src = 'about:blank';
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
        const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws';
        // On se connecte à un CHEMIN "/ws" sur le MÊME domaine, géré par le reverse proxy
        const socketUrl = `${protocol}://${window.location.host}/ws`;
        console.log(`Tentative de connexion au WebSocket sur ${socketUrl}`);
        
        try {
            const socket = new WebSocket(socketUrl);

            socket.onopen = () => console.log('WebSocket connecté avec succès via le reverse proxy.');

            socket.onmessage = (event) => {
                const payload = JSON.parse(event.data);
                if (payload.action === 'print_sent') {
                    showToast(payload.data.message, '🖨️');
                    updatePrintQueueDashboard();
                    const docRow = document.querySelector(`tr[data-doc-id="${payload.data.doc_id}"]`);
                    if (docRow) {
                        const statusDot = docRow.querySelector('.status-dot');
                        if (statusDot) {
                            statusDot.style.backgroundColor = '#ffc107';
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
            console.error("Impossible de créer la connexion WebSocket.", e);
        }
    }

    connectWebSocket();

    // --- Gestion du dashboard de la file d'impression ---
    const printQueueBody = document.getElementById('print-queue-body');

    async function updatePrintQueueDashboard() {
        try {
            const response = await fetch('/print-queue/status');
            if (!response.ok) {
                printQueueBody.innerHTML = '<tr><td colspan="4" class="empty-state" style="color: var(--danger-color);">Impossible de récupérer le statut de la file d\'impression.</td></tr>';
                return;
            }
            const queue = await response.json();
            const activeQueue = queue.filter(job => job.status !== 'Terminé');
            
            printQueueBody.innerHTML = '';

            if (activeQueue.length > 0) {
                activeQueue.forEach(job => {
                    const row = document.createElement('tr');
                    let statusHtml = `<span>${job.status}</span>`;
                    
                    if (job.status === 'En cours d\'impression') {
                        statusHtml = `<span style="color: var(--primary-color);">${job.status} ⏳</span>`;
                    } else if (job.status === 'Erreur') {
                        statusHtml = `<span style="color: var(--danger-color); font-weight: bold;">${job.status} ⚠️</span>`;
                    }

                    row.innerHTML = `
                        <td>${job.filename || 'N/A'}</td>
                        <td>${job.job_id || 'N/A'}</td>
                        <td>${statusHtml}</td>
                        <td>${job.error || 'Aucun détail'}</td>
                    `;
                    printQueueBody.appendChild(row);
                });
            } else {
                printQueueBody.innerHTML = '<tr><td colspan="4" class="empty-state">La file d\'impression est vide.</td></tr>';
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour du dashboard d\'impression:', error);
            printQueueBody.innerHTML = '<tr><td colspan="4" class="empty-state" style="color: var(--danger-color);">Erreur lors de la mise à jour du dashboard.</td></tr>';
        }
    }

    updatePrintQueueDashboard();
    setInterval(updatePrintQueueDashboard, 7000);
});
