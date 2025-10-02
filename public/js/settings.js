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
