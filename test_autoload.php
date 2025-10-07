<?php
// test_autoload.php

// On charge l'autoloader exactement comme le fait index.php
require_once __DIR__ . '/vendor/autoload.php';

echo "Autoloader chargé.\n";

// On essaie de charger la classe
if (class_exists(\App\Controllers\DocumentController::class)) {
    echo "SUCCÈS : La classe DocumentController a été trouvée !\n";
} else {
    echo "ÉCHEC : La classe DocumentController est introuvable.\n";
}
