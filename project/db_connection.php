<?php
// Database credentials
$host = '';
$username = '';
$password = '';
$port = ;
$dbName = '';

// Create a connection to the MySQL server
$connection = new mysqli($host, $username, $password, '', $port);

// Check for connection errors
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
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
