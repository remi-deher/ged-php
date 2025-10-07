// public/js/home/contextMenu.js

import { showToast } from '../utils.js';

let contextMenu;
let refreshCallback;

export function init(refreshDocumentsCallback) {
    refreshCallback = refreshDocumentsCallback;
    contextMenu = document.getElementById('context-menu');
    if (!contextMenu) return;

    // Cache le menu si on clique n'importe où sur la page
    document.addEventListener('click', () => {
        if (contextMenu.style.display === 'block') {
            contextMenu.style.display = 'none';
        }
    });

    // Gère le clic sur une action du menu
    contextMenu.addEventListener('click', (e) => {
        const action = e.target.closest('li')?.dataset.action;
        if (action) {
            const id = contextMenu.dataset.id;
            const type = contextMenu.dataset.type;
            const name = contextMenu.dataset.name;

            switch (action) {
                case 'open':
                    if (type === 'folder') {
                        window.location.href = `/?folder_id=${id}`;
                    } else {
                        GED.App.attachItemEventListeners(); // Simule un clic pour ouvrir les détails
                    }
                    break;
                case 'rename':
                    document.dispatchEvent(new CustomEvent('openRenameModal', { detail: { id, type, name } }));
                    break;
                case 'download':
                    downloadItem(id);
                    break;
                case 'delete':
                    deleteItem(id, type);
                    break;
            }
        }
    });
}

export function show(event, id, type, name) {
    if (!contextMenu) return;

    contextMenu.dataset.id = id;
    contextMenu.dataset.type = type;
    contextMenu.dataset.name = name;

    const isFolder = type === 'folder';

    contextMenu.innerHTML = `
        <li data-action="open"><i class="fas fa-eye"></i> Ouvrir</li>
        <li data-action="rename"><i class="fas fa-pencil-alt"></i> Renommer</li>
        ${!isFolder ? `<li data-action="download"><i class="fas fa-download"></i> Télécharger</li>` : ''}
        <li class="separator"></li>
        <li data-action="delete"><i class="fas fa-trash"></i> Supprimer</li>
    `;

    contextMenu.style.display = 'block';
    contextMenu.style.left = `${event.pageX}px`;
    contextMenu.style.top = `${event.pageY}px`;
}

export function downloadItem(docId) {
    window.location.href = `/document/download?id=${docId}`;
}

export async function deleteItem(id, type) {
    const itemType = type === 'folder' ? 'dossier' : 'document';
    if (!confirm(`Êtes-vous sûr de vouloir supprimer ce ${itemType} ?`)) {
        return;
    }

    try {
        const response = await fetch('/api/item/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id, type })
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Erreur du serveur.');
        }

        showToast(`${itemType.charAt(0).toUpperCase() + itemType.slice(1)} supprimé.`, 'success');
        if (refreshCallback) {
            refreshCallback();
        }

    } catch (error) {
        showToast(`Erreur : ${error.message}`, 'error');
    }
}
