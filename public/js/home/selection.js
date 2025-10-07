// public/js/home/selection.js

let selectAllCheckbox;
let rowCheckboxes;
let bulkActionsContainer;

export function init() {
    selectAllCheckbox = document.getElementById('select-all-checkbox');
    bulkActionsContainer = document.querySelector('.bulk-actions-container'); // Assurez-vous que ce conteneur existe
    
    document.addEventListener('change', (e) => {
        if (e.target.matches('#select-all-checkbox, .row-checkbox')) {
            rowCheckboxes = document.querySelectorAll('.row-checkbox');
            if (e.target === selectAllCheckbox) {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
            }
            updateBulkActionsVisibility();
        }
    });
}

export function updateBulkActionsVisibility() {
    if (!bulkActionsContainer) return;
    
    const anyChecked = document.querySelector('.row-checkbox:checked');
    if (anyChecked) {
        bulkActionsContainer.style.display = 'block';
    } else {
        bulkActionsContainer.style.display = 'none';
    }
}

export function getSelectedIds() {
    return Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.dataset.id);
}
