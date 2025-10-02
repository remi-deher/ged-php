<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réglages Multi-Tenants - GED</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/settings.css">
    <script defer src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
</head>
<body>
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>
        <div class="settings-container">
            <h1>⚙️ Réglages des Tenants</h1>

            <div id="tenant-list">
                <?php if (empty($tenants)): ?>
                    <p>Aucun tenant n'est configuré. Commencez par en ajouter un.</p>
                <?php else: ?>
                    <?php foreach($tenants as $tenant): ?>
                        <div class="tenant-card" id="tenant-<?= htmlspecialchars($tenant['tenant_id'] ?? '') ?>">
                            <div class="tenant-header btn-toggle-accounts"> <div class="tenant-info">
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

            <div id="tenant-modal" class="modal-overlay" style="display:none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="tenant-modal-title">Ajouter un Tenant</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="tenant-form" action="/settings/tenant/save" method="POST"></form>
                    </div>
                </div>
            </div>

             <div id="account-modal" class="modal-overlay" style="display:none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="account-modal-title">Ajouter une boîte mail</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="account-form" action="/settings/account/save" method="POST"></form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/js/settings-tenant.js"></script>
    <script>
        window.addEventListener('load', () => {
             if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
