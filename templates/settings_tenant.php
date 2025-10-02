<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réglages - GED</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/settings.css">
    <script defer src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
</head>
<body>
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>
        <div class="settings-container">

            <div class="settings-block">
                <h1><i data-lucide="printer"></i> Gestion des Imprimantes</h1>
                <div id="printer-list">
                    <?php if (empty($printers)): ?>
                        <p>Aucune imprimante configurée.</p>
                    <?php else: ?>
                        <table class="folder-table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>URI</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($printers as $printer): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($printer['name']) ?></td>
                                        <td><?= htmlspecialchars($printer['uri']) ?></td>
                                        <td class="col-actions" style="text-align: right;">
                                            <button class="button-icon btn-test-printer" data-printer-id="<?= $printer['id'] ?>" title="Envoyer une page de test"><i data-lucide="file-check"></i></button>
                                            <form action="/settings/printer/delete" method="POST" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer cette imprimante ?');">
                                                <input type="hidden" name="printer_id" value="<?= $printer['id'] ?>">
                                                <button type="submit" class="button-icon btn-delete" title="Supprimer"><i data-lucide="trash-2"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <div class="form-actions" style="justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" id="btn-show-printer-form" class="button"><i data-lucide="plus"></i> Ajouter une imprimante</button>
                </div>
            </div>

            <hr>

            <h1><i data-lucide="building"></i> Réglages des Tenants</h1>

            <div id="tenant-list">
                <?php if (empty($tenants)): ?>
                    <p>Aucun tenant n'est configuré. Commencez par en ajouter un.</p>
                <?php else: ?>
                    <?php foreach($tenants as $tenant): ?>
                        <div class="tenant-card" id="tenant-<?= htmlspecialchars($tenant['tenant_id'] ?? '') ?>">
                            <div class="tenant-header btn-toggle-accounts">
                                <div class="tenant-info">
                                    <i data-lucide="building"></i>
                                    <h2 class="tenant-name"><?= htmlspecialchars($tenant['tenant_name'] ?? 'Tenant non nommé') ?></h2>
                                </div>
                                <div class="tenant-actions">
                                    <button class="button-icon">
                                        <i data-lucide="chevron-down"></i>
                                        <span class="btn-text">Gérer</span>
                                    </button>
                                </div>
                            </div>
                            <div class="tenant-accounts-container">
                                <div class="tenant-settings-actions">
                                    <button class="button-icon btn-edit-tenant" data-tenant='<?= json_encode($tenant, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                        <i data-lucide="settings-2"></i> Modifier les paramètres du Tenant
                                    </button>
                                </div>
                                <div class="tenant-accounts-list">
                                    <?php if (empty($tenant['accounts'])): ?>
                                        <p class="no-accounts-message">Aucune boîte mail configurée pour ce tenant.</p>
                                    <?php else: ?>
                                        <?php foreach($tenant['accounts'] as $account): ?>
                                            <div class="account-item">
                                                <div class="account-info">
                                                    <i data-lucide="mail"></i>
                                                    <span>
                                                        <strong><?= htmlspecialchars($account['account_name'] ?? 'Compte non nommé') ?></strong><br>
                                                        <small><?= htmlspecialchars($account['user_email'] ?? 'E-mail non défini') ?></small>
                                                    </span>
                                                </div>
                                                <div class="account-actions">
                                                    <button class="button-icon btn-edit-account" data-tenant-id="<?= htmlspecialchars($tenant['tenant_id']) ?>" data-account='<?= json_encode($account, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                                        <i data-lucide="edit-3"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="add-account-container">
                                    <button class="button btn-add-account" data-tenant-id="<?= htmlspecialchars($tenant['tenant_id']) ?>">
                                        <i data-lucide="plus"></i> Ajouter une boîte mail
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="form-toggle-container">
                <button id="btn-show-tenant-form" class="button">
                    <i data-lucide="plus-circle"></i> Ajouter un Tenant
                </button>
            </div>

            <div id="printer-modal" class="modal-overlay" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="printer-modal-title">Ajouter une imprimante</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="printer-form" action="/settings/printer/save" method="POST">
                            <input type="hidden" name="printer_id" id="printer_id">
                            <div class="form-group">
                                <label for="printer_name">Nom de l'imprimante</label>
                                <input type="text" id="printer_name" name="printer_name" required placeholder="Ex: Compta HP LaserJet">
                                <small>Ce nom sera utilisé pour identifier l'imprimante dans l'application et sur CUPS.</small>
                            </div>
                            <div class="form-group">
                                <label for="printer_uri">URI de l'imprimante</label>
                                <input type="text" id="printer_uri" name="printer_uri" required placeholder="ipp://adresse-ip/printers/NomImprimante">
                                <small>Trouvable dans l'interface de CUPS. Ex: ipp://192.168.1.100/printers/MonImprimante</small>
                            </div>
                            <div class="form-actions" style="justify-content: flex-end;">
                                <button type="submit" class="button"><i data-lucide="save"></i> Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div id="tenant-modal" class="modal-overlay" style="display:none;"><div class="modal-content"><div class="modal-header"><h2 id="tenant-modal-title"></h2><button class="modal-close">&times;</button></div><div class="modal-body"><form id="tenant-form" action="/settings/tenant/save" method="POST"></form></div></div></div>
            <div id="account-modal" class="modal-overlay" style="display:none;"><div class="modal-content"><div class="modal-header"><h2 id="account-modal-title"></h2><button class="modal-close">&times;</button></div><div class="modal-body"><form id="account-form" action="/settings/account/save" method="POST"></form></div></div></div>
        </div>
    </div>
    <script>
        window.appFolders = <?= json_encode($appFolders ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.printers = <?= json_encode($printers ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="/js/settings-tenant.js"></script>
</body>
</html>
