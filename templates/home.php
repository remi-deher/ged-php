<?php
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
                        <a href="/" class="breadcrumb-link" id="root-dropzone" data-folder-id="root"><i class="fas fa-home"></i></a>
                        <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
                            <?php foreach ($breadcrumbs as $crumb): ?>
                                / <a href="/?folder_id=<?= $crumb['id'] ?>"><?= htmlspecialchars($crumb['name']) ?></a>
                            <?php endforeach; ?>
                        <?php elseif (isset($_GET['q'])): ?>
                            / <span>Recherche pour "<?= htmlspecialchars($_GET['q']) ?>"</span>
                        <?php endif; ?>
                    </h1>
                </div>
                <div class="content-actions">
                     <form action="/folder/create" method="POST" class="create-folder-form">
                        <input type="hidden" name="parent_id" value="<?= htmlspecialchars($currentFolder ? $currentFolder['id'] : '') ?>">
                        <input type="text" name="folder_name" placeholder="Nouveau dossier" required>
                        <button type="submit" class="button"><i class="fas fa-plus"></i></button>
                    </form>
                    <form action="/upload" method="post" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="folder_id" value="<?= htmlspecialchars($currentFolder ? $currentFolder['id'] : '') ?>">
                        <label for="document" class="button"><i class="fas fa-upload"></i> Envoyer</label>
                        <input type="file" name="document" id="document" onchange="this.form.submit()" multiple>
                    </form>
                </div>
            </div>

            <div class="card files-list-card">
                 <div class="card-header">
                     <span>Nom</span>
                     <div class="bulk-actions-container">
                        <form method="POST" id="bulk-action-form">
                            <button type="submit" id="bulk-print-button" class="button button-secondary"><i class="fas fa-print"></i> Imprimer</button>
                            <button type="submit" id="bulk-delete-button" class="button button-delete" formaction="/document/delete" onsubmit="return confirm('Confirmer la mise à la corbeille ?');"><i class="fas fa-trash"></i> Corbeille</button>
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
                                    <th>Nom</th>
                                    <th class="col-size">Taille</th>
                                    <th class="col-date">Date d'ajout</th>
                                    <th class="col-actions"></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (isset($items) && !empty($items)): ?>
                                <?php $status_map = ['received' => ['color' => '#007bff', 'label' => 'Reçu'], 'to_print' => ['color' => '#ffc107', 'label' => 'À imprimer'], 'printed' => ['color' => '#28a745', 'label' => 'Imprimé'], 'print_error' => ['color' => '#dc3545', 'label' => 'Erreur']]; ?>
                                <?php foreach ($items as $item): ?>
                                    <?php if ($item['type'] === 'folder'): ?>
                                        <tr data-folder-id="<?= $item['id'] ?>" class="folder-row dropzone">
                                            <td class="col-checkbox"></td>
                                            <td class="col-icon"><i class="fas fa-folder folder-icon-color"></i></td>
                                            <td><a href="/?folder_id=<?= $item['id'] ?>" class="folder-link"><?= htmlspecialchars($item['name']) ?></a></td>
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
                                <tr><td colspan="6" class="empty-state"><?= (isset($_GET['q'])) ? 'Aucun résultat trouvé.' : 'Ce dossier est vide.'; ?></td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="document-grid-view" class="grid-view-container" style="display:none;">
                        <?php if (isset($items) && !empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                                <?php if ($item['type'] === 'folder'): ?>
                                    <a href="/?folder_id=<?= $item['id'] ?>" class="grid-item folder-row dropzone" data-folder-id="<?= $item['id'] ?>">
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
                            <p class="empty-state"><?= (isset($_GET['q'])) ? 'Aucun résultat trouvé.' : 'Ce dossier est vide.'; ?></p>
                        <?php endif; ?>
                    </div>
                 </div>
            </div>
        </main>

        <aside id="details-sidebar" class="details-sidebar">
             <div class="sidebar-header"><h2 id="sidebar-title">Détails</h2><button id="sidebar-close-btn" class="modal-close">&times;</button></div>
             <div class="sidebar-body">
                 <div id="sidebar-info" class="sidebar-info"><h3>Informations</h3><ul id="sidebar-info-list"></ul></div>
                 <div id="sidebar-attachments" class="sidebar-attachments"><button id="sidebar-attachments-toggle-btn" class="attachments-toggle-btn">Pièces jointes <span class="arrow">▼</span></button><ul id="sidebar-attachments-list" class="sidebar-attachments-list collapsed"></ul></div>
             </div>
        </aside>
    </div>
    
    <div id="document-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 1200px; height: 90vh;">
            <div class="modal-header"><h2 id="modal-title"></h2><button class="modal-close">&times;</button></div>
            <div class="modal-body-split">
                <div id="modal-attachments" class="modal-attachments">
                    <div class="attachments-header"><h3>Pièces jointes</h3></div>
                    <ul id="modal-attachments-list" class="modal-attachments-list"></ul>
                </div>
                <button id="modal-attachments-toggle-btn" title="Masquer les pièces jointes">‹</button>
                <div id="modal-preview" class="modal-preview">
                    <iframe id="modal-preview-iframe" class="modal-preview-iframe"></iframe>
                </div>
            </div>
        </div>
    </div>
    <ul id="context-menu" class="context-menu">
        <li data-action="preview_sidebar">👁️ Aperçu rapide</li>
        <li data-action="preview_modal">📖 Ouvrir en grand</li>
        <li data-action="print">🖨️ Imprimer</li>
        <li data-action="download">📥 Télécharger</li>
        <li data-action="delete" class="separator">🗑️ Mettre à la corbeille</li>
    </ul>
    <div id="toast-container"></div>
    <script src="/js/home.js"></script>
</body>
</html>
