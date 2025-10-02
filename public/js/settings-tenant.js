document.addEventListener('DOMContentLoaded', () => {
    // --- Fonction utilitaire pour rafraîchir les icônes ---
    const refreshIcons = () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };
    
    // --- Initialisation au chargement ---
    refreshIcons();

    // --- Gestion du dépliage des cartes Tenant ---
    document.querySelectorAll('.tenant-header').forEach(header => {
        header.addEventListener('click', (e) => {
            // On ne déplie pas si le clic vient d'un bouton (comme le bouton "Modifier")
            if (e.target.closest('button')) {
                return;
            }

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


    // --- Gestion de la modale Tenant ---
    const tenantModal = document.getElementById('tenant-modal');
    const tenantForm = document.getElementById('tenant-form');
    const btnShowTenantForm = document.getElementById('btn-show-tenant-form');

    const openTenantModal = (tenant = null) => {
        const title = tenant ? 'Gérer le Tenant' : 'Ajouter un nouveau Tenant';
        document.getElementById('tenant-modal-title').innerText = title;

        tenantForm.innerHTML = `
            <input type="hidden" name="tenant_id" value="${tenant?.tenant_id || ''}">
            <div class="form-group">
                <label for="tenant_name">Nom du Tenant (ex: Client A)</label>
                <div class="input-group">
                    <i class="icon" data-lucide="building"></i>
                    <input type="text" id="tenant_name" name="tenant_name" value="${tenant?.tenant_name || ''}" required>
                </div>
            </div>
            <h3>Paramètres Microsoft Graph</h3>
            <div class="form-group">
                <label for="graph_tenant_id">ID du Tenant Microsoft</label>
                <div class="input-group">
                    <i class="icon" data-lucide="key-round"></i>
                    <input type="text" id="graph_tenant_id" name="graph_tenant_id" value="${tenant?.graph?.tenant_id || ''}" required>
                </div>
            </div>
            <div class="form-group">
                <label for="graph_client_id">ID du Client (Application)</label>
                <div class="input-group">
                    <i class="icon" data-lucide="app-window"></i>
                    <input type="text" id="graph_client_id" name="graph_client_id" value="${tenant?.graph?.client_id || ''}" required>
                </div>
            </div>
            <div class="form-group">
                <label for="graph_client_secret">Secret Client</label>
                <div class="input-group">
                    <i class="icon" data-lucide="lock"></i>
                    <input type="password" id="graph_client_secret" name="graph_client_secret" placeholder="${tenant ? 'Laissez vide pour ne pas modifier' : ''}" autocomplete="new-password">
                </div>
            </div>
            <div class="form-actions">
                ${tenant ? `
                <form action="/settings/tenant/delete" method="POST" onsubmit="return confirm('Supprimer ce tenant et tous ses comptes ?')">
                    <input type="hidden" name="tenant_id" value="${tenant.tenant_id}">
                    <button type="submit" class="button btn-delete">Supprimer</button>
                </form>
                ` : '<span></span>'}
                <button type="submit" class="button"><i data-lucide="save"></i> Enregistrer</button>
            </div>
        `;
        refreshIcons();
        tenantModal.style.display = 'flex';
    };

    btnShowTenantForm.addEventListener('click', () => openTenantModal());
    document.querySelectorAll('.btn-edit-tenant').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation(); // Empêche le dépliage de la carte
            const tenantData = JSON.parse(btn.dataset.tenant);
            openTenantModal(tenantData);
        });
    });

    // --- Gestion de la modale Compte ---
    const accountModal = document.getElementById('account-modal');
    const accountForm = document.getElementById('account-form');
    let currentTenantIdForAccount = null;

    const openAccountModal = (tenantId, account = null) => {
        currentTenantIdForAccount = tenantId;
        const title = account ? 'Modifier la boîte mail' : 'Ajouter une boîte mail';
        document.getElementById('account-modal-title').innerText = title;

        accountForm.innerHTML = `
            <input type="hidden" name="tenant_id" value="${tenantId}">
            <input type="hidden" name="account_id" value="${account?.id || ''}">
            <div class="form-group">
                <label for="account_name">Nom du compte (ex: Facturation)</label>
                <div class="input-group">
                    <i class="icon" data-lucide="tag"></i>
                    <input type="text" id="account_name" name="account_name" value="${account?.account_name || ''}" required>
                </div>
            </div>
            <div class="form-group">
                <label for="user_email">Adresse e-mail</label>
                <div class="input-group">
                    <i class="icon" data-lucide="at-sign"></i>
                    <input type="email" id="user_email" name="user_email" value="${account?.user_email || ''}" required>
                </div>
            </div>
            <div id="folder-selection-area">
                <button type="button" class="button" id="btn-list-folders">
                    <span class="spinner"></span>
                    <i class="icon-main" data-lucide="plug-zap"></i>
                    <span class="btn-text">Tester & Lister les dossiers</span>
                </button>
                <div class="feedback-message" id="folder-message"></div>
                <div class="folder-list" id="folder-list"></div>
            </div>
            <div class="form-actions">
                 ${account ? `
                <form action="/settings/account/delete" method="POST" onsubmit="return confirm('Supprimer ce compte ?')">
                    <input type="hidden" name="tenant_id" value="${tenantId}">
                    <input type="hidden" name="account_id" value="${account.id}">
                    <button type="submit" class="button btn-delete">Supprimer</button>
                </form>
                ` : '<span></span>'}
                <button type="submit" class="button" id="btn-save-account" disabled><i data-lucide="save"></i> Enregistrer</button>
            </div>
        `;

        if (account?.folders?.length) {
            // Si on édite un compte qui a déjà des dossiers, on les affiche tout de suite
            // (on ne peut pas connaître leur nom, juste l'ID, donc on affiche un nom générique)
            renderFolderList(account.folders.map(id => ({ id: id, name: `Dossier pré-sélectionné` })), account.folders);
        }

        refreshIcons();
        accountModal.style.display = 'flex';
        
        document.getElementById('btn-list-folders').addEventListener('click', () => listFolders(account?.folders || []));
    };
    
    document.querySelectorAll('.btn-add-account').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const tenantId = btn.dataset.tenantId;
            openAccountModal(tenantId);
        });
    });

    document.querySelectorAll('.btn-edit-account').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const tenantId = btn.dataset.tenantId;
            const accountData = JSON.parse(btn.dataset.account);
            openAccountModal(tenantId, accountData);
        });
    });

    // --- Fonctions AJAX et Rendu ---
    const listFolders = async (selectedFolders = []) => {
        const btn = document.getElementById('btn-list-folders');
        const folderMsg = document.getElementById('folder-message');
        const userEmail = document.getElementById('user_email').value;

        if (!userEmail) {
            folderMsg.className = 'feedback-message error';
            folderMsg.innerText = "Veuillez d'abord saisir une adresse e-mail.";
            return;
        }

        btn.classList.add('loading');
        btn.disabled = true;
        folderMsg.innerText = '';
        folderMsg.className = '';

        const formData = new FormData();
        formData.append('tenant_id', currentTenantIdForAccount);
        formData.append('user_email', userEmail);
        
        try {
            const response = await fetch('/settings/ajax/list-folders', { method: 'POST', body: formData });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error);

            folderMsg.className = 'feedback-message success';
            folderMsg.innerText = `Connexion réussie ! ${data.folders.length} dossiers trouvés.`;
            renderFolderList(data.folders, selectedFolders);

        } catch (error) {
            folderMsg.className = 'feedback-message error';
            folderMsg.innerText = `Erreur : ${error.message}`;
        } finally {
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    };
    
    const renderFolderList = (folders, selected = []) => {
        const folderList = document.getElementById('folder-list');
        folderList.innerHTML = '';
        if (folders.length > 0) {
            folders.forEach(folder => {
                const isChecked = selected.includes(folder.id) ? 'checked' : '';
                const name = folder.name || `Dossier ${folder.id}`;
                folderList.innerHTML += `
                    <li>
                        <input type="checkbox" name="folders[]" value="${folder.id}" id="f_${folder.id}" ${isChecked}>
                        <label for="f_${folder.id}">${name}</label>
                    </li>`;
            });
        }
        document.getElementById('btn-save-account').disabled = false;
    };


    // --- Fermeture des modales ---
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.style.display = 'none';
        });
    });
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay').style.display = 'none';
        });
    });
});
