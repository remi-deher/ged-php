// public/js/home/modal.js

GED.home = GED.home || {};

GED.home.modal = {
    init() {
        this.modalEl = document.getElementById('document-modal');
        if (!this.modalEl) return;

        this.titleEl = document.getElementById('modal-title');
        this.attachmentsListEl = document.getElementById('modal-attachments-list');
        this.previewIframeEl = document.getElementById('modal-preview-iframe');
        // Nouveaux éléments à cibler
        this.previewDocTitleEl = document.getElementById('preview-doc-title');
        this.previewNewTabBtn = document.getElementById('preview-new-tab');
        
        this.modalEl.querySelector('.modal-close')?.addEventListener('click', () => this.close());
        this.modalEl.addEventListener('click', (e) => {
            if (e.target === this.modalEl) this.close();
        });

        const attachmentsToggleBtn = document.getElementById('modal-attachments-toggle-btn');
        const attachmentsPanel = document.getElementById('modal-attachments');
        attachmentsToggleBtn?.addEventListener('click', () => {
            attachmentsPanel?.classList.toggle('collapsed');
            const isCollapsed = attachmentsPanel.classList.contains('collapsed');
            attachmentsToggleBtn.innerHTML = isCollapsed ? '›' : '‹';
            attachmentsToggleBtn.title = isCollapsed ? 'Afficher les pièces jointes' : 'Masquer les pièces jointes';
        });
    },
    async openForDocument(docId) {
        if (!docId || !this.modalEl) return;
        
        this.modalEl.style.display = 'flex';
        this.titleEl.textContent = 'Chargement...';
        this.attachmentsListEl.innerHTML = '<li>Chargement...</li>';
        this.previewIframeEl.src = 'about:blank';
        // Réinitialiser les éléments de l'en-tête de l'aperçu
        if(this.previewDocTitleEl) this.previewDocTitleEl.textContent = '';
        if(this.previewNewTabBtn) this.previewNewTabBtn.href = '#';

        try {
            const response = await fetch(`/document/details?id=${docId}`);
            if (!response.ok) throw new Error('Document non trouvé.');
            const data = await response.json();
            
            this.titleEl.textContent = data.main_document.original_filename;
            
            // MODIFICATION : Utiliser la route /document/preview
            this.previewIframeEl.src = `/document/preview?id=${data.main_document.id}`;

            // Mise à jour de l'en-tête de la prévisualisation
            if(this.previewDocTitleEl) this.previewDocTitleEl.textContent = data.main_document.original_filename;
            if(this.previewNewTabBtn) this.previewNewTabBtn.href = `/document/preview?id=${data.main_document.id}`;


            const attachmentsPanel = document.getElementById('modal-attachments');
            const attachmentsToggleBtn = document.getElementById('modal-attachments-toggle-btn');
            
            if (attachmentsPanel && attachmentsToggleBtn) {
                if (data.attachments && data.attachments.length > 0) {
                    attachmentsPanel.style.display = 'block';
                    attachmentsToggleBtn.style.display = 'block';
                    this.attachmentsListEl.innerHTML = '';
                    data.attachments.forEach(attachment => {
                        const li = document.createElement('li');
                        // Le lien pour les pièces jointes reste un lien de téléchargement direct
                        li.innerHTML = `📄 <a href="/document/download?id=${attachment.id}" target="_blank" title="${attachment.original_filename}">${attachment.original_filename}</a>`;
                        this.attachmentsListEl.appendChild(li);
                    });
                } else {
                     attachmentsPanel.style.display = 'none';
                     attachmentsToggleBtn.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.titleEl.textContent = 'Erreur';
            this.attachmentsListEl.innerHTML = `<li>Impossible de charger les informations.</li>`;
        }
    },
    close() {
        if (!this.modalEl) return;
        this.modalEl.style.display = 'none';
        if (this.previewIframeEl) this.previewIframeEl.src = 'about:blank';
    }
};
