// public/js/home/modal.js

GED.home = GED.home || {};

GED.home.modal = {
    init() {
        this.modalEl = document.getElementById('document-modal');
        if (!this.modalEl) return;

        this.titleEl = document.getElementById('modal-title');
        this.attachmentsListEl = document.getElementById('modal-attachments-list');
        this.previewIframeEl = document.getElementById('modal-preview-iframe');
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
        
        // AJOUT DE VÉRIFICATIONS "DÉFENSIVES" POUR ÉVITER LES ERREURS
        if (this.titleEl) this.titleEl.textContent = 'Chargement...';
        if (this.attachmentsListEl) this.attachmentsListEl.innerHTML = '<li>Chargement...</li>';
        if (this.previewIframeEl) this.previewIframeEl.src = 'about:blank';
        if (this.previewDocTitleEl) this.previewDocTitleEl.textContent = '';
        if (this.previewNewTabBtn) this.previewNewTabBtn.href = '#';

        try {
            const response = await fetch(`/document/details?id=${docId}`);
            if (!response.ok) throw new Error('Document non trouvé.');
            const data = await response.json();
            
            if (this.titleEl) this.titleEl.textContent = data.main_document.original_filename;
            
            const previewUrl = `/document/preview?id=${data.main_document.id}`;
            if (this.previewIframeEl) this.previewIframeEl.src = previewUrl;

            if (this.previewDocTitleEl) this.previewDocTitleEl.textContent = data.main_document.original_filename;
            if (this.previewNewTabBtn) this.previewNewTabBtn.href = previewUrl;

            const attachmentsPanel = document.getElementById('modal-attachments');
            const attachmentsToggleBtn = document.getElementById('modal-attachments-toggle-btn');
            
            if (attachmentsPanel && attachmentsToggleBtn) {
                if (data.attachments && data.attachments.length > 0) {
                    attachmentsPanel.style.display = 'block';
                    attachmentsToggleBtn.style.display = 'block';
                    this.attachmentsListEl.innerHTML = '';
                    data.attachments.forEach(attachment => {
                        const li = document.createElement('li');
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
