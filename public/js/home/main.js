import * as ViewSwitcher from './viewSwitcher.js';
import * as Uploader from './upload.js';
import * as Sidebar from './sidebar.js';
import * as Dnd from './dnd.js';
import * as ContextMenu from './contextMenu.js';
import * as Selection from './selection.js';
import * as Filters from './filters.js';
import { setupDocumentModal, setupFolderModals, setupRenameModal } from './modal.js';
import { initPrintQueue } from './printQueue.js';
import { connectWebSocket } from './websocket.js';
import { showToast, formatBytes, getFileExtension, getMimeTypeIcon } from '../common/utils.js';

GED.App = {
    currentFolderId: null,
    currentView: 'list',

    init() {
        this.currentFolderId = new URLSearchParams(window.location.search).get('folder_id');

        ViewSwitcher.init(this.fetchAndDisplayDocuments.bind(this));
        Uploader.init(this.currentFolderId, this.fetchAndDisplayDocuments.bind(this));
        Sidebar.init();
        
        Dnd.initializeDnd(this.currentFolderId, this.fetchAndDisplayDocuments.bind(this));
        
        ContextMenu.init(this.fetchAndDisplayDocuments.bind(this));
        Selection.init();
        Filters.init(this.fetchAndDisplayDocuments.bind(this));
        setupDocumentModal();
        setupFolderModals(this.fetchAndDisplayDocuments.bind(this));
        setupRenameModal(this.fetchAndDisplayDocuments.bind(this));
        initPrintQueue();
        connectWebSocket();

        this.fetchAndDisplayDocuments();

        const addBtn = document.getElementById('add-btn');
        const addDropdown = document.getElementById('add-dropdown');
        if (addBtn && addDropdown) {
            addBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                addDropdown.classList.toggle('show');
            });
        }
        
        window.addEventListener('click', (event) => {
            if (addDropdown && !addDropdown.contains(event.target)) {
                addDropdown.classList.remove('show');
            }
        });
    },

    async fetchAndDisplayDocuments() {
        try {
            const params = Filters.getFilterParams();
            if (this.currentFolderId) {
                params.set('folder_id', this.currentFolderId);
            }
            
            const response = await fetch(`/api/documents?${params.toString()}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => null);
                const errorMessage = errorData?.error?.message || 'Erreur lors de la récupération des documents.';
                throw new Error(errorMessage);
            }
            const data = await response.json();
            
            this.currentView = ViewSwitcher.getCurrentView();
            const container = document.getElementById('documents-container');

            if (this.currentView === 'grid') {
                container.innerHTML = this.renderGridView(data.documents);
                Dnd.attachGridDragEvents();
            } else {
                container.innerHTML = this.renderListView(data.documents);
            }
            
            this.attachEventListeners();
            Selection.updateBulkActionsVisibility();

        } catch (error) {
            console.error(error);
            showToast(error.message, 'error');
        }
    },

    renderListView(documents) {
        if (!documents || documents.length === 0) {
            return '<div class="empty-state"><i class="fas fa-folder-open"></i><p>Ce dossier est vide.</p></div>';
        }
    
        const tableHeader = `
            <table class="table documents-table">
                <thead>
                    <tr>
                        <th class="col-checkbox"><input type="checkbox" id="select-all-checkbox"></th>
                        <th class="col-icon"></th>
                        <th>Nom</th>
                        <th class="col-size">Taille</th>
                        <th class="col-date">Dernière modification</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
    
        const tableBody = documents.map(doc => {
            const isFolder = doc.type === 'folder';
            const icon = isFolder 
                ? '<i class="fas fa-folder folder-icon-color"></i>' 
                : getMimeTypeIcon(doc.filename);
            const size = isFolder ? '—' : (doc.size ? formatBytes(doc.size) : 'N/A');
            const date = new Date(doc.updated_at).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            
            const downloadButton = !isFolder
                ? `<button class="button-icon" data-action="download" data-id="${doc.id}" title="Télécharger"><i class="fas fa-download"></i></button>`
                : '';

            return `
                <tr class="${isFolder ? 'folder-row' : 'document-row'}" data-id="${doc.id}" data-type="${doc.type}" data-name="${doc.name || doc.filename}" draggable="${!isFolder}">
                    <td class="col-checkbox"><input type="checkbox" class="row-checkbox" data-id="${doc.id}"></td>
                    <td class="col-icon">${icon}</td>
                    <td>
                        <a href="${isFolder ? `/?folder_id=${doc.id}` : '#'}" class="folder-link" data-id="${doc.id}" data-type="${doc.type}">
                           ${doc.name || doc.filename}
                        </a>
                    </td>
                    <td class="col-size">${size}</td>
                    <td class="col-date">${date}</td>
                    <td class="col-actions">
                        <div class="document-actions">
                           ${downloadButton}
                           <button class="button-icon" data-action="delete" data-id="${doc.id}" data-type="${doc.type}" title="Supprimer"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    
        const tableFooter = '</tbody></table>';
        return tableHeader + tableBody + tableFooter;
    },

    renderGridView(documents) {
        if (!documents || documents.length === 0) {
            return '<div class="empty-state"><i class="fas fa-folder-open"></i><p>Ce dossier est vide.</p></div>';
        }

        const items = documents.map(doc => {
            const isFolder = doc.type === 'folder';
            const icon = isFolder 
                ? '<i class="fas fa-folder folder-icon-color"></i>'
                : getMimeTypeIcon(doc.filename);
            
            return `
                <div class="grid-item" data-id="${doc.id}" data-type="${doc.type}" data-name="${doc.name || doc.filename}" draggable="${!isFolder}">
                    <div class="grid-item-thumbnail">
                        ${icon}
                    </div>
                    <div class="grid-item-name">${doc.name || doc.filename}</div>
                </div>
            `;
        }).join('');
        
        return `<div class="grid-view-container">${items}</div>`;
    },

    attachEventListeners() {
        const container = document.getElementById('documents-container');

        container.addEventListener('click', (e) => {
            const target = e.target;
            const row = target.closest('.document-row, .grid-item');
            const folderLink = target.closest('.folder-link, .folder-row, .grid-item[data-type="folder"]');
            
            if (folderLink && !target.closest('.button-icon, .row-checkbox')) {
                e.preventDefault();
                const folderId = folderLink.dataset.id;
                window.location.href = `/?folder_id=${folderId}`;
                return;
            }

            if (row && !target.closest('.button-icon, .row-checkbox, a')) {
                const docId = row.dataset.id;
                const docType = row.dataset.type;
                if (docType !== 'folder') {
                    Sidebar.openDetails(docId);
                }
            }

            const actionButton = target.closest('button[data-action]');
            if(actionButton) {
                const action = actionButton.dataset.action;
                const id = actionButton.dataset.id;
                const type = actionButton.dataset.type; // Pass type for deletion
                if (action === 'delete') {
                    ContextMenu.deleteItem(id, type);
                } else if (action === 'download') {
                    ContextMenu.downloadItem(id);
                }
            }
        });
        
        container.addEventListener('contextmenu', (e) => {
            const item = e.target.closest('.document-row, .grid-item, .folder-row');
            if (item) {
                e.preventDefault();
                ContextMenu.show(e, item.dataset.id, item.dataset.type, item.dataset.name);
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    // Ensure the global GED object exists before trying to attach the App to it.
    window.GED = window.GED || {};
    GED.App.init();
});
