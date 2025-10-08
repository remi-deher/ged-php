<?php
// templates/settings_tenant.php

// Helper pour l'arborescence des dossiers, nécessaire pour la sidebar
function renderFolderTree(array $folderTree, ?int $currentFolderId) {
    echo '<ul>';
    foreach ($folderTree as $folder) {
        $isActive = ($currentFolderId == $folder['id']);
        $hasChildren = isset($folder['children']) && !empty($folder['children']);
        echo '<li class="' . ($isActive ? 'active' : '') . '">';
        echo '<a href="/?folder_id=' . $folder['id'] . '">';
        echo '<i class="fas fa-folder"></i> ' . htmlspecialchars($folder['name']);
        echo '</a>';
        if ($hasChildren) {
            renderFolderTree($folder['children'], $currentFolderId);
        }
        echo '</li>';
    }
    echo '</ul>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réglages - GED</title>
    
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/navbar.css">
    <link rel="stylesheet" href="/css/pages/settings.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php require_once __DIR__ . '/parts/navbar.php'; ?>

    <div class="app-layout">
        <aside class="app-sidebar-left">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="/"><i class="fas fa-copy"></i> Tous les fichiers</a></li>
                    <li><a href="/trash"><i class="fas fa-trash"></i> Corbeille</a></li>
                    <li class="active"><a href="/settings"><i class="fas fa-cog"></i> Réglages</a></li>
                </ul>
            </nav>
            <div class="sidebar-separator"></div>
            <div class="folder-tree">
                <?php if (isset($folderTree)): ?>
                    <?php renderFolderTree($folderTree, $currentFolderId ?? null); ?>
                <?php endif; ?>
            </div>
        </aside>

        <main id="main-content" class="main-content">
            <div class="settings-container">

                <div class="settings-block">
                    <h1>🖨️ Gestion des Imprimantes</h1>
                    <div id="printer-list">
                        <?php if (empty($printers)): ?>
                            <p>Aucune imprimante configurée.</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th style="width: 250px;">URI</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($printers as $printer): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($printer['name']) ?></td>
                                            <td class="col-uri" title="<?= htmlspecialchars($printer['uri']) ?>">
                                                <?= htmlspecialchars($printer['uri']) ?>
                                            </td>
                                            <td>
                                                <div class="actions-group">
                                                    <button class="button-icon btn-edit-printer" data-printer='<?= json_encode($printer, JSON_HEX_APOS | JSON_HEX_QUOT) ?>' title="Modifier cette imprimante">✏️</button>
                                                    <button class="button-icon btn-test-printer" data-printer-id="<?= $printer['id'] ?>" title="Envoyer une page de test">🧪</button>
                                                    <form action="/settings/printer/delete" method="POST" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer cette imprimante ?');">
                                                        <input type="hidden" name="printer_id" value="<?= $printer['id'] ?>">
                                                        <button type="submit" class="button-icon button-delete" title="Supprimer">🗑️</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <div class="form-actions">
                        <button type="button" id="btn-show-printer-form" class="button">➕ Ajouter une imprimante</button>
                    </div>
                </div>

                <hr>

                <h1>🏢 Réglages des Tenants & E-mails</h1>

                <div id="tenant-list">
                    <?php if (empty($tenants)): ?>
                        <p>Aucun tenant n'est configuré. Commencez par en ajouter un.</p>
                    <?php else: ?>
                        <?php foreach($tenants as $tenant): ?>
                            <div class="tenant-card" id="tenant-<?= htmlspecialchars($tenant['tenant_id'] ?? '') ?>">
                                <div class="tenant-header btn-toggle-accounts">
                                    <div class="tenant-info">
                                        <h2 class="tenant-name">🏢 <?= htmlspecialchars($tenant['tenant_name'] ?? 'Tenant non nommé') ?></h2>
                                    </div>
                                    <div class="tenant-actions">
                                        <button class="button-icon">
                                            <span class="btn-text">Gérer ▼</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="tenant-accounts-container">
                                    <div class="tenant-settings-actions">
                                        <button class="button-icon btn-edit-tenant" data-tenant='<?= json_encode($tenant, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                            ⚙️ Modifier les paramètres du Tenant
                                        </button>
                                    </div>
                                    <div class="tenant-accounts-list">
                                        <?php if (empty($tenant['accounts'])): ?>
                                            <p class="no-accounts-message">Aucune boîte mail configurée pour ce tenant.</p>
                                        <?php else: ?>
                                            <?php foreach($tenant['accounts'] as $account): ?>
                                                <div class="account-item">
                                                    <div class="account-info">
                                                        <span>
                                                            <strong>📧 <?= htmlspecialchars($account['account_name'] ?? 'Compte non nommé') ?></strong><br>
                                                            <small><?= htmlspecialchars($account['user_email'] ?? 'E-mail non défini') ?></small>
                                                        </span>
                                                    </div>
                                                    <div class="account-actions">
                                                        <button class="button-icon btn-edit-account" data-tenant-id="<?= htmlspecialchars($tenant['tenant_id']) ?>" data-account='<?= json_encode($account, JSON_HEX_APOS | JSON_HEX_QUOT) ?>' title="Modifier ce compte mail">
                                                            ✏️
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="add-account-container">
                                        <button class="button btn-add-account" data-tenant-id="<?= htmlspecialchars($tenant['tenant_id']) ?>">
                                            ➕ Ajouter une boîte mail
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="form-toggle-container" style="text-align:center; margin-top:2rem;">
                    <button id="btn-show-tenant-form" class="button">
                        ➕ Ajouter un Tenant
                    </button>
                </div>
            </div>
        </main>
    </div>

    <div id="printer-modal" class="modal-overlay" style="display:none;"><div class="modal-content"><div class="modal-header"><h2 id="printer-modal-title">Ajouter une imprimante</h2><button class="modal-close">&times;</button></div><div class="modal-body"><form id="printer-form" action="/settings/printer/save" method="POST"></form></div></div></div>
    <div id="tenant-modal" class="modal-overlay" style="display:none;"><div class="modal-content"><div class="modal-header"><h2 id="tenant-modal-title"></h2><button class="modal-close">&times;</button></div><div class="modal-body"><form id="tenant-form" action="/settings/tenant/save" method="POST"></form></div></div></div>
    <div id="account-modal" class="modal-overlay" style="display:none;"><div class="modal-content"><div class="modal-header"><h2 id="account-modal-title"></h2><button class="modal-close">&times;</button></div><div class="modal-body"><form id="account-form" action="/settings/account/save" method="POST"></form></div></div></div>
    <div id="toast-container"></div>

    <script>
        window.appFolders = <?= json_encode($appFolders ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.printers = <?= json_encode($printers ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    
    <script src="/js/common/utils.js" type="module"></script>
    <script src="/js/settings/main.js" type="module"></script> 
</body>
</html>
