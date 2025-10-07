// public/js/settings/printers.js

let printerForm;

function openModal(printer = null) {
    document.getElementById('printer-modal-title').innerText = printer ? "Modifier l'imprimante" : "Ajouter une imprimante";
    printerForm.innerHTML = `
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
}

async function testPrinter(button) {
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

        button.innerHTML = '✅';
        alert('Succès : ' + data.message);

    } catch (error) {
        alert('Erreur : ' + error.message);
        button.innerHTML = '🧪';
    } finally {
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 3000);
    }
}

export function init() {
    const printerModal = document.getElementById('printer-modal');
    if (!printerModal) return;

    printerForm = document.getElementById('printer-form');
    
    document.getElementById('btn-show-printer-form')?.addEventListener('click', () => openModal());

    document.querySelectorAll('.btn-edit-printer').forEach(btn => {
        btn.addEventListener('click', (e) => openModal(JSON.parse(e.currentTarget.dataset.printer)));
    });

    document.querySelectorAll('.btn-test-printer').forEach(btn => {
        btn.addEventListener('click', (e) => testPrinter(e.currentTarget));
    });
}
