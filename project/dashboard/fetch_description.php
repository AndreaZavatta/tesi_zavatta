<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permission'])) {
    // Database connection
    include '../db_connection.php'; // Replace with your database connection script

    // Get the permission name
    $permissionName = $_POST['permission'];

    // Fetch the description from the database
    $query = "SELECT description FROM permissions WHERE permission_name = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $permissionName);
    $stmt->execute();
    $stmt->bind_result($description);

    if ($stmt->fetch()) {
        echo htmlspecialchars($description);
    } else {
        echo "Seleziona un permesso per vedere la descrizione.";
    }

    $stmt->close();
    $connection->close();
}
?>
