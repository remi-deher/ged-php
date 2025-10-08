// public/js/home/contextMenu.js

import { showToast } from '../common/utils.js';

let contextMenu = null;

export function initializeContextMenu() {
    contextMenu = document.getElementById('context-menu');
    if (!contextMenu) return;

    document.addEventListener('click', () => {
        if (contextMenu.style.display === 'block') {
            contextMenu.style.display = 'none';
        }
    });
}

export function showContextMenu(event, element) {
    event.preventDefault();

    const isFolder = element.dataset.type === 'folder';
    
    // Afficher/masquer les options selon le type
    document.getElementById('ctx-open').style.display = isFolder ? 'block' : 'none';
    document.getElementById('ctx-preview').style.display = isFolder ? 'none' : 'block';
    document.getElementById('ctx-download').style.display = isFolder ? 'none' : 'block';

    contextMenu.style.display = 'block';
    contextMenu.style.left = `${event.pageX}px`;
    contextMenu.style.top = `${event.pageY}px`;

    // Attacher les actions
    document.getElementById('ctx-rename').onclick = () => renameItem(element.dataset.id, element.dataset.type);
    document.getElementById('ctx-delete').onclick = () => deleteItem(element.dataset.id, element.dataset.type);
    
    if (isFolder) {
        document.getElementById('ctx-open').onclick = () => {
            window.location.href = `/?folder_id=${element.dataset.id}`;
        };
    } else {
        document.getElementById('ctx-preview').onclick = () => {
             // Simule un clic gauche pour ouvrir le modal de prévisualisation
            element.click();
        };
        document.getElementById('ctx-download').onclick = () => {
            window.location.href = `/download?id=${element.dataset.id}`;
        };
    }
}

function renameItem(id, type) {
    const renameModal = document.getElementById('rename-modal');
    renameModal.style.display = 'block';
    document.getElementById('rename-id-input').value = id;
    document.getElementById('rename-type-input').value = type;
    const currentName = document.querySelector(`[data-id='${id}'] .item-name`).textContent;
    document.getElementById('new-name-input').value = currentName;
    document.getElementById('new-name-input').focus();
    document.getElementById('new-name-input').select();
}

function deleteItem(id, type) {
    const itemName = document.querySelector(`[data-id='${id}'] .item-name`).textContent;
    if (confirm(`Êtes-vous sûr de vouloir supprimer "${itemName}" ?`)) {
        fetch('/api/delete-item', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, type: type })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Élément déplacé à la corbeille.');
                document.querySelector(`[data-id='${id}']`).remove();
            } else {
                showToast(data.message, 'error');
            }
        });
    }
}
