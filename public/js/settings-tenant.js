document.addEventListener('DOMContentLoaded', () => {
    const refreshIcons = () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };
    refreshIcons();

    document.querySelectorAll('.tenant-header').forEach(header => {
        header.addEventListener('click', (e) => {
            if (e.target.closest('button')) return;
            const card = header.closest('.tenant-card');
            const button = header.querySelector('.button-icon');
            const icon = button.querySelector('i');
            const text = button.querySelector('.btn-text');
            card.classList.toggle('is-open');
            if (card.classList.contains('is-open')) {
                icon.setAttribute('data-lucide', 'chevron-up');
                text.textContent = 'Fermer';
            } else {
                icon.setAttribute('data-lucide', 'chevron-down');
                text.textContent = 'Gérer';
            }
            refreshIcons();
        });
    });

    const tenantModal = document.getElementById('tenant-modal');
    const tenantForm = document.getElementById('tenant-form');
    document.getElementById('btn-show-tenant-form').addEventListener('click', () => openTenantModal());
    document.querySelectorAll('.btn-edit-tenant').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            openTenantModal(JSON.parse(btn.dataset.tenant));
        });
    });

    const openTenantModal = (tenant = null) => {
        document.getElementById('tenant-modal-title').innerText = tenant ? 'Gérer le Tenant' : 'Ajouter un nouveau Tenant';
        tenantForm.innerHTML = `
            <input type="hidden" name="tenant_id" value="${tenant?.tenant_id || ''}">
            <div class="form-group"><label>Nom du Tenant</label><div class="input-group"><i class="icon" data-lucide="building"></i><input type="text" name="tenant_name" value="${tenant?.tenant_name || ''}" required></div></div>
            <h3>Paramètres Microsoft Graph</h3>
            <div class="form-group"><label>ID du Tenant Microsoft</label><div class="input-group"><i class="icon" data-lucide="key-round"></i><input type="text" name="graph_tenant_id" value="${tenant?.graph?.tenant_id || ''}" required></div></div>
            <div class="form-group"><label>ID du Client (Application)</label><div class="input-group"><i class="icon" data-lucide="app-window"></i><input type="text" name="graph_client_id" value="${tenant?.graph?.client_id || ''}" required></div></div>
            <div class="form-group"><label>Secret Client</label><div class="input-group"><i class="icon" data-lucide="lock"></i><input type="password" name="graph_client_secret" placeholder="${tenant ? 'Laissez vide pour ne pas modifier' : ''}" autocomplete="new-password"></div></div>
            <div class="form-actions">${tenant ? `<form action="/settings/tenant/delete" method="POST" onsubmit="return confirm('Supprimer ce tenant et tous ses comptes ?')"><input type="hidden" name="tenant_id" value="${tenant.tenant_id}"><button type="submit" class="button btn-delete">Supprimer</button></form>` : '<span></span>'}<button type="submit" class="button"><i data-lucide="save"></i> Enregistrer</button></div>
        `;
        refreshIcons();
        tenantModal.style.display = 'flex';
    };

    const accountModal = document.getElementById('account-modal');
    const accountForm = document.getElementById('account-form');
    let currentTenantIdForAccount = null;

    const openAccountModal = (tenantId, account = null) => {
        currentTenantIdForAccount = tenantId;
        document.getElementById('account-modal-title').innerText = account ? 'Modifier la boîte mail' : 'Ajouter une boîte mail';
        accountForm.innerHTML = `
            <input type="hidden" name="tenant_id" value="${tenantId}"><input type="hidden" name="account_id" value="${account?.id || ''}">
            <div class="form-group"><label>Nom du compte</label><div class="input-group"><i class="icon" data-lucide="tag"></i><input type="text" id="account_name" name="account_name" value="${account?.account_name || ''}" required></div></div>
            <div class="form-group"><label>Adresse e-mail</label><div class="input-group"><i class="icon" data-lucide="at-sign"></i><input type="email" id="user_email" name="user_email" value="${account?.user_email || ''}" required></div></div>
            <div id="folder-selection-area">
                <button type="button" class="button" id="btn-list-folders"><span class="spinner"></span><i class="icon-main" data-lucide="plug-zap"></i><span class="btn-text">Tester & Lister les dossiers</span></button>
                <div class="feedback-message" id="folder-message"></div><div class="folder-list" id="folder-list"></div>
            </div>
            <div class="form-actions">${account ? `<form action="/settings/account/delete" method="POST" onsubmit="return confirm('Supprimer ce compte ?')"><input type="hidden" name="tenant_id" value="${tenantId}"><input type="hidden" name="account_id" value="${account.id}"><button type="submit" class="button btn-delete">Supprimer</button></form>` : '<span></span>'}<button type="submit" class="button" id="btn-save-account" disabled><i data-lucide="save"></i> Enregistrer</button></div>
        `;
        if (account?.folders?.length) {
            renderFolderList(account.folders, account.folders);
        }
        refreshIcons();
        accountModal.style.display = 'flex';
        document.getElementById('btn-list-folders').addEventListener('click', () => listFolders(account?.folders || []));
    };

    document.querySelectorAll('.btn-add-account').forEach(btn => btn.addEventListener('click', (e) => { e.stopPropagation(); openAccountModal(btn.dataset.tenantId); }));
    document.querySelectorAll('.btn-edit-account').forEach(btn => btn.addEventListener('click', (e) => { e.stopPropagation(); openAccountModal(btn.dataset.tenantId, JSON.parse(btn.dataset.account)); }));

    const listFolders = async (selectedMappings = []) => {
        const btn = document.getElementById('btn-list-folders'), folderMsg = document.getElementById('folder-message'), userEmail = document.getElementById('user_email').value;
        if (!userEmail) { folderMsg.className = 'feedback-message error'; folderMsg.innerText = "Veuillez d'abord saisir une adresse e-mail."; return; }
        btn.classList.add('loading'); btn.disabled = true; folderMsg.innerText = ''; folderMsg.className = '';
        const formData = new FormData();
        formData.append('tenant_id', currentTenantIdForAccount);
        formData.append('user_email', userEmail);
        try {
            const response = await fetch('/settings/ajax/list-folders', { method: 'POST', body: formData });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error);
            folderMsg.className = 'feedback-message success';
            folderMsg.innerText = `Connexion réussie ! ${data.folders.length} dossiers trouvés.`;
            renderFolderList(data.folders, selectedMappings);
        } catch (error) {
            folderMsg.className = 'feedback-message error';
            folderMsg.innerText = `Erreur : ${error.message}`;
        } finally {
            btn.classList.remove('loading'); btn.disabled = false;
        }
    };

    const renderFolderList = (foldersFromApi, selectedMappings = []) => {
        const container = document.getElementById('folder-list');
        if (foldersFromApi.length === 0) { container.innerHTML = '<p style="text-align:center; color:#6c757d;">Aucun dossier retourné par l\'API.</p>'; return; }
        let options = '<option value="root">Racine de l\'application</option>';
        window.appFolders.forEach(f => { options += `<option value="${f.id}">${f.name}</option>`; });
        options += '<option value="new_folder">--- Créer un nouveau dossier ---</option>';
        let tableHtml = `<table class="folder-table"><thead><tr><th class="col-checkbox"><input type="checkbox" id="select-all-folders"></th><th>Dossier (Boîte Mail)</th><th>Dossier de Destination (Application)</th></tr></thead><tbody>`;
        foldersFromApi.forEach(folder => {
            const mapping = selectedMappings.find(m => m.id === folder.id);
            const isChecked = mapping ? 'checked' : '';
            tableHtml += `<tr data-folder-id="${folder.id}" data-folder-name="${folder.name}"><td class="col-checkbox"><input type="checkbox" class="folder-checkbox" ${isChecked}></td><td><label>${folder.name}</label></td><td><select class="destination-folder-select" ${!isChecked ? 'disabled' : ''}>${options}</select></td></tr>`;
        });
        container.innerHTML = tableHtml + `</tbody></table>`;
        container.querySelectorAll('tbody tr').forEach(row => {
            const mapping = selectedMappings.find(m => m.id === row.dataset.folderId);
            if (mapping) row.querySelector('.destination-folder-select').value = mapping.destination_folder_id;
        });
        document.getElementById('select-all-folders').addEventListener('change', (e) => container.querySelectorAll('.folder-checkbox').forEach(cb => { cb.checked = e.target.checked; cb.closest('tr').querySelector('select').disabled = !e.target.checked; }));
        container.querySelectorAll('.folder-checkbox').forEach(cb => cb.addEventListener('change', (e) => e.target.closest('tr').querySelector('select').disabled = !e.target.checked));
        container.querySelectorAll('.destination-folder-select').forEach(sel => sel.addEventListener('change', handleFolderCreation));
        document.getElementById('btn-save-account').disabled = false;
    };

    const handleFolderCreation = async (e) => {
        if (e.target.value !== 'new_folder') return;
        const newName = prompt("Entrez le nom du nouveau dossier :");
        if (!newName?.trim()) { e.target.value = 'root'; return; }
        const sel = e.target; sel.disabled = true;
        try {
            const fd = new FormData(); fd.append('folder_name', newName.trim());
            const res = await fetch('/settings/ajax/create-folder', { method: 'POST', body: fd });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error);
            const newOpt = new Option(data.name, data.id);
            window.appFolders.push({ id: data.id, name: data.name });
            document.querySelectorAll('.destination-folder-select').forEach(s => s.add(newOpt.cloneNode(true), s.options[s.options.length - 1]));
            sel.value = data.id;
        } catch (error) {
            alert(`Erreur : ${error.message}`);
            sel.value = 'root';
        } finally {
            sel.disabled = false;
        }
    };

    accountForm.addEventListener('submit', (e) => {
        accountForm.querySelectorAll('input[name="folders[]"]').forEach(el => el.remove());
        document.querySelectorAll('.folder-table tbody tr').forEach(row => {
            if (row.querySelector('.folder-checkbox:checked')) {
                const mapping = { id: row.dataset.folderId, name: row.dataset.folderName, destination_folder_id: row.querySelector('.destination-folder-select').value };
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'folders[]'; input.value = JSON.stringify(mapping);
                accountForm.appendChild(input);
            }
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(modal => modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; }));
    document.querySelectorAll('.modal-close').forEach(btn => btn.addEventListener('click', () => btn.closest('.modal-overlay').style.display = 'none'));
});
