<?php
// templates/trash.php

// Helper pour l'arborescence des dossiers (nécessaire pour la sidebar)
function renderFolderTree(array $folderTree, ?int $currentFolderId) {
    echo '<ul>';
    foreach ($folderTree as $folder) {
        $isActive = ($currentFolderId == $folder['id']);
        $hasChildren = isset($folder['children']) && !empty($folder['children']);
        echo '<li class="' . ($isActive ? 'active' : '') . '">';
        echo '<a href="/?folder_id=' . $folder['id'] . '">'; // Lien normal
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
    <title>Corbeille - GED</title>
    
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/pages/home.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php require_once __DIR__ . '/parts/navbar.php'; ?>

    <div class="app-layout">
        <aside class="app-sidebar-left">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="/"><i class="fas fa-copy"></i> Tous les fichiers</a></li>
                    <li class="active"><a href="/trash"><i class="fas fa-trash"></i> Corbeille</a></li>
                    <li><a href="/settings"><i class="fas fa-cog"></i> Réglages</a></li>
                </ul>
            </nav>
            <div class="sidebar-separator"></div>
            <div class="folder-tree">
                <?php if (isset($folderTree)): ?>
                    <?php renderFolderTree($folderTree, $currentFolderId); ?>
                <?php endif; ?>
            </div>
        </aside>

        <main id="main-content" class="main-content">
            <div class="content-header">
                <h1>🗑️ Corbeille</h1>
            </div>

            <p>Les documents dans la corbeille sont supprimés définitivement après 30 jours.</p>

            <div class="card files-list-card">
                <div class="card-body">
                    <form id="trash-form" method="POST">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="col-checkbox"><input type="checkbox" id="select-all-checkbox-trash" title="Tout sélectionner"></th>
                                    <th>Nom du fichier</th>
                                    <th>Date de suppression</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($documents) && !empty($documents)): ?>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td class="col-checkbox">
                                                <input type="checkbox" name="doc_ids[]" value="<?= $doc['id'] ?>" class="trash-checkbox">
                                            </td>
                                            <td>
                                                <span style="color: var(--danger-color); margin-right: 8px;">📄</span>
                                                <?= htmlspecialchars($doc['original_filename']) ?>
                                            </td>
                                            <td>
                                                <?= date('d/m/Y à H:i', strtotime($doc['deleted_at'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="empty-state">La corbeille est vide.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <div id="trash-actions" class="trash-actions" style="display:none; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                            <button type="submit" formaction="/document/restore" class="button">
                                🔄 Restaurer la sélection
                            </button>
                            <button type="submit" formaction="/document/force-delete" class="button button-delete" onclick="return confirm('ATTENTION : Cette action est irréversible. Voulez-vous vraiment supprimer définitivement ces documents ?');">
                                ⚠️ Suppression définitive
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="/js/trash.js"></script>
</body>
</html>
