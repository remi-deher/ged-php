// public/js/settings/modals.js

GED.settings = GED.settings || {};

GED.settings.modals = {
    init() {
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });
            modal.querySelector('.modal-close')?.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });
    }
};
