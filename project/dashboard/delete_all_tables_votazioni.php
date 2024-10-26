<?php
header('Content-Type: application/json');

try {
    // Connect to MySQL
    $conn = new mysqli('localhost', 'root', 'ErZava01', 'prova');

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connessione al database fallita: " . $conn->connect_error);
    }

    // List of tables to delete
    $tables = ['votazioni', 'presenze', 'sedute', 'politici'];

    $deletedTables = [];

    // Drop each table
    foreach ($tables as $table) {
        $query = "DROP TABLE IF EXISTS $table";
        if ($conn->query($query) === TRUE) {
            $deletedTables[] = $table;
        } else {
            throw new Exception("Errore durante l'eliminazione della tabella $table: " . $conn->error);
        }
    }

    // Close connection
    $conn->close();

    // Return success response
    echo json_encode([
        'message' => 'Tutte le tabelle sono state eliminate con successo.',
        'deleted_tables' => $deletedTables
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
