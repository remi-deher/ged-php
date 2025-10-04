// public/js/home.js

// Utilisation de 'var' pour permettre l'extension de l'objet sur plusieurs fichiers
var GED = window.GED || {};

GED.home = {
    init() {
        this.events.init();
        this.sidebar.init();
        this.modal.init();
        this.selection.init();
        this.viewSwitcher.init();
        this.contextMenu.init();
        this.dnd.init();
        this.printQueue.init();
        this.websocket.init();
    },

    events: {
        init() {
            document.querySelectorAll('.document-row:not(.folder-row)').forEach(row => {
                let clickTimer = null;
                row.addEventListener('click', (e) => {
                    if (e.target.closest('button, a, input[type="checkbox"], form')) return;
                    
                    if (clickTimer === null) {
                        clickTimer = setTimeout(() => {
                            clickTimer = null;
                            const docId = row.dataset.docId;
                            if (docId) GED.home.sidebar.openForDocument(docId);
                        }, 250);
                    } else {
                        clearTimeout(clickTimer);
                        clickTimer = null;
                        const docId = row.dataset.docId;
                        if (docId) GED.home.modal.openForDocument(docId);
                    }
                });
            });
        }
    },

    sidebar: {
        init() {
            this.sidebarEl = document.getElementById('details-sidebar');
            this.mainContentEl = document.getElementById('main-content');
            if (!this.sidebarEl || !this.mainContentEl) return;

            this.titleEl = document.getElementById('sidebar-title');
            this.infoListEl = document.getElementById('sidebar-info-list');
            this.attachmentsListEl = document.getElementById('sidebar-attachments-list');
            
            document.getElementById('sidebar-close-btn')?.addEventListener('click', () => this.close());
            document.getElementById('sidebar-attachments-toggle-btn')?.addEventListener('click', (e) => {
                e.currentTarget.classList.toggle('collapsed');
                this.attachmentsListEl?.classList.toggle('collapsed');
            });
        },
        async openForDocument(docId) {
            if (!docId || !this.sidebarEl) return;
            
            this.sidebarEl.classList.add('open');
            this.mainContentEl.classList.add('sidebar-open');
            
            this.titleEl.textContent = 'Chargement...';
            this.infoListEl.innerHTML = '<li>Chargement...</li>';
            this.attachmentsListEl.innerHTML = '';

            try {
                const response = await fetch(`/document/details?id=${docId}`);
                if (!response.ok) throw new Error('Document non trouvé.');
                const data = await response.json();
                
                this.titleEl.textContent = data.main_document.original_filename;
                this.infoListEl.innerHTML = `
                    <li><strong>Taille:</strong> <span>${data.main_document.size_formatted}</span></li>
                    <li><strong>Type:</strong> <span>${data.main_document.mime_type}</span></li>
                    <li><strong>Ajouté le:</strong> <span>${new Date(data.main_document.created_at).toLocaleString('fr-FR')}</span></li>
                    <li><strong>Source:</strong> <span>${data.main_document.source_account_id ? 'E-mail' : 'Manuel'}</span></li>
                 `;

                const attachmentsContainer = document.getElementById('sidebar-attachments');
                if (attachmentsContainer) {
                    if (data.attachments && data.attachments.length > 0) {
                        attachmentsContainer.style.display = 'block';
                        this.attachmentsListEl.innerHTML = '';
                        data.attachments.forEach(att => {
                            this.attachmentsListEl.innerHTML += `<li><a href="/document/download?id=${att.id}" target="_blank">${att.original_filename}</a></li>`;
                        });
                    } else {
                        attachmentsContainer.style.display = 'none';
                    }
                }
            } catch (error) {
                this.titleEl.textContent = 'Erreur';
                this.infoListEl.innerHTML = `<li>${error.message}</li>`;
            }
        },
        close() {
            this.sidebarEl?.classList.remove('open');
            this.mainContentEl?.classList.remove('sidebar-open');
        }
    },

    modal: {
        init() {
            this.modalEl = document.getElementById('document-modal');
            if (!this.modalEl) return;

            this.titleEl = document.getElementById('modal-title');
            this.attachmentsListEl = document.getElementById('modal-attachments-list');
            this.previewIframeEl = document.getElementById('modal-preview-iframe');
            
            this.modalEl.querySelector('.modal-close')?.addEventListener('click', () => this.close());
            this.modalEl.addEventListener('click', (e) => {
                if (e.target === this.modalEl) this.close();
            });

            const attachmentsToggleBtn = document.getElementById('modal-attachments-toggle-btn');
            const attachmentsPanel = document.getElementById('modal-attachments');
            attachmentsToggleBtn?.addEventListener('click', () => {
                attachmentsPanel?.classList.toggle('collapsed');
                const isCollapsed = attachmentsPanel.classList.contains('collapsed');
                attachmentsToggleBtn.innerHTML = isCollapsed ? '›' : '‹';
                attachmentsToggleBtn.title = isCollapsed ? 'Afficher les pièces jointes' : 'Masquer les pièces jointes';
            });
        },
        async openForDocument(docId) {
            if (!docId || !this.modalEl) return;
            
            this.modalEl.style.display = 'flex';
            this.titleEl.textContent = 'Chargement...';
            this.attachmentsListEl.innerHTML = '<li>Chargement...</li>';
            this.previewIframeEl.src = 'about:blank';

            try {
                const response = await fetch(`/document/details?id=${docId}`);
                if (!response.ok) throw new Error('Document non trouvé.');
                const data = await response.json();
                
                this.titleEl.textContent = data.main_document.original_filename;
                this.previewIframeEl.src = `/document/download?id=${data.main_document.id}`;

                const attachmentsPanel = document.getElementById('modal-attachments');
                const attachmentsToggleBtn = document.getElementById('modal-attachments-toggle-btn');
                
                if (attachmentsPanel && attachmentsToggleBtn) {
                    if (data.attachments && data.attachments.length > 0) {
                        attachmentsPanel.style.display = 'block';
                        attachmentsToggleBtn.style.display = 'block';
                        this.attachmentsListEl.innerHTML = '';
                        data.attachments.forEach(attachment => {
                            const li = document.createElement('li');
                            li.innerHTML = `📄 <a href="/document/download?id=${attachment.id}" target="_blank" title="${attachment.original_filename}">${attachment.original_filename}</a>`;
                            this.attachmentsListEl.appendChild(li);
                        });
                    } else {
                         attachmentsPanel.style.display = 'none';
                         attachmentsToggleBtn.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.titleEl.textContent = 'Erreur';
                this.attachmentsListEl.innerHTML = `<li>Impossible de charger les informations.</li>`;
            }
        },
        close() {
            if (!this.modalEl) return;
            this.modalEl.style.display = 'none';
            if (this.previewIframeEl) this.previewIframeEl.src = 'about:blank';
        }
    },

    contextMenu: {
        init() {
            const contextMenu = document.getElementById('context-menu');
            if (!contextMenu) return;

            let currentItemId = null;

            document.addEventListener('contextmenu', e => {
                const targetRow = e.target.closest('.document-row, .folder-row');
                if (targetRow) {
                    e.preventDefault();
                    currentItemId = targetRow.dataset.docId || targetRow.dataset.folderId;
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
                if (!action) return;

                const selectedIds = Array.from(document.querySelectorAll('.doc-checkbox:checked')).map(cb => cb.value);
                const targetIds = selectedIds.length > 0 ? selectedIds : (currentItemId ? [currentItemId] : []);
                if (targetIds.length === 0) return;

                switch (action) {
                    case 'preview_sidebar':
                        if (targetIds.length === 1) GED.home.sidebar.openForDocument(targetIds[0]);
                        break;
                    case 'preview_modal':
                         if (targetIds.length === 1) GED.home.modal.openForDocument(targetIds[0]);
                        break;
                    case 'print':
                        GED.utils.createAndSubmitForm('/document/bulk-print', 'POST', { 'doc_ids': targetIds });
                        break;
                    case 'download':
                        targetIds.forEach(id => window.open(`/document/download?id=${id}`, '_blank'));
                        break;
                    case 'delete':
                        if (confirm('Confirmer la mise à la corbeille des éléments sélectionnés ?')) {
                            GED.utils.createAndSubmitForm('/document/delete', 'POST', { 'doc_ids': targetIds });
                        }
                        break;
                    case 'move':
                        GED.utils.showToast('Pour déplacer, glissez et déposez les éléments sur un dossier.', '➔');
                        break;
                }
            });
        }
    },
    
    selection: {
        init() {
            const mainCheckbox = document.getElementById("select-all-checkbox");
            const docCheckboxes = document.querySelectorAll(".doc-checkbox");
            
            this.updateButtonVisibility = () => {
                const anyChecked = Array.from(docCheckboxes).some(checkbox => checkbox.checked);
                document.getElementById("bulk-delete-button")?.style.setProperty('display', anyChecked ? 'inline-flex' : 'none');
                document.getElementById("bulk-print-button")?.style.setProperty('display', anyChecked ? 'inline-flex' : 'none');
            };

            mainCheckbox?.addEventListener("change", (event) => {
                docCheckboxes.forEach(checkbox => {
                    if (checkbox.closest("tr")?.style.display !== "none") {
                        checkbox.checked = event.target.checked;
                    }
                });
                this.updateButtonVisibility();
            });

            docCheckboxes.forEach(checkbox => checkbox.addEventListener("change", this.updateButtonVisibility));
            this.updateButtonVisibility();
        }
    },
    
    viewSwitcher: {
        init() {
            const listViewBtn = document.getElementById("list-view-btn");
            const gridViewBtn = document.getElementById("grid-view-btn");
            const listView = document.getElementById("document-list-view");
            const gridView = document.getElementById("document-grid-view");

            if (!listViewBtn || !gridViewBtn || !listView || !gridView) return;

            const switchView = (view) => {
                if (view === "grid") {
                    listView.style.display = "none";
                    gridView.style.display = "grid";
                    listViewBtn.classList.remove("active");
                    gridViewBtn.classList.add("active");
                } else {
                    gridView.style.display = "none";
                    listView.style.display = "block";
                    gridViewBtn.classList.remove("active");
                    listViewBtn.classList.add("active");
                }
                localStorage.setItem("ged_view_mode", view);
            };
            
            listViewBtn.addEventListener("click", () => switchView("list"));
            gridViewBtn.addEventListener("click", () => switchView("grid"));

            switchView(localStorage.getItem("ged_view_mode") || "list");
        }
    },

    dnd: {
        init() {
            const draggables = document.querySelectorAll('[draggable="true"]');
            const dropzones = document.querySelectorAll('.dropzone');

            draggables.forEach(draggable => {
                draggable.addEventListener('dragstart', e => {
                    const selectedCheckboxes = document.querySelectorAll('.doc-checkbox:checked');
                    let draggedItems = [];
                    const isChecked = draggable.querySelector('.doc-checkbox')?.checked;

                    if (isChecked && selectedCheckboxes.length > 1) {
                        selectedCheckboxes.forEach(checkbox => {
                            const row = checkbox.closest('[draggable="true"]');
                            if(row) {
                                const type = row.classList.contains('folder-row') ? 'folder' : 'document';
                                const id = type === 'folder' ? row.dataset.folderId : row.dataset.docId;
                                if(id) draggedItems.push({ type, id });
                            }
                        });
                    } else {
                        const type = draggable.classList.contains('folder-row') ? 'folder' : 'document';
                        const id = type === 'folder' ? draggable.dataset.folderId : draggable.dataset.docId;
                        if(id) draggedItems.push({ type, id });
                    }
                    
                    if (draggedItems.length > 0) {
                        e.dataTransfer.setData('application/json', JSON.stringify(draggedItems));
                        setTimeout(() => draggedItems.forEach(item => {
                            document.querySelector(`[data-doc-id="${item.id}"], [data-folder-id="${item.id}"]`)?.classList.add('dragging');
                        }), 0);
                    } else {
                        e.preventDefault();
                    }
                });

                draggable.addEventListener('dragend', () => {
                    document.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging'));
                });
            });

            dropzones.forEach(zone => {
                zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
                zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
                zone.addEventListener('drop', async e => {
                    e.preventDefault();
                    zone.classList.remove('drag-over');

                    const items = JSON.parse(e.dataTransfer.getData('application/json'));
                    const targetFolderId = zone.dataset.folderId;
                    
                    if (!items || items.length === 0 || typeof targetFolderId === 'undefined') return;

                    const docIds = items.filter(item => item.type === 'document').map(item => item.id);
                    const folderIds = items.filter(item => item.type === 'folder').map(item => item.id);

                    if (folderIds.includes(targetFolderId)) {
                        return GED.utils.showToast('Un dossier ne peut pas être déplacé dans lui-même.', '⚠️');
                    }

                    try {
                        let moved = false;
                        if (docIds.length > 0) {
                            const formData = new FormData();
                            docIds.forEach(id => formData.append('doc_ids[]', id));
                            formData.append('folder_id', targetFolderId);
                            const response = await fetch('/document/move', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                            const result = await response.json();
                            if (!response.ok) throw new Error(result.message);
                            moved = true;
                        }

                        if (folderIds.length > 0) {
                             GED.utils.showToast('Le déplacement de dossiers n\'est pas encore pris en charge.', '📁');
                        }

                        if (moved) {
                            GED.utils.showToast('Déplacement réussi ! Actualisation...', '✅');
                            setTimeout(() => window.location.reload(), 1000);
                        }
                    } catch (error) {
                        GED.utils.showToast(`Erreur : ${error.message}`, '⚠️');
                    }
                });
            });
        }
    },

    printQueue: {
        init() {
            this.queueBody = document.getElementById("print-queue-body");
            this.dashboard = document.getElementById("print-queue-dashboard");
            if (!this.queueBody || !this.dashboard) return;
            
            this.updateQueueStatus();
            setInterval(() => this.updateQueueStatus(), 7000);
        },
        async performAction(url, docId, button, successIcon, originalIcon) {
            button.disabled = true;
            button.textContent = '...';
            const formData = new FormData();
            formData.append('doc_id', docId);
            try {
                const response = await fetch(url, { method: 'POST', body: formData });
                const result = await response.json();
                if (!response.ok) throw new Error(result.error || 'Erreur inconnue');
                GED.utils.showToast(result.message, successIcon);
                this.updateQueueStatus();
            } catch (error) {
                GED.utils.showToast(`Erreur : ${error.message}`, '⚠️');
                button.disabled = false;
                button.textContent = originalIcon;
            }
        },
        async updateQueueStatus() {
            try {
                const response = await fetch('/print-queue/status');
                if (!response.ok) throw new Error('Impossible de récupérer le statut de la file d\'impression.');
                const queue = await response.json();
                
                const activeJobs = queue.filter(job => job.status !== 'Terminé');
    
                if (activeJobs.length > 0) {
                    this.dashboard.style.display = 'block';
                    this.queueBody.innerHTML = '';
                    activeJobs.forEach(job => {
                        const row = document.createElement('tr');
                        row.dataset.docId = job.id;
                        let statusHtml = `<span>${job.status}</span>`;
                        let actionsHtml = `<button class="button-icon button-delete btn-cancel-print" title="Annuler">❌</button>`;
    
                        if (job.status === 'Erreur') {
                            statusHtml = `<span style="color: var(--danger-color); font-weight: bold;">⚠️ ${job.status}</span>`;
                            actionsHtml = `<button class="button-icon btn-clear-error" title="Effacer l'erreur">🗑️</button>`;
                        }
    
                        row.innerHTML = `<td>${job.filename || 'N/A'}</td><td>${job.job_id || 'N/A'}</td><td>${statusHtml}</td><td>${job.error || 'Aucun détail'}</td><td>${actionsHtml}</td>`;
                        this.queueBody.appendChild(row);
                    });
    
                    this.queueBody.querySelectorAll('.btn-cancel-print').forEach(btn => {
                        btn.addEventListener('click', e => this.performAction('/document/cancel-print', e.currentTarget.closest('tr').dataset.docId, e.currentTarget, '🗑️', '❌'));
                    });
                    this.queueBody.querySelectorAll('.btn-clear-error').forEach(btn => {
                        btn.addEventListener('click', e => this.performAction('/document/clear-print-error', e.currentTarget.closest('tr').dataset.docId, e.currentTarget, '✨', '🗑️'));
                    });
                } else {
                    this.dashboard.style.display = 'none';
                }
            } catch (error) {
                this.dashboard.style.display = 'block';
                this.queueBody.innerHTML = `<tr><td colspan="5" class="empty-state" style="color: var(--danger-color);">${error.message}</td></tr>`;
            }
        }
    },
    
    websocket: {
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
    }
};

document.addEventListener('DOMContentLoaded', () => {
    GED.home.init();
});
