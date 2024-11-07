<?php
require "../db_connection.php";
session_start();

// Select the database
$connection->select_db($dbName);

// Create `profile` table
$createProfileTable = "
    CREATE TABLE IF NOT EXISTS profile (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_name VARCHAR(50) NOT NULL UNIQUE,
        authorization_level INT NOT NULL
    ) ENGINE=InnoDB;
";
$connection->query($createProfileTable);

// Create `admin` table
$createAdminTable = "
    CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        profile_id INT,
        FOREIGN KEY (profile_id) REFERENCES profile(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;
";
$connection->query($createAdminTable);

// Create `permissions` table
$createPermissionsTable = "
    CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        permission_name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB;
";
$connection->query($createPermissionsTable);

// Create `profile_permissions` table
$createProfilePermissionsTable = "
    CREATE TABLE IF NOT EXISTS profile_permissions (
        profile_id INT,
        permission_id INT,
        PRIMARY KEY (profile_id, permission_id),
        FOREIGN KEY (profile_id) REFERENCES profile(id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
";
$connection->query($createProfilePermissionsTable);

// Insert "admin" and "user" profiles
$insertAdminProfile = "
    INSERT IGNORE INTO profile (role_name, authorization_level)
    VALUES ('admin', 1), ('user', 2);
";
$connection->query($insertAdminProfile);

// Get the profile IDs for "admin" and "user"
$adminProfileIdQuery = "SELECT id FROM profile WHERE role_name = 'admin'";
$adminProfileIdResult = $connection->query($adminProfileIdQuery);
$adminProfileId = $adminProfileIdResult->fetch_assoc()['id'];

$userProfileIdQuery = "SELECT id FROM profile WHERE role_name = 'user'";
$userProfileIdResult = $connection->query($userProfileIdQuery);
$userProfileId = $userProfileIdResult->fetch_assoc()['id'];

// Define permissions
$permissions = [
    'Import Map Data',
    'Import Voting Data',
    'Delete Map Data',
    'Delete Voting Data',
    'Register User',
    'Modify User Name',
    'Modify User Password',
    'Delete User',
    'View Map Data',       // New permission
    'View Voting Data'     // New permission
];

// Insert permissions and link them to profiles
foreach ($permissions as $permission) {
    // Insert permission
    $stmt = $connection->prepare("INSERT IGNORE INTO permissions (permission_name) VALUES (?)");
    $stmt->bind_param("s", $permission);
    $stmt->execute();

    // Get the permission ID
    $permissionIdQuery = "SELECT id FROM permissions WHERE permission_name = ?";
    $permStmt = $connection->prepare($permissionIdQuery);
    $permStmt->bind_param("s", $permission);
    $permStmt->execute();
    $permStmt->bind_result($permissionId);
    $permStmt->fetch();
    $permStmt->close();

    // Link permissions to the "admin" profile
    if (in_array($permission, [
        'Import Map Data', 'Import Voting Data', 'Delete Map Data', 'Delete Voting Data',
        'Register User', 'Modify User Name', 'Modify User Password', 'Delete User',
        'View Map Data', 'View Voting Data'
    ])) {
        $linkStmt = $connection->prepare("INSERT IGNORE INTO profile_permissions (profile_id, permission_id) VALUES (?, ?)");
        $linkStmt->bind_param("ii", $adminProfileId, $permissionId);
        $linkStmt->execute();
        $linkStmt->close();
    }

    // Link only view permissions to the "user" profile
    if (in_array($permission, ['View Map Data', 'View Voting Data'])) {
        $linkStmt = $connection->prepare("INSERT IGNORE INTO profile_permissions (profile_id, permission_id) VALUES (?, ?)");
        $linkStmt->bind_param("ii", $userProfileId, $permissionId);
        $linkStmt->execute();
        $linkStmt->close();
    }
}

?>
