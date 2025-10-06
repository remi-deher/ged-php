<?php
// templates/home.php

// Helpers de formatage
function formatSizeUnits($bytes) {
    if ($bytes === null) return '';
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    if ($bytes > 1) return $bytes . ' bytes';
    if ($bytes == 1) return '1 byte';
    return '0 bytes';
}

function getFileIconClass($mimeType) {
    if ($mimeType === 'folder') return 'fa-folder';
    if (str_contains($mimeType, 'html')) return 'fa-file-alt';
    if (str_contains($mimeType, 'pdf')) return 'fa-file-pdf';
    if (str_contains($mimeType, 'image')) return 'fa-file-image';
    if (str_contains($mimeType, 'word')) return 'fa-file-word';
    return 'fa-file';
}

// Helper récursif pour afficher l'arborescence
function renderFolderTree(array $folderTree, ?int $currentFolderId) {
    echo '<ul>';
    foreach ($folderTree as $folder) {
        $isActive = ($currentFolderId == $folder['id']);
        $hasChildren = isset($folder['children']) && !empty($folder['children']);
        echo '<li class="' . ($isActive ? 'active' : '') . '">';
        echo '<a href="/?folder_id=' . $folder['id'] . '" class="dropzone" data-folder-id="' . $folder['id'] . '">';
        echo '<i class="fas fa-folder"></i> ' . htmlspecialchars($folder['name']);
        echo '</a>';
        if ($hasChildren) {
            renderFolderTree($folder['children'], $currentFolderId);
        }
        echo '</li>';
    }
    echo '</ul>';
}

// Helper pour générer les liens de tri
function getSortLink(string $column, string $title): string {
    $currentSort = $_GET['sort'] ?? 'created_at';
    $currentOrder = $_GET['order'] ?? 'desc';
    
    $queryParams = $_GET;
    $queryParams['sort'] = $column;
    $queryParams['order'] = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
    
    $queryString = http_build_query($queryParams);

    $icon = '';
    if ($currentSort === $column) {
        $icon = $currentOrder === 'asc' ? ' <i class="fas fa-arrow-up"></i>' : ' <i class="fas fa-arrow-down"></i>';
    }
    return "<a href=\"?$queryString\">$title$icon</a>";
}

