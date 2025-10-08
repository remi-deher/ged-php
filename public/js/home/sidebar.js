// public/js/home/sidebar.js

import { showToast } from '../common/utils.js'; // Nouveau chemin relatif correct

export function initializeSidebar() {
    const createFolderBtn = document.getElementById('create-folder-btn');
    const createFolderModal = document.getElementById('create-folder-modal');
    const closeModal = document.querySelector('#create-folder-modal .close');
    const createFolderForm = document.getElementById('create-folder-form');

    if (createFolderBtn) {
        createFolderBtn.addEventListener('click', () => {
            createFolderModal.style.display = 'block';
        });
    }

    if (closeModal) {
        closeModal.onclick = () => {
            createFolderModal.style.display = 'none';
        };
    }

    window.addEventListener('click', (event) => {
        if (event.target == createFolderModal) {
            createFolderModal.style.display = 'none';
        }
    });

    if (createFolderForm) {
        createFolderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const folderName = document.getElementById('folder-name').value;
            const parentId = new URLSearchParams(window.location.search).get('folder_id');

            fetch('/api/create-folder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ folderName, parentId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Dossier créé avec succès.');
                    createFolderModal.style.display = 'none';
                    // Idéalement, ici on devrait rafraîchir la liste des fichiers/dossiers
                    // Pour l'instant, on recharge la page
                    window.location.reload(); 
                } else {
                    showToast(data.message, 'error');
                }
            });
        });
    }
}
