document.addEventListener('DOMContentLoaded', () => {
    // --- Gestion des cases à cocher ---
    const mainCheckbox = document.getElementById('select-all-checkbox');
    const docCheckboxes = document.querySelectorAll('.doc-checkbox');
    const bulkDeleteButton = document.getElementById('bulk-delete-button');

    function toggleBulkDeleteButton() {
        const anyChecked = document.querySelectorAll('.doc-checkbox:checked').length > 0;
        if (bulkDeleteButton) {
            bulkDeleteButton.style.display = anyChecked ? 'inline-block' : 'none';
        }
    }

    if (mainCheckbox) {
        mainCheckbox.addEventListener('change', (event) => {
            docCheckboxes.forEach(checkbox => {
                checkbox.checked = event.target.checked;
            });
            toggleBulkDeleteButton();
        });
    }

    docCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            if (!checkbox.classList.contains('attachment-checkbox')) {
                const parentId = checkbox.closest('tr').dataset.docId;
                document.querySelectorAll(`.attachment-row[data-parent-id="${parentId}"] .doc-checkbox`).forEach(child => {
                    child.checked = checkbox.checked;
                });
            }
            toggleBulkDeleteButton();
        });
    });

    // --- Gestion de la modale ---
    const modal = document.getElementById('email-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalAttachmentsList = document.getElementById('modal-attachments-list');
    const modalPreviewIframe = document.getElementById('modal-preview-iframe');
    const closeModalButton = document.getElementById('modal-close-button');

    document.querySelectorAll('.email-row').forEach(row => {
        row.addEventListener('click', async (event) => {
            // Empêche l'ouverture de la modale si on clique sur un input ou un select
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'SELECT' || event.target.tagName === 'FORM') {
                return;
            }
            
            const docId = row.dataset.docId;
            
            // Afficher un loader (optionnel)
            modalTitle.textContent = 'Chargement...';
            modalAttachmentsList.innerHTML = '';
            modalPreviewIframe.src = 'about:blank';
            modal.style.display = 'flex';

            try {
                const response = await fetch(`/document/details?id=${docId}`);
                if (!response.ok) {
                    throw new Error('Document non trouvé ou erreur serveur.');
                }
                const data = await response.json();
                
                // Remplir la modale
                modalTitle.textContent = data.main_document.original_filename;
                
                // Iframe pour l'aperçu du PDF de l'e-mail
                modalPreviewIframe.src = `/document/download?id=${data.main_document.id}`;

                // Liste des pièces jointes
                if (data.attachments.length > 0) {
                    data.attachments.forEach(att => {
                        const li = document.createElement('li');
                        const icon = '<i data-lucide="file-text" style="width:16px;"></i>';
                        li.innerHTML = `${icon} <a href="/document/download?id=${att.id}" target="_blank">${att.original_filename}</a>`;
                        modalAttachmentsList.appendChild(li);
                    });
                } else {
                    modalAttachmentsList.innerHTML = '<li>Aucune pièce jointe.</li>';
                }
                
                // Rafraîchir les icônes dans la modale
                lucide.createIcons();

            } catch (error) {
                modalTitle.textContent = 'Erreur';
                console.error(error);
            }
        });
    });

    // Fermer la modale
    const closeModal = () => {
        modal.style.display = 'none';
        modalPreviewIframe.src = 'about:blank'; // Important pour arrêter le chargement du PDF
    };

    closeModalButton.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Initialisation des icônes Lucide
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
