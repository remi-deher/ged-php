// public/js/home/modal.js

GED.home = GED.home || {};

GED.home.modal = {
    init() {
        this.modalEl = document.getElementById('document-modal');
        if (!this.modalEl) return;

        this.titleEl = document.getElementById('modal-title');
        this.attachmentsListEl = document.getElementById('modal-attachments-list');
        this.previewIframeEl = document.getElementById('modal-preview-iframe');
        
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

        try {
            const response = await fetch(`/document/details?id=${docId}`);
            if (!response.ok) throw new Error('Document non trouvé.');
            const data = await response.json();
            
            this.titleEl.textContent = data.main_document.original_filename;
            this.previewIframeEl.src = `/document/download?id=${data.main_document.id}`;

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
