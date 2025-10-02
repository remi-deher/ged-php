<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réglages - GED</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/settings.css">
    <script defer src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
</head>
<body>
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>
        <div class="settings-container">

            <div class="settings-block">
                <h1><i data-lucide="printer"></i> Paramètres d'Impression</h1>
                <form action="/settings/print/save" method="POST">
                    <div class="form-group">
                        <label for="printer_uri">URI de l'imprimante</label>
                        <input type="text" id="printer_uri" name="printer_uri" value="<?= htmlspecialchars($printSettings['printer_uri'] ?? '') ?>" placeholder="ipp://adresse-ip/printers/NomImprimante">
                        <small>Exemple : ipp://192.168.1.100/printers/MonImprimante. Trouvable dans l'interface de CUPS.</small>
                    </div>
                    <div class="form-actions" style="justify-content: flex-end;">
                        <button type="submit" class="button"><i data-lucide="save"></i> Enregistrer les paramètres d'impression</button>
                    </div>
                </form>
            </div>

            <hr>
            
            <h1><i data-lucide="building"></i> Réglages des Tenants</h1>
            </div>
    </div>
    <script>
        window.appFolders = <?= json_encode($appFolders ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="/js/settings-tenant.js"></script>
</body>
</html>1~<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réglages - GED</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/settings.css">
    <script defer src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
</head>
<body>
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>
        <div class="settings-container">

            <div class="settings-block">
                <h1><i data-lucide="printer"></i> Paramètres d'Impression</h1>
                <form action="/settings/print/save" method="POST">
                    <div class="form-group">
                        <label for="printer_uri">URI de l'imprimante</label>
                        <input type="text" id="printer_uri" name="printer_uri" value="<?= htmlspecialchars($printSettings['printer_uri'] ?? '') ?>" placeholder="ipp://adresse-ip/printers/NomImprimante">
                        <small>Exemple : ipp://192.168.1.100/printers/MonImprimante. Trouvable dans l'interface de CUPS.</small>
                    </div>
                    <div class="form-actions" style="justify-content: flex-end;">
                        <button type="submit" class="button"><i data-lucide="save"></i> Enregistrer les paramètres d'impression</button>
                    </div>
                </form>
            </div>

            <hr>
            
            <h1><i data-lucide="building"></i> Réglages des Tenants</h1>
            </div>
    </div>
    <script>
        window.appFolders = <?= json_encode($appFolders ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="/js/settings-tenant.js"></script>
</body>
</html>1~<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réglages - GED</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/settings.css">
    <script defer src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
</head>
<body>
    <div class="container">
        <?php require_once __DIR__ . '/parts/navbar.php'; ?>
        <div class="settings-container">

            <div class="settings-block">
                <h1><i data-lucide="printer"></i> Paramètres d'Impression</h1>
                <form action="/settings/print/save" method="POST">
                    <div class="form-group">
                        <label for="printer_uri">URI de l'imprimante</label>
                        <input type="text" id="printer_uri" name="printer_uri" value="<?= htmlspecialchars($printSettings['printer_uri'] ?? '') ?>" placeholder="ipp://adresse-ip/printers/NomImprimante">
                        <small>Exemple : ipp://192.168.1.100/printers/MonImprimante. Trouvable dans l'interface de CUPS.</small>
                    </div>
                    <div class="form-actions" style="justify-content: flex-end;">
                        <button type="submit" class="button"><i data-lucide="save"></i> Enregistrer les paramètres d'impression</button>
                    </div>
                </form>
            </div>

            <hr>
            
            <h1><i data-lucide="building"></i> Réglages des Tenants</h1>
            </div>
    </div>
    <script>
        window.appFolders = <?= json_encode($appFolders ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="/js/settings-tenant.js"></script>
</body>
</html>
