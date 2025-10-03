// public/js/trash.js

document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.trash-checkbox');
    const actionsContainer = document.getElementById('trash-actions');
    const mainCheckbox = document.getElementById('select-all-checkbox-trash');

    function toggleActionButtons() {
        const anyChecked = document.querySelectorAll('.trash-checkbox:checked').length > 0;
        if (actionsContainer) {
            actionsContainer.style.display = anyChecked ? 'block' : 'none';
        }
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', toggleActionButtons);
    });

    if (mainCheckbox) {
        mainCheckbox.addEventListener('change', (event) => {
            checkboxes.forEach(checkbox => {
                checkbox.checked = event.target.checked;
            });
            toggleActionButtons();
        });
    }
});
