<?php
// Start the session and include the database connection
session_start();
require "../db_connection.php";

// Set the header to expect JSON
header("Content-Type: application/json");

// Check if the user is authorized (optional: ensure admin permissions)
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized request."]);
    exit;
}

// Get the JSON data from the request
$data = json_decode(file_get_contents("php://input"), true);

// Validate the user ID
if (!isset($data['id']) || !is_numeric($data['id'])) {
    echo json_encode(["success" => false, "error" => "Invalid user ID."]);
    exit;
}

$userId = $data['id'];

// Prepare and execute the delete statement
$stmt = $connection->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    // Check if any row was actually deleted
    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "User deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "error" => "User not found."]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Error deleting user: " . $stmt->error]);
}

// Close the statement and connection
$stmt->close();
$connection->close();
?>
