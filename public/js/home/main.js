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
        GED.home.upload.init();
        GED.home.filters.init(); // AJOUT DE CETTE LIGNE

        // Gère les événements globaux de la page comme le simple/double clic
        this.initGlobalEvents();
    },

    initGlobalEvents() {
        document.querySelectorAll('.document-row:not(.folder-row)').forEach(row => {
            let clickTimer = null;
            row.addEventListener('click', (e) => {
                // Ignore les clics sur les éléments interactifs
                if (e.target.closest('button, a, input[type="checkbox"], form')) return;
                
                if (clickTimer === null) {
                    // Premier clic : lance un minuteur
                    clickTimer = setTimeout(() => {
                        clickTimer = null;
                        const docId = row.dataset.docId;
                        if (docId) GED.home.sidebar.openForDocument(docId); // Ouvre la sidebar
                    }, 250);
                } else {
                    // Deuxième clic rapide : annule le minuteur et ouvre la modale
                    clearTimeout(clickTimer);
                    clickTimer = null;
                    const docId = row.dataset.docId;
                    if (docId) GED.home.modal.openForDocument(docId);
                }
            });
        });
    }
};

// Lance l'initialisation quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    GED.home.main.init();
});
