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
    return hasPermission('Modify User Name') || hasPermission('Modify User Password') || hasPermission('Delete User');
}

function hasViewPermissions(){
    return hasPermission('View Map Data') || hasPermission('View Voting Data'); 
}

function hasImportDataPermissions(){
    return hasPermission('Import Map Data') || hasPermission('Import Voting Data'); 
}

function hasDeletingPermissions(){
    return hasPermission('Delete Map Data') || hasPermission('Delete Voting Data') || hasPermission('Delete User'); 
}

function hasRegisterPermissions(){
    return hasPermission('Register User');
}

function hasModifyPermissions(){
    return hasPermission('Modify User Name') || hasPermission('Modify User Password');
}

function hasOnlyViewPermission(){
    return hasViewPermissions() && !(hasImportDataPermissions() || hasDeletingPermissions() || hasRegisterPermissions() || hasModifyPermissions());
}
?>
