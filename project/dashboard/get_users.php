<?php
require "../db_connection.php";

header('Content-Type: application/json');

$result = $connection->query("SELECT id, username FROM users");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode(['users' => $users]);
$connection->close();
?>
