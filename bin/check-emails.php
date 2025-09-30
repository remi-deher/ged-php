#!/usr/bin/env php
<?php
// bin/check-emails.php

chdir(dirname(__DIR__));
require_once 'vendor/autoload.php';

use Webklex\PHPIMAP\ClientManager;
use App\Core\Database;
use WebSocket\Client;
use Dompdf\Dompdf;
use Dompdf\Options;

$mailConfig = require 'config/mail.php';
$cm = new ClientManager();
$client = $cm->make($mailConfig);

echo "Connexion au serveur IMAP...\n";

try {
    $client->connect();
    echo "Connexion réussie.\n";

    $inbox = $client->getFolder('INBOX');
    $messages = $inbox->messages()->unseen()->get();
    echo count($messages) . " message(s) non lu(s) trouvé(s).\n";

    foreach ($messages as $message) {
        echo "Traitement du message : " . $message->getSubject() . "\n";
        $parentDocumentId = null;

        // --- 1. Sauvegarder le corps de l'e-mail en PDF ---
        if ($message->hasHTMLBody()) {
            $emailBodyHtml = $message->getHTMLBody();
            
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($emailBodyHtml);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfOutput = $dompdf->output();

            $emailSubject = $message->getSubject() ?: 'Email sans sujet';
            $storedPdfFilename = 'email_' . uniqid('', true) . '.pdf';
            $fullPdfPath = dirname(__DIR__) . '/storage/' . $storedPdfFilename;
            file_put_contents($fullPdfPath, $pdfOutput);

            $pdo = Database::getInstance();
            $sql = "INSERT INTO documents (original_filename, stored_filename, storage_path, mime_type, size, status, created_at, updated_at) 
                    VALUES (?, ?, ?, 'application/pdf', ?, 'received', NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $emailSubject . '.pdf', $storedPdfFilename, 'storage/', strlen($pdfOutput)
            ]);
            $parentDocumentId = $pdo->lastInsertId();
            echo "   -> Corps de l'e-mail sauvegardé en PDF (ID: $parentDocumentId).\n";
            notifyWebSocket($emailSubject . '.pdf');
        }

        // --- 2. Traiter les pièces jointes et les lier à l'e-mail parent ---
        foreach ($message->getAttachments() as $attachment) {
            echo " -> Pièce jointe trouvée : " . $attachment->getName() . "\n";
            
            $originalFilename = $attachment->getName();
            $storedFilename = 'doc_' . uniqid('', true) . '.' . pathinfo($originalFilename, PATHINFO_EXTENSION);
            $fullStoragePath = dirname(__DIR__) . '/storage/' . $storedFilename;

            if ($attachment->save(dirname(__DIR__), 'storage', $storedFilename) === false) {
                echo "   -> Erreur lors de la sauvegarde de la pièce jointe.\n";
                continue;
            }
            
            $pdo = Database::getInstance();
            $sql = "INSERT INTO documents (original_filename, stored_filename, storage_path, mime_type, size, status, parent_document_id, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, 'received', ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $originalFilename, $storedFilename, 'storage/', $attachment->getMimeType(), $attachment->getSize(), $parentDocumentId
            ]);
            
            $attachmentId = $pdo->lastInsertId();
            echo "   -> Pièce jointe importée (ID: $attachmentId) et liée à l'e-mail (ID: $parentDocumentId).\n";

            // 3. Lancer l'impression automatique pour la pièce jointe
            printAndTrackDocument($pdo, $attachmentId, $fullStoragePath);

            // 4. Notifier les clients WebSocket de l'arrivée de la pièce jointe
            notifyWebSocket($originalFilename);
        }

        $message->setFlag('Seen');
    }

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

function printAndTrackDocument(PDO $pdo, int $docId, string $filePath): void
{
    $printerName = 'GedPrinter';
    echo "   -> Envoi du document $docId à l'imprimante $printerName...\n";

    $command = "sudo lp -d " . escapeshellarg($printerName) . " " . escapeshellarg($filePath);
    $output = shell_exec($command . " 2>&1");

    preg_match('/request id is\s(.*?)\s/', $output, $matches);
    
    if (isset($matches[1])) {
        $jobId = $matches[1];
        $updateStmt = $pdo->prepare("UPDATE documents SET print_job_id = ?, status = 'to_print' WHERE id = ?");
        $updateStmt->execute([$jobId, $docId]);
        echo "   -> Succès. Job ID : $jobId. Statut mis à jour à 'to_print'.\n";
    } else {
        echo "   -> ERREUR lors de l'envoi à l'imprimante : $output\n";
        error_log("Erreur d'impression CUPS pour document ID $docId : " . $output);
    }
}

function notifyWebSocket(string $filename): void 
{
    try {
        $client = new Client("ws://127.0.0.1:8082");
        $payload = json_encode(['action' => 'new_document', 'filename' => $filename, 'timestamp' => date('Y-m-d H:i:s')]);
        $client->send($payload);
        $client->close();
        echo "   -> Notification WebSocket envoyée.\n";
    } catch (\Exception $e) {
        error_log("CRON - Erreur WebSocket : " . $e->getMessage());
    }
}

