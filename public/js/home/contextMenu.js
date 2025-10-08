// public/js/home/contextMenu.js

import { showToast } from '../common/utils.js';

let contextMenu;
let currentItemId;
let currentItemType;
let currentItemName;
let onRefresh;

export function init(refreshCallback) {
    onRefresh = refreshCallback;
    contextMenu = document.getElementById('context-menu');
    
    document.addEventListener('click', () => {
        if (contextMenu && contextMenu.style.display === 'block') {
            contextMenu.style.display = 'none';
        }
    });

    // Initial event listeners for menu items can be set up here if they are static.
    // Otherwise, they are set up in `show` based on the item clicked.
}

export function show(event, id, type, name) {
    event.preventDefault();

    currentItemId = id;
    currentItemType = type;
    currentItemName = name;

    const isFolder = currentItemType === 'folder';

    contextMenu.innerHTML = `
        <li id="ctx-open" style="display: ${isFolder ? 'block' : 'none'}"><i class="fas fa-folder-open"></i> Ouvrir</li>
        <li id="ctx-preview" style="display: ${!isFolder ? 'block' : 'none'}"><i class="fas fa-eye"></i> Aperçu</li>
        <li id="ctx-rename"><i class="fas fa-edit"></i> Renommer</li>
        <li id="ctx-download" style="display: ${!isFolder ? 'block' : 'none'}"><i class="fas fa-download"></i> Télécharger</li>
        <li id="ctx-delete"><i class="fas fa-trash"></i> Supprimer</li>
    `;

    contextMenu.style.display = 'block';
    contextMenu.style.left = `${event.pageX}px`;
    contextMenu.style.top = `${event.pageY}px`;

    // Add event listeners
    document.getElementById('ctx-rename').addEventListener('click', () => renameItem(currentItemId, currentItemType, currentItemName));
    document.getElementById('ctx-delete').addEventListener('click', () => deleteItem(currentItemId, currentItemType));
    
    if (isFolder) {
        document.getElementById('ctx-open').addEventListener('click', () => {
            window.location.href = `/?folder_id=${currentItemId}`;
        });
    } else {
        document.getElementById('ctx-preview').addEventListener('click', () => {
             const itemElement = document.querySelector(`[data-id='${currentItemId}']`);
             if(itemElement) itemElement.click();
        });
        document.getElementById('ctx-download').addEventListener('click', () => downloadItem(currentItemId));
    }
}

export function renameItem(id, type, currentName) {
    const renameModal = document.getElementById('rename-modal');
    if(renameModal) {
        renameModal.style.display = 'block';
        document.getElementById('rename-id-input').value = id;
        document.getElementById('rename-type-input').value = type;
        const newNameInput = document.getElementById('new-name-input');
        newNameInput.value = currentName;
        newNameInput.focus();
        newNameInput.select();
    }
}

export function deleteItem(id, type) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer cet élément ?`)) {
        fetch('/api/delete-item', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, type: type })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Élément déplacé dans la corbeille.', 'success');
                if (onRefresh) onRefresh();
            } else {
                showToast(data.message || 'Erreur lors de la suppression.', 'error');
            }
        })
        .catch(() => showToast('Une erreur réseau est survenue.', 'error'));
    }
}

export function downloadItem(id) {
    window.location.href = `/download?id=${id}`;
}
