// public/js/home/selection.js

GED.home = GED.home || {};

GED.home.selection = {
    init() {
        const mainCheckbox = document.getElementById("select-all-checkbox");
        const docCheckboxes = document.querySelectorAll(".doc-checkbox");
        
        this.updateButtonVisibility = () => {
            const anyChecked = Array.from(docCheckboxes).some(checkbox => checkbox.checked);
            document.getElementById("bulk-delete-button")?.style.setProperty('display', anyChecked ? 'inline-flex' : 'none');
            document.getElementById("bulk-print-button")?.style.setProperty('display', anyChecked ? 'inline-flex' : 'none');
        };

        mainCheckbox?.addEventListener("change", (event) => {
            docCheckboxes.forEach(checkbox => {
                if (checkbox.closest("tr")?.style.display !== "none") {
                    checkbox.checked = event.target.checked;
                }
            });
            this.updateButtonVisibility();
        });

        docCheckboxes.forEach(checkbox => checkbox.addEventListener("change", this.updateButtonVisibility));
        this.updateButtonVisibility();
    }
};
