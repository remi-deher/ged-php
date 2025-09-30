#!/usr/bin/env php
<?php
// bin/check-print-queue.php

chdir(dirname(__DIR__));
require_once 'vendor/autoload.php';

use App\Core\Database;

$pdo = Database::getInstance();
$printerName = 'GedPrinter';

// 1. Récupérer tous les documents qui sont en cours d'impression
$stmt = $pdo->query("SELECT id, print_job_id FROM documents WHERE status = 'to_print' AND print_job_id IS NOT NULL");
$documentsToPrint = $stmt->fetchAll();

if (empty($documentsToPrint)) {
    echo "Aucun document en attente d'impression.\n";
    exit;
}

// 2. Demander à CUPS la liste des travaux en attente pour notre imprimante
$command = "sudo lpstat -o " . escapeshellarg($printerName);
$output = shell_exec($command);

// 3. Parcourir nos documents et vérifier s'ils sont encore dans la liste
foreach ($documentsToPrint as $doc) {
    // Si le Job ID du document N'EST PAS dans la sortie de lpstat, c'est qu'il est terminé.
    if (strpos($output, $doc['print_job_id']) === false) {
        echo "Le travail {$doc['print_job_id']} est terminé. Mise à jour du document {$doc['id']}.\n";
        
        // Mettre à jour le statut en "Imprimé"
        $updateStmt = $pdo->prepare("UPDATE documents SET status = 'printed' WHERE id = ?");
        $updateStmt->execute([$doc['id']]);
        
        // On pourrait ajouter une notification WebSocket ici si on le voulait
    }
}
