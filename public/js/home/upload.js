// public/js/home/upload.js

GED.home = GED.home || {};

GED.home.upload = {
    init() {
        this.dropOverlay = document.getElementById('drop-overlay');
        if (!this.dropOverlay) return;

        let enterCounter = 0; // Compteur pour gérer les événements dragenter/dragleave

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
            e.preventDefault(); // Indispensable pour que l'événement 'drop' se déclenche
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
    },

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
            // AJOUT DE L'EN-TÊTE POUR LA DÉTECTION AJAX
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
            if(result.success) {
                GED.utils.showToast(result.message, '✅');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                 throw new Error(result.message || 'Une erreur est survenue.');
            }
        })
        .catch(error => {
            GED.utils.showToast(`Erreur : ${error.message}`, '⚠️');
        });
    }
};
