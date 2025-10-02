<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réglages de la messagerie - GED</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; max-width: 900px; margin: auto; padding: 2rem; background-color: #f8f9fa; color: #333; }
        .container { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #0056b3; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: .5rem; font-weight: bold; }
        input, select, button { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #007bff; color: white; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .account-list { list-style-type: none; padding: 0; }
        .account-item { display: flex; justify-content: space-between; align-items: center; padding: 1rem; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 1rem; }
        .account-item .actions { display: flex; gap: 0.5rem; }
        .account-item .actions button { width: auto; font-size: 14px; padding: 5px 10px; }
        .btn-delete { background-color: #dc3545; }
        #form-container { border-top: 2px solid #0056b3; margin-top: 2rem; padding-top: 1rem; }
        .folder-list { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
        .folder-list li { list-style: none; }
    </style>
</head>
<body>
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>
        <h1>⚙️ Réglages de la messagerie</h1>

        <h2>Comptes configurés</h2>
        <div id="account-list">
            <?php if (empty($accounts)): ?>
                <p>Aucun compte n'est configuré pour le moment.</p>
            <?php else: ?>
                <?php foreach($accounts as $account): ?>
                    <div class="account-item">
                        <span><strong><?= htmlspecialchars($account['account_name']) ?></strong> (<?= htmlspecialchars($account['graph']['user_email']) ?>)</span>
                        <div class="actions">
                            <button onclick='editAccount(<?= json_encode($account, JSON_HEX_APOS) ?>)'>Modifier</button>
                            <form action="/settings/account/delete" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer ce compte ?');" style="display:inline;">
                                <input type="hidden" name="account_id" value="<?= htmlspecialchars($account['id']) ?>">
                                <button type="submit" class="btn-delete">Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="form-container">
            <h2 id="form-title">Ajouter un nouveau compte</h2>
            <form id="account-form" action="/settings/account/save" method="post">
                <input type="hidden" id="account_id" name="account_id">
                
                <div class="form-group">
                    <label for="account_name">Nom du compte (ex: Facturation)</label>
                    <input type="text" id="account_name" name="account_name" required>
                </div>

                <h3>Paramètres Microsoft Graph</h3>
                <div class="form-group">
                    <label for="graph_tenant_id">ID du Tenant</label>
                    <input type="text" id="graph_tenant_id" name="graph_tenant_id">
                </div>
                <div class="form-group">
                    <label for="graph_client_id">ID du Client</label>
                    <input type="text" id="graph_client_id" name="graph_client_id">
                </div>
                <div class="form-group">
                    <label for="graph_client_secret">Secret Client</label>
                    <input type="password" id="graph_client_secret" name="graph_client_secret">
                </div>
                <div class="form-group">
                    <label for="graph_user_email">Adresse e-mail</label>
                    <input type="email" id="graph_user_email" name="graph_user_email">
                </div>

                <div class="form-group">
                    <button type="button" id="btn-list-folders" onclick="listFolders()">Tester la connexion & Lister les dossiers</button>
                </div>
                
                <div id="folders-container" class="form-group" style="display:none;">
                    <h3>Dossiers à synchroniser</h3>
                    <div id="folder-list" class="folder-list"></div>
                    <small id="folder-message"></small>
                </div>

                <button type="submit" id="btn-save">Enregistrer</button>
                <button type="button" onclick="resetForm()" style="background-color: #6c757d;">Annuler</button>
            </form>
        </div>
    </div>

<script>
    function editAccount(account) {
        document.getElementById('form-title').innerText = 'Modifier le compte';
        document.getElementById('account_id').value = account.id;
        document.getElementById('account_name').value = account.account_name;
        document.getElementById('graph_tenant_id').value = account.graph.tenant_id;
        document.getElementById('graph_client_id').value = account.graph.client_id;
        document.getElementById('graph_client_secret').value = account.graph.client_secret;
        document.getElementById('graph_user_email').value = account.graph.user_email;
        
        // Si le compte a déjà des dossiers, on les affiche pour modification
        if (account.folders && account.folders.length > 0) {
            listFolders(account.folders);
        } else {
             document.getElementById('folders-container').style.display = 'none';
        }
        
        window.scrollTo(0, document.body.scrollHeight);
    }

    function resetForm() {
        document.getElementById('form-title').innerText = 'Ajouter un nouveau compte';
        document.getElementById('account-form').reset();
        document.getElementById('account_id').value = '';
        document.getElementById('folders-container').style.display = 'none';
        document.getElementById('folder-list').innerHTML = '';
    }

    async function listFolders(selectedFolders = []) {
        const btn = document.getElementById('btn-list-folders');
        const folderContainer = document.getElementById('folders-container');
        const folderList = document.getElementById('folder-list');
        const folderMsg = document.getElementById('folder-message');
        
        btn.disabled = true;
        btn.innerText = 'Connexion en cours...';
        folderList.innerHTML = '';
        folderMsg.innerText = '';

        const formData = new FormData();
        formData.append('tenant_id', document.getElementById('graph_tenant_id').value);
        formData.append('client_id', document.getElementById('graph_client_id').value);
        formData.append('client_secret', document.getElementById('graph_client_secret').value);
        formData.append('user_email', document.getElementById('graph_user_email').value);
        
        try {
            const response = await fetch('/settings/ajax/list-folders', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!response.ok || data.error) {
                throw new Error(data.error || 'Une erreur est survenue.');
            }

            folderContainer.style.display = 'block';
            if (data.folders.length === 0) {
                folderMsg.innerText = 'Aucun dossier trouvé dans cette boîte mail.';
            } else {
                data.folders.forEach(folder => {
                    const isChecked = selectedFolders.includes(folder.id) ? 'checked' : '';
                    folderList.innerHTML += `
                        <li>
                            <input type="checkbox" name="folders[]" value="${folder.id}" id="f_${folder.id}" ${isChecked}>
                            <label for="f_${folder.id}">${folder.name}</label>
                        </li>`;
                });
            }
        } catch (error) {
            folderContainer.style.display = 'block';
            folderMsg.style.color = 'red';
            folderMsg.innerText = `Erreur : ${error.message}`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Tester la connexion & Lister les dossiers';
        }
    }
</script>
</body>
</html>
