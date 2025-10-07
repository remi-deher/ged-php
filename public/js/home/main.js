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
import { showToast } from '../utils.js';

GED.App = {
    currentFolderId: null,
    currentView: 'list',

    init() {
        this.currentFolderId = new URLSearchParams(window.location.search).get('folder_id');

        ViewSwitcher.init(this.fetchAndDisplayDocuments.bind(this));
        Uploader.init(this.currentFolderId, this.fetchAndDisplayDocuments.bind(this));
        Sidebar.init();
        
        // CORRECTION ICI : Remplacement de Dnd.init par Dnd.initializeDnd
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

        // Gestion du dropdown "Ajouter"
        const addBtn = document.getElementById('add-btn');
        const addDropdown = document.getElementById('add-dropdown');
        if (addBtn && addDropdown) {
            addBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                addDropdown.classList.toggle('show');
            });
        }
        
        // Fermer le dropdown si on clique ailleurs
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
                throw new Error('Erreur lors de la récupération des documents.');
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
            showToast('Impossible de charger les documents.', 'error');
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
                : this.getFileIcon(doc.filename);
            const size = isFolder ? '—' : (doc.size ? this.formatBytes(doc.size) : 'N/A');
            const date = new Date(doc.updated_at).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            
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
                           <button class="button-icon" data-action="download" data-id="${doc.id}" title="Télécharger"><i class="fas fa-download"></i></button>
                           <button class="button-icon" data-action="delete" data-id="${doc.id}" title="Supprimer"><i class="fas fa-trash"></i></button>
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
                : this.getFileIcon(doc.filename);
            
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
                Sidebar.openDetails(docId);
            }

            const actionButton = target.closest('button[data-action]');
            if(actionButton) {
                const action = actionButton.dataset.action;
                const id = actionButton.dataset.id;
                if (action === 'delete') {
                    ContextMenu.deleteItem(id);
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
    },

    getFileIcon(filename) {
        const extension = filename.split('.').pop().toLowerCase();
        switch (extension) {
            case 'pdf': return '<i class="fas fa-file-pdf" style="color: #D32F2F;"></i>';
            case 'doc':
            case 'docx': return '<i class="fas fa-file-word" style="color: #1976D2;"></i>';
            case 'xls':
            case 'xlsx': return '<i class="fas fa-file-excel" style="color: #388E3C;"></i>';
            case 'png':
            case 'jpg':
            case 'jpeg':
            case 'gif': return '<i class="fas fa-file-image" style="color: #FBC02D;"></i>';
            case 'eml':
            case 'msg': return '<i class="fas fa-envelope" style="color: #00796B;"></i>';
            default: return '<i class="fas fa-file-alt" style="color: #757575;"></i>';
        }
    },

    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
};

document.addEventListener('DOMContentLoaded', () => {
    GED.App.init();
});
