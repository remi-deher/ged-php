// public/js/home/filters.js

let refreshCallback;

export function init(refreshDocumentsCallback) {
    refreshCallback = refreshDocumentsCallback;
    const toggleBtn = document.getElementById('toggle-filters-btn');
    const filterPanel = document.getElementById('advanced-filters');
    const filterForm = document.getElementById('filter-form');

    if (toggleBtn && filterPanel) {
        toggleBtn.addEventListener('click', () => {
            filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
        });
    }

    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (refreshCallback) refreshCallback();
            filterPanel.style.display = 'none'; // Ferme le panneau après application
            updateFilterPills();
        });
    }
    document.getElementById('filter-pills-container')?.addEventListener('click', handlePillClick);
}

export function getFilterParams() {
    const filterForm = document.getElementById('filter-form');
    if (!filterForm) return new URLSearchParams();
    
    const formData = new FormData(filterForm);
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
        if (value) { // N'ajoute que les filtres qui ont une valeur
            params.append(key, value);
        }
    }
    return params;
}

function updateFilterPills() {
    const container = document.getElementById('filter-pills-container');
    if (!container) return;

    container.innerHTML = '';
    const params = getFilterParams();
    let hasFilters = false;

    params.forEach((value, key) => {
        hasFilters = true;
        const pill = document.createElement('div');
        pill.className = 'pill';
        pill.innerHTML = `
            <span>${getFilterLabel(key)}: <strong class="pill-value">${value}</strong></span>
            <button class="remove-pill" data-filter-key="${key}">&times;</button>
        `;
        container.appendChild(pill);
    });

    if (hasFilters) {
        const clearBtn = document.createElement('button');
        clearBtn.className = 'clear-all-pills';
        clearBtn.textContent = 'Tout effacer';
        container.appendChild(clearBtn);
    }
}

function handlePillClick(e) {
    if (e.target.matches('.remove-pill')) {
        const key = e.target.dataset.filterKey;
        const input = document.getElementById(`filter-${key.replace('_', '-')}`); // ex: start_date -> filter-start-date
        if (input) input.value = '';
    } else if (e.target.matches('.clear-all-pills')) {
        document.getElementById('filter-form').reset();
    }
    
    if (refreshCallback) refreshCallback();
    updateFilterPills();
}

function getFilterLabel(key) {
    const labels = {
        'type': 'Type',
        'status': 'Statut',
        'start_date': 'Après le',
        'end_date': 'Avant le'
    };
    return labels[key] || key;
}
