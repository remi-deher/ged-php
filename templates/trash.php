<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Corbeille - GED</title>
    
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/components.css">
</head>
<body>
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>
        
        <div class="header-actions">
            <h1>🗑️ Corbeille</h1>
        </div>

        <p>Les documents dans la corbeille sont supprimés définitivement après 30 jours.</p>

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
                            <td colspan="3" style="text-align: center; padding: 20px;">La corbeille est vide.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div id="trash-actions" class="trash-actions">
                <button type="submit" formaction="/document/restore" class="button">
                    🔄 Restaurer la sélection
                </button>
                <button type="submit" formaction="/document/force-delete" class="button button-delete" onclick="return confirm('ATTENTION : Cette action est irréversible. Voulez-vous vraiment supprimer définitivement ces documents ?');">
                    ⚠️ Suppression définitive
                </button>
            </div>
        </form>
    </div>

    <script src="/js/trash.js"></script>
</body>
</html>
