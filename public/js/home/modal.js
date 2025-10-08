// public/js/home/modal.js

import { showToast } from '../common/utils.js'; // Nouveau chemin correct

let currentDocumentId = null;

export function initializeModal() {
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
