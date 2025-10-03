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
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>

        <div id="print-queue-dashboard" class="card">
            <div class="card-header">
                <h2>🖨️ File d'impression en cours</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fichier</th>
                            <th>Job ID</th>
                            <th>Statut</th>
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <tbody id="print-queue-body">
                        <tr>
                            <td colspan="4" class="empty-state">Chargement du statut de la file d'impression...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="header-actions">
             <?php if (isset($currentFolder) && $currentFolder): ?>
                <h1>
                    <a href="/" class="breadcrumb-link">Documents</a> / 📁 <?= htmlspecialchars($currentFolder['name']) ?>
                </h1>
            <?php else: ?>
                <h1>📁 Documents</h1>
            <?php endif; ?>

            <form action="/upload" method="post" enctype="multipart/form-data" class="upload-form">
                <label for="document" class="button">➕ Envoyer un document</label>
                <input type="file" name="document" id="document" onchange="this.form.submit()" style="display:none;">
            </form>
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
            <div class="card-body">
                <?php if (isset($folders) && !empty($folders)): ?>
                    <div class="folder-grid">
                        <?php foreach ($folders as $folder): ?>
                            <a href="/?folder_id=<?= $folder['id'] ?>" class="folder-item">
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
                 <h2><?= (isset($currentFolder) && $currentFolder) ? '📄 Fichiers dans ce dossier' : '📄 Fichiers à la racine'; ?></h2>
                 <div class="bulk-actions-container">
                    <form method="POST" id="bulk-action-form">
                        <button type="submit" id="bulk-print-button" class="button button-secondary" formaction="/document/bulk-print">
                            🖨️ Imprimer la sélection
                        </button>
                        <button type="submit" id="bulk-delete-button" class="button button-delete" formaction="/document/delete" onsubmit="return confirm('Confirmer la mise à la corbeille ?');">
                            🗑️ Mettre à la corbeille
                        </button>
                    </form>
                </div>
            </div>
             <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="col-checkbox"><input type="checkbox" id="select-all-checkbox" title="Tout sélectionner"></th>
                            <th class="col-status">Statut</th>
                            <th>Nom du fichier</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($documents) && !empty($documents)): ?>
                            <?php 
                                $status_map = [
                                    'received'    => ['color' => '#007bff', 'label' => 'Reçu'],
                                    'to_print'    => ['color' => '#ffc107', 'label' => 'À imprimer'],
                                    'printed'     => ['color' => '#28a745', 'label' => 'Imprimé'],
                                    'print_error' => ['color' => '#dc3545', 'label' => 'Erreur d\'impression']
                                ];
                            ?>
                            <?php foreach ($documents as $doc): ?>
                                <tr data-doc-id="<?= $doc['id'] ?>" class="email-row">
                                    <td class="col-checkbox">
                                        <input type="checkbox" name="doc_ids[]" value="<?= $doc['id'] ?>" class="doc-checkbox" form="bulk-action-form">
                                    </td>
                                    <td class="col-status">
                                        <span class="status-dot" style="background-color: <?= $status_map[$doc['status']]['color'] ?? '#6c757d' ?>;" title="<?= $status_map[$doc['status']]['label'] ?? 'Inconnu' ?>"></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($doc['original_filename']) ?></strong>
                                    </td>
                                    <td class="col-actions">
                                        <div class="document-actions">
                                            <form action="/document/print" method="POST" class="action-form">
                                                <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                                <button type="submit" class="button-icon" title="Imprimer ce document">🖨️</button>
                                            </form>
                                            <form action="/document/delete" method="POST" class="action-form" onsubmit="return confirm('Confirmer la mise à la corbeille ?');">
                                                <input type="hidden" name="doc_ids[]" value="<?= $doc['id'] ?>">
                                                <button type="submit" class="button-icon button-delete" title="Mettre à la corbeille">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <?= (isset($currentFolder) && $currentFolder) ? 'Ce dossier est vide.' : 'Aucun document à la racine.'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="document-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 1200px; height: 90vh;">
            <div class="modal-header">
                <h2 id="modal-title"></h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body" style="display: flex; gap: 1.5rem; overflow: hidden;">
                <div id="modal-attachments" style="flex: 0 0 300px; border-right: 1px solid var(--border-color); padding-right: 1.5rem; overflow-y: auto;">
                    <h3>Pièces jointes</h3>
                    <ul id="modal-attachments-list"></ul>
                </div>
                <div id="modal-preview" style="flex: 1; display: flex;">
                    <iframe id="modal-preview-iframe" style="width: 100%; height: 100%; border: 1px solid #ddd;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>
    <script src="/js/home.js"></script>
</body>
</html>
