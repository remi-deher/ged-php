// public/js/settings.js

document.addEventListener('DOMContentLoaded', () => {
    // --- Éléments du DOM ---
    const formContainer = document.getElementById('account-form-container');
    const form = document.getElementById('account-form');
    const formTitle = document.getElementById('form-title');
    const btnShowForm = document.getElementById('btn-show-form');
    const formToggleText = document.getElementById('form-toggle-text');
    
    // --- Wizard ---
    const steps = document.querySelectorAll('.form-step');
    const wizardSteps = document.querySelectorAll('.wizard-step');
    const nextButtons = document.querySelectorAll('.btn-next');
    const prevButtons = document.querySelectorAll('.btn-prev');
    let currentStep = 1;

    /**
     * Rafraîchit les icônes Lucide de manière sécurisée.
     */
    const refreshIcons = () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };

    /**
     * Met à jour l'interface du wizard (étapes, couleurs, etc.).
     */
    const updateWizardUI = () => {
        steps.forEach(step => step.classList.remove('active'));
        document.getElementById(`step-${currentStep}`).classList.add('active');

        wizardSteps.forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.remove('active', 'completed'); // Reset classes
            if (stepNumber < currentStep) {
                step.classList.add('completed');
            } else if (stepNumber === currentStep) {
                step.classList.add('active');
            }
        });
        refreshIcons();
    };

    nextButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (currentStep < steps.length) {
                currentStep++;
                updateWizardUI();
            }
        });
    });

    prevButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateWizardUI();
            }
        });
    });
    
    /**
     * Affiche le formulaire pour ajouter/modifier un compte.
     */
    const showForm = () => {
        formContainer.style.display = 'block';
        formToggleText.textContent = 'Annuler';
        btnShowForm.querySelector('i').setAttribute('data-lucide', 'x-circle');
        refreshIcons();
        formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    /**
     * Cache et réinitialise le formulaire.
     */
    const hideForm = () => {
        formContainer.style.display = 'none';
        resetForm();
    };

    btnShowForm.addEventListener('click', () => {
        if (formContainer.style.display === 'block') {
            hideForm();
        } else {
            resetForm(); // Assure que le form est propre avant de l'afficher
            showForm();
        }
    });

    /**
     * Réinitialise complètement le formulaire à son état initial.
     */
    const resetForm = () => {
        formTitle.innerText = 'Ajouter un nouveau compte';
        form.reset();
        document.getElementById('account_id').value = '';
        document.getElementById('graph_client_secret').placeholder = "Laissez vide pour ne pas modifier";
        
        document.getElementById('folder-list').innerHTML = '';
        const folderMsg = document.getElementById('folder-message');
        folderMsg.innerText = '';
        folderMsg.className = '';

        currentStep = 1;
        updateWizardUI();

        formToggleText.textContent = 'Ajouter un nouveau compte';
        btnShowForm.querySelector('i').setAttribute('data-lucide', 'plus-circle');
        refreshIcons();
    };

    /**
     * Prépare le formulaire pour la modification d'un compte existant.
     * @param {object} account - L'objet compte à modifier.
     */
    const editAccount = (account) => {
        resetForm();
        formTitle.innerText = 'Modifier le compte';
        
        // Remplir les champs
        document.getElementById('account_id').value = account.id;
        document.getElementById('account_name').value = account.account_name;
        document.getElementById('graph_tenant_id').value = account.graph.tenant_id;
        document.getElementById('graph_client_id').value = account.graph.client_id;
        document.getElementById('graph_user_email').value = account.graph.user_email;
        document.getElementById('graph_client_secret').value = '';

        showForm();
        currentStep = 2; // Aller directement à l'étape 2 pour l'édition
        updateWizardUI();

        // Stocker les dossiers pré-sélectionnés pour la fonction de test
        btnListFolders.dataset.selectedFolders = JSON.stringify(account.folders || []);
    };
    
    // --- Attacher les écouteurs d'événements pour les boutons "Modifier" ---
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', () => {
            const accountData = JSON.parse(button.dataset.account);
            editAccount(accountData);
        });
    });

    // --- Logique du test de connexion ---
    const btnListFolders = document.getElementById('btn-list-folders');
    btnListFolders.addEventListener('click', async () => {
        const folderList = document.getElementById('folder-list');
        const folderMsg = document.getElementById('folder-message');
        const selectedFolders = JSON.parse(btnListFolders.dataset.selectedFolders || '[]');
        
        // --- VÉRIFICATION CÔTÉ CLIENT ---
        const clientSecretInput = document.getElementById('graph_client_secret');
        if (currentStep === 2 && !clientSecretInput.value) {
            folderMsg.className = 'error';
            folderMsg.innerText = 'Veuillez renseigner le "Secret Client" pour tester la connexion.';
            clientSecretInput.style.borderColor = '#dc3545';
            clientSecretInput.focus();
            return; // Arrêt de l'exécution
        }
        clientSecretInput.style.borderColor = '#ccc'; // Réinitialisation du style

        btnListFolders.disabled = true;
        btnListFolders.classList.add('loading');
        folderList.innerHTML = '';
        folderMsg.innerText = '';
        folderMsg.className = '';

        const formData = new FormData();
        formData.append('tenant_id', document.getElementById('graph_tenant_id').value);
        formData.append('client_id', document.getElementById('graph_client_id').value);
        formData.append('client_secret', clientSecretInput.value);
        formData.append('user_email', document.getElementById('graph_user_email').value);
        
        try {
            const response = await fetch('/settings/ajax/list-folders', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (!response.ok || data.error) throw new Error(data.error || 'Une erreur est survenue.');
            
            currentStep = 3;
            updateWizardUI();

            folderMsg.className = 'success';
            folderMsg.innerText = `Connexion réussie ! ${data.folders.length} dossier(s) trouvé(s).`;
            
            if (data.folders.length > 0) {
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
            folderMsg.className = 'error';
            folderMsg.innerText = `Erreur : ${error.message}`;
        } finally {
            btnListFolders.disabled = false;
            btnListFolders.classList.remove('loading');
            refreshIcons();
        }
    });
});
