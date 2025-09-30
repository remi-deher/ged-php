<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GED Collaborative</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; max-width: 900px; margin: auto; padding: 2rem; background-color: #f8f9fa; color: #333; }
        .container { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h1, h2 { color: #0056b3; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; }
        a.trash-link { color: #dc3545; text-decoration: none; }
        a.trash-link:hover { text-decoration: underline; }
        button, .button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        button.delete-btn { background-color: #dc3545; font-size: 12px; padding: 5px 10px; margin-left: 10px; }
        ul { list-style: none; padding: 0; }
        li { background: #f4f4f4; padding: 12px; border-radius: 4px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
        li span { flex-grow: 1; }
        .new-item { animation: fadeIn 0.5s ease-in-out; }
        .deleted-item { transition: all 0.5s ease; transform: translateX(100%); opacity: 0; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 GED Collaborative</h1>

        <form action="/upload" method="post" enctype="multipart/form-data">
            <h2>Envoyer un nouveau document</h2>
            <input type="file" name="document" required>
            <button type="submit">Envoyer</button>
        </form>

        <div class="header-actions">
            <h2>Documents stockés</h2>
            <a href="/trash" class="trash-link">Voir la corbeille 🗑️</a>
        </div>
        <ul id="document-list">
            <?php if (isset($documents) && !empty($documents)): ?>
                <?php foreach ($documents as $doc): ?>
                    <li data-doc-id="<?= $doc['id'] ?>">
                        <span>📄 <?= htmlspecialchars($doc['original_filename']) ?></span>
                        <form class="inline-form" action="/document/update-status" method="POST" style="display: inline;">
                            <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="received" <?= $doc['status'] == 'received' ? 'selected' : '' ?>>Reçu</option>
                                <option value="to_print" <?= $doc['status'] == 'to_print' ? 'selected' : '' ?>>À imprimer</option>
                                <option value="printed"  <?= $doc['status'] == 'printed' ? 'selected' : '' ?>>Imprimé ✅</option>
                            </select>
                        </form>
                        <form action="/document/delete" method="POST" style="display: inline;" onsubmit="return confirm('Voulez-vous vraiment mettre ce document à la corbeille ?');">
                            <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                            <button type="submit" class="delete-btn">Supprimer</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li id="no-docs">Aucun document pour le moment.</li>
            <?php endif; ?>
        </ul>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const documentList = document.getElementById('document-list');
            const conn = new WebSocket('ws://localhost:8082');

            conn.onopen = (e) => console.log("Connexion WebSocket établie !");
            conn.onclose = (e) => console.log("Connexion WebSocket fermée.");
            conn.onerror = (err) => console.error('Erreur WebSocket:', err);

            conn.onmessage = (e) => {
                try {
                    const payload = JSON.parse(e.data);
                    const data = payload.data;

                    switch (payload.action) {
                        case 'new_document':
                            // La logique pour ajouter un nouveau document reste la même
                            break;
                        case 'status_update':
                            // La logique pour mettre à jour le statut reste la même
                            break;
                        case 'document_deleted':
                            handleDocumentDeleted(data);
                            break;
                    }
                } catch (error) {
                    console.error("Erreur lors du traitement du message WebSocket :", error);
                }
            };

            function handleDocumentDeleted(data) {
                const docElement = document.querySelector(`li[data-doc-id="${data.doc_id}"]`);
                if (docElement) {
                    docElement.classList.add('deleted-item');
                    // Attend la fin de l'animation pour supprimer l'élément du DOM
                    setTimeout(() => {
                        docElement.remove();
                    }, 500);
                }
            }
            
            function handleNewDocument(data) {
                const noDocsMessage = document.getElementById('no-docs');
                if (noDocsMessage) noDocsMessage.remove();

                const newLi = document.createElement('li');
                newLi.classList.add('new-item');
                // Note : Pour une application réelle, il faudrait générer le formulaire complet
                // pour que le nouvel élément ait aussi le sélecteur de statut.
                // Par simplicité ici, nous affichons juste le nom.
                newLi.innerHTML = `<span>📄 ${escapeHtml(data.filename)}</span> <small>Nouveau</small>`;
                documentList.prepend(newLi);
            }

            function handleStatusUpdate(data) {
                const docElement = document.querySelector(`li[data-doc-id="${data.doc_id}"]`);
                if (docElement) {
                    const selectElement = docElement.querySelector('select[name="status"]');
                    if (selectElement) {
                        selectElement.value = data.new_status;
                        // Ajoute une petite animation pour montrer le changement
                        docElement.style.transition = 'background-color 0.5s';
                        docElement.style.backgroundColor = '#d4edda';
                        setTimeout(() => { docElement.style.backgroundColor = '#f4f4f4'; }, 1000);
                    }
                }
            }

            function escapeHtml(unsafe) {
                return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
            }
        });
    </script>
</body>
</html>
