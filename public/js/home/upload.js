// public/js/home/upload.js

GED.home = GED.home || {};

GED.home.upload = {
    init() {
        this.dropOverlay = document.getElementById('drop-overlay');
        const fileInput = document.getElementById('document-upload-input'); // Nouvel ID pour l'input

        if (!this.dropOverlay || !fileInput) return;

        let enterCounter = 0;

        // --- Logique du Drag and Drop (inchangée) ---
        window.addEventListener('dragenter', (e) => {
            e.preventDefault();
            enterCounter++;
            const hasFiles = e.dataTransfer && e.dataTransfer.types.includes('Files');
            if (hasFiles && enterCounter === 1) {
                this.dropOverlay.classList.add('visible');
            }
        });

        window.addEventListener('dragleave', (e) => {
            e.preventDefault();
            enterCounter--;
            if (enterCounter === 0) {
                this.dropOverlay.classList.remove('visible');
            }
        });

        window.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        window.addEventListener('drop', (e) => {
            e.preventDefault();
            enterCounter = 0;
            this.dropOverlay.classList.remove('visible');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.handleFiles(files);
            }
        });

        // --- NOUVELLE LOGIQUE POUR LE BOUTON "ENVOYER" ---
        // Ajout d'un écouteur sur le changement de l'input file
        fileInput.addEventListener('change', (e) => {
            const files = e.target.files;
            if (files.length > 0) {
                this.handleFiles(files);
            }
        });
    },

    // --- Fonction de téléversement (commune) ---
    handleFiles(files) {
        GED.utils.showToast(`Téléversement de ${files.length} fichier(s)...`, '⏳');

        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('documents[]', files[i]);
        }

        const urlParams = new URLSearchParams(window.location.search);
        const folderId = urlParams.get('folder_id') || '';
        formData.append('folder_id', folderId);

        fetch('/upload', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw new Error(err.message || 'Erreur serveur'); });
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                GED.utils.showToast(result.message, '✅');
                if (result.documents) {
                    result.documents.forEach(doc => {
                        GED.home.main.addDocumentToView(doc);
                    });
                }
            } else {
                 throw new Error(result.message || 'Une erreur est survenue.');
            }
        })
        .catch(error => {
            GED.utils.showToast(`Erreur : ${error.message}`, '⚠️');
        });
    }
};
