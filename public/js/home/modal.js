// public/js/home/modal.js
import { showToast } from '../utils.js';

let refreshCallback;

export function setupDocumentModal() {
    const modal = document.getElementById('document-modal');
    if (!modal) return;
    
    modal.querySelector('#modal-close-btn').addEventListener('click', () => modal.style.display = 'none');
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    document.addEventListener('openDocumentModal', (e) => openDocumentModal(e.detail.docId));
}

export function setupFolderModals(refreshDocumentsCallback) {
    refreshCallback = refreshDocumentsCallback;
    const createModal = document.getElementById('create-folder-modal');
    if (createModal) {
        document.getElementById('create-folder-btn').addEventListener('click', () => createModal.style.display = 'flex');
        createModal.querySelector('[data-dismiss="create-folder-modal"]').addEventListener('click', () => createModal.style.display = 'none');
        createModal.addEventListener('click', e => { if(e.target === createModal) createModal.style.display = 'none'; });

        document.getElementById('create-folder-form').addEventListener('submit', handleCreateFolder);
    }
}

export function setupRenameModal(refreshDocumentsCallback) {
    refreshCallback = refreshDocumentsCallback;
    const renameModal = document.getElementById('rename-modal');
    if (renameModal) {
        renameModal.querySelector('[data-dismiss="rename-modal"]').addEventListener('click', () => renameModal.style.display = 'none');
        renameModal.addEventListener('click', e => { if(e.target === renameModal) renameModal.style.display = 'none'; });

        document.addEventListener('openRenameModal', (e) => {
            document.getElementById('rename-id').value = e.detail.id;
            document.getElementById('rename-type').value = e.detail.type;
            document.getElementById('new-name').value = e.detail.name;
            renameModal.style.display = 'flex';
        });

        document.getElementById('rename-form').addEventListener('submit', handleRename);
    }
}

async function openDocumentModal(docId) {
    const modal = document.getElementById('document-modal');
    const title = document.getElementById('modal-document-title');
    const iframe = document.getElementById('modal-preview-iframe');
    
    title.textContent = 'Chargement...';
    iframe.src = 'about:blank';
    modal.style.display = 'flex';
    
    try {
        const response = await fetch(`/api/document/details?id=${docId}`);
        if (!response.ok) throw new Error('Document non trouvé');
        const data = await response.json();
        
        title.textContent = data.document.filename || 'Prévisualisation';
        iframe.src = `/document/preview?id=${docId}`;
        
        // Gérer les pièces jointes
        const attachmentList = document.getElementById('modal-attachments-list');
        const attachmentCount = document.getElementById('modal-attachment-count');
        attachmentList.innerHTML = '';
        if (data.attachments && data.attachments.length > 0) {
            attachmentCount.textContent = data.attachments.length;
            data.attachments.forEach(att => {
                const li = document.createElement('li');
                li.innerHTML = `<i class="fas fa-file"></i> <a href="/document/download?id=${att.id}" target="_blank">${att.filename}</a>`;
                attachmentList.appendChild(li);
            });
            document.getElementById('modal-attachments-panel').style.display = 'block';
        } else {
            attachmentCount.textContent = 0;
            document.getElementById('modal-attachments-panel').style.display = 'none';
        }

    } catch(error) {
        console.error(error);
        title.textContent = 'Erreur';
        showToast('Impossible de charger le document.', 'error');
        setTimeout(() => modal.style.display = 'none', 1500);
    }
}

async function handleCreateFolder(e) {
    e.preventDefault();
    const form = e.target;
    const folderName = form.querySelector('#folder-name').value;
    const parentId = new URLSearchParams(window.location.search).get('folder_id') || null;

    try {
        const response = await fetch('/api/folder/create', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ folderName, parentId })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);

        showToast('Dossier créé avec succès.', 'success');
        form.closest('.modal-overlay').style.display = 'none';
        form.reset();
        if(refreshCallback) refreshCallback();
    } catch(error) {
        showToast(`Erreur: ${error.message}`, 'error');
    }
}

async function handleRename(e) {
    e.preventDefault();
    const form = e.target;
    const id = form.querySelector('#rename-id').value;
    const type = form.querySelector('#rename-type').value;
    const newName = form.querySelector('#new-name').value;

    try {
        const response = await fetch('/api/item/rename', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id, type, newName })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);

        showToast('Élément renommé.', 'success');
        form.closest('.modal-overlay').style.display = 'none';
        if(refreshCallback) refreshCallback();
    } catch (error) {
        showToast(`Erreur: ${error.message}`, 'error');
    }
}
