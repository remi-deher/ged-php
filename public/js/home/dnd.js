// public/js/home/dnd.js

import { showToast } from '../common/utils.js';

let currentFolderId;
let refreshCallback;

export function initializeDnd(folderId, refreshDocumentsCallback) {
    currentFolderId = folderId;
    refreshCallback = refreshDocumentsCallback;
    setupGlobalDragEvents();
}

function setupGlobalDragEvents() {
    const dropOverlay = document.getElementById('drop-overlay');
    if (!dropOverlay) return;

    let dragCounter = 0;

    window.addEventListener('dragenter', (e) => {
        e.preventDefault();
        const hasFiles = e.dataTransfer.types.includes('Files');
        if (hasFiles) {
            dragCounter++;
            dropOverlay.classList.add('visible');
        }
    }, false);

    window.addEventListener('dragleave', (e) => {
        e.preventDefault();
        const hasFiles = e.dataTransfer.types.includes('Files');
        if (hasFiles) {
            dragCounter--;
            if (dragCounter === 0) {
                dropOverlay.classList.remove('visible');
            }
        }
    }, false);

    window.addEventListener('dragover', (e) => e.preventDefault(), false);
    
    window.addEventListener('drop', (e) => {
        e.preventDefault();
        dragCounter = 0;
        dropOverlay.classList.remove('visible');

        if (e.dataTransfer.files.length > 0) {
            handleDrop(e.dataTransfer.files, currentFolderId);
        }
    }, false);
}

export function attachGridDragEvents() {
    const items = document.querySelectorAll('.grid-item[draggable="true"]');
    items.forEach(item => {
        item.addEventListener('dragstart', handleDragStart, false);
    });

    const dropzones = document.querySelectorAll('.grid-item[data-type="folder"]');
    dropzones.forEach(zone => {
        zone.addEventListener('dragover', handleDragOver, false);
        zone.addEventListener('dragleave', handleDragLeave, false);
        zone.addEventListener('drop', handleItemDrop, false);
    });
}

function handleDragStart(e) {
    e.dataTransfer.setData('text/plain', e.target.dataset.id);
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragOver(e) {
    e.preventDefault();
    this.classList.add('drag-over');
    return false;
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

async function handleItemDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    this.classList.remove('drag-over');

    const docId = e.dataTransfer.getData('text/plain');
    const folderId = this.dataset.id;

    if (docId && folderId) {
        await moveItem(docId, folderId);
    }
}

async function handleDrop(files, folderId) {
    const uploader = document.querySelector('#file-input');
    if (uploader) {
        uploader.files = files;
        uploader.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

async function moveItem(itemId, targetFolderId) {
    try {
        const response = await fetch('/api/item/move', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ itemId, targetFolderId })
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Erreur lors du déplacement.');
        }

        showToast('Document déplacé avec succès.', 'success');
        if (refreshCallback) {
            refreshCallback();
        }
    } catch (error) {
        showToast(`Erreur : ${error.message}`, 'error');
    }
}
