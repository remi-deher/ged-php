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
                            statusDot.style.backgroundColor = '#ffc107'; // Jaune "À imprimer"
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
    const printQueueDashboard = document.getElementById('print-queue-dashboard');

    async function handleCancelPrint(event) {
        const button = event.currentTarget;
        const row = button.closest('tr');
        const docId = row.dataset.docId;

        if (!docId || !confirm("Voulez-vous vraiment annuler cette impression ?")) {
            return;
        }

        button.disabled = true;
        button.textContent = '...';

        const formData = new FormData();
        formData.append('doc_id', docId);

        try {
            const response = await fetch('/document/cancel-print', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.error || 'Erreur inconnue');
            }
            showToast(data.message, '🗑️');
            updatePrintQueueDashboard(); // Recharge la file pour être sûr
            
            // Met à jour le statut dans la liste principale
            const mainDocRow = document.querySelector(`.email-row[data-doc-id="${docId}"] .status-dot`);
            if(mainDocRow) {
                mainDocRow.style.backgroundColor = '#007bff'; // Bleu "Reçu"
                mainDocRow.title = 'Reçu (Annulée)';
            }

        } catch (error) {
            showToast(`Erreur : ${error.message}`, '⚠️');
            button.disabled = false;
            button.textContent = '❌';
        }
    }
    
    async function handleClearError(event) {
        const button = event.currentTarget;
        const row = button.closest('tr');
        const docId = row.dataset.docId;

        button.disabled = true;
        button.textContent = '...';

        const formData = new FormData();
        formData.append('doc_id', docId);

        try {
            const response = await fetch('/document/clear-print-error', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.error || 'Erreur inconnue');
            }
            showToast(data.message, '✨');
            updatePrintQueueDashboard(); // Recharge la file
            
            // Met à jour le statut dans la liste principale
            const mainDocRow = document.querySelector(`.email-row[data-doc-id="${docId}"] .status-dot`);
            if(mainDocRow) {
                mainDocRow.style.backgroundColor = '#007bff'; // Bleu "Reçu"
                mainDocRow.title = 'Reçu';
            }

        } catch (error) {
            showToast(`Erreur : ${error.message}`, '⚠️');
            button.disabled = false;
            button.textContent = '🗑️';
        }
    }

    async function updatePrintQueueDashboard() {
        try {
            const response = await fetch('/print-queue/status');
            if (!response.ok) {
                printQueueDashboard.style.display = 'block';
                printQueueBody.innerHTML = '<tr><td colspan="5" class="empty-state" style="color: var(--danger-color);">Impossible de récupérer le statut.</td></tr>';
                return;
            }
            
            const queue = await response.json();
            const activeQueue = queue.filter(job => job.status !== 'Terminé');
            
            if (activeQueue.length > 0) {
                printQueueDashboard.style.display = 'block';
                printQueueBody.innerHTML = '';

                activeQueue.forEach(job => {
                    const row = document.createElement('tr');
                    row.dataset.docId = job.id;

                    let statusHtml = '';
                    let actionHtml = '';

                    if (job.status === 'En cours d\'impression') {
                        statusHtml = `<span>🖨️ ${job.status}</span>`;
                        actionHtml = `<button class="button-icon button-delete btn-cancel-print" title="Annuler l'impression">❌</button>`;
                    } else if (job.status === 'Erreur') {
                        statusHtml = `<span style="color: var(--danger-color); font-weight: bold;">⚠️ ${job.status}</span>`;
                        actionHtml = `<button class="button-icon btn-clear-error" title="Effacer l'erreur">🗑️</button>`;
                    } else { // Statut en attente
                        statusHtml = `<span>⏳ En attente</span>`;
                        actionHtml = `<button class="button-icon button-delete btn-cancel-print" title="Annuler l'impression">❌</button>`;
                    }

                    row.innerHTML = `
                        <td>${job.filename || 'N/A'}</td>
                        <td>${job.job_id || 'N/A'}</td>
                        <td>${statusHtml}</td>
                        <td>${job.error || 'Aucun détail'}</td>
                        <td>${actionHtml}</td>
                    `;
                    printQueueBody.appendChild(row);
                });

                document.querySelectorAll('.btn-cancel-print').forEach(button => {
                    button.addEventListener('click', handleCancelPrint);
                });
                document.querySelectorAll('.btn-clear-error').forEach(button => {
                    button.addEventListener('click', handleClearError);
                });

            } else {
                printQueueDashboard.style.display = 'none';
                printQueueBody.innerHTML = '';
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour du dashboard d\'impression:', error);
            printQueueDashboard.style.display = 'block';
            printQueueBody.innerHTML = '<tr><td colspan="5" class="empty-state" style="color: var(--danger-color);">Erreur de mise à jour.</td></tr>';
        }
    }

    updatePrintQueueDashboard();
    setInterval(updatePrintQueueDashboard, 7000);
});
