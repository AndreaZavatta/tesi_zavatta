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
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        profile_id INT,
        FOREIGN KEY (profile_id) REFERENCES profile(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;
";
$connection->query($createAdminTable);

$createPermissionsTable = "
    CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        permission_name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT NOT NULL
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
    'Import Dati Nella Mappa' => 'Consente di importare dati geografici nella mappa.',
    'Import Dati Votazioni' => 'Consente di importare i dati relativi alle votazioni.',
    'Eliminazione Dati Mappa' => 'Consente di eliminare dati geografici dalla mappa.',
    'Eliminazione Dati Votazioni' => 'Consente di eliminare i dati relativi alle votazioni.',
    'Registrazione Utente' => 'Consente di registrare un nuovo utente.',
    'Modifica Nome Utente' => 'Consente di modificare il nome di un utente esistente.',
    'Modifica Password Utente' => 'Consente di modificare la password di un utente.',
    'Eliminazione Utente' => 'Consente di eliminare un utente esistente.',
    'Visualizzazione Dati Mappa' => 'Consente di visualizzare i dati geografici sulla mappa.',
    'Visualizzazione Dati Votazioni' => 'Consente di visualizzare i dati relativi alle votazioni.'
];

// Loop through permissions to insert them along with their descriptions
foreach ($permissions as $permission => $description) {
    // Insert permission with description
    $stmt = $connection->prepare("INSERT IGNORE INTO permissions (permission_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $permission, $description);
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
        'Import Dati Nella Mappa', 'Import Dati Votazioni', 'Eliminazione Dati Mappa', 'Eliminazione Dati Votazioni',
        'Registrazione Utente', 'Modifica Nome Utente', 'Modifica Password Utente', 'Eliminazione Utente',
        'Visualizzazione Dati Mappa', 'Visualizzazione Dati Votazioni'
    ])) {
        $linkStmt = $connection->prepare("INSERT IGNORE INTO profile_permissions (profile_id, permission_id) VALUES (?, ?)");
        $linkStmt->bind_param("ii", $adminProfileId, $permissionId);
        $linkStmt->execute();
        $linkStmt->close();
    }

    // Link only view permissions to the "user" profile
    if (in_array($permission, ['Visualizzazione Dati Mappa', 'Visualizzazione Dati Votazioni'])) {
        $linkStmt = $connection->prepare("INSERT IGNORE INTO profile_permissions (profile_id, permission_id) VALUES (?, ?)");
        $linkStmt->bind_param("ii", $userProfileId, $permissionId);
        $linkStmt->execute();
        $linkStmt->close();
    }
}


?>
