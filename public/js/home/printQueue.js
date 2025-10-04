// public/js/home/printQueue.js

GED.home = GED.home || {};

GED.home.printQueue = {
    init() {
        this.queueBody = document.getElementById("print-queue-body");
        this.dashboard = document.getElementById("print-queue-dashboard");
        if (!this.queueBody || !this.dashboard) return;
        
        this.updateQueueStatus();
        setInterval(() => this.updateQueueStatus(), 7000);
    },
    async performAction(url, docId, button, successIcon, originalIcon) {
        button.disabled = true;
        button.textContent = '...';
        const formData = new FormData();
        formData.append('doc_id', docId);
        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Erreur inconnue');
            GED.utils.showToast(result.message, successIcon);
            this.updateQueueStatus();
        } catch (error) {
            GED.utils.showToast(`Erreur : ${error.message}`, '⚠️');
            button.disabled = false;
            button.textContent = originalIcon;
        }
    },
    async updateQueueStatus() {
        try {
            const response = await fetch('/print-queue/status');
            if (!response.ok) throw new Error('Impossible de récupérer le statut de la file d\'impression.');
            const queue = await response.json();
            
            const activeJobs = queue.filter(job => job.status !== 'Terminé');

            if (activeJobs.length > 0) {
                this.dashboard.style.display = 'block';
                this.queueBody.innerHTML = '';
                activeJobs.forEach(job => {
                    const row = document.createElement('tr');
                    row.dataset.docId = job.id;
                    let statusHtml = `<span>${job.status}</span>`;
                    let actionsHtml = `<button class="button-icon button-delete btn-cancel-print" title="Annuler">❌</button>`;

                    if (job.status === 'Erreur') {
                        statusHtml = `<span style="color: var(--danger-color); font-weight: bold;">⚠️ ${job.status}</span>`;
                        actionsHtml = `<button class="button-icon btn-clear-error" title="Effacer l'erreur">🗑️</button>`;
                    }

                    row.innerHTML = `<td>${job.filename || 'N/A'}</td><td>${job.job_id || 'N/A'}</td><td>${statusHtml}</td><td>${job.error || 'Aucun détail'}</td><td>${actionsHtml}</td>`;
                    this.queueBody.appendChild(row);
                });

                this.queueBody.querySelectorAll('.btn-cancel-print').forEach(btn => {
                    btn.addEventListener('click', e => this.performAction('/document/cancel-print', e.currentTarget.closest('tr').dataset.docId, e.currentTarget, '🗑️', '❌'));
                });
                this.queueBody.querySelectorAll('.btn-clear-error').forEach(btn => {
                    btn.addEventListener('click', e => this.performAction('/document/clear-print-error', e.currentTarget.closest('tr').dataset.docId, e.currentTarget, '✨', '🗑️'));
                });
            } else {
                this.dashboard.style.display = 'none';
            }
        } catch (error) {
            this.dashboard.style.display = 'block';
            this.queueBody.innerHTML = `<tr><td colspan="5" class="empty-state" style="color: var(--danger-color);">${error.message}</td></tr>`;
        }
    }
};
