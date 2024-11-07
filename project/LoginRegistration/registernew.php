<?php
require "../db_connection.php";
require_once "insertAdminData.php";
session_start();

header('Content-Type: application/json');

$errorMessage = '';

// Function to register a new user with profile_id
function register($username, $password, $profileId, $connection) {
    global $errorMessage;

    // Log start of registration
    error_log("Register function called with username: $username, profileId: $profileId");

    // Server-side password validation
    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[\\W]/", $password)) {
        $errorMessage = "La password non soddisfa i requisiti di sicurezza.";
        error_log("Password validation failed for user $username: $errorMessage");
        return false;
    }

    // Check if the username already exists
    $checkUserExists = $connection->prepare("SELECT * FROM users WHERE username = ?");
    if (!$checkUserExists) {
        $errorMessage = "Error preparing username check: " . $connection->error;
        error_log("Error preparing username check for $username: " . $connection->error);
        return false;
    }
    $checkUserExists->bind_param('s', $username);
    $checkUserExists->execute();
    $checkUserExists->store_result();

    if ($checkUserExists->num_rows > 0) {
        $errorMessage = "Il nome utente è già in uso!";
        error_log("Username $username already exists.");
        return false;
    }

    // Debug: Check profileId value before inserting
    if (empty($profileId)) {
        $errorMessage = "Profile ID is missing or invalid.";
        error_log("Profile ID missing for user $username");
        return false;
    }

    // Hash the password and insert the new user with profile_id
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $connection->prepare("INSERT INTO users (username, password_hash, profile_id) VALUES (?, ?, ?)");
    if (!$stmt) {
        $errorMessage = "Error preparing insert statement: " . $connection->error;
        error_log("Error preparing insert statement for $username: " . $connection->error);
        return false;
    }
    $stmt->bind_param('ssi', $username, $passwordHash, $profileId);

    if ($stmt->execute()) {
        error_log("User $username successfully registered with profile ID $profileId.");
        return true;
    } else {
        $errorMessage = "Errore nella registrazione: " . $stmt->error;
        error_log("Error executing insert for $username: " . $stmt->error);
        return false;
    }
}

// Process registration request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['new_password'] ?? '';
    $profileId = $data['profile_id'] ?? '';

    // Debug: Log received data
    if (empty($username) || empty($password) || empty($profileId)) {
        error_log("Missing required fields in registration request: username=$username, profileId=$profileId");
        echo json_encode([
            'success' => false,
            'error' => "Missing required fields.",
            'debug' => [
                'username' => $username,
                'profile_id' => $profileId
            ]
        ]);
        exit;
    }

    if (register($username, $password, $profileId, $connection)) {
        echo json_encode(['success' => true, 'message' => 'Registrazione completata con successo']);
    } else {
        error_log("Registration failed for username $username with error: $errorMessage");
        echo json_encode([
            'success' => false,
            'error' => $errorMessage,
            'debug' => [
                'username' => $username,
                'profile_id' => $profileId
            ]
        ]);
    }
}

$connection->close();
?>
