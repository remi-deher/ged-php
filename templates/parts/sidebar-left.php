<?php
// templates/parts/sidebar-left.php

/** @var \App\Services\FolderService $folderService */

// Fonction récursive pour afficher les dossiers et leurs enfants
function buildFolderTree(array $folders, $parentId = null, $level = 0) {
    $html = '';
    foreach ($folders as $folder) {
        if ($folder['parent_id'] == $parentId) {
            $html .= '<li>';
            $html .= '<a href="/?folder_id=' . $folder['id'] . '">';
            $html .= str_repeat('&nbsp;&nbsp;', $level); // Indentation
            $html .= '<i class="fas fa-folder"></i> ';
            $html .= htmlspecialchars($folder['name']);
            $html .= '</a>';
            
            // Appel récursif pour les enfants
            $childrenHtml = buildFolderTree($folders, $folder['id'], $level + 1);
            if ($childrenHtml) {
                $html .= '<ul>' . $childrenHtml . '</ul>';
            }
            $html .= '</li>';
        }
    }
    return $html;
}

$allFolders = $folderService->getAllFolders();
?>

<div class="sidebar-section">
    <h4>Dossiers</h4>
    <ul class="folder-tree">
        <?php
        // Appel de la fonction pour construire l'arborescence
        echo buildFolderTree($allFolders);
        ?>
    </ul>
</div>

<div class="sidebar-section">
    <h4>Actions Rapides</h4>
    <ul>
        <li><a href="/trash"><i class="fas fa-trash"></i> Corbeille</a></li>
    </ul>
</div>
