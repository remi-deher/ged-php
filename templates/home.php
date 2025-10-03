<?php
// Helper functions pour le formatage
function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    if ($bytes > 1) return $bytes . ' bytes';
    if ($bytes == 1) return '1 byte';
    return '0 bytes';
}

function getFileIcon($mimeType) {
    if (str_contains($mimeType, 'html')) return '📧';
    if (str_contains($mimeType, 'pdf')) return '📄';
    if (str_contains($mimeType, 'image')) return '🖼️';
    if (str_contains($mimeType, 'word')) return '📝';
    return '📁'; // Icône par défaut
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GED Collaborative</title>
    
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/pages/home.css">
</head>
<body>
    <div class="container-fluid">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>

        <div class="main-layout">
            <main id="main-content" class="main-content">
                <div id="print-queue-dashboard" class="card" style="display: none;">
                    <div class="card-header">
                        <h2>🖨️ File d'impression en cours</h2>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead><tr><th>Fichier</th><th>Job ID</th><th>Statut</th><th>Détails</th><th>Actions</th></tr></thead>
                            <tbody id="print-queue-body"></tbody>
                        </table>
                    </div>
                </div>
                
                <div class="header-actions">
                     <?php if (isset($currentFolder) && $currentFolder): ?>
                        <h1><a href="/" class="breadcrumb-link" id="root-dropzone" data-folder-id="root">Documents</a> / 📁 <?= htmlspecialchars($currentFolder['name']) ?></h1>
                    <?php elseif (isset($_GET['q'])): ?>
                         <h1>🔍 Résultats de recherche pour "<?= htmlspecialchars($_GET['q']) ?>"</h1>
                    <?php else: ?>
                        <h1>📁 Documents</h1>
                    <?php endif; ?>
                    <div class="actions-group">
                        <form action="/upload" method="post" enctype="multipart/form-data" class="upload-form">
                            <label for="document" class="button">➕ Envoyer un document</label>
                            <input type="file" name="document" id="document" onchange="this.form.submit()">
                        </form>
                    </div>
                </div>

                <?php if (!isset($currentFolder) || !$currentFolder): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>📂 Dossiers</h2>
                        <form action="/folder/create" method="POST" class="create-folder-form">
                            <input type="text" name="folder_name" placeholder="Nom du nouveau dossier" required>
                            <button type="submit" class="button">Créer</button>
                        </form>
                    </div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <?php if (isset($folders) && !empty($folders)): ?>
                            <div class="folder-grid">
                                <?php foreach ($folders as $folder): ?>
                                    <a href="/?folder_id=<?= $folder['id'] ?>" class="folder-item dropzone" data-folder-id="<?= $folder['id'] ?>">
                                        <div class="folder-icon">📁</div>
                                        <div class="folder-name"><?= htmlspecialchars($folder['name']) ?></div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">Aucun dossier n'a été créé.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card">
                     <div class="card-header">
                        <h2><?= (isset($currentFolder) && $currentFolder) ? '📄 Fichiers dans ce dossier' : '📄 Fichiers'; ?></h2>
                         <div class="bulk-actions-container">
                            <form method="POST" id="bulk-action-form">
                                <button type="submit" id="bulk-print-button" class="button button-secondary" formaction="/document/bulk-print">🖨️ Imprimer</button>
                                <button type="submit" id="bulk-delete-button" class="button button-delete" formaction="/document/delete" onsubmit="return confirm('Confirmer la mise à la corbeille ?');">🗑️ Corbeille</button>
                            </form>
                        </div>
                    </div>
                     <div class="card-body">
                        <div id="document-list-view">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="col-checkbox"><input type="checkbox" id="select-all-checkbox" title="Tout sélectionner"></th>
                                        <th class="col-status">Statut</th>
                                        <th>Nom du fichier</th>
                                        <th>Source</th>
                                        <th>Taille</th>
                                        <th>Date d'ajout</th>
                                        <th class="col-actions">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($documents) && !empty($documents)): ?>
                                        <?php $status_map = ['received' => ['color' => '#007bff', 'label' => 'Reçu'], 'to_print' => ['color' => '#ffc107', 'label' => 'À imprimer'], 'printed' => ['color' => '#28a745', 'label' => 'Imprimé'], 'print_error' => ['color' => '#dc3545', 'label' => 'Erreur d\'impression']]; ?>
                                        <?php foreach ($documents as $doc): ?>
                                            <tr data-doc-id="<?= $doc['id'] ?>" class="document-row" draggable="true">
                                                <td class="col-checkbox"><input type="checkbox" name="doc_ids[]" value="<?= $doc['id'] ?>" class="doc-checkbox" form="bulk-action-form"></td>
                                                <td class="col-status"><span class="status-dot" style="background-color: <?= $status_map[$doc['status']]['color'] ?? '#6c757d' ?>;" title="<?= $status_map[$doc['status']]['label'] ?? 'Inconnu' ?>"></span></td>
                                                <td class="col-name"><span class="file-icon"><?= getFileIcon($doc['mime_type'] ?? '') ?></span><strong><?= htmlspecialchars($doc['original_filename']) ?></strong></td>
                                                <td><?= $doc['source_account_id'] ? '📧 E-mail' : '📥 Manuel' ?></td>
                                                <td><?= formatSizeUnits($doc['size']) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></td>
                                                <td class="col-actions">
                                                    <div class="document-actions">
                                                        <form action="/document/print" method="POST" class="action-form"><input type="hidden" name="doc_id" value="<?= $doc['id'] ?>"><button type="submit" class="button-icon" title="Imprimer">🖨️</button></form>
                                                        <form action="/document/delete" method="POST" class="action-form" onsubmit="return confirm('Confirmer la mise à la corbeille ?');"><input type="hidden" name="doc_ids[]" value="<?= $doc['id'] ?>"><button type="submit" class="button-icon button-delete" title="Corbeille">🗑️</button></form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="empty-state"><?= (isset($_GET['q'])) ? 'Aucun résultat trouvé.' : ((isset($currentFolder) && $currentFolder) ? 'Ce dossier est vide.' : 'Aucun document à la racine.'); ?></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="document-grid-view" class="grid-view-container" style="display:none;">
                            <?php if (isset($documents) && !empty($documents)): ?>
                                <?php foreach ($documents as $doc): ?>
                                    <div class="grid-item document-row" data-doc-id="<?= $doc['id'] ?>" draggable="true">
                                        <div class="grid-item-thumbnail"><span class="file-icon large"><?= getFileIcon($doc['mime_type'] ?? '') ?></span></div>
                                        <div class="grid-item-name" title="<?= htmlspecialchars($doc['original_filename']) ?>"><?= htmlspecialchars($doc['original_filename']) ?></div>
                                    </div>
                                <?php endforeach; ?>
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
                    <div id="sidebar-info" class="sidebar-info">
                        <h3>Informations</h3>
                        <ul id="sidebar-info-list"></ul>
                    </div>
                    <div id="sidebar-attachments" class="sidebar-attachments">
                        <button id="sidebar-attachments-toggle-btn" class="attachments-toggle-btn">Pièces jointes <span class="arrow">▼</span></button>
                        <ul id="sidebar-attachments-list" class="sidebar-attachments-list collapsed"></ul>
                    </div>
                </div>
            </aside>
        </div>
    </div>
    
    <div id="document-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 1200px; height: 90vh;">
            <div class="modal-header">
                <h2 id="modal-title"></h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body-split">
                <div id="modal-attachments" class="modal-attachments">
                    <div class="attachments-header">
                        <h3>Pièces jointes</h3>
                        <button id="modal-attachments-toggle-btn" title="Masquer/Afficher les pièces jointes">‹</button>
                    </div>
                    <ul id="modal-attachments-list" class="modal-attachments-list"></ul>
                </div>
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
