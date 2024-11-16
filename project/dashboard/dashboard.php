<?php
    require_once "../db_connection.php";
    require_once "./checkPermissions.php";
    require_once "./getProfiles.php";
    require_once "./utils.php";
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $activeTab = $_GET['tab'] ?? 'Traffic';

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="dashboard_style.css"> <!-- Collegamento al file CSS -->
    <link rel="stylesheet" href="navbar.css"> <!-- Collegamento al file CSS -->
    <script src="dashboard.js" defer></script> <!-- Collegamento al file JS separato -->
</head>
<body>
        <nav class="nav">
            <ul class="menu">
                <li class="menu-item">
                    <a href="#">Traffico</a>
                    <ul class="submenu">
                        <li><a href="?tab=traffic-load-data">Carica Dati</a></li>
                        <li><a href="?tab=traffic">Visualizza Mappa</a></li>
                    </ul>
                </li>
                <li class="menu-item">
                    <a href="?tab=balloting">Votazioni</a>
                    <ul class="submenu">
                        <li><a href="?tab=balloting-load-data">Carica Dati</a></li>
                        <li><a href="?tab=balloting" onclick="simulateClick('sedute-desktop-button')">Visualizza Sedute</a></li>
                        <li><a href="?tab=balloting" onclick="simulateClick('consiglieri-desktop-button')">Visualizza Consiglieri</a></li>
                        <li><a href="?tab=balloting" onclick="simulateClick('gruppi-desktop-button')">Visualizza Gruppi</a></li>
                    </ul>
                </li>
                <li class="menu-item">
                    <a href="?tab=register-user">Register a User</a>
                </li>
                <li class="menu-item">
                    <a href="?tab=handle-user">Handle Users</a>
                </li>
                <li class="space-nav"></li>
                <li class="menu-item profile-tab">
                    <div class="profile-info-nav">
                            <p>
                                <?php if (isset($_SESSION['admin_id'])): ?>
                                    <?php
                                        $adminId = $_SESSION['admin_id'];

                                        // Fetch username
                                        $query = "SELECT username FROM users WHERE id = ?";
                                        $stmt = $connection->prepare($query);
                                        $stmt->bind_param('i', $adminId);
                                        $stmt->execute();
                                        $result = $stmt->get_result();

                                        if ($result->num_rows > 0) {
                                            $row = $result->fetch_assoc();
                                            echo htmlspecialchars($row['username']);
                                        } else {
                                            echo "Errore nel recupero del profilo.";
                                        }

                                        $stmt->close();

                                        // Fetch profile name using the function
                                        $profileName = getProfileNameByUserId($adminId, $connection);

                                        // Fetch permissions using the function
                                        $permissions = getUserPermissions($adminId, $connection);
                                    ?>
                                <?php else: ?>
                                    anonimo
                                    <?php 
                                        $profileName = "N/A";
                                        $permissions = [];
                                    ?>
                                <?php endif; ?>
                            </p>
                            <i class="fas fa-user profile-icon"></i>
                    </div>
                </li>
            </ul>


        </nav>
    <div class="inside-body">
                    <!-- Spinner Container -->
        <div id="loading-spinner" class="spinner" style="display: none;">
            <div class="loader"></div>
            <p id="progress-text">Load data... 0%</p>
            <button id="stop-import-btn" style="margin-top: 10px;">Interrompi Importazione</button>
        </div>
            <div id="modalOverlay" class="modal-overlay" onclick="closeEditModal()"></div>
            <div id="editUsernameModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close" onclick="closeEditModal()">&times;</span>
                    <h3 id="modal-title">Edit</h3>
                    <form onsubmit="event.preventDefault(); saveUsernameChanges();">
                        <label id="edit-field-label" for="edit-field-input">Username:</label>
                        <input type="text" id="edit-field-input" required>
                        <button type="submit">Save Changes</button>
                    </form>
                </div>
            </div>
                <div id="editPasswordModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3 id="modal-title">Edit</h3>
            <form onsubmit="event.preventDefault(); savePasswordChanges();">
                <label for="new-password">New Password:</label>
                <input type="password" id="new-password" required>

                <label for="confirm-password">Confirm New Password:</label>
                <input type="password" id="confirm-password" required>

                <!-- Error message for password validation -->
                <p id="password-error-message" style="color: red; display: none;">Passwords must match and meet criteria.</p>

                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>
        <div class="profile_info cont">
            <h2>Utente</h2>
            <div class="profile-container">
                <!-- Display username -->
                <div class="profile-container-name">
                    <p>Username: </p>
                    <p>
                        <?php if (isset($_SESSION['admin_id'])): ?>
                            <?php
                                $adminId = $_SESSION['admin_id'];

                                // Fetch username
                                $query = "SELECT username FROM users WHERE id = ?";
                                $stmt = $connection->prepare($query);
                                $stmt->bind_param('i', $adminId);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    $row = $result->fetch_assoc();
                                    echo htmlspecialchars($row['username']);
                                } else {
                                    echo "Errore nel recupero del profilo.";
                                }

                                $stmt->close();

                                // Fetch profile name using the function
                                $profileName = getProfileNameByUserId($adminId, $connection);

                                // Fetch permissions using the function
                                $permissions = getUserPermissions($adminId, $connection);
                            ?>
                        <?php else: ?>
                            anonimo
                            <?php 
                                $profileName = "N/A";
                                $permissions = [];
                            ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if (isset($_SESSION['admin_id'])): ?>
                    <!-- Display profile name -->
                    <div class="profile-name">
                        <p>Profilo: <?php echo htmlspecialchars($profileName); ?></p>
                    </div>

                    <!-- Display permissions as a picklist -->
                    <div class="profile-permissions-picklist">
                        <h3>Permessi:</h3>
                        <select name="permissions" id="permissions-picklist" onchange="fetchPermissionDescription()">
                            <?php if (!empty($permissions)): ?>
                                <?php foreach ($permissions as $permission): ?>
                                    <option value="<?php echo htmlspecialchars($permission); ?>">
                                        <?php echo htmlspecialchars($permission); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="none">No permissions available</option>
                            <?php endif; ?>
                        </select>
                        <p id="permission-description">Select a permission to see the description</p>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['admin_id'])): ?>
                    <button onclick="window.location.href='logout.php';">Logout</button>
                <?php else: ?>
                    <button onclick="window.location.href='../LoginRegistration/login.php';">Login</button>
                <?php endif; ?>
            </div>
        </div>


        <div class="cont container-width">
                <!-- Menu Tabs -->
                <?php if (isset($_SESSION['admin_id']) && hasImportDataPermissions()): ?>
                    <div class="tab-content" style="display: <?php echo $activeTab === 'traffic-load-data' ? 'block' : 'none'; ?>;">
                        <h3>Load file</h3>
                        <!-- Section for Application 1 -->
                        <?php if (isset($_SESSION['admin_id'])): ?>
                            <?php if (haspermission('Import Dati Nella Mappa ')): ?>
                                <fieldset id="traffic-fieldset">
                                    <legend>Mappa</legend>
                                    <form id="upload-form" onsubmit="event.preventDefault(); uploadFile();">
                                        <label for="csv_file_app1">Choose the CSV file for visualizing data on the map</label>
                                        <input type="file" id="csv_file_app1" name="csv_file" accept=".csv" required>
                                        <div class="load_buttons">
                                            <button type="submit">Load File</button>
                                            <div id="delete_container">
                                                <button id="delete-table-btn" onclick="deleteAllTablesMap();">Delete tables</button>
                                            </div>
                                        </div>
                                    </form>
                                </fieldset>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                                
                <?php if (isset($_SESSION['admin_id']) && hasImportDataPermissions()): ?>
                    <div class="tab-content" style="display: <?php echo $activeTab === 'balloting-load-data' ? 'block' : 'none'; ?>;">
                        <h3>Load file</h3>
                        <!-- Section for Application 1 -->
                        <?php if (isset($_SESSION['admin_id'])): ?>
                        <!-- Section for Application 2 -->
                            <?php if (haspermission('Import Dati Votazioni')): ?>
                                <fieldset id="balloting-fieldset">
                                    <legend>Votazioni</legend>
                                    <form id="upload-form-app2" method="POST" enctype="multipart/form-data">
                                        <label for="json_file">Choose the json file for visualizing balloting data:</label>
                                        <input type="file" id="json_file" name="json_file" accept=".json" required>
                                        <div class="load_buttons">
                                            <button type="submit">Load File</button>
                                            <div id="delete_container">
                                                <button id="delete-table-btn-app2" onclick="deleteAllTablesVotazioni();">Delete tables</button>
                                            </div>
                                        </div>
                                    </form>

                                </fieldset>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['admin_id']) && hasPermission('Registrazione Utente')): ?>
                    <div class="tab-content" id="register-user-tab" style="display: <?php echo $activeTab === 'register-user' ? 'block' : 'none'; ?>;">
                        <h3>Register a User</h3>
                        <form onsubmit="event.preventDefault(); registerUser();">
                            <label for="username">Username:</label>
                            <input type="text" id="username" required>

                            <label for="password">Password:</label>
                            <input type="password" id="password" required>

                            <label for="confirm_password">Confirm Password:</label>
                            <input type="password" id="confirm_password" required>

                            <!-- Profile Picklist -->
                            <label for="profile">Profile:</label>
                            <select id="profile" required>
                                <option value="" disabled selected>Select a Profile</option>
                                <?php foreach ($profiles as $profile): ?>
                                    <option value="<?= $profile['id']; ?>"><?= htmlspecialchars($profile['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <div id="password-error-registration" style="color: red; display: none; margin-top: 10px;"></div>
                            <div class="center_row">
                                <button type="submit">Registrazione Utente</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            <?php if (isset($_SESSION['admin_id']) && hasSomeUserPermission()): ?>
                <div class="tab-content" style="display: <?php echo $activeTab === 'handle-user' ? 'flex' : 'none'; ?>;">
                    <h3>Handle Users</h3>
                    <table id="users-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Le righe degli utenti verranno popolate dinamicamente -->
                        </tbody>
                    </table>

                </div>
            <?php endif; ?>
        <!-- Tab per il contenuto di Traffic -->
        <div class="tab-content" id="traffic-tab" style="display: <?php echo $activeTab === 'traffic' ? 'block' : 'none'; ?>;">
            <h3>Visualizzazione Dati Traffico</h3>
            <?php
                if ($activeTab === 'traffic') {
                    include './mapVisualization.php'; // Carica il contenuto del traffico
                }
            ?>
        </div>

        <!-- Tab per il contenuto di Balloting -->
        <div class="tab-content" id="balloting-tab" style="display: <?php echo $activeTab === 'balloting' ? 'block' : 'none'; ?>;">
            <h3>Visualizzazione Dati Votazioni</h3>
            <?php
                if ($activeTab === 'balloting') {
                    include './votazioni/home.html'; // Carica il contenuto delle votazioni
                }
            ?>
        </div>







            <!-- Elementmodal per visualizzare il riepilogo -->
            <div id="summary" style="display: none;">
                <h3>Riepilogo Importazione</h3>
                <button onclick="closeSummary()">Chiudi</button>
                <p id="successful-inserts"></p>
                <p id="skipped-rows"></p>
                <p id="total-rows"></p>
                <p id="deleted-rows"></p> <!-- Aggiunto per mostrare le righe eliminate -->
            </div>


        </div>

    </div>

    <script>
        function closeSummary() {
            document.getElementById('summary').style.display = 'none';
        }
    </script>
</body>
</html>
