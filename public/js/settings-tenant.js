// public/js/settings-tenant.js

document.addEventListener('DOMContentLoaded', () => {

    // --- Gestion des Imprimantes ---
    const printerModal = document.getElementById('printer-modal');
    if (printerModal) {
        const printerForm = document.getElementById('printer-form');
        document.getElementById('btn-show-printer-form').addEventListener('click', () => {
            printerForm.reset();
            document.getElementById('printer_id').value = '';
            document.getElementById('printer-modal-title').innerText = 'Ajouter une imprimante';
            printerModal.style.display = 'flex';
        });

        document.querySelectorAll('.btn-test-printer').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const button = e.currentTarget;
                const printerId = button.dataset.printerId;
                const originalText = button.innerHTML;

                button.innerHTML = '⏳'; // Spinner emoji
                button.disabled = true;
                
                try {
                    const response = await fetch('/settings/printer/test', { method: 'POST', body: new FormData(printerForm) });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.error || 'Erreur inconnue');
                    
                    button.innerHTML = '✅';
                    alert('Succès : ' + data.message);

                } catch (error) {
                    alert('Erreur : ' + error.message);
                    button.innerHTML = originalText;
                } finally {
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }, 3000);
                }
            });
        });
    }

    // --- Gestion des Tenants et Comptes ---
    document.querySelectorAll('.tenant-header').forEach(header => {
        header.addEventListener('click', (e) => {
            if (e.target.closest('button')) return;
            const card = header.closest('.tenant-card');
            const buttonText = header.querySelector('.btn-text');
            card.classList.toggle('is-open');
            if (card.classList.contains('is-open')) {
                buttonText.textContent = 'Fermer ▲';
            } else {
                buttonText.textContent = 'Gérer ▼';
            }
        });
    });

    const tenantModal = document.getElementById('tenant-modal');
    if (tenantModal) {
        const tenantForm = document.getElementById('tenant-form');
        document.getElementById('btn-show-tenant-form').addEventListener('click', () => openTenantModal());
        document.querySelectorAll('.btn-edit-tenant').forEach(btn => btn.addEventListener('click', (e) => { e.stopPropagation(); openTenantModal(JSON.parse(btn.dataset.tenant)); }));

        const openTenantModal = (tenant = null) => {
            document.getElementById('tenant-modal-title').innerText = tenant ? 'Gérer le Tenant' : 'Ajouter un nouveau Tenant';
            // Les icônes sont remplacées par des émojis
            tenantForm.innerHTML = `
                <input type="hidden" name="tenant_id" value="${tenant?.tenant_id || ''}">
                <div class="form-group"><label>Nom du Tenant</label><div class="input-group"><span>🏢</span><input style="padding-left: 30px;" type="text" name="tenant_name" value="${tenant?.tenant_name || ''}" required></div></div>
                <h3>Paramètres Microsoft Graph</h3>
                <div class="form-group"><label>ID du Tenant Microsoft</label><div class="input-group"><span>🔑</span><input style="padding-left: 30px;" type="text" name="graph_tenant_id" value="${tenant?.graph?.tenant_id || ''}" required></div></div>
                <div class="form-group"><label>ID du Client (Application)</label><div class="input-group"><span>💻</span><input style="padding-left: 30px;" type="text" name="graph_client_id" value="${tenant?.graph?.client_id || ''}" required></div></div>
                <div class="form-group"><label>Secret Client</label><div class="input-group"><span>🔒</span><input style="padding-left: 30px;" type="password" name="graph_client_secret" placeholder="${tenant ? 'Laissez vide pour ne pas modifier' : ''}" autocomplete="new-password"></div></div>
                <div class="form-actions">${tenant ? `<form action="/settings/tenant/delete" method="POST" onsubmit="return confirm('Supprimer ce tenant et tous ses comptes ?')"><input type="hidden" name="tenant_id" value="${tenant.tenant_id}"><button type="submit" class="button button-delete">Supprimer</button></form>` : '<span></span>'}<button type="submit" class="button">💾 Enregistrer</button></div>
            `;
            tenantModal.style.display = 'flex';
        };
    }

    const accountModal = document.getElementById('account-modal');
    if(accountModal) {
        const accountForm = document.getElementById('account-form');
        let currentTenantIdForAccount = null;

        const openAccountModal = (tenantId, account = null) => {
            currentTenantIdForAccount = tenantId;
            document.getElementById('account-modal-title').innerText = account ? 'Modifier la boîte mail' : 'Ajouter une boîte mail';

            let printerOptions = '<option value="">-- Imprimante par défaut --</option>';
            (window.printers || []).forEach(p => {
                const isSelected = account?.default_printer_id === p.id ? 'selected' : '';
                printerOptions += `<option value="${p.id}" ${isSelected}>${p.name}</option>`;
            });

            accountForm.innerHTML = `
                <input type="hidden" name="tenant_id" value="${tenantId}"><input type="hidden" name="account_id" value="${account?.id || ''}">
                <div class="form-group"><label>Nom du compte</label><div class="input-group"><span>🏷️</span><input style="padding-left: 30px;" type="text" id="account_name" name="account_name" value="${account?.account_name || ''}" required></div></div>
                <div class="form-group"><label>Adresse e-mail</label><div class="input-group"><span>@</span><input style="padding-left: 30px;" type="email" id="user_email" name="user_email" value="${account?.user_email || ''}" required></div></div>
                <div class="form-group"><label>Imprimante par défaut pour ce compte</label><select name="default_printer_id">${printerOptions}</select></div>
                <hr>
                <div id="folder-selection-area">
                    <h3>Liaison des dossiers</h3>
                    <button type="button" class="button" id="btn-list-folders"><span class="spinner"></span><span class="btn-text">🔗 Lier les dossiers</span></button>
                    <div class="feedback-message" id="folder-message"></div>
                    <div class="folder-list" id="folder-list"></div>
                </div>
                 <hr>
                <div id="automation-rules-area"><h3>🤖 Règles d'impression automatique</h3><div id="rules-list"></div><button type="button" class="button button-secondary" id="btn-add-rule">➕ Ajouter une règle</button></div>
                <div class="form-actions">${account ? `<form action="/settings/account/delete" method="POST" onsubmit="return confirm('Supprimer ce compte ?')"><input type="hidden" name="tenant_id" value="${tenantId}"><input type="hidden" name="account_id" value="${account.id}"><button type="submit" class="button button-delete">Supprimer</button></form>` : '<span></span>'}<button type="submit" class="button" id="btn-save-account">💾 Enregistrer</button></div>
            `;
            if (account?.folders?.length) renderFolderList(account.folders, account.folders);
            if (account?.automation_rules?.length) renderAutomationRules(account.automation_rules);
            accountModal.style.display = 'flex';
            document.getElementById('btn-list-folders').addEventListener('click', () => listFolders(account?.folders || []));
            document.getElementById('btn-add-rule').addEventListener('click', () => addRule());
            accountForm.addEventListener('submit', prepareFormForSubmit);
        };

        document.querySelectorAll('.btn-add-account').forEach(btn => btn.addEventListener('click', (e) => { e.stopPropagation(); openAccountModal(btn.dataset.tenantId); }));
        document.querySelectorAll('.btn-edit-account').forEach(btn => btn.addEventListener('click', (e) => { e.stopPropagation(); openAccountModal(btn.dataset.tenantId, JSON.parse(btn.dataset.account)); }));

        const listFolders = async (selectedMappings = []) => {
            const btn = document.getElementById('btn-list-folders'), folderMsg = document.getElementById('folder-message'), userEmail = document.getElementById('user_email').value;
            if (!userEmail) { folderMsg.className = 'feedback-message error'; folderMsg.innerText = "Veuillez d'abord saisir une adresse e-mail."; return; }
            btn.classList.add('loading'); btn.disabled = true; folderMsg.innerText = ''; folderMsg.className = '';
            const formData = new FormData();
            formData.append('tenant_id', currentTenantIdForAccount); formData.append('user_email', userEmail);
            try {
                const response = await fetch('/settings/ajax/list-folders', { method: 'POST', body: formData });
                const data = await response.json();
                if (!response.ok) throw new Error(data.error);
                folderMsg.className = 'feedback-message success';
                folderMsg.innerText = `Connexion réussie ! ${data.folders.length} dossiers trouvés.`;
                renderFolderList(data.folders, selectedMappings);
            } catch (error) {
                folderMsg.className = 'feedback-message error'; folderMsg.innerText = `Erreur : ${error.message}`;
            } finally {
                btn.classList.remove('loading'); btn.disabled = false;
            }
        };

        const renderFolderList = (foldersFromApi, selectedMappings = []) => {
            const container = document.getElementById('folder-list');
            if (foldersFromApi.length === 0) { container.innerHTML = '<p style="text-align:center; color:#6c757d;">Aucun dossier retourné par l\'API.</p>'; return; }
            
            let destFolderOptions = '<option value="root">Racine de l\'application</option>';
            window.appFolders.forEach(f => { destFolderOptions += `<option value="${f.id}">${f.name}</option>`; });
            destFolderOptions += '<option value="new_folder">--- Créer un nouveau dossier ---</option>';

            let printerOptions = '<option value="">-- Imprimante par défaut --</option>';
            (window.printers || []).forEach(p => { printerOptions += `<option value="${p.id}">${p.name}</option>`; });

            let tableHtml = `<table class="table"><thead><tr><th class="col-checkbox"><input type="checkbox" id="select-all-folders"></th><th>Dossier (Boîte Mail)</th><th>Dossier de Destination (GED)</th><th>Imprimante du dossier</th></tr></thead><tbody>`;
            foldersFromApi.forEach(folder => {
                const mapping = selectedMappings.find(m => m.id === folder.id);
                const isChecked = mapping ? 'checked' : '';
                const appFolder = window.appFolders.find(f => f.id == (mapping ? mapping.destination_folder_id : null));

                tableHtml += `<tr data-folder-id="${folder.id}" data-folder-name="${folder.name}">
                                <td class="col-checkbox"><input type="checkbox" class="folder-checkbox" ${isChecked}></td>
                                <td>${folder.name}</td>
                                <td><select class="destination-folder-select" ${!isChecked ? 'disabled' : ''}>${destFolderOptions}</select></td>
                                <td><select class="folder-printer-select" name="folder_printers[${appFolder?.id}]" ${!isChecked || !appFolder ? 'disabled' : ''}>${printerOptions}</select></td>
                              </tr>`;
            });
            container.innerHTML = tableHtml + `</tbody></table>`;
            
            container.querySelectorAll('tbody tr').forEach(row => {
                const mapping = selectedMappings.find(m => m.id === row.dataset.folderId);
                if (mapping) {
                    row.querySelector('.destination-folder-select').value = mapping.destination_folder_id;
                    const appFolder = window.appFolders.find(f => f.id == mapping.destination_folder_id);
                    if (appFolder) {
                        row.querySelector('.folder-printer-select').value = appFolder.default_printer_id || '';
                    }
                }
            });

            document.getElementById('select-all-folders').addEventListener('change', (e) => container.querySelectorAll('.folder-checkbox').forEach(cb => { cb.checked = e.target.checked; cb.dispatchEvent(new Event('change')); }));
            container.querySelectorAll('.folder-checkbox').forEach(cb => cb.addEventListener('change', (e) => {
                const row = e.target.closest('tr');
                row.querySelectorAll('select').forEach(sel => sel.disabled = !e.target.checked);
            }));
            container.querySelectorAll('.destination-folder-select').forEach(sel => sel.addEventListener('change', (e) => {
                handleFolderCreation(e);
                const row = e.target.closest('tr');
                const printerSelect = row.querySelector('.folder-printer-select');
                printerSelect.name = `folder_printers[${e.target.value}]`;
                printerSelect.disabled = e.target.value === 'root' || e.target.value === 'new_folder';
            }));
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
                const newOpt = new Option(data.name, data.id, false, true);
                window.appFolders.push({ id: data.id, name: data.name, default_printer_id: null });
                document.querySelectorAll('.destination-folder-select').forEach(s => {
                    const clonedOpt = newOpt.cloneNode(true);
                    s.insertBefore(clonedOpt, s.options[s.options.length - 1]);
                });
                sel.value = data.id;
                sel.dispatchEvent(new Event('change'));
            } catch (error) {
                alert(`Erreur : ${error.message}`); sel.value = 'root';
            } finally {
                sel.disabled = false;
            }
        };

        const renderAutomationRules = (rules) => {
            document.getElementById('rules-list').innerHTML = '';
            rules.forEach(rule => addRule(rule));
        };

        const addRule = (rule = null) => {
            const ruleId = rule?.rule_id || `rule_${Date.now()}`;
            const ruleDiv = document.createElement('div');
            ruleDiv.className = 'automation-rule';
            ruleDiv.dataset.ruleId = ruleId;
            ruleDiv.innerHTML = `
                <div class="rule-header"><input type="text" class="rule-name" value="${rule?.rule_name || ''}" placeholder="Nom de la règle"><button type="button" class="button-icon button-delete btn-remove-rule">🗑️</button></div>
                <div class="rule-body"><div class="rule-conditions"></div><div class="rule-actions"><span><strong>Alors :</strong> Imprimer</span><select class="rule-action-print"><option value="attachments" ${rule?.action_print === 'attachments' ? 'selected' : ''}>les pièces jointes</option><option value="body" ${rule?.action_print === 'body' ? 'selected' : ''}>le corps de l'e-mail</option><option value="all" ${rule?.action_print === 'all' ? 'selected' : ''}>les deux</option></select></div><button type="button" class="btn-add-condition">➕ Ajouter une condition</button></div>
            `;
            document.getElementById('rules-list').appendChild(ruleDiv);
            const condContainer = ruleDiv.querySelector('.rule-conditions');
            if (rule?.conditions?.length) {
                rule.conditions.forEach(cond => addCondition(condContainer, cond));
            } else {
                addCondition(condContainer);
            }
            ruleDiv.querySelector('.btn-remove-rule').addEventListener('click', () => ruleDiv.remove());
            ruleDiv.querySelector('.btn-add-condition').addEventListener('click', (e) => addCondition(condContainer));
        };

        const addCondition = (container, condition = null) => {
            const condDiv = document.createElement('div');
            condDiv.className = 'rule-condition';
            condDiv.innerHTML = `
                <span><strong>Si :</strong></span><select class="rule-cond-field"><option value="from" ${condition?.field === 'from' ? 'selected' : ''}>l'expéditeur</option><option value="subject" ${condition?.field === 'subject' ? 'selected' : ''}>l'objet</option></select>
                <select class="rule-cond-operator"><option value="contains" ${condition?.operator === 'contains' ? 'selected' : ''}>contient</option><option value="equals" ${condition?.operator === 'equals' ? 'selected' : ''}>est égal à</option></select>
                <input type="text" class="rule-cond-value" value="${condition?.value || ''}" placeholder="valeur..."><button type="button" class="button-icon button-delete btn-remove-condition">❌</button>
            `;
            container.appendChild(condDiv);
            condDiv.querySelector('.btn-remove-condition').addEventListener('click', () => condDiv.remove());
        };

        const prepareFormForSubmit = (e) => {
            accountForm.querySelectorAll('input[name="folders[]"], input[name^="folder_printers"], input[name="automation_rules"]').forEach(el => el.remove());
            
            document.querySelectorAll('.table tbody tr').forEach(row => {
                if (row.querySelector('.folder-checkbox:checked')) {
                    const mapping = { 
                        id: row.dataset.folderId, 
                        name: row.dataset.folderName, 
                        destination_folder_id: row.querySelector('.destination-folder-select').value 
                    };
                    const input = document.createElement('input');
                    input.type = 'hidden'; 
                    input.name = 'folders[]'; 
                    input.value = JSON.stringify(mapping);
                    accountForm.appendChild(input);
                }
            });

            document.querySelectorAll('.folder-printer-select').forEach(select => {
                if (select.name && select.value && !select.disabled) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = select.name;
                    input.value = select.value;
                    accountForm.appendChild(input);
                }
            });

            const rules = [];
            document.querySelectorAll('.automation-rule').forEach(ruleDiv => {
                const rule = { rule_id: ruleDiv.dataset.ruleId, rule_name: ruleDiv.querySelector('.rule-name').value, action_print: ruleDiv.querySelector('.rule-action-print').value, conditions: [] };
                ruleDiv.querySelectorAll('.rule-condition').forEach(condDiv => {
                    rule.conditions.push({ field: condDiv.querySelector('.rule-cond-field').value, operator: condDiv.querySelector('.rule-cond-operator').value, value: condDiv.querySelector('.rule-cond-value').value });
                });
                rules.push(rule);
            });
            const rulesInput = document.createElement('input');
            rulesInput.type = 'hidden'; rulesInput.name = 'automation_rules'; rulesInput.value = JSON.stringify(rules);
            accountForm.appendChild(rulesInput);
        };
    }
    
    // --- Logique commune de fermeture des modales ---
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        const closeButton = modal.querySelector('.modal-close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }
    });
});
