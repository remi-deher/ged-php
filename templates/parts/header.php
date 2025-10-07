<?php
// templates/parts/header.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - GED' : 'GED'; ?></title>
    
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/navbar.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/pages/home.css">
    <link rel="stylesheet" href="/css/pages/settings.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script>
        // Initialise l'objet global 'GED' pour le rendre disponible
        // à tous les autres scripts de l'application.
        // Cette initialisation simple et sans PHP évite les erreurs de syntaxe.
        window.GED = {};
    </script>
</head>
<body>

<?php 
// Inclut la barre de navigation qui existe déjà
include __DIR__ . '/navbar.php'; 
?>
