<?php
// templates/settings_tenant.php

// Helper pour l'arborescence des dossiers
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
    <link rel="stylesheet" href="/css/pages/home.css"> <link rel="stylesheet" href="/css/pages/settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/css/navbar.css">
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
                    <?php renderFolderTree($folderTree, $currentFolderId); ?>
                <?php endif; ?>
            </div>
        </aside>

        <main id="main-content" class="main-content">
            <div class="settings-container">
                </div>
        </main>
    </div>

    <div id="toast-container"></div>

    <script>
        // --- START OF CORRECTION ---
        // Initialize the global GED object so that our modules can attach to it.
        window.GED = window.GED || {};
        // --- END OF CORRECTION ---

        window.appFolders = <?= json_encode($appFolders ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        window.printers = <?= json_encode($printers ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    
    <script src="/js/settings/main.js" type="module"></script> 
</body>
</html>
