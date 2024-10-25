<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start session if not already started
}

// Set the database name
$dbName = "prova"; // Replace with your actual database name

// Check if the session variables for DB connection are set
if (isset($_SESSION['host'], $_SESSION['db_username'], $_SESSION['db_password'], $_SESSION['port'])) {
    $host = $_SESSION['host'];
    $username = $_SESSION['db_username'];
    $password = $_SESSION['db_password'];
    $port = $_SESSION['port'];
    echo("ci siamo");
    try {
        // Attempt to establish a connection to the MySQL server
        $connection = new mysqli($host, $username, $password, '', $port);

        // Check for connection errors
        if ($connection->connect_error) {
            throw new Exception("Connessione al database fallita. Verifica le tue credenziali.");
        }

        // Check if the database exists, if not create it
        $checkDbQuery = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'";
        if ($connection->query($checkDbQuery)->num_rows === 0) {
            // Database doesn't exist, create it
            $createDbQuery = "CREATE DATABASE $dbName";
            if ($connection->query($createDbQuery) !== TRUE) {
                throw new Exception("Errore durante la creazione del database: " . $connection->error);
            }
        }

        // Now select the database
        if (!$connection->select_db($dbName)) {
            throw new Exception("Errore nella selezione del database: " . $connection->error);
        }
    } catch (Exception $e) {
        // Set the error message in the session for display
        $_SESSION['db_error'] = $e->getMessage();
        header("Location: register.php"); // Redirect back to registration page
        exit();
    }
} else {
    die("Parametri di connessione mancanti.");
}



// Check if the database exists
$dbCheck = $connection->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");

if ($dbCheck === FALSE) {
    die("Error checking database existence: " . $connection->error);
}

// If database doesn't exist, create it
if ($dbCheck->num_rows == 0) {
    $createDB = "CREATE DATABASE $dbName";
    if ($connection->query($createDB) === TRUE) {
        echo "Database created successfully<br>";
    } else {
        die("Error creating database: " . $connection->error);
    }
}

// Select the database
if (!$connection->select_db($dbName)) {
    die("Error selecting database: " . $connection->error);
}

?>
