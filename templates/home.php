<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GED Collaborative</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>
        <h1>📁 GED Collaborative</h1>

        <form action="/upload" method="post" enctype="multipart/form-data">
            <h2>Envoyer un nouveau document</h2>
            <input type="file" name="document" required>
            <button type="submit">Envoyer</button>
        </form>
        <hr>

        <div class="header-actions">
            <h2>Documents stockés</h2>
            <a href="/trash" class="trash-link">Voir la corbeille 🗑️</a>
        </div>

        <h3>Dossiers</h3>
        <ul>
            <li><a href="/">📁 Racine</a></li>
            <?php if (isset($folders)): ?>
                <?php foreach ($folders as $folder): ?>
                    <li><a href="/?folder_id=<?= $folder['id'] ?>">📁 <?= htmlspecialchars($folder['name']) ?></a></li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <form action="/folder/create" method="POST" style="margin-top: 1rem;">
            <input type="text" name="folder_name" placeholder="Nom du nouveau dossier" required>
            <button type="submit">Créer dossier</button>
        </form>
        <hr>

        <form action="/document/delete" method="POST" id="bulk-action-form" onsubmit="return confirm('Êtes-vous sûr de vouloir mettre les documents sélectionnés à la corbeille ?');">
            <h3>Fichiers</h3>
            <ul id="document-list">
                <?php if (isset($documents) && !empty($documents)): ?>
                    <?php foreach ($documents as $doc): ?>
                        <li data-doc-id="<?= $doc['id'] ?>">
                            <input type="checkbox" name="doc_ids[]" value="<?= $doc['id'] ?>">
                            <span>📄 <?= htmlspecialchars($doc['original_filename']) ?></span>
                            
                            <form class="inline-form" action="/document/move" method="POST" style="display: inline; margin-left: auto; margin-right: 10px;">
                                <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                <select name="folder_id" onchange="this.form.submit()" title="Déplacer le document">
                                    <option value="" disabled selected>Déplacer vers...</option>
                                    <option value="root">Racine</option>
                                    <?php foreach ($folders as $folder): ?>
                                    <option value="<?= $folder['id'] ?>"><?= htmlspecialchars($folder['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>

                            <form class="inline-form" action="/document/update-status" method="POST" style="display: inline;">
                                <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                <select name="status" onchange="this.form.submit()" title="Changer le statut">
                                    <option value="received" <?= $doc['status'] == 'received' ? 'selected' : '' ?>>Reçu</option>
                                    <option value="to_print" <?= $doc['status'] == 'to_print' ? 'selected' : '' ?>>À imprimer</option>
                                    <option value="printed"  <?= $doc['status'] == 'printed' ? 'selected' : '' ?>>Imprimé ✅</option>
                                </select>
                            </form>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li id="no-docs">Aucun document dans ce dossier.</li>
                <?php endif; ?>
            </ul>
             <?php if (isset($documents) && !empty($documents)): ?>
                <button type="submit" class="delete-btn">Mettre la sélection à la corbeille</button>
             <?php endif; ?>
        </form>
    </div>

    <script src="/js/home.js" defer></script>
</body>
</html>
