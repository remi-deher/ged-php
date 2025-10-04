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
    // La recherche côté client est désactivée car elle est désormais gérée côté serveur (navigation par dossier et recherche)
    // initClientSideSearch(); 
    initPrintQueueDashboard();

    // --- Gestionnaire central pour clic et double-clic ---
    function initClickAndDoubleClick() {
        // S'applique aux documents (pas aux dossiers)
        const rows = document.querySelectorAll('.document-row');
        rows.forEach(row => {
            let clickTimer = null;
            row.addEventListener('click', (e) => {
                // Ignore si on clique sur un élément interactif (bouton, case à cocher, etc.)
                if (e.target.closest('button, a, input[type="checkbox"], form')) return;
                
                // Si on clique sur un dossier (dans la vue grille), on ne fait rien
                if (row.classList.contains('folder-row')) return;

                if (clickTimer === null) {
                    // Démarrer un timer. Si rien d'autre ne se passe, c'est un simple clic.
                    clickTimer = setTimeout(() => {
                        clickTimer = null; // Réinitialiser le timer
                        const docId = row.dataset.docId;
                        if (docId) window.openSidebarForDocument(docId);
                    }, 250); // Délai de 250ms pour détecter un double-clic
                } else {
                    // Si un timer existe déjà, c'est un double-clic
                    clearTimeout(clickTimer);
                    clickTimer = null; // Réinitialiser le timer
                    const docId = row.dataset.docId;
                    if (docId) window.openModalForDocument(docId);
                }
            });
        });
    }

    // --- Gestion de la BARRE LATERALE de détails (Droite) ---
    function initDetailsSidebar() {
        const sidebar = document.getElementById('details-sidebar');
        const mainContent = document.getElementById('main-content');
        if (!sidebar || !mainContent) return;

        const title = document.getElementById('sidebar-title');
        const infoList = document.getElementById('sidebar-info-list');
        const attachmentsList = document.getElementById('sidebar-attachments-list');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const attachmentsToggleBtn = document.getElementById('sidebar-attachments-toggle-btn');
        
        window.openSidebarForDocument = async (docId) => {
            if (!docId) return;
            sidebar.classList.add('open');
            mainContent.classList.add('sidebar-open');
            
            if(title) title.textContent = 'Chargement...';
            if(infoList) infoList.innerHTML = '<li>Chargement...</li>';
            if(attachmentsList) attachmentsList.innerHTML = '';

            try {
                const response = await fetch(`/document/details?id=${docId}`);
                if (!response.ok) throw new Error('Document non trouvé.');
                const data = await response.json();
                
                if(title) title.textContent = data.main_document.original_filename;
                if(infoList) infoList.innerHTML = `
                    <li><strong>Taille:</strong> <span>${data.main_document.size_formatted}</span></li>
                    <li><strong>Type:</strong> <span>${data.main_document.mime_type}</span></li>
                    <li><strong>Ajouté le:</strong> <span>${new Date(data.main_document.created_at).toLocaleString('fr-FR')}</span></li>
                    <li><strong>Source:</strong> <span>${data.main_document.source_account_id ? 'E-mail' : 'Manuel'}</span></li>
                 `;

                const attachmentsContainer = document.getElementById('sidebar-attachments');
                if (attachmentsContainer && attachmentsList) {
                    if (data.attachments && data.attachments.length > 0) {
                        attachmentsContainer.style.display = 'block';
                        data.attachments.forEach(att => {
                            attachmentsList.innerHTML += `<li><a href="/document/download?id=${att.id}" target="_blank">${att.original_filename}</a></li>`;
                        });
                    } else {
                        attachmentsContainer.style.display = 'none';
                    }
                }
            } catch (error) {
                if(title) title.textContent = 'Erreur';
                if(infoList) infoList.innerHTML = `<li>${error.message}</li>`;
            }
        };
        
        const closeSidebar = () => {
             sidebar.classList.remove('open');
             mainContent.classList.remove('sidebar-open');
        };

        if (closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }
        if (attachmentsToggleBtn) {
            attachmentsToggleBtn.addEventListener('click', () => {
                attachmentsToggleBtn.classList.toggle('collapsed');
                if (attachmentsList) attachmentsList.classList.toggle('collapsed');
            });
        }
    }

    // --- Gestion de la MODALE d'aperçu ---
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

            if(attachmentsPanel) attachmentsPanel.classList.remove('collapsed');
            if(attachmentsToggleBtn) {
                attachmentsToggleBtn.innerHTML = '‹';
                attachmentsToggleBtn.title = 'Masquer les pièces jointes';
            }
            
            modal.style.display = 'flex';
            if(modalTitle) modalTitle.textContent = 'Chargement...';
            if(attachmentsList) attachmentsList.innerHTML = '<li>Chargement...</li>';
            if(previewIframe) previewIframe.src = 'about:blank';

            try {
                const response = await fetch(`/document/details?id=${docId}`);
                if (!response.ok) throw new Error('Document non trouvé.');
                const data = await response.json();
                
                if(modalTitle) modalTitle.textContent = data.main_document.original_filename;
                if(previewIframe) previewIframe.src = `/document/download?id=${data.main_document.id}`;

                if (attachmentsPanel && attachmentsToggleBtn && attachmentsList) {
                    if (data.attachments && data.attachments.length > 0) {
                        attachmentsPanel.style.display = 'block';
                        attachmentsToggleBtn.style.display = 'block';
                        attachmentsList.innerHTML = '';
                        data.attachments.forEach(attachment => {
                            const li = document.createElement('li');
                            li.innerHTML = `📄 <a href="/document/download?id=${attachment.id}" target="_blank" title="${attachment.original_filename}">${attachment.original_filename}</a>`;
                            attachmentsList.appendChild(li);
                        });
                    } else {
                         attachmentsPanel.style.display = 'none';
                         attachmentsToggleBtn.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
                if(modalTitle) modalTitle.textContent = 'Erreur';
                if(attachmentsList) attachmentsList.innerHTML = `<li>Impossible de charger les informations.</li>`;
            }
        };
        
        const closeModal = () => {
            modal.style.display = 'none';
            if(previewIframe) previewIframe.src = 'about:blank';
        };

        if(closeBtn) closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        if (attachmentsToggleBtn && attachmentsPanel) {
            attachmentsToggleBtn.addEventListener('click', () => {
                attachmentsPanel.classList.toggle('collapsed');
                const isCollapsed = attachmentsPanel.classList.contains('collapsed');
                attachmentsToggleBtn.innerHTML = isCollapsed ? '›' : '‹';
                attachmentsToggleBtn.title = isCollapsed ? 'Afficher les pièces jointes' : 'Masquer les pièces jointes';
            });
        }
    }
    
    // --- MENU CONTEXTUEL ---
    function initContextMenu() {
        const contextMenu = document.getElementById('context-menu');
        if (!contextMenu) return;

        let currentDocId = null;

        document.addEventListener('contextmenu', e => {
            const targetRow = e.target.closest('.document-row, .folder-row');
            if (targetRow) {
                e.preventDefault();
                currentDocId = targetRow.dataset.docId || targetRow.dataset.folderId;
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
            const targetIds = selectedIds.length > 0 ? selectedIds : [currentDocId];

            if (targetIds.length === 0) return;

            switch (action) {
                case 'preview_sidebar':
                    if (targetIds.length === 1) window.openSidebarForDocument(targetIds[0]);
                    break;
                case 'preview_modal':
                    if (targetIds.length === 1) window.openModalForDocument(targetIds[0]);
                    break;
                case 'print':
                    const printForm = document.createElement('form');
                    printForm.method = 'POST';
                    printForm.action = '/document/bulk-print';
                    targetIds.forEach(id => {
                        printForm.innerHTML += `<input type="hidden" name="doc_ids[]" value="${id}">`;
                    });
                    document.body.appendChild(printForm);
                    printForm.submit();
                    break;
                case 'download':
                    if (targetIds.length === 1) {
                        window.open(`/document/download?id=${targetIds[0]}`, '_blank');
                    } else {
                        targetIds.forEach(id => window.open(`/document/download?id=${id}`, '_blank'));
                    }
                    break;
                case 'delete':
                    if (confirm('Confirmer la mise à la corbeille ?')) {
                        const deleteForm = document.createElement('form');
                        deleteForm.method = 'POST';
                        deleteForm.action = '/document/delete';
                        targetIds.forEach(id => {
                            deleteForm.innerHTML += `<input type="hidden" name="doc_ids[]" value="${id}">`;
                        });
                        document.body.appendChild(deleteForm);
                        deleteForm.submit();
                    }
                    break;
                case 'move':
                    alert('Pour déplacer, glissez et déposez les éléments sélectionnés sur un dossier.');
                    console.log('Move items:', targetIds);
                    break;
            }
        });
    }

    // --- SÉLECTION MULTIPLE ---
    function initBulkSelection() {
        const mainCheckbox = document.getElementById("select-all-checkbox");
        const docCheckboxes = document.querySelectorAll(".doc-checkbox");
        const bulkDeleteButton = document.getElementById("bulk-delete-button");
        const bulkPrintButton = document.getElementById("bulk-print-button");

        function updateButtonVisibility() {
            const anyChecked = Array.from(docCheckboxes).some(checkbox => checkbox.checked);
            if(bulkDeleteButton) bulkDeleteButton.style.display = anyChecked ? 'inline-flex' : 'none';
            if(bulkPrintButton) bulkPrintButton.style.display = anyChecked ? 'inline-flex' : 'none';
        }

        if (mainCheckbox) {
            mainCheckbox.addEventListener("change", (event) => {
                docCheckboxes.forEach(checkbox => {
                    // Ne coche que les éléments visibles
                    if (checkbox.closest("tr").style.display !== "none") {
                        checkbox.checked = event.target.checked;
                    }
                });
                updateButtonVisibility();
            });
        }

        docCheckboxes.forEach(checkbox => checkbox.addEventListener("change", updateButtonVisibility));
        
        // Exécuter une première fois au chargement
        updateButtonVisibility();
    }

    // --- CHANGEMENT DE VUE (LISTE/GRILLE) ---
    function initViewSwitcher() {
        const listViewBtn = document.getElementById("list-view-btn");
        const gridViewBtn = document.getElementById("grid-view-btn");
        const listView = document.getElementById("document-list-view");
        const gridView = document.getElementById("document-grid-view");

        if (!listViewBtn || !gridViewBtn || !listView || !gridView) return;

        const currentView = localStorage.getItem("ged_view_mode") || "list";

        function switchView(view) {
            if (view === "grid") {
                listView.style.display = "none";
                gridView.style.display = "grid";
                listViewBtn.classList.remove("active");
                gridViewBtn.classList.add("active");
                localStorage.setItem("ged_view_mode", "grid");
            } else {
                gridView.style.display = "none";
                listView.style.display = "block";
                gridViewBtn.classList.remove("active");
                listViewBtn.classList.add("active");
                localStorage.setItem("ged_view_mode", "list");
            }
        }
        
        listViewBtn.addEventListener("click", () => switchView("list"));
        gridViewBtn.addEventListener("click", () => switchView("grid"));

        switchView(currentView);
    }
    
    // --- GLISSER-DÉPOSER (DRAG & DROP) ---
    function initDragAndDrop() {
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
                        const isFolder = row.classList.contains('folder-row');
                        const type = isFolder ? 'folder' : 'document';
                        const id = isFolder ? row.dataset.folderId : row.dataset.docId;
                        if (id) {
                            draggedItems.push({ type, id });
                        }
                    });
                } else {
                    const isFolder = draggable.classList.contains('folder-row');
                    const type = isFolder ? 'folder' : 'document';
                    const id = isFolder ? draggable.dataset.folderId : draggable.dataset.docId;
                    if (id) {
                        draggedItems.push({ type, id });
                    }
                }

                if (draggedItems.length > 0) {
                    e.dataTransfer.setData('application/json', JSON.stringify(draggedItems));
                    setTimeout(() => {
                        draggedItems.forEach(item => {
                            const el = document.querySelector(`[data-doc-id="${item.id}"], [data-folder-id="${item.id}"]`);
                            if (el) el.classList.add('dragging');
                        });
                    }, 0);
                } else {
                    e.preventDefault();
                }
            });

            draggable.addEventListener('dragend', () => {
                document.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging'));
            });
        });


        dropzones.forEach(zone => {
            zone.addEventListener('dragover', e => {
                e.preventDefault();
                zone.classList.add('drag-over');
            });
            zone.addEventListener('dragleave', e => {
                zone.classList.remove('drag-over');
            });
            zone.addEventListener('drop', async e => {
                e.preventDefault();
                zone.classList.remove('drag-over');

                const items = JSON.parse(e.dataTransfer.getData('application/json'));
                const targetFolderId = zone.dataset.folderId;
                
                if (!items || items.length === 0 || typeof targetFolderId === 'undefined') return;

                const docIds = items.filter(item => item.type === 'document').map(item => item.id);
                const folderIds = items.filter(item => item.type === 'folder').map(item => item.id);

                if (folderIds.includes(targetFolderId)) {
                    showToast('Un dossier ne peut pas être déplacé dans lui-même.', '⚠️');
                    return;
                }

                try {
                    let moved = false;
                    if (docIds.length > 0) {
                        const formData = new FormData();
                        docIds.forEach(id => formData.append('doc_ids[]', id));
                        formData.append('folder_id', targetFolderId);

                        const response = await fetch('/document/move', {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            body: formData
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) throw new Error(result.message || 'Erreur lors du déplacement des documents.');
                        moved = true;
                    }

                    if (folderIds.length > 0) {
                        const formData = new FormData();
                        folderIds.forEach(id => formData.append('folder_ids[]', id));
                        formData.append('target_folder_id', targetFolderId);
                        
                        // Assurez-vous que la route /folder/move existe et gère les tableaux
                        const response = await fetch('/folder/move', {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            body: formData
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) throw new Error(result.message || 'Erreur lors du déplacement des dossiers.');
                        moved = true;
                    }

                    if (moved) {
                        showToast('Déplacement réussi.', '📁');
                        setTimeout(() => window.location.reload(), 1000);
                    }

                } catch (error) {
                    showToast(`Erreur : ${error.message}`, '⚠️');
                }
            });
        });
    }

    // --- NOTIFICATIONS "TOAST" ---
    function showToast(message, icon = 'ℹ️') {
        const container = document.getElementById("toast-container");
        if (!container) return;
        const toast = document.createElement("div");
        toast.className = "toast";
        toast.innerHTML = `<span style="margin-right: 8px;">${icon}</span> ${message}`;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add("show"), 100);
        setTimeout(() => {
            toast.classList.remove("show");
            toast.addEventListener("transitionend", () => toast.remove());
        }, 5000);
    }

    // --- DASHBOARD DE LA FILE D'IMPRESSION ---
    function initPrintQueueDashboard() {
        const queueBody = document.getElementById("print-queue-body");
        const dashboard = document.getElementById("print-queue-dashboard");
        if (!queueBody || !dashboard) return;
    
        async function performAction(url, docId, button, successIcon, originalIcon) {
            button.disabled = true;
            button.textContent = '...';
            const formData = new FormData();
            formData.append('doc_id', docId);
            try {
                const response = await fetch(url, { method: 'POST', body: formData });
                const result = await response.json();
                if (!response.ok) throw new Error(result.error || 'Erreur inconnue');
                showToast(result.message, successIcon);
                updateQueueStatus();
            } catch (error) {
                showToast(`Erreur : ${error.message}`, '⚠️');
                button.disabled = false;
                button.textContent = originalIcon;
            }
        }
    
        async function updateQueueStatus() {
            try {
                const response = await fetch('/print-queue/status');
                if (!response.ok) throw new Error('Impossible de récupérer le statut.');
                const queue = await response.json();
                
                const activeJobs = queue.filter(job => job.status !== 'Terminé');
    
                if (activeJobs.length > 0) {
                    dashboard.style.display = 'block';
                    queueBody.innerHTML = '';
                    activeJobs.forEach(job => {
                        const row = document.createElement('tr');
                        row.dataset.docId = job.id;
                        let statusHtml = '<span>⏳ En attente</span>';
                        let actionsHtml = `<button class="button-icon button-delete btn-cancel-print" title="Annuler">❌</button>`;
    
                        if (job.status === "En cours d'impression") {
                            statusHtml = `<span>🖨️ ${job.status}</span>`;
                        } else if (job.status === 'Erreur') {
                            statusHtml = `<span style="color: var(--danger-color); font-weight: bold;">⚠️ ${job.status}</span>`;
                            actionsHtml = `<button class="button-icon btn-clear-error" title="Effacer">🗑️</button>`;
                        }
    
                        row.innerHTML = `
                            <td>${job.filename || 'N/A'}</td>
                            <td>${job.job_id || 'N/A'}</td>
                            <td>${statusHtml}</td>
                            <td>${job.error || 'Aucun détail'}</td>
                            <td>${actionsHtml}</td>
                        `;
                        queueBody.appendChild(row);
                    });
    
                    queueBody.querySelectorAll('.btn-cancel-print').forEach(btn => {
                        btn.addEventListener('click', e => performAction('/document/cancel-print', e.currentTarget.closest('tr').dataset.docId, e.currentTarget, '🗑️', '❌'));
                    });
                    queueBody.querySelectorAll('.btn-clear-error').forEach(btn => {
                        btn.addEventListener('click', e => performAction('/document/clear-print-error', e.currentTarget.closest('tr').dataset.docId, e.currentTarget, '✨', '🗑️'));
                    });
                } else {
                    dashboard.style.display = 'none';
                }
            } catch (error) {
                dashboard.style.display = 'block';
                queueBody.innerHTML = `<tr><td colspan="5" class="empty-state" style="color: var(--danger-color);">${error.message}</td></tr>`;
            }
        }
    
        // --- GESTION WEBSOCKET ---
        function connectWebSocket() {
            const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws';
            const wsUrl = `${protocol}://${window.location.host}/ws`;
            
            try {
                const ws = new WebSocket(wsUrl);
                ws.onopen = () => console.log('WebSocket connecté.');
                ws.onmessage = (event) => {
                    try {
                        const message = JSON.parse(event.data);
                        if (['new_document', 'document_deleted', 'print_cancelled', 'print_error_cleared'].includes(message.action)) {
                            showToast('Mise à jour du serveur reçue, actualisation...', '🔄');
                            setTimeout(() => window.location.reload(), 1500);
                        } else if (message.action === 'print_sent') {
                            showToast(message.data.message, '🖨️');
                            updateQueueStatus();
                            const statusDot = document.querySelector(`[data-doc-id="${message.data.doc_id}"] .status-dot`);
                            if (statusDot) {
                                statusDot.style.backgroundColor = '#ffc107';
                                statusDot.title = 'À imprimer';
                            }
                        }
                    } catch (e) {
                        console.error('Erreur parsing WebSocket', e);
                    }
                };
                ws.onclose = () => {
                    console.log('WebSocket déconnecté, reconnexion...');
                    setTimeout(connectWebSocket, 5000);
                };
                ws.onerror = (error) => {
                    console.error('Erreur WebSocket:', error);
                    ws.close();
                };
            } catch(e) {
                console.error('Impossible de créer la connexion WebSocket.', e);
            }
        }
    
        // Lancement initial
        updateQueueStatus();
        setInterval(updateQueueStatus, 7000); // Mise à jour régulière
        connectWebSocket();
    }
});
