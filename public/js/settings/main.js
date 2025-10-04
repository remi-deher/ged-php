// public/js/settings/main.js

GED.settings = GED.settings || {};

GED.settings.init = function() {
    GED.settings.printers.init();
    GED.settings.tenants.init();
    GED.settings.accounts.init();
    GED.settings.modals.init();
};

document.addEventListener('DOMContentLoaded', () => {
    GED.settings.init();
});
