// public/js/home/dnd.js

GED.home = GED.home || {};

GED.home.dnd = {
    init() {
        const draggables = document.querySelectorAll('[draggable="true"]');
        const dropzones = document.querySelectorAll('.dropzone');

        draggables.forEach(draggable => {
            draggable.addEventListener('dragstart', e => {
                const selectedCheckboxes = document.querySelectorAll('.doc-checkbox:checked');
                let draggedItems = [];
                const isChecked = draggable.querySelector('.doc-checkbox')?.checked;

                if (isChecked && selectedCheckboxes.length > 1) {
                    selectedCheckboxes.forEach(checkbox => {
                        const row = checkbox.closest('[draggable="true"]');
                        if(row) {
                            const type = row.classList.contains('folder-row') ? 'folder' : 'document';
                            const id = type === 'folder' ? row.dataset.folderId : row.dataset.docId;
                            if(id) draggedItems.push({ type, id });
                        }
                    });
                } else {
                    const type = draggable.classList.contains('folder-row') ? 'folder' : 'document';
                    const id = type === 'folder' ? draggable.dataset.folderId : draggable.dataset.docId;
                    if(id) draggedItems.push({ type, id });
                }
                
                if (draggedItems.length > 0) {
                    e.dataTransfer.setData('application/json', JSON.stringify(draggedItems));
                    setTimeout(() => draggedItems.forEach(item => {
                        document.querySelector(`[data-doc-id="${item.id}"], [data-folder-id="${item.id}"]`)?.classList.add('dragging');
                    }), 0);
                } else {
                    e.preventDefault();
                }
            });

            draggable.addEventListener('dragend', () => {
                document.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging'));
            });
        });

        dropzones.forEach(zone => {
            zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
            zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
            zone.addEventListener('drop', async e => {
                e.preventDefault();
                zone.classList.remove('drag-over');

                const items = JSON.parse(e.dataTransfer.getData('application/json'));
                const targetFolderId = zone.dataset.folderId;
                
                if (!items || items.length === 0 || typeof targetFolderId === 'undefined') return;

                const docIds = items.filter(item => item.type === 'document').map(item => item.id);
                const folderIds = items.filter(item => item.type === 'folder').map(item => item.id);

                if (folderIds.includes(targetFolderId)) {
                    return GED.utils.showToast('Un dossier ne peut pas être déplacé dans lui-même.', '⚠️');
                }

                try {
                    let moved = false;
                    if (docIds.length > 0) {
                        const formData = new FormData();
                        docIds.forEach(id => formData.append('doc_ids[]', id));
                        formData.append('folder_id', targetFolderId);
                        const response = await fetch('/document/move', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        const result = await response.json();
                        if (!response.ok) throw new Error(result.message);
                        moved = true;
                    }

                    if (folderIds.length > 0) {
                         GED.utils.showToast('Le déplacement de dossiers n\'est pas encore pris en charge.', '📁');
                    }

                    if (moved) {
                        GED.utils.showToast('Déplacement réussi ! Actualisation...', '✅');
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } catch (error) {
                    GED.utils.showToast(`Erreur : ${error.message}`, '⚠️');
                }
            });
        });
    }
};
