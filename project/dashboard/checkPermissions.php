<?php
session_start();
require_once "../db_connection.php";

// Ensure the user is logged in


if (!isset($connection) || !$connection instanceof mysqli) {
    die("Database connection is not established or is closed.");
}
if (isset($_SESSION['admin_id'])) {
    // Get the user's profile_id based on their session
    $userId = $_SESSION['admin_id'];
    $query = "
        SELECT profile_id 
        FROM users 
        WHERE id = ?
    ";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        die("Failed to prepare statement: " . $connection->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($profileId);
    $stmt->fetch();
    $stmt->close();
    }

// Function to check if the user has a specific permission
function hasPermission($permissionName) {
    global $connection, $profileId;

    // Check if the connection is valid
    if (!isset($connection) || !$connection instanceof mysqli) {
        die("Database connection is closed.");
    }

    // Prepare and execute the query to check permission
    $query = "
        SELECT p.permission_name 
        FROM permissions p
        INNER JOIN profile_permissions pp ON p.id = pp.permission_id
        WHERE pp.profile_id = ? AND p.permission_name = ?
    ";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        die("Failed to prepare statement: " . $connection->error);
    }
    $stmt->bind_param("is", $profileId, $permissionName);
    $stmt->execute();
    $stmt->store_result();

    $hasPermission = $stmt->num_rows > 0;
    $stmt->close();

    return $hasPermission;
}

// Function to check if the user is an admin
function isAdmin() {
    global $profileId;

    // Assuming profile_id = 1 corresponds to the "admin" role
    return $profileId === 1;
}

function hasSomeUserPermission(){
    return hasPermission('Modifica Nome Utente') || hasPermission('Modifica Password Utente') || hasPermission('Eliminazione Utente');
}

function hasViewPermissions(){
    return hasPermission('Visualizzazione Dati Mappa') || hasPermission('Visualizzazione Dati Votazioni'); 
}

function hasImportDataPermissions(){
    return hasPermission('Import Dati Nella Mappa ') || hasPermission('Import Dati Votazioni'); 
}

function hasDeletingPermissions(){
    return hasPermission('Eliminazione Dati Mappa') || hasPermission('Eliminazione Dati Votazioni') || hasPermission('Eliminazione Utente'); 
}

function hasRegisterPermissions(){
    return hasPermission('Registrazione Utente');
}

function hasModifyPermissions(){
    return hasPermission('Modifica Nome Utente') || hasPermission('Modifica Password Utente');
}

function hasOnlyViewPermission(){
    return hasViewPermissions() && !(hasImportDataPermissions() || hasDeletingPermissions() || hasRegisterPermissions() || hasModifyPermissions());
}
?>
