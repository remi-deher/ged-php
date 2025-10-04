// public/js/home/viewSwitcher.js

GED.home = GED.home || {};

GED.home.viewSwitcher = {
    init() {
        const listViewBtn = document.getElementById("list-view-btn");
        const gridViewBtn = document.getElementById("grid-view-btn");
        const listView = document.getElementById("document-list-view");
        const gridView = document.getElementById("document-grid-view");

        if (!listViewBtn || !gridViewBtn || !listView || !gridView) return;

        const switchView = (view) => {
            if (view === "grid") {
                listView.style.display = "none";
                gridView.style.display = "grid";
                listViewBtn.classList.remove("active");
                gridViewBtn.classList.add("active");
            } else {
                gridView.style.display = "none";
                listView.style.display = "block";
                gridViewBtn.classList.remove("active");
                listViewBtn.classList.add("active");
            }
            localStorage.setItem("ged_view_mode", view);
        };
        
        listViewBtn.addEventListener("click", () => switchView("list"));
        gridViewBtn.addEventListener("click", () => switchView("grid"));

        // Applique la vue sauvegardée ou la vue par défaut au chargement
        switchView(localStorage.getItem("ged_view_mode") || "list");
    }
};
