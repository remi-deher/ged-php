// public/js/settings/printers.js

GED.settings = GED.settings || {};

GED.settings.printers = {
    init() {
        const printerModal = document.getElementById('printer-modal');
        if (!printerModal) return;

        this.printerForm = document.getElementById('printer-form');
        
        document.getElementById('btn-show-printer-form')?.addEventListener('click', () => this.openModal());

        // Logique pour le bouton "Modifier"
        document.querySelectorAll('.btn-edit-printer').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openModal(JSON.parse(btn.dataset.printer));
            });
        });

        // Logique pour le bouton "Tester"
        document.querySelectorAll('.btn-test-printer').forEach(btn => {
            btn.addEventListener('click', (e) => this.testPrinter(e.currentTarget));
        });
    },
    
    openModal(printer = null) {
        document.getElementById('printer-modal-title').innerText = printer ? "Modifier l'imprimante" : "Ajouter une imprimante";
        this.printerForm.innerHTML = `
            <input type="hidden" name="printer_id" value="${printer?.id || ''}">
            <div class="form-group">
                <label>Nom de l'imprimante</label>
                <div class="input-group">
                    <span>🖨️</span>
                    <input style="padding-left: 30px;" type="text" name="printer_name" value="${printer?.name || ''}" required placeholder="Ex: Ordonnances Bureau 1">
                </div>
            </div>
            <div class="form-group">
                <label>URI de l'imprimante</label>
                <div class="input-group">
                    <span>🔗</span>
                    <input style="padding-left: 30px;" type="text" name="printer_uri" value="${printer?.uri || ''}" required placeholder="ipp://192.168.1.100/printers/MonImprimante">
                </div>
                <small>Doit correspondre à l'URI visible dans l'interface de CUPS.</small>
            </div>
            <div class="form-actions">
                ${printer ? `<form action="/settings/printer/delete" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer cette imprimante ?');" style="display:inline; margin-right: auto;"><input type="hidden" name="printer_id" value="${printer.id}"><button type="submit" class="button button-delete">Supprimer</button></form>` : '<span></span>'}
                <button type="submit" class="button">💾 Enregistrer</button>
            </div>
        `;
        document.getElementById('printer-modal').style.display = 'flex';
    },

    async testPrinter(button) {
        const printerId = button.dataset.printerId;
        const originalText = button.innerHTML;
        button.innerHTML = '⏳';
        button.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('printer_id', printerId);
            const response = await fetch('/settings/printer/test', { method: 'POST', body: formData });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Erreur inconnue');
            
            GED.utils.showToast('Succès : ' + data.message, '✅');
            button.innerHTML = '🧪';

        } catch (error) {
            GED.utils.showToast('Erreur : ' + error.message, '⚠️');
            button.innerHTML = '🧪';
        } finally {
            setTimeout(() => {
                button.disabled = false;
            }, 2000);
        }
    }
};