$currentFolderId = $_GET['folder_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GED - Documents</title>
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/pages/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php require_once __DIR__ . '/parts/navbar.php'; ?>

    <div id="drop-overlay" class="drop-overlay">
        <div class="drop-overlay-content">
            <i class="fas fa-upload"></i>
            <h2>Déposez vos fichiers ici</h2>
        </div>
    </div>

    <div class="app-layout">
        <aside class="app-sidebar-left">
            <nav class="sidebar-nav">
                <ul>
                    <li class="<?= ($currentFolderId === null && basename($_SERVER['SCRIPT_NAME']) === 'index.php') ? 'active' : '' ?>"><a href="/"><i class="fas fa-copy"></i> Tous les fichiers</a></li>
                    <li><a href="/trash"><i class="fas fa-trash"></i> Corbeille</a></li>
                    <li><a href="/settings"><i class="fas fa-cog"></i> Réglages</a></li>
                </ul>
            </nav>
            <div class="sidebar-separator"></div>
            <div class="folder-tree">
                <?php if (isset($folderTree)): ?>
                    <?php renderFolderTree($folderTree, $currentFolderId ? (int)$currentFolderId : null); ?>
                <?php endif; ?>
            </div>
        </aside>

        <main id="main-content" class="main-content">
            <div id="print-queue-dashboard" class="card" style="display: none; margin-bottom: 1rem;">
                <div class="card-header"><h2><i class="fas fa-print"></i> File d'impression</h2></div>
                <div class="card-body">
                    <table class="table">
                        <thead><tr><th>Fichier</th><th>Job ID</th><th>Statut</th><th>Détails</th><th>Actions</th></tr></thead>
                        <tbody id="print-queue-body"></tbody>
                    </table>
                </div>
            </div>

            <div class="content-header">
                <div class="breadcrumb">
                    <h1>
                        <a href="/" class="breadcrumb-link dropzone" id="root-dropzone" data-folder-id="root"><i class="fas fa-home"></i></a>
                        <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
                            <?php foreach ($breadcrumbs as $crumb): ?>
                                / <a href="/?folder_id=<?= $crumb['id'] ?>"><?= htmlspecialchars($crumb['name']) ?></a>
                            <?php endforeach; ?>
                        <?php elseif (isset($_GET['q'])): ?>
                            / <span>Recherche pour "<?= htmlspecialchars($_GET['q']) ?>"</span>
                        <?php endif; ?>
                        
                        <a href="#" id="breadcrumb-add-folder" class="button-icon" title="Nouveau dossier ici">+</a>
                    </h1>
                </div>
                <div class="content-actions">
                     <form id="create-folder-form" action="/folder/create" method="POST" class="create-folder-form" style="display: none;">
                        <input type="hidden" name="parent_id" value="<?= htmlspecialchars($currentFolder ? $currentFolder['id'] : '') ?>">
                        <input type="text" name="folder_name" placeholder="Nouveau dossier" required>
                        <button type="submit" class="button"><i class="fas fa-plus"></i></button>
                    </form>
                    
                    <div class="upload-form">
                        <input type="hidden" name="folder_id" value="<?= htmlspecialchars($currentFolder ? $currentFolder['id'] : '') ?>">
                        <label for="document-upload-input" class="button"><i class="fas fa-upload"></i> Envoyer</label>
                        <input type="file" name="document" id="document-upload-input" multiple style="display: none;">
                    </div>
                </div>
            </div>

            <div class="card files-list-card">
                 <div class="card-header">
                    <div class="header-main-actions">
                         <div class="bulk-actions-container">
                            <form method="POST" id="bulk-action-form">
                                <button type="submit" id="bulk-print-button" class="button button-secondary"><i class="fas fa-print"></i> Imprimer</button>
                                <button type="submit" id="bulk-delete-button" class="button button-delete" formaction="/document/delete" onsubmit="return confirm('Confirmer la mise à la corbeille ?');"><i class="fas fa-trash"></i> Corbeille</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="filter-bar">
                        <form method="GET" action="/" class="filter-form" style="display: contents;">
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($_GET['sort'] ?? 'created_at') ?>">
                            <input type="hidden" name="order" value="<?= htmlspecialchars($_GET['order'] ?? 'desc') ?>">
                            <?php if (isset($_GET['folder_id'])): ?>
                                <input type="hidden" name="folder_id" value="<?= htmlspecialchars($_GET['folder_id']) ?>">
                            <?php endif; ?>
                            <div class="filter-group">
                                <label for="filter-type"><i class="fas fa-file-alt"></i></label>
                                <select name="mime_type" id="filter-type" onchange="this.form.submit()">
                                    <option value="">Type de fichier</option>
                                    <option value="application/pdf" <?= ($_GET['mime_type'] ?? '') == 'application/pdf' ? 'selected' : '' ?>>PDF</option>
                                    <option value="image" <?= ($_GET['mime_type'] ?? '') == 'image' ? 'selected' : '' ?>>Image</option>
                                    <option value="word" <?= ($_GET['mime_type'] ?? '') == 'word' ? 'selected' : '' ?>>Document Word</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="filter-source"><i class="fas fa-satellite-dish"></i></label>
                                <select name="source" id="filter-source" onchange="this.form.submit()">
                                    <option value="">Source</option>
                                    <option value="email" <?= ($_GET['source'] ?? '') == 'email' ? 'selected' : '' ?>>Email</option>
                                    <option value="manual" <?= ($_GET['source'] ?? '') == 'manual' ? 'selected' : '' ?>>Manuel</option>
                                </select>
                            </div>
                            <?php if (!empty($_GET['mime_type']) || !empty($_GET['source'])): 
                                $resetParams = [];
                                if (isset($_GET['folder_id'])) $resetParams['folder_id'] = $_GET['folder_id'];
                                $resetUrl = "/" . (empty($resetParams) ? '' : '?' . http_build_query($resetParams));
                            ?>
                                <a href="<?= $resetUrl ?>" class="button-icon" title="Réinitialiser les filtres"><i class="fas fa-times-circle"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                 </div>
                 <div class="card-body">
                    <div id="document-list-view">
                        <table class="table documents-table">
                            <thead>
                                <tr>
                                    <th class="col-checkbox"><input type="checkbox" id="select-all-checkbox" title="Tout sélectionner"></th>
                                    <th class="col-icon"></th>
                                    <th><?= getSortLink('name', 'Nom') ?></th>
                                    <th>Source</th>
                                    <th class="col-size"><?= getSortLink('size', 'Taille') ?></th>
                                    <th class="col-date"><?= getSortLink('created_at', 'Date d\'ajout') ?></th>
                                    <th class="col-actions"></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (isset($items) && !empty($items)): ?>
                                <?php $status_map = ['received' => ['color' => '#007bff', 'label' => 'Reçu'], 'to_print' => ['color' => '#ffc107', 'label' => 'À imprimer'], 'printed' => ['color' => '#28a745', 'label' => 'Imprimé'], 'print_error' => ['color' => '#dc3545', 'label' => 'Erreur']]; ?>
                                <?php foreach ($items as $item): ?>
                                    <?php if ($item['type'] === 'folder'): ?>
                                        <tr data-folder-id="<?= $item['id'] ?>" class="folder-row dropzone" draggable="true">
                                            <td class="col-checkbox"></td>
                                            <td class="col-icon"><i class="fas fa-folder folder-icon-color"></i></td>
                                            <td><a href="/?folder_id=<?= $item['id'] ?>" class="folder-link"><?= htmlspecialchars($item['name']) ?></a></td>
                                            <td></td>
                                            <td class="col-size">--</td>
                                            <td class="col-date"></td>
                                            <td class="col-actions"></td>
                                        </tr>
                                    <?php else: // Document ?>
                                         <tr data-doc-id="<?= $item['id'] ?>" class="document-row" draggable="true">
                                            <td class="col-checkbox"><input type="checkbox" name="doc_ids[]" value="<?= $item['id'] ?>" class="doc-checkbox" form="bulk-action-form"></td>
                                            <td class="col-icon"><i class="fas <?= getFileIconClass($item['mime_type'] ?? '') ?>"></i></td>
                                            <td class="col-name">
                                                <span class="status-dot" style="background-color: <?= $status_map[$item['status']]['color'] ?? '#6c757d' ?>;" title="<?= $status_map[$item['status']]['label'] ?? 'Inconnu' ?>"></span>
                                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($item['source_details'] ?? 'N/A') ?></td>
                                            <td class="col-size"><?= formatSizeUnits($item['size']) ?></td>
                                            <td class="col-date"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                                            <td class="col-actions">
                                                <div class="document-actions">
                                                    <form action="/document/print" method="POST" class="action-form"><input type="hidden" name="doc_id" value="<?= $item['id'] ?>"><button type="submit" class="button-icon" title="Imprimer"><i class="fas fa-print"></i></button></form>
                                                    <form action="/document/delete" method="POST" class="action-form" onsubmit="return confirm('Confirmer ?');"><input type="hidden" name="doc_ids[]" value="<?= $item['id'] ?>"><button type="submit" class="button-icon button-delete" title="Corbeille"><i class="fas fa-trash"></i></button></form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="empty-state"><?= (isset($_GET['q']) || !empty(array_filter($_GET))) ? 'Aucun résultat trouvé.' : 'Ce dossier est vide.'; ?></td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="document-grid-view" class="grid-view-container" style="display:none;">
                        <?php if (isset($items) && !empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                                <?php if ($item['type'] === 'folder'): ?>
                                    <a href="/?folder_id=<?= $item['id'] ?>" class="grid-item folder-row dropzone" data-folder-id="<?= $item['id'] ?>" draggable="true">
                                        <div class="grid-item-thumbnail"><i class="fas fa-folder folder-icon-color"></i></div>
                                        <div class="grid-item-name" title="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars($item['name']) ?></div>
                                    </a>
                                <?php else: ?>
                                    <div class="grid-item document-row" data-doc-id="<?= $item['id'] ?>" draggable="true">
                                        <div class="grid-item-thumbnail"><i class="fas <?= getFileIconClass($item['mime_type'] ?? '') ?>"></i></div>
                                        <div class="grid-item-name" title="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars($item['name']) ?></div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="empty-state"><?= (isset($_GET['q']) || !empty(array_filter($_GET))) ? 'Aucun résultat trouvé.' : 'Ce dossier est vide.'; ?></p>
                        <?php endif; ?>
                    </div>
                 </div>
            </div>
        </main>

        <aside id="details-sidebar" class="details-sidebar">
             <div class="sidebar-header">
                <h2 id="sidebar-title">Détails</h2>
                <button id="sidebar-close-btn" class="modal-close">&times;</button>
             </div>
             <div class="sidebar-body">
                <div id="sidebar-content-wrapper">
                    </div>
             </div>
        </aside>
    </div>
    
    <div id="document-modal" class="modal-overlay" style="display: none;">
        </div>

    <ul id="context-menu" class="context-menu">
        <li data-action="create-folder">➕ Nouveau dossier</li>
        <li class="separator item-specific"></li>
        <li data-action="preview_sidebar" class="item-specific">👁️ Aperçu rapide</li>
        <li data-action="preview_modal" class="item-specific">📖 Ouvrir en grand</li>
        <li data-action="print" class="item-specific">🖨️ Imprimer</li>
        <li data-action="download" class="item-specific">📥 Télécharger</li>
        <li data-action="move" class="item-specific">➔ Déplacer</li>
        <li data-action="delete" class="separator item-specific">🗑️ Mettre à la corbeille</li>
    </ul>
    
    <div id="toast-container"></div>
    
    <script src="/js/app.js"></script>
    <script src="/js/utils.js"></script>
    <script src="/js/home/sidebar.js"></script>
    <script src="/js/home/modal.js"></script>
    <script src="/js/home/contextMenu.js"></script>
    <script src="/js/home/selection.js"></script>
    <script src="/js/home/viewSwitcher.js"></script>
    <script src="/js/home/dnd.js"></script>
    <script src="/js/home/printQueue.js"></script>
    <script src="/js/home/websocket.js"></script>
    <script src="/js/home/upload.js"></script>
    <script src="/js/home/main.js"></script> 
</body>
</html>
