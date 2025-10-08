// public/js/home/printQueue.js
import { showToast } from '../common/utils.js';
let printQueueModal;

export function initPrintQueue() {
    printQueueModal = document.getElementById('print-queue-modal');
    // S'il n'y a pas de section pour la file d'impression sur cette page, on arrête.
    if (!printQueueModal) return;

    // Met à jour le statut au chargement de la page
    updateQueueStatus();

    // Ajoute un écouteur pour le bouton de rafraîchissement
    const refreshBtn = document.getElementById('refresh-print-queue');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', updateQueueStatus);
    }
}

async function updateQueueStatus() {
    const queueBody = document.getElementById('print-queue-body');
    const queueStatus = document.getElementById('print-queue-status');
    if (!queueBody || !queueStatus) return;

    try {
        const response = await fetch('/api/print/queue-status');
        if (!response.ok) throw new Error('Erreur réseau lors de la récupération de la file d\'impression.');
        
        const data = await response.json();
        
        queueStatus.textContent = `Statut : ${data.status || 'Inconnu'}`;
        queueBody.innerHTML = ''; // Vide la liste actuelle

        if (data.queue && data.queue.length > 0) {
            data.queue.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.id}</td>
                    <td>${item.document_name || 'Nom inconnu'}</td>
                    <td>${item.status}</td>
                    <td>${new Date(item.created_at).toLocaleString('fr-FR')}</td>
                    <td>${item.error_message || '—'}</td>
                    <td>
                        <button class="button button-delete" onclick="handleQueueAction('cancel', ${item.id})">Annuler</button>
                        ${item.status === 'error' ? `<button class="button" onclick="handleQueueAction('retry', ${item.id})">Réessayer</button>` : ''}
                    </td>
                `;
                queueBody.appendChild(row);
            });
        } else {
            queueBody.innerHTML = '<tr><td colspan="6" class="text-center">La file d\'impression est vide.</td></tr>';
        }

    } catch (error) {
        console.error('Erreur lors de la mise à jour de la file d\'impression:', error);
        showToast('Impossible de rafraîchir la file d\'impression.', 'error');
    }
}

// Rend la fonction accessible globalement pour les boutons `onclick`
window.handleQueueAction = async function(action, itemId) {
    try {
        const response = await fetch(`/api/print/queue-action`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action, item_id: itemId })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);

        showToast(result.message, 'success');
        updateQueueStatus();
    } catch(error) {
        showToast(`Erreur : ${error.message}`, 'error');
    }
}
