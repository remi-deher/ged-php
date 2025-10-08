// public/js/utils.js

/**
 * Crée le conteneur pour les notifications (toasts) s'il n'existe pas.
 * @returns {HTMLElement} Le conteneur de toasts.
 */
function createToastContainer() {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    return container;
}

/**
 * Affiche une notification (toast) à l'écran.
 * @param {string} message Le message à afficher.
 * @param {string} type Le type de toast ('success', 'error', 'info').
 * @param {number} duration La durée d'affichage en millisecondes.
 */
export function showToast(message, type = 'success', duration = 4000) {
    const container = createToastContainer();
    const toast = document.createElement('div');
    // Ajoute une classe en fonction du type pour la couleur
    toast.className = `toast toast-${type}`;

    let iconClass;
    switch(type) {
        case 'error':
            iconClass = 'fas fa-times-circle';
            break;
        case 'info':
            iconClass = 'fas fa-info-circle';
            break;
        case 'success':
        default:
            iconClass = 'fas fa-check-circle';
            break;
    }

    toast.innerHTML = `<i class="toast-icon ${iconClass}"></i> <span class="toast-message">${message}</span>`;
    
    container.appendChild(toast);

    // Animation d'apparition
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);

    // Animation de disparition
    setTimeout(() => {
        toast.classList.remove('show');
        // Supprime l'élément du DOM après la fin de l'animation
        toast.addEventListener('transitionend', () => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        });
    }, duration);
}
