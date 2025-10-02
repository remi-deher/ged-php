<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réglages de la messagerie - GED</title>
    <link rel="stylesheet" href="/css/style.css">
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

    <script src="/js/settings.js" defer></script>
</body>
</html>
