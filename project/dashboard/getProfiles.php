<?php
require_once "../db_connection.php";

$profiles = [];

// Fetch profiles from the database
$query = "SELECT id, role_name FROM profile";
$result = $connection->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $profiles[] = $row; // Store each profile as an array element
    }
} else {
    die("Error fetching profiles: " . $connection->error);
}
?>
