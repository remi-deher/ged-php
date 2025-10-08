<?php
// templates/home.php

/** @var \App\Services\FolderService $folderService */
/** @var array $documents */
/** @var array $currentFolder */
/** @var array|null $parentFolder */

$pageTitle = $currentFolder ? htmlspecialchars($currentFolder['name']) : 'Tableau de bord';
// CORRIGÉ : Utilisation de __DIR__ pour construire un chemin absolu et fiable.
include __DIR__ . '/parts/header.php';
?>

<div class="app-layout">
    <aside class="app-sidebar-left">
        <?php 
        // CORRIGÉ : Utilisation de __DIR__ pour construire un chemin absolu.
        include __DIR__ . '/parts/sidebar-left.php'; 
        ?>
    </aside>

    <main class="main-content">
        <div class="card files-list-card">
            <div class="card-header">
                <div class="header-main-actions">
                    <div class="filter-pills" id="filter-pills-container">
                        </div>
                </div>
                <div class="header-secondary-actions">
                    <div class="filter-bar">
                        <button id="toggle-filters-btn" class="button-icon" title="Filtrer les documents">
                            <i class="fas fa-filter"></i>
                        </button>
                        <div class="view-switcher">
                            <button class="button-icon active" id="list-view-btn" title="Vue liste"><i class="fas fa-list"></i></button>
                            <button class="button-icon" id="grid-view-btn" title="Vue grille"><i class="fas fa-th-large"></i></button>
                        </div>
                    </div>
                    <div class="advanced-filters" id="advanced-filters" style="display: none;">
                        <form id="filter-form">
                            <div class="filter-grid">
                                <div class="form-group">
                                    <label for="filter-type">Type de document</label>
                                    <select id="filter-type" name="type">
                                        <option value="">Tous</option>
                                        <option value="email">Email</option>
                                        <option value="invoice">Facture</option>
                                        <option value="quote">Devis</option>
                                        <option value="delivery_note">Bon de livraison</option>
                                        <option value="other">Autre</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filter-status">Statut</label>
                                    <select id="filter-status" name="status">
                                        <option value="">Tous</option>
                                        <option value="new">Nouveau</option>
                                        <option value="processed">Traité</option>
                                        <option value="archived">Archivé</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="filter-start-date">Date de début</label>
                                    <input type="date" id="filter-start-date" name="start_date">
                                </div>
                                <div class="form-group">
                                    <label for="filter-end-date">Date de fin</label>
                                    <input type="date" id="filter-end-date" name="end_date">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="button">Appliquer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body" id="documents-container">
                </div>
        </div>
    </main>

    <aside class="details-sidebar" id="details-sidebar">
        </aside>
</div>

<?php 
// CORRIGÉ : Utilisation de __DIR__ pour les inclusions.
include __DIR__ . '/parts/modal-document.php'; 
include __DIR__ . '/parts/modal-create-folder.php'; 
include __DIR__ . '/parts/modal-rename.php'; 
?>

<div class="drop-overlay" id="drop-overlay">
    <div class="drop-overlay-content">
        <i class="fas fa-upload"></i>
        <h2>Déposez vos fichiers ici</h2>
    </div>
</div>

<ul class="context-menu" id="context-menu"></ul>

<script src="/js/home/main.js" type="module"></script>

<?php 
// CORRIGÉ : Utilisation de __DIR__ pour le footer.
include __DIR__ . '/parts/footer.php'; 
?>
