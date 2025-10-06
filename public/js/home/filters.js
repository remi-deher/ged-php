// public/js/home/filters.js

GED.home = GED.home || {};

GED.home.filters = {
    init() {
        this.initAddDropdown();
        this.initFilterPanel();
        this.renderFilterPills();
    },

    initAddDropdown() {
        const addBtn = document.getElementById('add-btn');
        const dropdown = document.getElementById('add-dropdown');
        const newFolderBtn = document.getElementById('new-folder-btn');
        const newFolderForm = document.getElementById('new-folder-form');
        const uploadFileBtn = document.getElementById('upload-file-btn');
        const documentInput = document.getElementById('document-input');

        addBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            addBtn.parentElement.classList.toggle('show');
        });

        newFolderBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            const input = newFolderForm.querySelector('input[name="folder_name"]');
            const newName = prompt("Entrez le nom du nouveau dossier :");
            if (newName && newName.trim() !== "") {
                input.value = newName.trim();
                newFolderForm.submit();
            }
        });

        uploadFileBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            documentInput.click();
        });
        
        documentInput?.addEventListener('change', () => {
             if(documentInput.files.length > 0) {
                GED.utils.showToast(`Téléversement de ${documentInput.files.length} fichier(s)...`, '⏳');
                document.getElementById('upload-form').submit();
             }
        });
    },

    initFilterPanel() {
        const filterBtn = document.getElementById('advanced-filter-btn');
        const filterPanel = document.getElementById('advanced-filter-panel');
        
        filterBtn?.addEventListener('click', (e) => {
            e.stopPropagation(); // Empêche le clic de se propager au document
            const isVisible = filterPanel.style.display === 'block';
            filterPanel.style.display = isVisible ? 'none' : 'block';
            filterBtn.classList.toggle('active', !isVisible);
        });

        // Ferme le panneau si on clique en dehors
        document.addEventListener('click', (e) => {
            if (filterPanel && filterPanel.style.display === 'block') {
                if (!filterPanel.contains(e.target) && e.target !== filterBtn) {
                    filterPanel.style.display = 'none';
                    filterBtn.classList.remove('active');
                }
            }
            // Ferme aussi le dropdown "Ajouter"
            const addDropdown = document.getElementById('add-dropdown');
            if(addDropdown && addDropdown.parentElement.classList.contains('show')) {
                if (!addDropdown.parentElement.contains(e.target)) {
                    addDropdown.parentElement.classList.remove('show');
                }
            }
        });
    },

    renderFilterPills() {
        const container = document.getElementById('filter-pills-container');
        if (!container) return;

        container.innerHTML = '';
        const params = new URLSearchParams(window.location.search);
        const activeFilters = [];
        
        const filterLabels = {
            mime_type: 'Type',
            source: 'Source',
            status: 'Statut'
        };

        // Labels pour des valeurs spécifiques
        const valueLabels = {
            'application/pdf': 'PDF',
            'image': 'Image',
            'word': 'Document Word',
            'received': 'Reçu',
            'to_print': 'À imprimer',
            'printed': 'Imprimé',
            'print_error': 'Erreur'
        };

        params.forEach((value, key) => {
            if (filterLabels[key] && value) {
                const label = filterLabels[key];
                const displayValue = valueLabels[value] || value.charAt(0).toUpperCase() + value.slice(1);
                activeFilters.push({ key, value, label, displayValue });
            }
        });

        if (activeFilters.length > 0) {
            activeFilters.forEach(filter => {
                const pill = document.createElement('div');
                pill.className = 'pill';
                pill.innerHTML = `
                    <span class="pill-value">${filter.displayValue}</span>
                    <button class="remove-pill" data-filter-key="${filter.key}" title="Retirer ce filtre">&times;</button>
                `;
                container.appendChild(pill);
            });
            
            const clearAll = document.createElement('button');
            clearAll.className = 'clear-all-pills';
            clearAll.innerHTML = `&times; Effacer tout`;
            container.appendChild(clearAll);

            container.querySelectorAll('.remove-pill').forEach(btn => {
                btn.addEventListener('click', () => this.removeFilter(btn.dataset.filterKey));
            });
            clearAll.addEventListener('click', () => this.removeAllFilters(activeFilters.map(f => f.key)));
        }
    },

    removeFilter(keyToRemove) {
        const params = new URLSearchParams(window.location.search);
        params.delete(keyToRemove);
        window.location.search = params.toString();
    },
    
    removeAllFilters(keysToRemove) {
        const params = new URLSearchParams(window.location.search);
        keysToRemove.forEach(key => params.delete(key));
        window.location.search = params.toString();
    }
};
