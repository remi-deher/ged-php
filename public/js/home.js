// public/js/home.js

document.addEventListener('DOMContentLoaded', () => {
    
    // Initialisation de tous les modules
    initClickAndDoubleClick();
    initDetailsSidebar();
    initDocumentModal();
    initBulkSelection();
    initViewSwitcher();
    initContextMenu();
    initDragAndDrop();
    initClientSideSearch();
    initPrintQueueDashboard();

    // --- Gestionnaire central pour clic et double-clic ---
    function initClickAndDoubleClick() {
        const rows = document.querySelectorAll('.document-row');
        rows.forEach(row => {
            let clickTimer = null;

            row.addEventListener('click', (e) => {
                // Ignorer si on clique sur un élément interactif
                if (e.target.closest('button, a, input[type="checkbox"], form')) return;

                if (clickTimer === null) {
                    // Démarrer un timer. Si rien ne se passe, c'est un simple clic.
                    clickTimer = setTimeout(() => {
                        clickTimer = null; // Réinitialiser le timer
                        const docId = row.dataset.docId;
                        window.openSidebarForDocument(docId);
                    }, 250); // Délai de 250ms pour détecter un double-clic
                } else {
                    // Si un timer existe déjà, c'est un double-clic
                    clearTimeout(clickTimer);
                    clickTimer = null; // Réinitialiser le timer
                    const docId = row.dataset.docId;
                    window.openModalForDocument(docId);
                }
            });
        });
    }

    // --- Gestion de la BARRE LATERALE ---
    function initDetailsSidebar() {
        const sidebar = document.getElementById('details-sidebar');
        const mainContent = document.getElementById('main-content');
        if (!sidebar) return;

        const title = document.getElementById('sidebar-title');
        const infoList = document.getElementById('sidebar-info-list');
        const attachmentsList = document.getElementById('sidebar-attachments-list');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const attachmentsToggleBtn = document.getElementById('sidebar-attachments-toggle-btn');
        
        window.openSidebarForDocument = async (docId) => {
            if (!docId) return;
            sidebar.classList.add('open');
            mainContent.classList.add('sidebar-open');
            
            title.textContent = 'Chargement...';
            infoList.innerHTML = '<li>Chargement...</li>';
            attachmentsList.innerHTML = '';

            try {
                const response = await fetch(`/document/details?id=${docId}`);
                if (!response.ok) throw new Error('Document non trouvé.');
                const data = await response.json();
                
                title.textContent = data.main_document.original_filename;
                 infoList.innerHTML = `
                    <li><strong>Taille:</strong> <span>${data.main_document.size_formatted}</span></li>
                    <li><strong>Type:</strong> <span>${data.main_document.mime_type}</span></li>
                    <li><strong>Ajouté le:</strong> <span>${new Date(data.main_document.created_at).toLocaleString('fr-FR')}</span></li>
                    <li><strong>Source:</strong> <span>${data.main_document.source_account_id ? 'E-mail' : 'Manuel'}</span></li>
                 `;

                const attachmentsContainer = document.getElementById('sidebar-attachments');
                if (data.attachments && data.attachments.length > 0) {
                    attachmentsContainer.style.display = 'block';
                    data.attachments.forEach(att => {
                        attachmentsList.innerHTML += `<li><a href="/document/download?id=${att.id}" target="_blank">${att.original_filename}</a></li>`;
                    });
                } else {
                    attachmentsContainer.style.display = 'none';
                }

            } catch (error) {
                title.textContent = 'Erreur';
                infoList.innerHTML = `<li>${error.message}</li>`;
            }
        };
        
        const closeSidebar = () => {
             sidebar.classList.remove('open');
             mainContent.classList.remove('sidebar-open');
        };

        closeBtn.addEventListener('click', closeSidebar);
        attachmentsToggleBtn.addEventListener('click', () => {
            attachmentsToggleBtn.classList.toggle('collapsed');
            attachmentsList.classList.toggle('collapsed');
        });
    }

    // --- Gestion de la MODALE ---
    function initDocumentModal() {
        const modal = document.getElementById('document-modal');
        if (!modal) return;

        const modalTitle = document.getElementById('modal-title');
        const attachmentsList = document.getElementById('modal-attachments-list');
        const previewIframe = document.getElementById('modal-preview-iframe');
        const closeBtn = modal.querySelector('.modal-close');
        const attachmentsToggleBtn = document.getElementById('modal-attachments-toggle-btn');
        const attachmentsPanel = document.getElementById('modal-attachments');

        window.openModalForDocument = async (docId) => {
            if (!docId) return;
            
            modal.style.display = 'flex';
            modalTitle.textContent = 'Chargement...';
            attachmentsList.innerHTML = '<li>Chargement...</li>';
            previewIframe.src = 'about:blank';

            try {
                const response = await fetch(`/document/details?id=${docId}`);
                if (!response.ok) throw new Error('Document non trouvé.');
                const data = await response.json();
                
                modalTitle.textContent = data.main_document.original_filename;
                previewIframe.src = `/document/download?id=${data.main_document.id}`;

                if (data.attachments && data.attachments.length > 0) {
                    attachmentsPanel.style.display = 'block';
                    attachmentsList.innerHTML = '';
                    data.attachments.forEach(attachment => {
                        const li = document.createElement('li');
                        li.innerHTML = `📄 <a href="/document/download?id=${attachment.id}" target="_blank" title="${attachment.original_filename}">${attachment.original_filename}</a>`;
                        attachmentsList.appendChild(li);
                    });
                } else {
                     attachmentsPanel.style.display = 'none';
                }
            } catch (error) {
                console.error('Erreur:', error);
                modalTitle.textContent = 'Erreur';
                attachmentsList.innerHTML = `<li>Impossible de charger les informations.</li>`;
            }
        };
        
        const closeModal = () => {
            modal.style.display = 'none';
            previewIframe.src = 'about:blank';
        };

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        attachmentsToggleBtn.addEventListener('click', () => {
            attachmentsPanel.classList.toggle('collapsed');
            const isCollapsed = attachmentsPanel.classList.contains('collapsed');
            attachmentsToggleBtn.textContent = isCollapsed ? '›' : '‹';
            attachmentsToggleBtn.title = isCollapsed ? 'Afficher' : 'Masquer';
        });
    }

    // --- MENU CONTEXTUEL ---
    function initContextMenu() {
        const contextMenu = document.getElementById('context-menu');
        let currentDocId = null;

        document.addEventListener('contextmenu', e => {
            const targetRow = e.target.closest('.document-row');
            if (targetRow) {
                e.preventDefault();
                currentDocId = targetRow.dataset.docId;
                contextMenu.style.top = `${e.clientY}px`;
                contextMenu.style.left = `${e.clientX}px`;
                contextMenu.style.display = 'block';
            } else {
                contextMenu.style.display = 'none';
            }
        });
        document.addEventListener('click', () => contextMenu.style.display = 'none');

        contextMenu.addEventListener('click', e => {
            const action = e.target.dataset.action;
            if (!action || !currentDocId) return;

            switch (action) {
                case 'preview_sidebar':
                    window.openSidebarForDocument(currentDocId);
                    break;
                case 'preview_modal':
                    window.openModalForDocument(currentDocId);
                    break;
                case 'print':
                    const printForm = document.createElement('form');
                    printForm.method = 'POST';
                    printForm.action = '/document/print';
                    printForm.innerHTML = `<input type="hidden" name="doc_id" value="${currentDocId}">`;
                    document.body.appendChild(printForm);
                    printForm.submit();
                    break;
                case 'download':
                    window.open(`/document/download?id=${currentDocId}`, '_blank');
                    break;
                case 'delete':
                     if (confirm('Confirmer la mise à la corbeille ?')) {
                        const deleteForm = document.createElement('form');
                        deleteForm.method = 'POST';
                        deleteForm.action = '/document/delete';
                        deleteForm.innerHTML = `<input type="hidden" name="doc_ids[]" value="${currentDocId}">`;
                        document.body.appendChild(deleteForm);
                        deleteForm.submit();
                    }
                    break;
            }
        });
    }

    // --- AUTRES FONCTIONS (INCHANGEES) ---
    function initBulkSelection() {
        const mainCheckbox = document.getElementById('select-all-checkbox');
        const docCheckboxes = document.querySelectorAll('.doc-checkbox');
        const bulkDeleteButton = document.getElementById('bulk-delete-button');
        const bulkPrintButton = document.getElementById('bulk-print-button');
        function toggleBulkActionButtons() {
            const anyChecked = Array.from(docCheckboxes).some(cb => cb.checked);
            if(bulkDeleteButton) bulkDeleteButton.style.display = anyChecked ? 'inline-flex' : 'none';
            if(bulkPrintButton) bulkPrintButton.style.display = anyChecked ? 'inline-flex' : 'none';
        }
        if (mainCheckbox) {
            mainCheckbox.addEventListener('change', (e) => {
                docCheckboxes.forEach(checkbox => {
                    if (checkbox.closest('.document-row').style.display !== 'none') {
                        checkbox.checked = e.target.checked;
                    }
                });
                toggleBulkActionButtons();
            });
        }
        docCheckboxes.forEach(checkbox => checkbox.addEventListener('change', toggleBulkActionButtons));
        toggleBulkActionButtons();
    }
    
    function initViewSwitcher() {
        const listViewBtn = document.getElementById('list-view-btn');
        const gridViewBtn = document.getElementById('grid-view-btn');
        const listView = document.getElementById('document-list-view');
        const gridView = document.getElementById('document-grid-view');
        if (!listViewBtn || !gridViewBtn || !listView || !gridView) return;
        const savedView = localStorage.getItem('ged_view_mode') || 'list';
        function setView(view) {
            if (view === 'grid') {
                listView.style.display = 'none';
                gridView.style.display = 'grid';
                listViewBtn.classList.remove('active');
                gridViewBtn.classList.add('active');
                localStorage.setItem('ged_view_mode', 'grid');
            } else {
                gridView.style.display = 'none';
                listView.style.display = 'block';
                gridViewBtn.classList.remove('active');
                listViewBtn.classList.add('active');
                localStorage.setItem('ged_view_mode', 'list');
            }
        }
        listViewBtn.addEventListener('click', () => setView('list'));
        gridViewBtn.addEventListener('click', () => setView('grid'));
        setView(savedView);
    }

    function initClientSideSearch() {
        const searchInput = document.getElementById('search-input');
        if (!searchInput) return;
        searchInput.closest('form').addEventListener('submit', e => e.preventDefault());
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();
            document.querySelectorAll('.document-row').forEach(row => {
                const docNameEl = row.querySelector('.col-name strong, .grid-item-name');
                const docName = docNameEl ? docNameEl.textContent.toLowerCase() : '';
                const displayStyle = row.tagName === 'TR' ? 'table-row' : 'flex';
                row.style.display = docName.includes(query) ? displayStyle : 'none';
            });
        });
    }

    function initDragAndDrop() {
        const draggableDocuments = document.querySelectorAll('.document-row[draggable="true"]');
        const dropzones = document.querySelectorAll('.dropzone, #root-dropzone');
        draggableDocuments.forEach(doc => {
            doc.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', doc.dataset.docId);
                setTimeout(() => doc.classList.add('dragging'), 0);
            });
            doc.addEventListener('dragend', () => doc.classList.remove('dragging'));
        });
        dropzones.forEach(zone => {
            zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
            zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
            zone.addEventListener('drop', async e => {
                e.preventDefault();
                zone.classList.remove('drag-over');
                const docId = e.dataTransfer.getData('text/plain');
                const folderId = zone.dataset.folderId;
                if (!docId || !folderId) return;
                const formData = new FormData();
                formData.append('doc_id', docId);
                formData.append('folder_id', folderId);
                try {
                    const response = await fetch('/document/move', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Erreur.');
                    showToast(result.message, '📁');
                    const movedDocRow = document.querySelector(`[data-doc-id="${docId}"]`);
                    if (movedDocRow) {
                        movedDocRow.style.transition = 'opacity 0.5s ease';
                        movedDocRow.style.opacity = '0';
                        setTimeout(() => movedDocRow.remove(), 500);
                    }
                } catch (error) {
                    showToast(`Erreur : ${error.message}`, '⚠️');
                }
            });
        });
    }

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
    
    function initPrintQueueDashboard() {
        const printQueueBody = document.getElementById('print-queue-body');
        const printQueueDashboard = document.getElementById('print-queue-dashboard');
        if (!printQueueBody || !printQueueDashboard) return;

        async function handlePrintAction(url, docId, button, successIcon, errorIcon) {
            button.disabled = true;
            button.textContent = '...';
            const formData = new FormData();
            formData.append('doc_id', docId);
            try {
                const response = await fetch(url, { method: 'POST', body: formData });
                const data = await response.json();
                if (!response.ok) throw new Error(data.error || 'Erreur inconnue');
                showToast(data.message, successIcon);
                updatePrintQueueDashboard();
            } catch (error) {
                showToast(`Erreur : ${error.message}`, '⚠️');
                button.disabled = false;
                button.textContent = errorIcon;
            }
        }
        
        async function updatePrintQueueDashboard() {
            try {
                const response = await fetch('/print-queue/status');
                if (!response.ok) throw new Error('Impossible de récupérer le statut.');
                const queue = await response.json();
                const activeQueue = queue.filter(job => job.status !== 'Terminé');
                
                if (activeQueue.length > 0) {
                    printQueueDashboard.style.display = 'block';
                    printQueueBody.innerHTML = '';
                    activeQueue.forEach(job => {
                        const row = document.createElement('tr');
                        row.dataset.docId = job.id;
                        let statusHtml = `<span>⏳ En attente</span>`;
                        let actionHtml = `<button class="button-icon button-delete btn-cancel-print" title="Annuler">❌</button>`;
                        if (job.status === 'En cours d\'impression') statusHtml = `<span>🖨️ ${job.status}</span>`;
                        else if (job.status === 'Erreur') {
                            statusHtml = `<span style="color: var(--danger-color); font-weight: bold;">⚠️ ${job.status}</span>`;
                            actionHtml = `<button class="button-icon btn-clear-error" title="Effacer">🗑️</button>`;
                        }
                        row.innerHTML = `<td>${job.filename||'N/A'}</td><td>${job.job_id||'N/A'}</td><td>${statusHtml}</td><td>${job.error||'Aucun détail'}</td><td>${actionHtml}</td>`;
                        printQueueBody.appendChild(row);
                    });
                    printQueueBody.querySelectorAll('.btn-cancel-print').forEach(b => b.addEventListener('click', e => handlePrintAction('/document/cancel-print', e.currentTarget.closest('tr').dataset.docId, e.currentTarget, '🗑️', '❌')));
                    printQueueBody.querySelectorAll('.btn-clear-error').forEach(b => b.addEventListener('click', e => handlePrintAction('/document/clear-print-error', e.currentTarget.closest('tr').dataset.docId, e.currentTarget, '✨', '🗑️')));
                } else {
                    printQueueDashboard.style.display = 'none';
                }
            } catch (error) {
                printQueueDashboard.style.display = 'block';
                printQueueBody.innerHTML = `<tr><td colspan="5" class="empty-state" style="color: var(--danger-color);">${error.message}</td></tr>`;
            }
        }
        
        function connectWebSocket() {
            const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws';
            const socketUrl = `${protocol}://${window.location.host}/ws`;
            try {
                const socket = new WebSocket(socketUrl);
                socket.onopen = () => console.log('WebSocket connecté.');
                socket.onmessage = (event) => {
                    try {
                        const payload = JSON.parse(event.data);
                        if (['new_document', 'document_deleted', 'print_cancelled', 'print_error_cleared'].includes(payload.action)) {
                           showToast("Mise à jour du serveur reçue, actualisation...", "🔄");
                           setTimeout(() => window.location.reload(), 1500);
                        }
                        if (payload.action === 'print_sent') {
                           showToast(payload.data.message, '🖨️');
                           updatePrintQueueDashboard();
                           const docRow = document.querySelector(`[data-doc-id="${payload.data.doc_id}"] .status-dot`);
                           if (docRow) {
                               docRow.style.backgroundColor = '#ffc107'; // Jaune
                               docRow.title = 'À imprimer';
                           }
                        }
                    } catch (e) { console.error("Erreur parsing WebSocket", e); }
                };
                socket.onclose = () => { console.log('WebSocket déconnecté, reconnexion...'); setTimeout(connectWebSocket, 5000); };
                socket.onerror = (err) => { console.error('Erreur WebSocket:', err); socket.close(); };
            } catch(e) { console.error("Impossible de créer la connexion WebSocket.", e); }
        }

        updatePrintQueueDashboard();
        setInterval(updatePrintQueueDashboard, 7000);
        connectWebSocket();
    }
});
