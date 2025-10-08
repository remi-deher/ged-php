// public/js/common/utils.js

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

export function formatBytes(bytes, decimals = 2) {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

export function getFileExtension(filename) {
    if (typeof filename !== 'string' || filename.indexOf('.') === -1) {
        return null;
    }
    return filename.split('.').pop().toLowerCase();
}

export function getMimeTypeIcon(filename) {
    const extension = getFileExtension(filename);
    switch (extension) {
        case 'pdf': return '<i class="fas fa-file-pdf" style="color: #D32F2F;"></i>';
        case 'doc':
        case 'docx': return '<i class="fas fa-file-word" style="color: #1976D2;"></i>';
        case 'xls':
        case 'xlsx': return '<i class="fas fa-file-excel" style="color: #388E3C;"></i>';
        case 'png':
        case 'jpg':
        case 'jpeg':
        case 'gif': return '<i class="fas fa-file-image" style="color: #FBC02D;"></i>';
        case 'eml':
        case 'msg': return '<i class="fas fa-envelope" style="color: #00796B;"></i>';
        default: return '<i class="fas fa-file-alt" style="color: #757575;"></i>';
    }
}
