<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GED Collaborative</title>
    <link rel="stylesheet" href="/css/style.css">
    <script defer src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
</head>
<body>
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>
        
        <div class="header-actions">
            <h1>📁 Documents</h1>
            <a href="/trash" class="button btn-delete">
                <i data-lucide="trash-2" style="width:16px; margin-right: 8px;"></i>
                Voir la corbeille
            </a>
        </div>

        <form action="/upload" method="post" enctype="multipart/form-data" style="margin-bottom: 2rem;">
            <label for="document"><strong>Envoyer un nouveau document</strong></label>
            <input type="file" name="document" id="document" required>
            <button type="submit">
                <i data-lucide="upload" style="width:16px; margin-right: 8px;"></i>
                Envoyer
            </button>
        </form>
        <hr>

        <h3>Dossiers</h3>
        <form action="/folder/create" method="POST" style="margin-top: 1rem; margin-bottom: 2rem;">
            <input type="text" name="folder_name" placeholder="Nom du nouveau dossier" required>
            <button type="submit">Créer dossier</button>
        </form>

        <h3>Fichiers</h3>
        <form action="/document/delete" method="POST" id="bulk-action-form" onsubmit="return confirm('Êtes-vous sûr de vouloir mettre les documents sélectionnés à la corbeille ?');">
            
            <table class="documents-table">
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
                                'received' => ['color' => '#007bff', 'label' => 'Reçu'],
                                'to_print' => ['color' => '#ffc107', 'label' => 'À imprimer'],
                                'printed'  => ['color' => '#28a745', 'label' => 'Imprimé']
                            ];
                        ?>
                        <?php foreach ($documents as $doc): ?>
                            <tr data-doc-id="<?= $doc['id'] ?>" class="email-row">
                                <td class="col-checkbox">
                                    <input type="checkbox" name="doc_ids[]" value="<?= $doc['id'] ?>" class="doc-checkbox">
                                </td>
                                <td class="col-status">
                                    <span class="status-dot" style="background-color: <?= $status_map[$doc['status']]['color'] ?? '#6c757d' ?>;" title="<?= $status_map[$doc['status']]['label'] ?? 'Inconnu' ?>"></span>
                                </td>
                                <td>
                                    <i data-lucide="mail" style="width:16px; margin-right: 8px; color: #495057;"></i>
                                    <strong><?= htmlspecialchars($doc['original_filename']) ?></strong>
                                </td>
                                <td class="col-actions">
                                    <div class="document-actions">
                                        <form action="/document/move" method="POST">
                                            <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                            <select name="folder_id" onchange="this.form.submit()" title="Déplacer le document">
                                                <option value="" disabled selected>Déplacer...</option>
                                                <option value="root">Racine</option>
                                                <?php foreach ($folders as $folder): ?>
                                                <option value="<?= $folder['id'] ?>"><?= htmlspecialchars($folder['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                        <form action="/document/update-status" method="POST">
                                            <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                            <select name="status" onchange="this.form.submit()" title="Changer le statut">
                                                <option value="received" <?= $doc['status'] == 'received' ? 'selected' : '' ?>>Reçu</option>
                                                <option value="to_print" <?= $doc['status'] == 'to_print' ? 'selected' : '' ?>>À imprimer</option>
                                                <option value="printed"  <?= $doc['status'] == 'printed' ? 'selected' : '' ?>>Imprimé</option>
                                            </select>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php if (!empty($doc['attachments'])): ?>
                                <?php foreach ($doc['attachments'] as $attachment): ?>
                                    <tr class="attachment-row" data-parent-id="<?= $doc['id'] ?>">
                                        <td class="col-checkbox">
                                            <input type="checkbox" name="doc_ids[]" value="<?= $attachment['id'] ?>" class="doc-checkbox attachment-checkbox">
                                        </td>
                                        <td class="col-status">
                                            <span class="status-dot" style="background-color: <?= $status_map[$attachment['status']]['color'] ?? '#6c757d' ?>;" title="<?= $status_map[$attachment['status']]['label'] ?? 'Inconnu' ?>"></span>
                                        </td>
                                        <td>
                                            <i data-lucide="paperclip" style="width:16px; margin-right: 8px; color: #495057;"></i>
                                            <?= htmlspecialchars($attachment['original_filename']) ?>
                                        </td>
                                        <td class="col-actions">
                                            <div class="document-actions">
                                                <form action="/document/update-status" method="POST">
                                                    <input type="hidden" name="doc_id" value="<?= $attachment['id'] ?>">
                                                    <select name="status" onchange="this.form.submit()" title="Changer le statut">
                                                        <option value="received" <?= $attachment['status'] == 'received' ? 'selected' : '' ?>>Reçu</option>
                                                        <option value="to_print" <?= $attachment['status'] == 'to_print' ? 'selected' : '' ?>>À imprimer</option>
                                                        <option value="printed"  <?= $attachment['status'] == 'printed' ? 'selected' : '' ?>>Imprimé</option>
                                                    </select>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">Aucun document dans ce dossier.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top: 1rem;">
                <button type="submit" id="bulk-delete-button" class="button btn-delete">
                    <i data-lucide="trash-2" style="width:16px; margin-right: 8px;"></i>
                    Mettre la sélection à la corbeille
                </button>
            </div>
        </form>
    </div>

    <div id="email-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title"></h2>
                <button id="modal-close-button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="modal-attachments">
                    <h3>Pièces jointes</h3>
                    <ul id="modal-attachments-list"></ul>
                </div>
                <div id="modal-preview">
                    <h3>Aperçu de l'e-mail</h3>
                    <iframe id="modal-preview-iframe" src="" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/home.js"></script>
</body>
</html>
