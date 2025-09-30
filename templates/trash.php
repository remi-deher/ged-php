<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corbeille - GED</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; max-width: 900px; margin: auto; padding: 2rem; background-color: #f8f9fa; color: #333; }
        .container { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h1 { color: #dc3545; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; }
        a.back-link { text-decoration: none; }
        button, .button { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .restore-btn { background-color: #28a745; color: white; }
        .force-delete-btn { background-color: #c82333; color: white; }
        ul { list-style: none; padding: 0; }
        li { background: #f4f4f4; padding: 12px; border-radius: 4px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
        li span { flex-grow: 1; color: #666; }
        .actions form { display: inline; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <h1>🗑️ Corbeille</h1>
            <a href="/" class="button back-link">Retour aux documents</a>
        </div>
        
        <p>Les documents dans la corbeille sont supprimés définitivement après 30 jours (logique à implémenter).</p>

        <ul id="trash-list">
            <?php if (isset($documents) && !empty($documents)): ?>
                <?php foreach ($documents as $doc): ?>
                    <li>
                        <span>📄 <?= htmlspecialchars($doc['original_filename']) ?> <small>(supprimé le <?= date('d/m/Y', strtotime($doc['deleted_at'])) ?>)</small></span>
                        <div class="actions">
                            <form action="/document/restore" method="POST" style="display: inline;">
                                <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                <button type="submit" class="restore-btn">Restaurer</button>
                            </form>
                            <form action="/document/force-delete" method="POST" style="display: inline;" onsubmit="return confirm('ATTENTION : Cette action est irréversible. Voulez-vous vraiment supprimer définitivement ce document ?');">
                                <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                <button type="submit" class="force-delete-btn">Suppression définitive</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>La corbeille est vide.</li>
            <?php endif; ?>
        </ul>
    </div>
</body>
</html>
