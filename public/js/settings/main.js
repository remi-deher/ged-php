// public/js/settings/main.js

import * as Printers from './printers.js';
import * as Tenants from './tenants.js';
import * as Accounts from './accounts.js';
import * as Modals from './modals.js';

// The main initialization function for the settings page.
function init() {
    Printers.init();
    Tenants.init();
    Accounts.init();
    Modals.init();
}

// Run the initialization function once the page's DOM is fully loaded.
document.addEventListener('DOMContentLoaded', init);
