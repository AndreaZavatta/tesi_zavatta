<?php
require "../db_connection.php";
$data = json_decode(file_get_contents("php://input"), true);

$response = ['success' => false, 'error' => ''];

if (isset($data['id']) && isset($data['username'])) {
    // Modifica dell'username
    $userId = $data['id'];
    $newUsername = $data['username'];
    $stmt = $connection->prepare("UPDATE admin SET username = ? WHERE id = ?");
    $stmt->bind_param("si", $newUsername, $userId);

    if ($stmt->execute()) {
        $response['success'] = true;
    } else {
        $response['error'] = "Errore durante l'aggiornamento dell'username.";
    }

    $stmt->close();
} elseif (isset($data['id']) && isset($data['password'])) {
    // Modifica della password
    $userId = $data['id'];
    $newPasswordHash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $connection->prepare("UPDATE admin SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $newPasswordHash, $userId);

    if ($stmt->execute()) {
        $response['success'] = true;
    } else {
        $response['error'] = "Errore durante l'aggiornamento della password.";
    }

    $stmt->close();
} else {
    $response['error'] = "Dati mancanti per l'aggiornamento.";
}

echo json_encode($response);
$connection->close();
