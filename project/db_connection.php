<?php

// Load configuration settings
$configPath = require __DIR__ . '/db_config.php';

// Extract database credentials from the config file
$host = $configPath['host'];
$username = $configPath['user'];
$password = $configPath['password'];
$port = isset($configPath['port']) ? $configPath['port'] : 3306;
$dbName = $configPath['database'];

// Create a connection to the MySQL server without specifying a database
$connection = new mysqli($host, $username, $password, '', $port);

// Check for connection errors
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Check if the database exists, and create it if not
$dbCheck = $connection->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
if ($dbCheck === FALSE) {
    die("Error checking database existence: " . $connection->error);
}

if ($dbCheck->num_rows == 0) {
    $createDB = "CREATE DATABASE $dbName";
    if (!$connection->query($createDB)) {
        die("Error creating database: " . $connection->error);
    }
}

// Select the database
if (!$connection->select_db($dbName)) {
    die("Error selecting database: " . $connection->error);
}

// No $connection->close(); here to keep the connection open for the entire script
?>
