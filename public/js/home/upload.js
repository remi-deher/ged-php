// public/js/home/upload.js

import { showToast } from '../utils.js';

let fileInput;
let refreshCallback;
let currentFolderId;

export function init(folderId, refreshDocumentsCallback) {
    currentFolderId = folderId;
    refreshCallback = refreshDocumentsCallback;

    const uploadBtn = document.getElementById('upload-file-btn');
    fileInput = document.getElementById('file-input');
    
    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fileInput.click();
        });
        
        fileInput.addEventListener('change', handleFileUpload);
    }
}

async function handleFileUpload(event) {
    const files = event.target.files;
    if (files.length === 0) return;

    const formData = new FormData();
    for (const file of files) {
        formData.append('documents[]', file);
    }
    if (currentFolderId) {
        formData.append('folder_id', currentFolderId);
    }

    // Affiche un toast de début d'upload
    const toastMessage = files.length > 1 ? `${files.length} fichiers en cours de téléversement...` : `'${files[0].name}' en cours de téléversement...`;
    showToast(toastMessage, 'info');

    try {
        const response = await fetch('/api/document/upload', {
            method: 'POST',
            body: formData,
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Une erreur est survenue.');
        }
        
        showToast('Téléversement terminé avec succès !', 'success');
        if (refreshCallback) {
            refreshCallback();
        }
    } catch (error) {
        console.error('Erreur d\'upload:', error);
        showToast(`Erreur d'upload : ${error.message}`, 'error');
    } finally {
        // Réinitialise le champ de fichier pour permettre de re-téléverser le même fichier
        fileInput.value = '';
    }
}
