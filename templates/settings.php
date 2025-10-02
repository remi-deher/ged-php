<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réglages de la messagerie - GED</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; max-width: 900px; margin: auto; padding: 2rem; background-color: #f8f9fa; color: #333; }
        .container { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h1, h2 { color: #0056b3; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: .5rem; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .folder-list { list-style: none; padding: 0; }
        .folder-list li { margin-bottom: 0.5rem; }
        .error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: .75rem 1.25rem; margin-bottom: 1rem; border-radius: .25rem; }
    </style>
</head>
<body>
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>

        <h1>⚙️ Réglages de la messagerie</h1>

        <?php if (!empty($errorMessage)): ?>
            <div class="error-message">
                <strong>La connexion a échoué.</strong><br>
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form action="/settings/save" method="post">
            <h2>Configuration de la connexion</h2>

            <div class="form-group">
                <label for="service">Type de messagerie</label>
                <select id="service" name="service" onchange="toggleServiceFields()">
                    <option value="graph" <?= ($settings['service'] ?? 'graph') === 'graph' ? 'selected' : '' ?>>Microsoft 365 (Graph API)</option>
                    <option value="imap" <?= ($settings['service'] ?? '') === 'imap' ? 'selected' : '' ?>>IMAP</option>
                </select>
            </div>

            <div id="graph-fields" style="display: <?= ($settings['service'] ?? 'graph') === 'graph' ? 'block' : 'none' ?>;">
                <h3>Paramètres Microsoft Graph</h3>
                <div class="form-group">
                    <label for="graph_tenant_id">ID du Tenant (Annuaire)</label>
                    <input type="text" id="graph_tenant_id" name="graph_tenant_id" value="<?= htmlspecialchars($settings['graph']['tenant_id'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="graph_client_id">ID de l'Application (Client)</label>
                    <input type="text" id="graph_client_id" name="graph_client_id" value="<?= htmlspecialchars($settings['graph']['client_id'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="graph_client_secret">Secret Client</label>
                    <input type="password" id="graph_client_secret" name="graph_client_secret" value="">
                </div>
                <div class="form-group">
                    <label for="graph_user_email">Adresse e-mail de la boîte à lire</label>
                    <input type="email" id="graph_user_email" name="graph_user_email" value="<?= htmlspecialchars($settings['graph']['user_email'] ?? '') ?>">
                </div>
            </div>
            
            <div id="imap-fields" style="display: <?= ($settings['service'] ?? '') === 'imap' ? 'block' : 'none' ?>;">
                 <h3>Paramètres IMAP</h3>
                <p>La connexion IMAP n'est pas encore implémentée.</p>
            </div>
            
            <?php if (!empty($folders)): ?>
                <h2>Dossiers à synchroniser</h2>
                <div class="form-group">
                    <ul class="folder-list">
                        <?php foreach ($folders as $folder): ?>
                            <li>
                                <input type="checkbox" name="folders[]" value="<?= htmlspecialchars($folder['id']) ?>" id="folder_<?= htmlspecialchars($folder['id']) ?>"
                                    <?= in_array($folder['id'], $settings['folders'] ?? []) ? 'checked' : '' ?>>
                                <label for="folder_<?= htmlspecialchars($folder['id']) ?>"><?= htmlspecialchars($folder['name']) ?></label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <button type="submit">Enregistrer les réglages</button>
        </form>
    </div>

    <script>
        function toggleServiceFields() {
            const service = document.getElementById('service').value;
            document.getElementById('graph-fields').style.display = service === 'graph' ? 'block' : 'none';
            document.getElementById('imap-fields').style.display = service === 'imap' ? 'block' : 'none';
        }
        document.addEventListener('DOMContentLoaded', toggleServiceFields);
    </script>
</body>
</html>
