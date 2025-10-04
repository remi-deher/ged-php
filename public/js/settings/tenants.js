// public/js/settings/tenants.js

GED.settings = GED.settings || {};

GED.settings.tenants = {
    init() {
        document.querySelectorAll('.tenant-header').forEach(header => {
            header.addEventListener('click', (e) => {
                if (e.target.closest('button')) return;
                const card = header.closest('.tenant-card');
                card.classList.toggle('is-open');
                const buttonText = header.querySelector('.btn-text');
                if (buttonText) buttonText.textContent = card.classList.contains('is-open') ? 'Fermer ▲' : 'Gérer ▼';
            });
        });

        const tenantModal = document.getElementById('tenant-modal');
        if(tenantModal) {
            const tenantForm = document.getElementById('tenant-form');
            document.getElementById('btn-show-tenant-form')?.addEventListener('click', () => this.openModal(tenantForm));
            document.querySelectorAll('.btn-edit-tenant').forEach(btn => btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openModal(tenantForm, JSON.parse(btn.dataset.tenant));
            }));
        }
    },
    openModal(form, tenant = null) {
        document.getElementById('tenant-modal-title').innerText = tenant ? 'Gérer le Tenant' : 'Ajouter un nouveau Tenant';
        form.innerHTML = `
            <input type="hidden" name="tenant_id" value="${tenant?.tenant_id || ''}">
            <div class="form-group"><label>Nom du Tenant</label><div class="input-group"><span>🏢</span><input style="padding-left: 30px;" type="text" name="tenant_name" value="${tenant?.tenant_name || ''}" required></div></div>
            <h3>Paramètres Microsoft Graph</h3>
            <div class="form-group"><label>ID du Tenant Microsoft</label><div class="input-group"><span>🔑</span><input style="padding-left: 30px;" type="text" name="graph_tenant_id" value="${tenant?.graph?.tenant_id || ''}" required></div></div>
            <div class="form-group"><label>ID du Client (Application)</label><div class="input-group"><span>💻</span><input style="padding-left: 30px;" type="text" name="graph_client_id" value="${tenant?.graph?.client_id || ''}" required></div></div>
            <div class="form-group"><label>Secret Client</label><div class="input-group"><span>🔒</span><input style="padding-left: 30px;" type="password" name="graph_client_secret" placeholder="${tenant ? 'Laissez vide pour ne pas modifier' : ''}" autocomplete="new-password"></div></div>
            <div class="form-actions">${tenant ? `<form action="/settings/tenant/delete" method="POST" onsubmit="return confirm('Supprimer ce tenant et tous ses comptes ?')"><input type="hidden" name="tenant_id" value="${tenant.tenant_id}"><button type="submit" class="button button-delete">Supprimer</button></form>` : '<span></span>'}<button type="submit" class="button">💾 Enregistrer</button></div>
        `;
        document.getElementById('tenant-modal').style.display = 'flex';
    }
};
