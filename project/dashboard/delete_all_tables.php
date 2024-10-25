<?php
// Start the session
require "../db_connection.php";
session_start();

// Check if the user is logged in (optional, but recommended)
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Non sei autorizzato.']);
    exit;
}

// Array of table names to delete
$tables = ['dettagli_traffico', 'dettagli_generali', 'rilevazioni_traffico', 'spire', 'vie'];
$errors = [];
$deleted_tables = 0;

// Loop through and drop each table
foreach ($tables as $table) {
    $query = "DROP TABLE IF EXISTS $table";
    if ($connection->query($query) === TRUE) {
        $deleted_tables++;
    } else {
        $errors[] = "Errore durante l'eliminazione della tabella $table: " . $connection->error;
    }
}

$connection->close();

// Send the response back to the client
if (count($errors) > 0) {
    echo json_encode(['error' => implode('. ', $errors)]);
} else {
    echo json_encode(['message' => 'Tutte le tabelle sono state eliminate con successo.', 'deleted_tables' => $deleted_tables]);
}
?>
