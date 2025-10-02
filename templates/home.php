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
        
        <h3>Fichiers</h3>
        <form action="/document/delete" method="POST" id="bulk-action-form">
            
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
                                        <form action="/document/print" method="POST" class="action-form">
                                            <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                            <button type="submit" class="button-icon btn-print" title="Imprimer ce document">
                                                <i data-lucide="printer"></i>
                                            </button>
                                        </form>
                                        <form action="/document/move" method="POST" class="action-form">
                                            </form>
                                        <form action="/document/update-status" method="POST" class="action-form">
                                            </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 20px;">Aucun document dans ce dossier.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="bulk-actions-container" style="margin-top: 1rem;">
                <button type="submit" id="bulk-print-button" class="button btn-print" formaction="/document/bulk-print">
                    <i data-lucide="printer" style="width:16px;"></i> Imprimer la sélection
                </button>
                <button type="submit" id="bulk-delete-button" class="button btn-delete" formaction="/document/delete" onsubmit="return confirm('Confirmer ?');">
                    <i data-lucide="trash-2" style="width:16px;"></i> Mettre à la corbeille
                </button>
            </div>
        </form>
    </div>

    <div id="toast-container"></div>

    <script src="/js/home.js"></script>
</body>
</html>
