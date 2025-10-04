// public/js/home/sidebar.js

GED.home = GED.home || {};

GED.home.sidebar = {
    init() {
        this.sidebarEl = document.getElementById('details-sidebar');
        this.mainContentEl = document.getElementById('main-content');
        if (!this.sidebarEl || !this.mainContentEl) return;

        this.titleEl = document.getElementById('sidebar-title');
        this.contentWrapper = document.getElementById('sidebar-content-wrapper');
        
        document.getElementById('sidebar-close-btn')?.addEventListener('click', () => this.close());
    },

    async openForDocument(docId) {
        if (!docId || !this.sidebarEl) return;
        
        this.sidebarEl.classList.add('open');
        this.mainContentEl.classList.add('sidebar-open');
        
        // Afficher un état de chargement
        this.titleEl.textContent = 'Chargement...';
        this.contentWrapper.innerHTML = '<div style="padding: 2rem; text-align: center;">Chargement des détails...</div>';

        try {
            const response = await fetch(`/document/details?id=${docId}`);
            if (!response.ok) throw new Error('Document non trouvé.');
            const data = await response.json();
            
            this.titleEl.textContent = data.main_document.original_filename;
            this.renderSidebarContent(data.main_document, data.attachments);

        } catch (error) {
            this.titleEl.textContent = 'Erreur';
            this.contentWrapper.innerHTML = `<div style="padding: 2rem; text-align: center; color: var(--danger-color);">${error.message}</div>`;
        }
    },

    close() {
        this.sidebarEl?.classList.remove('open');
        this.mainContentEl?.classList.remove('sidebar-open');
    },

    getFileIconClass(mimeType) {
        if (!mimeType) return 'fa-file';
        if (mimeType.includes('pdf')) return 'fa-file-pdf';
        if (mimeType.includes('image')) return 'fa-file-image';
        if (mimeType.includes('word')) return 'fa-file-word';
        if (mimeType.includes('html')) return 'fa-file-code';
        return 'fa-file-alt';
    },

    renderSidebarContent(doc, attachments) {
        const docId = doc.id;
        const iconClass = this.getFileIconClass(doc.mime_type);

        let attachmentsHtml = '';
        if (attachments && attachments.length > 0) {
            attachmentsHtml = `
                <div class="sidebar-section">
                    <h3>Pièces jointes (${attachments.length})</h3>
                    <ul class="attachments-list">
                        ${attachments.map(att => `
                            <li class="attachment-item">
                                <i class="fas ${this.getFileIconClass(att.mime_type)} attachment-item-icon"></i>
                                <div class="attachment-item-info">
                                    <strong title="${att.original_filename}">${att.original_filename}</strong>
                                    <span>${att.size_formatted}</span>
                                </div>
                                <div class="attachment-item-actions">
                                    <a href="/document/download?id=${att.id}" class="button-icon" title="Télécharger" target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }

        this.contentWrapper.innerHTML = `
            <div class="sidebar-preview">
                <i class="fas ${iconClass}"></i>
            </div>

            <div class="sidebar-actions">
                <form action="/document/print" method="POST" class="action-form" style="width: 100%;">
                    <input type="hidden" name="doc_id" value="${docId}">
                    <button type="submit" class="button"><i class="fas fa-print"></i> Imprimer</button>
                </form>
                <a href="/document/download?id=${docId}" class="button button-secondary" target="_blank">
                    <i class="fas fa-download"></i> Télécharger
                </a>
                <form action="/document/delete" method="POST" class="action-form" style="width: 100%;" onsubmit="return confirm('Confirmer la mise à la corbeille ?');">
                    <input type="hidden" name="doc_ids[]" value="${docId}">
                    <button type="submit" class="button button-delete"><i class="fas fa-trash"></i> Corbeille</button>
                </form>
            </div>

            <div class="sidebar-section">
                <h3>Informations</h3>
                <ul class="sidebar-info-list">
                    <li><strong>Taille:</strong> <span>${doc.size_formatted}</span></li>
                    <li><strong>Type:</strong> <span>${doc.mime_type}</span></li>
                    <li><strong>Ajouté le:</strong> <span>${new Date(doc.created_at).toLocaleString('fr-FR')}</span></li>
                    <li><strong>Source:</strong> <span>${doc.source_account_id ? 'E-mail' : 'Manuel'}</span></li>
                </ul>
            </div>

            ${attachmentsHtml}
        `;
    }
};
