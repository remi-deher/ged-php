document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.doc-checkbox');
    const bulkDeleteButton = document.getElementById('bulk-delete-button');
    const mainCheckbox = document.getElementById('select-all-checkbox');

    function toggleBulkDeleteButton() {
        const anyChecked = document.querySelectorAll('.doc-checkbox:checked').length > 0;
        if (bulkDeleteButton) {
            bulkDeleteButton.style.display = anyChecked ? 'inline-block' : 'none';
        }
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', toggleBulkDeleteButton);
    });

    if (mainCheckbox) {
        mainCheckbox.addEventListener('change', (event) => {
            checkboxes.forEach(checkbox => {
                checkbox.checked = event.target.checked;
            });
            toggleBulkDeleteButton();
        });
    }
    
    // Pour l'intégration des icônes Lucide
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
