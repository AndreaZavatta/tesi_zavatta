<?php
require "../db_connection.php";
session_start();

header('Content-Type: application/json');

// Create the 'admin' table if it doesn't exist
$createTableQuery = "
    CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL
    );
";
if ($connection->query($createTableQuery) !== TRUE) {
    echo json_encode(['success' => false, 'error' => "Error creating table: " . $connection->error]);
    exit();
}

// Initialize error message
$errorMessage = '';

// Function to register a new user
function register($username, $password, $connection) {
    global $errorMessage;

    // Server-side password validation
    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[\\W]/", $password)) {
        $errorMessage = "La password non soddisfa i requisiti di sicurezza.";
        return false;
    }

    // Check if the username already exists
    $checkUserExists = $connection->prepare("SELECT * FROM admin WHERE username = ?");
    $checkUserExists->bind_param('s', $username);
    $checkUserExists->execute();
    $checkUserExists->store_result();

    if ($checkUserExists->num_rows > 0) {
        $errorMessage = "Il nome utente è già in uso!";
        return false;
    }

    // Hash the password and insert the new user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $connection->prepare("INSERT INTO admin (username, password_hash) VALUES (?, ?)");
    $stmt->bind_param('ss', $username, $passwordHash);

    if ($stmt->execute()) {
        return true;
    } else {
        $errorMessage = "Errore nella registrazione: " . $connection->error;
        return false;
    }
}

// Process registration request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['new_password'] ?? '';

    if (register($username, $password, $connection)) {
        echo json_encode(['success' => true, 'message' => 'Registrazione completata con successo']);
    } else {
        echo json_encode(['success' => false, 'error' => $errorMessage]);
    }
}

$connection->close();
?>
