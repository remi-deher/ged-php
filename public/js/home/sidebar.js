// public/js/home/sidebar.js

GED.home = GED.home || {};

GED.home.sidebar = {
    init() {
        this.sidebarEl = document.getElementById('details-sidebar');
        this.mainContentEl = document.getElementById('main-content');
        if (!this.sidebarEl || !this.mainContentEl) return;

        this.titleEl = document.getElementById('sidebar-title');
        this.infoListEl = document.getElementById('sidebar-info-list');
        this.attachmentsListEl = document.getElementById('sidebar-attachments-list');
        
        document.getElementById('sidebar-close-btn')?.addEventListener('click', () => this.close());
        document.getElementById('sidebar-attachments-toggle-btn')?.addEventListener('click', (e) => {
            e.currentTarget.classList.toggle('collapsed');
            this.attachmentsListEl?.classList.toggle('collapsed');
        });
    },
    async openForDocument(docId) {
        if (!docId || !this.sidebarEl) return;
        
        this.sidebarEl.classList.add('open');
        this.mainContentEl.classList.add('sidebar-open');
        
        this.titleEl.textContent = 'Chargement...';
        this.infoListEl.innerHTML = '<li>Chargement...</li>';
        this.attachmentsListEl.innerHTML = '';

        try {
            const response = await fetch(`/document/details?id=${docId}`);
            if (!response.ok) throw new Error('Document non trouvé.');
            const data = await response.json();
            
            this.titleEl.textContent = data.main_document.original_filename;
            this.infoListEl.innerHTML = `
                <li><strong>Taille:</strong> <span>${data.main_document.size_formatted}</span></li>
                <li><strong>Type:</strong> <span>${data.main_document.mime_type}</span></li>
                <li><strong>Ajouté le:</strong> <span>${new Date(data.main_document.created_at).toLocaleString('fr-FR')}</span></li>
                <li><strong>Source:</strong> <span>${data.main_document.source_account_id ? 'E-mail' : 'Manuel'}</span></li>
             `;

            const attachmentsContainer = document.getElementById('sidebar-attachments');
            if (attachmentsContainer) {
                if (data.attachments && data.attachments.length > 0) {
                    attachmentsContainer.style.display = 'block';
                    this.attachmentsListEl.innerHTML = '';
                    data.attachments.forEach(att => {
                        this.attachmentsListEl.innerHTML += `<li><a href="/document/download?id=${att.id}" target="_blank">${att.original_filename}</a></li>`;
                    });
                } else {
                    attachmentsContainer.style.display = 'none';
                }
            }
        } catch (error) {
            this.titleEl.textContent = 'Erreur';
            this.infoListEl.innerHTML = `<li>${error.message}</li>`;
        }
    },
    close() {
        this.sidebarEl?.classList.remove('open');
        this.mainContentEl?.classList.remove('sidebar-open');
    }
};
