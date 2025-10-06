// public/js/home/main.js

GED.home = GED.home || {};

GED.home.main = {
    init() {
        // Initialise tous les modules de la page d'accueil
        GED.home.sidebar.init();
        GED.home.modal.init();
        GED.home.contextMenu.init();
        GED.home.selection.init();
        GED.home.viewSwitcher.init();
        GED.home.dnd.init();
        GED.home.printQueue.init();
        GED.home.websocket.init();
        GED.home.upload.init(); // Initialisation du nouveau module de téléversement

        // Gère les événements globaux de la page comme le simple/double clic
        this.initGlobalEvents();
    },

    initGlobalEvents() {
        // Utilise la délégation d'événements pour gérer les clics sur les documents actuels et futurs
        document.getElementById('main-content').addEventListener('click', (e) => {
            const row = e.target.closest('.document-row:not(.folder-row)');
            if (!row || e.target.closest('button, a, input[type="checkbox"], form')) {
                return;
            }

            if (row.clickTimer) {
                // Double clic
                clearTimeout(row.clickTimer);
                row.clickTimer = null;
                const docId = row.dataset.docId;
                if (docId) GED.home.modal.openForDocument(docId);
            } else {
                // Premier clic
                row.clickTimer = setTimeout(() => {
                    row.clickTimer = null;
                    const docId = row.dataset.docId;
                    if (docId) GED.home.sidebar.openForDocument(docId);
                }, 250);
            }
        });
    },

    // --- NOUVELLE FONCTION ---
    addDocumentToView(doc) {
        // Supprimer le message "dossier vide" s'il existe
        const emptyState = document.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }

        const docName = doc.original_filename || doc.name;

        // 1. Créer la ligne pour la vue "Liste"
        const tableBody = document.querySelector('.documents-table tbody');
        if (tableBody) {
            const newRow = document.createElement('tr');
            newRow.className = 'document-row';
            newRow.dataset.docId = doc.id;
            newRow.setAttribute('draggable', 'true');
            
            const statusMap = {
                'received': { color: '#007bff', label: 'Reçu' },
                'to_print': { color: '#ffc107', label: 'À imprimer' },
                'printed': { color: '#28a745', label: 'Imprimé' },
                'print_error': { color: '#dc3545', label: 'Erreur' }
            };
            const statusInfo = statusMap[doc.status] || { color: '#6c757d', label: 'Inconnu' };
            const date = new Date(doc.created_at).toLocaleString('fr-FR');
            
            newRow.innerHTML = `
                <td class="col-checkbox"><input type="checkbox" name="doc_ids[]" value="${doc.id}" class="doc-checkbox" form="bulk-action-form"></td>
                <td class="col-icon"><i class="fas ${GED.home.sidebar.getFileIconClass(doc.mime_type)}"></i></td>
                <td class="col-name">
                    <span class="status-dot" style="background-color: ${statusInfo.color};" title="${statusInfo.label}"></span>
                    <strong>${docName}</strong>
                </td>
                <td>${doc.source_details || 'Manuel'}</td>
                <td class="col-size">${doc.size_formatted}</td>
                <td class="col-date">${date}</td>
                <td class="col-actions">
                    <div class="document-actions">
                        <form action="/document/print" method="POST" class="action-form"><input type="hidden" name="doc_id" value="${doc.id}"><button type="submit" class="button-icon" title="Imprimer"><i class="fas fa-print"></i></button></form>
                        <form action="/document/delete" method="POST" class="action-form" onsubmit="return confirm('Confirmer ?');"><input type="hidden" name="doc_ids[]" value="${doc.id}"><button type="submit" class="button-icon button-delete" title="Corbeille"><i class="fas fa-trash"></i></button></form>
                    </div>
                </td>
            `;
            tableBody.prepend(newRow); // Ajoute en haut de la liste
        }
        
        // 2. Créer l'élément pour la vue "Grille"
        const gridView = document.getElementById('document-grid-view');
        if (gridView) {
            const newGridItem = document.createElement('div');
            newGridItem.className = 'grid-item document-row';
            newGridItem.dataset.docId = doc.id;
            newGridItem.setAttribute('draggable', 'true');
            
            newGridItem.innerHTML = `
                <div class="grid-item-thumbnail"><i class="fas ${GED.home.sidebar.getFileIconClass(doc.mime_type)}"></i></div>
                <div class="grid-item-name" title="${docName}">${docName}</div>
            `;
            gridView.prepend(newGridItem); // Ajoute au début de la grille
        }
        
        // 3. Ré-initialiser les modules qui dépendent des éléments du DOM
        GED.home.dnd.init(); // pour que le nouvel élément soit draggable
        GED.home.selection.init(); // pour que la nouvelle checkbox soit prise en compte
    }
};

// Lance l'initialisation quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    GED.home.main.init();
});
