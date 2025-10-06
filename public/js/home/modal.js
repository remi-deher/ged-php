// public/js/home/modal.js

GED.home = GED.home || {};

GED.home.modal = {
    init() {
        this.modalEl = document.getElementById('document-modal');
        if (!this.modalEl) return;

        this.titleEl = document.getElementById('modal-title');
        this.attachmentsListEl = document.getElementById('modal-attachments-list');
        this.previewIframeEl = document.getElementById('modal-preview-iframe');
        
        // Suppression des références aux anciens éléments qui n'existent plus
        // this.previewDocTitleEl = document.getElementById('preview-doc-title');
        // this.previewNewTabBtn = document.getElementById('preview-new-tab');
        
        this.modalEl.querySelector('.modal-close')?.addEventListener('click', () => this.close());
        this.modalEl.addEventListener('click', (e) => {
            if (e.target === this.modalEl) this.close();
        });

        const attachmentsToggleBtn = document.getElementById('modal-attachments-toggle-btn');
        const attachmentsPanel = document.getElementById('modal-attachments');

        // Au clic sur l'en-tête, on bascule la classe 'collapsed'
        attachmentsPanel?.querySelector('.attachments-header')?.addEventListener('click', () => {
            attachmentsPanel.classList.toggle('collapsed');
            const isCollapsed = attachmentsPanel.classList.contains('collapsed');
            // On change l'icône et le titre en fonction de l'état
            attachmentsToggleBtn.innerHTML = isCollapsed ? 'ˇ' : 'ˆ';
            attachmentsToggleBtn.title = isCollapsed ? 'Afficher les pièces jointes' : 'Masquer les pièces jointes';
        });
    },

    async openForDocument(docId) {
        if (!docId || !this.modalEl) return;
        
        this.modalEl.style.display = 'flex';
        
        // Réinitialisation de l'affichage
        if (this.titleEl) this.titleEl.textContent = 'Chargement...';
        if (this.attachmentsListEl) this.attachmentsListEl.innerHTML = '<li>Chargement...</li>';
        if (this.previewIframeEl) this.previewIframeEl.src = 'about:blank';

        try {
            const response = await fetch(`/document/details?id=${docId}`);
            if (!response.ok) throw new Error('Document non trouvé.');
            const data = await response.json();
            
            if (this.titleEl) this.titleEl.textContent = data.main_document.original_filename;
            
            const previewUrl = `/document/preview?id=${data.main_document.id}`;
            if (this.previewIframeEl) this.previewIframeEl.src = previewUrl;

            const attachmentsPanel = document.getElementById('modal-attachments');
            
            if (attachmentsPanel) {
                if (data.attachments && data.attachments.length > 0) {
                    attachmentsPanel.style.display = 'block';
                    this.attachmentsListEl.innerHTML = '';
                    data.attachments.forEach(attachment => {
                        const li = document.createElement('li');
                        li.innerHTML = `📄 <a href="/document/download?id=${attachment.id}" target="_blank" title="${attachment.original_filename}">${attachment.original_filename}</a>`;
                        this.attachmentsListEl.appendChild(li);
                    });
                } else {
                     attachmentsPanel.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Erreur:', error);
            if (this.titleEl) this.titleEl.textContent = 'Erreur';
            if (this.attachmentsListEl) this.attachmentsListEl.innerHTML = `<li>Impossible de charger les informations.</li>`;
        }
    },

    close() {
        if (!this.modalEl) return;
        this.modalEl.style.display = 'none';
        if (this.previewIframeEl) this.previewIframeEl.src = 'about:blank';
    }
};
