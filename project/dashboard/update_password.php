<?php
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../db_connection.php";
session_start();
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['old_password'], $data['new_password'])) {
    echo json_encode(["success" => false, "error" => "Dati mancanti."]);
    exit();
}

$oldPassword = $data['old_password'];
$newPassword = $data['new_password'];
$userId = $_SESSION['admin_id'];

$query = "SELECT password_hash FROM admin WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (password_verify($oldPassword, $row['password_hash'])) {
        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateQuery = "UPDATE admin SET password_hash = ? WHERE id = ?";
        $updateStmt = $connection->prepare($updateQuery);
        $updateStmt->bind_param('si', $newPasswordHash, $userId);

        if ($updateStmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Errore durante l'aggiornamento della password."]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "La vecchia password non è corretta."]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Utente non trovato."]);
}

$stmt->close();
$connection->close();
exit();
?>
