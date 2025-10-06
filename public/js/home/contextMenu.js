// public/js/home/contextMenu.js

GED.home = GED.home || {};

GED.home.contextMenu = {
    init() {
        const contextMenu = document.getElementById('context-menu');
        const mainContent = document.getElementById('main-content');
        if (!contextMenu || !mainContent) return;

        let currentItemId = null;

        mainContent.addEventListener('contextmenu', e => {
            e.preventDefault();
            const targetRow = e.target.closest('.document-row, .folder-row');
            
            if (targetRow) {
                // Clic sur un élément : affiche les actions spécifiques
                currentItemId = targetRow.dataset.docId || targetRow.dataset.folderId;
                contextMenu.querySelectorAll('.item-specific').forEach(item => item.style.display = 'block');
            } else {
                // Clic dans le vide : cache les actions spécifiques
                currentItemId = null;
                contextMenu.querySelectorAll('.item-specific').forEach(item => item.style.display = 'none');
            }
            
            contextMenu.style.top = `${e.clientY}px`;
            contextMenu.style.left = `${e.clientX}px`;
            contextMenu.style.display = 'block';
        });

        document.addEventListener('click', () => contextMenu.style.display = 'none');

        contextMenu.addEventListener('click', e => {
            const action = e.target.dataset.action;
            if (!action) return;

            if (action === 'create-folder') {
                GED.home.main.createFolder();
                return;
            }

            const selectedIds = Array.from(document.querySelectorAll('.doc-checkbox:checked')).map(cb => cb.value);
            const targetIds = selectedIds.length > 0 ? selectedIds : (currentItemId ? [currentItemId] : []);
            if (targetIds.length === 0) return;

            switch (action) {
                case 'preview_sidebar':
                    if (targetIds.length === 1) GED.home.sidebar.openForDocument(targetIds[0]);
                    break;
                case 'preview_modal':
                     if (targetIds.length === 1) GED.home.modal.openForDocument(targetIds[0]);
                    break;
                case 'print':
                    GED.utils.createAndSubmitForm('/document/bulk-print', 'POST', { 'doc_ids': targetIds });
                    break;
                case 'download':
                    targetIds.forEach(id => window.open(`/document/download?id=${id}`, '_blank'));
                    break;
                case 'delete':
                    if (confirm('Confirmer la mise à la corbeille des éléments sélectionnés ?')) {
                        GED.utils.createAndSubmitForm('/document/delete', 'POST', { 'doc_ids': targetIds });
                    }
                    break;
                case 'move':
                    GED.utils.showToast('Pour déplacer, glissez et déposez les éléments sur un dossier.', '➔');
                    break;
            }
        });
    }
};
