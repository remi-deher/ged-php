// public/js/home/modal.js

import { showToast } from '../common/utils.js';

let currentDocumentId = null;

export function setupDocumentModal() {
    const modal = document.getElementById('document-modal');
    const closeModalButton = document.querySelector('.modal .close');
    const renameButton = document.getElementById('rename-button');
    const downloadButton = document.getElementById('download-button');
    const deleteButton = document.getElementById('delete-button');

    if (closeModalButton) {
        closeModalButton.onclick = () => {
            modal.style.display = "none";
        };
    }

    window.onclick = (event) => {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };

    if(renameButton) {
        renameButton.addEventListener('click', () => {
            // Logique pour renommer
            console.log('Renommer doc ID:', currentDocumentId);
            modal.style.display = "none";
            // Ouvrir le modal de renommage
            const renameModal = document.getElementById('rename-modal');
            renameModal.style.display = 'block';
            document.getElementById('rename-id-input').value = currentDocumentId;
            document.getElementById('rename-type-input').value = 'document'; // ou 'folder'
            document.getElementById('new-name-input').focus();
        });
    }

    if(downloadButton) {
        downloadButton.addEventListener('click', () => {
            if (currentDocumentId) {
                window.location.href = `/download?id=${currentDocumentId}`;
            }
        });
    }

    if(deleteButton) {
        deleteButton.addEventListener('click', () => {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce document ?')) {
                fetch('/api/delete-item', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: currentDocumentId, type: 'document' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Document déplacé à la corbeille.');
                        document.querySelector(`[data-id='${currentDocumentId}']`).remove();
                        modal.style.display = "none";
                    } else {
                        showToast(data.message, 'error');
                    }
                });
            }
        });
    }
}


export function openDocumentModal(docId, docName, previewUrl) {
    currentDocumentId = docId;
    const modal = document.getElementById('document-modal');
    const modalTitle = document.getElementById('modal-doc-title');
    const previewFrame = document.getElementById('preview-frame');

    modalTitle.textContent = docName;
    previewFrame.src = previewUrl;
    
    modal.style.display = "block";
}

export function setupFolderModals(onFolderCreated) {
    const createFolderModal = document.getElementById('create-folder-modal');
    const openModalBtn = document.getElementById('create-folder-btn');
    const closeModalBtn = createFolderModal.querySelector('.close');
    const createFolderForm = document.getElementById('create-folder-form');

    if (openModalBtn) {
        openModalBtn.addEventListener('click', () => {
            createFolderModal.style.display = 'block';
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            createFolderModal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === createFolderModal) {
            createFolderModal.style.display = 'none';
        }
    });

    if (createFolderForm) {
        createFolderForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const folderName = document.getElementById('folder-name').value;
            const parentFolderId = new URLSearchParams(window.location.search).get('folder_id');

            const response = await fetch('/api/folders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: folderName,
                    parent_id: parentFolderId
                })
            });

            const result = await response.json();

            if (response.ok) {
                showToast('Dossier créé avec succès.', 'success');
                createFolderModal.style.display = 'none';
                createFolderForm.reset();
                if (onFolderCreated) {
                    onFolderCreated();
                }
            } else {
                showToast(result.error || 'Erreur lors de la création du dossier.', 'error');
            }
        });
    }
}

export function setupRenameModal(onItemRenamed) {
    const renameModal = document.getElementById('rename-modal');
    const closeModalBtn = renameModal.querySelector('.close');
    const renameForm = document.getElementById('rename-form');

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            renameModal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === renameModal) {
            renameModal.style.display = 'none';
        }
    });

    if (renameForm) {
        renameForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('rename-id-input').value;
            const type = document.getElementById('rename-type-input').value;
            const newName = document.getElementById('new-name-input').value;

            const response = await fetch('/api/rename-item', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: id,
                    type: type,
                    new_name: newName
                })
            });

            const result = await response.json();

            if (response.ok) {
                showToast('Élément renommé avec succès.', 'success');
                renameModal.style.display = 'none';
                renameForm.reset();
                if (onItemRenamed) {
                    onItemRenamed();
                }
            } else {
                showToast(result.error || 'Erreur lors du renommage.', 'error');
            }
        });
    }
}
