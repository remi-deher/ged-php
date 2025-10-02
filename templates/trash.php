<!DOCTYPE html>
<html lang="fr">
<body>
    <div class="container">
        <form id="trash-form" method="POST">
            <ul id="trash-list">
                <?php if (isset($documents) && !empty($documents)): ?>
                    <?php foreach ($documents as $doc): ?>
                        <li>
                            <input type="checkbox" name="doc_ids[]" value="<?= $doc['id'] ?>">
                            <span>📄 <?= htmlspecialchars($doc['original_filename']) ?> <small>(supprimé le <?= date('d/m/Y', strtotime($doc['deleted_at'])) ?>)</small></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>La corbeille est vide.</li>
                <?php endif; ?>
            </ul>

            <button type="submit" formmethod="POST" formaction="/document/restore" class="restore-btn">Restaurer la sélection</button>
            <button type="submit" formmethod="POST" formaction="/document/force-delete" class="force-delete-btn" onsubmit="return confirm('ATTENTION : Cette action est irréversible. Voulez-vous vraiment supprimer définitivement ces documents ?');">Suppression définitive</button>
        </form>

    </div>
</body>
</html>
