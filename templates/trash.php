<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Corbeille - GED</title>
    <link rel="stylesheet" href="/css/style.css">
    <script defer src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
</head>
<body>
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>
        
        <div class="header-actions">
            <h1>🗑️ Corbeille</h1>
        </div>

        <p>Les documents dans la corbeille sont supprimés définitivement après 30 jours.</p>

        <form id="trash-form" method="POST">
            <table class="documents-table">
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
                                    <i data-lucide="file-x-2" style="width:16px; margin-right: 8px; color: #dc3545;"></i>
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
                    <i data-lucide="rotate-ccw"></i>
                    Restaurer la sélection
                </button>
                <button type="submit" formaction="/document/force-delete" class="button btn-delete" onclick="return confirm('ATTENTION : Cette action est irréversible. Voulez-vous vraiment supprimer définitivement ces documents ?');">
                    <i data-lucide="alert-triangle"></i>
                    Suppression définitive
                </button>
            </div>
        </form>
    </div>

    <script src="/js/trash.js"></script>
    <script>
        // Initialise les icônes après le chargement de la page
        lucide.createIcons();
    </script>
</body>
</html>
