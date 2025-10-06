// public/js/home/main.js

GED.home = GED.home || {};

GED.home.main = {
    init() {
        // Initialise tous les modules de la page d'accueil
        GED.home.sidebar.init();
        GED.home.modal.init();
        GED.home.contextMenu.init();
        GED.home.selection.init();
        GED.home.viewSwitcher.init();
        GED.home.dnd.init();
        GED.home.printQueue.init();
        GED.home.websocket.init();
        GED.home.upload.init();

        // Gère les événements globaux
        this.initGlobalEvents();

        // Gère le clic sur le bouton "+" du fil d'Ariane
        document.getElementById('breadcrumb-add-folder')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.createFolder();
        });
    },

    initGlobalEvents() {
        document.getElementById('main-content').addEventListener('click', (e) => {
            const row = e.target.closest('.document-row:not(.folder-row)');
            if (!row || e.target.closest('button, a, input[type="checkbox"], form')) {
                return;
            }

            if (row.clickTimer) {
                clearTimeout(row.clickTimer);
                row.clickTimer = null;
                const docId = row.dataset.docId;
                if (docId) GED.home.modal.openForDocument(docId);
            } else {
                row.clickTimer = setTimeout(() => {
                    row.clickTimer = null;
                    const docId = row.dataset.docId;
                    if (docId) GED.home.sidebar.openForDocument(docId);
                }, 250);
            }
        });
    },
    
    async createFolder() {
        const folderName = prompt("Entrez le nom du nouveau dossier :");
        if (!folderName || !folderName.trim()) {
            return;
        }

        const formData = new FormData();
        formData.append('folder_name', folderName.trim());

        const urlParams = new URLSearchParams(window.location.search);
        const parentId = urlParams.get('folder_id') || 'root';
        formData.append('parent_id', parentId);

        try {
            const response = await fetch('/folder/create', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Erreur du serveur.');
            }

            GED.utils.showToast(`Dossier "${result.folder.name}" créé.`, '✅');
            this.addFolderToView(result.folder);

        } catch (error) {
            GED.utils.showToast(`Erreur : ${error.message}`, '⚠️');
        }
    },
    
    addFolderToView(folder) {
        document.querySelector('.empty-state')?.remove();

        // 1. Ajouter à la vue principale (liste)
        const tableBody = document.querySelector('.documents-table tbody');
        if (tableBody) {
            const newRow = document.createElement('tr');
            newRow.className = 'folder-row dropzone';
            newRow.dataset.folderId = folder.id;
            newRow.setAttribute('draggable', 'true');
            newRow.innerHTML = `
                <td class="col-checkbox"></td>
                <td class="col-icon"><i class="fas fa-folder folder-icon-color"></i></td>
                <td><a href="/?folder_id=${folder.id}" class="folder-link">${folder.name}</a></td>
                <td></td>
                <td class="col-size">--</td>
                <td class="col-date"></td>
                <td class="col-actions"></td>
            `;
            tableBody.prepend(newRow);
        }

        // 2. Ajouter à la vue principale (grille)
        const gridView = document.getElementById('document-grid-view');
        if (gridView) {
            const newGridItem = document.createElement('a');
            newGridItem.href = `/?folder_id=${folder.id}`;
            newGridItem.className = 'grid-item folder-row dropzone';
            newGridItem.dataset.folderId = folder.id;
            newGridItem.setAttribute('draggable', 'true');
            newGridItem.innerHTML = `
                <div class="grid-item-thumbnail"><i class="fas fa-folder folder-icon-color"></i></div>
                <div class="grid-item-name" title="${folder.name}">${folder.name}</div>
            `;
            gridView.prepend(newGridItem);
        }

        // 3. Ajouter à l'arborescence de la sidebar
        const folderTree = document.querySelector('.folder-tree');
        const parentLink = folder.parent_id 
            ? folderTree.querySelector(`a[href="/?folder_id=${folder.parent_id}"]`)
            : null;
        
        let targetUl = parentLink ? parentLink.closest('li').querySelector('ul') : folderTree.querySelector(':scope > ul');

        if (!targetUl) {
            if (parentLink) {
                 targetUl = document.createElement('ul');
                 parentLink.closest('li').append(targetUl);
            } else {
                targetUl = folderTree.querySelector('ul');
            }
        }
        
        const newLi = document.createElement('li');
        newLi.innerHTML = `<a href="/?folder_id=${folder.id}" class="dropzone" data-folder-id="${folder.id}"><i class="fas fa-folder"></i> ${folder.name}</a>`;
        if (targetUl) {
            targetUl.prepend(newLi);
        }

        GED.home.dnd.init();
    },

    addDocumentToView(doc) {
        document.querySelector('.empty-state')?.remove();

        const docName = doc.original_filename || doc.name;

        // Vue Liste
        const tableBody = document.querySelector('.documents-table tbody');
        if (tableBody) {
            const newRow = document.createElement('tr');
            newRow.className = 'document-row';
            newRow.dataset.docId = doc.id;
            newRow.setAttribute('draggable', 'true');
            
            const statusMap = { 'received': { color: '#007bff', label: 'Reçu' }};
            const statusInfo = statusMap[doc.status] || { color: '#6c757d', label: 'Inconnu' };
            const date = new Date(doc.created_at).toLocaleString('fr-FR');
            
            newRow.innerHTML = `
                <td class="col-checkbox"><input type="checkbox" name="doc_ids[]" value="${doc.id}" class="doc-checkbox" form="bulk-action-form"></td>
                <td class="col-icon"><i class="fas ${GED.home.sidebar.getFileIconClass(doc.mime_type)}"></i></td>
                <td class="col-name"><span class="status-dot" style="background-color: ${statusInfo.color};" title="${statusInfo.label}"></span><strong>${docName}</strong></td>
                <td>${doc.source_details || 'Manuel'}</td>
                <td class="col-size">${doc.size_formatted}</td>
                <td class="col-date">${date.split(' ')[0]}</td>
                <td class="col-actions">
                    <div class="document-actions">
                        <form action="/document/print" method="POST" class="action-form"><input type="hidden" name="doc_id" value="${doc.id}"><button type="submit" class="button-icon" title="Imprimer"><i class="fas fa-print"></i></button></form>
                        <form action="/document/delete" method="POST" class="action-form" onsubmit="return confirm('Confirmer ?');"><input type="hidden" name="doc_ids[]" value="${doc.id}"><button type="submit" class="button-icon button-delete" title="Corbeille"><i class="fas fa-trash"></i></button></form>
                    </div>
                </td>
            `;
            tableBody.prepend(newRow);
        }
        
        // Vue Grille
        const gridView = document.getElementById('document-grid-view');
        if (gridView) {
            const newGridItem = document.createElement('div');
            newGridItem.className = 'grid-item document-row';
            newGridItem.dataset.docId = doc.id;
            newGridItem.setAttribute('draggable', 'true');
            
            newGridItem.innerHTML = `
                <div class="grid-item-thumbnail"><i class="fas ${GED.home.sidebar.getFileIconClass(doc.mime_type)}"></i></div>
                <div class="grid-item-name" title="${docName}">${docName}</div>
            `;
            gridView.prepend(newGridItem);
        }
        
        GED.home.dnd.init();
        GED.home.selection.init();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    GED.home.main.init();
});
