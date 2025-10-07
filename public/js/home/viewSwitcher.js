// public/js/home/viewSwitcher.js

let currentView = 'list';
let refreshCallback;

export function init(refreshDocumentsCallback) {
    refreshCallback = refreshDocumentsCallback;
    const listViewBtn = document.getElementById('list-view-btn');
    const gridViewBtn = document.getElementById('grid-view-btn');

    if (listViewBtn && gridViewBtn) {
        listViewBtn.addEventListener('click', () => switchView('list'));
        gridViewBtn.addEventListener('click', () => switchView('grid'));
    }
}

function switchView(view) {
    currentView = view;
    const listViewBtn = document.getElementById('list-view-btn');
    const gridViewBtn = document.getElementById('grid-view-btn');

    if (view === 'grid') {
        gridViewBtn.classList.add('active');
        listViewBtn.classList.remove('active');
    } else {
        listViewBtn.classList.add('active');
        gridViewBtn.classList.remove('active');
    }
    if (refreshCallback) {
        refreshCallback();
    }
}

export function getCurrentView() {
    return currentView;
}

// Ces fonctions de rendu sont déplacées de main.js pour centraliser la logique de vue
export function render(documents, app) {
    if (currentView === 'grid') {
        return renderGridView(documents, app);
    }
    return renderListView(documents, app);
}

function renderListView(documents, app) {
    if (!documents || documents.length === 0) {
        return '<div class="empty-state" style="text-align: center; padding: 4rem; color: #6c757d;"><i class="fas fa-folder-open" style="font-size: 3rem;"></i><p>Ce dossier est vide.</p></div>';
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
        const icon = isFolder ? '<i class="fas fa-folder" style="color: var(--primary-color);"></i>' : app.getFileIcon(doc.filename);
        const size = isFolder ? '—' : (doc.size ? app.formatBytes(doc.size) : 'N/A');
        const date = new Date(doc.updated_at).toLocaleDateString('fr-FR');
        
        return `
            <tr class="${isFolder ? 'folder-row' : 'document-row'}" data-id="${doc.id}" data-type="${doc.type}" data-name="${doc.name || doc.filename}" draggable="true">
                <td class="col-checkbox"><input type="checkbox" class="row-checkbox" data-id="${doc.id}"></td>
                <td class="col-icon">${icon}</td>
                <td>${doc.name || doc.filename}</td>
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
    return tableHeader + tableBody + '</tbody></table>';
}

function renderGridView(documents, app) {
    if (!documents || documents.length === 0) {
        return '<div class="empty-state" style="text-align: center; padding: 4rem; color: #6c757d;"><i class="fas fa-folder-open" style="font-size: 3rem;"></i><p>Ce dossier est vide.</p></div>';
    }
    const items = documents.map(doc => {
        const isFolder = doc.type === 'folder';
        const icon = isFolder ? '<i class="fas fa-folder" style="color: var(--primary-color);"></i>' : app.getFileIcon(doc.filename);
        return `
            <div class="grid-item" data-id="${doc.id}" data-type="${doc.type}" data-name="${doc.name || doc.filename}" draggable="true">
                <div class="grid-item-thumbnail">${icon}</div>
                <div class="grid-item-name">${doc.name || doc.filename}</div>
            </div>
        `;
    }).join('');
    return `<div class="grid-view-container">${items}</div>`;
}
