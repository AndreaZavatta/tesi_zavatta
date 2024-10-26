<?php
require "../db_connection.php";
session_start();

// List of tables to delete
$tables = ['votazioni', 'presenze', 'sedute', 'politici'];

$deletedTables = [];

// Drop each table
foreach ($tables as $table) {
    $query = "DROP TABLE IF EXISTS $table";
    if ($connection->query($query) === TRUE) {
        $deletedTables[] = $table;
    } else {
        throw new Exception("Errore durante l'eliminazione della tabella $table: " . $connection->error);
    }
}

// Close connection
$connection->close();

// Return success response
echo json_encode([
    'message' => 'Tutte le tabelle sono state eliminate con successo.',
    'deleted_tables' => $deletedTables
]);
