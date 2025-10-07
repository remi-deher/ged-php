<?php
// templates/parts/navbar.php

/** @var array $currentFolder */
/** @var App\Services\FolderService $folderService */

// On s'assure que les variables nécessaires sont disponibles
if (!isset($currentFolder) || !isset($folderService)) {
    // Fallback pour éviter les erreurs si les variables ne sont pas passées
    $currentFolder = null; 
    global $folderService; // Tente de récupérer depuis la portée globale si possible
}
?>

<header class="app-header">
    <div class="header-left">
        <div class="logo">
            <img src="/img/logo.svg" alt="Logo">
            <span>GED</span>
        </div>
        
        <nav class="breadcrumb-nav">
            <ol class="breadcrumb">
                <?php if ($currentFolder && $currentFolder['id'] !== null): ?>
                    <?php 
                        $pathFolders = $folderService->getFolderPath($currentFolder['id']);
                        // Lien vers la racine pour le premier élément
                        echo '<li><a href="/"><i class="fas fa-home"></i></a></li>';
                        
                        foreach ($pathFolders as $index => $folder) {
                            if ($index < count($pathFolders) - 1) {
                                echo '<li><a href="/?folder_id=' . $folder['id'] . '">' . htmlspecialchars($folder['name']) . '</a></li>';
                            } else {
                                // Le dossier actuel, non cliquable
                                echo '<li class="active">' . htmlspecialchars($folder['name']) . '</li>';
                            }
                        }
                    ?>
                <?php else: ?>
                    <li class="active"><i class="fas fa-home"></i> Mes documents</li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>
    <div class="header-right">
        <div class="main-actions">
             <div class="dropdown" id="add-dropdown">
                <button class="button button-primary add-button" id="add-btn">
                    <i class="fas fa-plus"></i>
                    <span>Ajouter</span>
                </button>
                <div class="dropdown-content" id="add-dropdown-content">
                    <a href="#" id="upload-file-btn">
                        <i class="fas fa-upload"></i>
                        <span>Téléverser un fichier</span>
                    </a>
                    <input type="file" id="file-input" multiple style="display: none;">
                    
                    <a href="#" id="create-folder-btn">
                        <i class="fas fa-folder-plus"></i>
                        <span>Nouveau dossier</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="user-menu">
            <i class="fas fa-user-circle"></i>
        </div>
    </div>
</header>
