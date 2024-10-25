<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Establish a connection to the MySQL database
$connection = new mysqli('localhost', 'root', 'ErZava01', 'prova', 3306);

// Check for connection errors
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// SQL query to count the rows in the table
$count_query = "SELECT COUNT(*) AS row_count FROM rilevazione_flusso_veicoli";
$result = $connection->query($count_query);
$row_count = 0;

if ($result) {
    $row = $result->fetch_assoc();
    $row_count = $row['row_count'];
}

// SQL query to drop the table
$drop_table_query = "DROP TABLE IF EXISTS rilevazione_flusso_veicoli";

if ($connection->query($drop_table_query) === TRUE) {
    echo json_encode([
        "message" => "Tabella eliminata con successo.",
        "row_count" => $row_count
    ]);
} else {
    echo json_encode(["error" => "Errore durante l'eliminazione della tabella: " . $connection->error]);
}

// Close the database connection
$connection->close();
?>
