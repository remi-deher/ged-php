// public/js/home/main.js

// Crée le namespace spécifique à la page d'accueil
GED.home = GED.home || {};

// Module principal qui orchestre l'initialisation
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

        // Gère les événements globaux de la page comme le simple/double clic
        this.initGlobalEvents();
    },

    initGlobalEvents() {
        document.querySelectorAll('.document-row:not(.folder-row)').forEach(row => {
            let clickTimer = null;
            row.addEventListener('click', (e) => {
                if (e.target.closest('button, a, input[type="checkbox"], form')) return;
                
                if (clickTimer === null) {
                    clickTimer = setTimeout(() => {
                        clickTimer = null;
                        const docId = row.dataset.docId;
                        if (docId) GED.home.sidebar.openForDocument(docId);
                    }, 250);
                } else {
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
