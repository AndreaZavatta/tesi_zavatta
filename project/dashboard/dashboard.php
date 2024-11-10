<?php
    require_once "../db_connection.php";
    require_once "./checkPermissions.php";
    require_once "./getProfiles.php";
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $selectedDataset = $_POST['datasetsVisualization'] ?? 'Traffic'; // Default to 'Traffic' if nothing is selected

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="dashboard_style.css"> <!-- Collegamento al file CSS -->
    <script src="dashboard.js" defer></script> <!-- Collegamento al file JS separato -->
</head>
<body>
        <div class="profile_section">
            <?php if (isset($_SESSION['admin_id'])): ?>
                <div class="profile_data" onclick="toggleLogout()">
                    <p>
                        <?php
                            $adminId = $_SESSION['admin_id'];
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
                        ?>
                    </p>
                    <i class="fas fa-user"></i>
                </div>
                <div class="log_button" style="display: none;">
                    <button onclick="window.location.href='logout.php';">Logout</button>
                </div>
                <?php else: ?>
                <div class="log_button">
                    <button onclick="window.location.href='../LoginRegistration/login.php';">Login</button>
                </div>
            <?php endif; ?>
        </div>

    <?php if (isset($_SESSION['admin_id'])): ?>
        <div class="tab-container-column">
            <?php if (haspermission('Import Voting Data') || haspermission('Import Map Data')): ?>
                <span class="tab" onclick="showTab(0)">Load File</span>
            <?php endif; ?>
            <!--<span class="tab" onclick="showTab(2)">Cambia Password</span>-->
            <?php if (haspermission('Register User')): ?>
                <span class="tab" onclick="showTab(1)">Register a User</span>
            <?php endif; ?>
            <?php if (hasSomeUserPermission()): ?>
                <span class="tab" onclick="showTab(2)">Handle Users</span>
            <?php endif; ?>
            <?php if (hasSomeUserPermission()): ?>
                <span class="tab" onclick="showTab(3)">Data Visualization</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="center_column cover_full">
        <h2 id="title_dashboard">Dashboard</h2>
        <div class="container">
            <?php if (isset($_SESSION['admin_id'])): ?>
                <div class="tab-container-row">
                    <?php if (haspermission('Import Voting Data') || haspermission('Import Map Data')): ?>
                        <span class="tab" onclick="showTab(0)">Load File</span>
                    <?php endif; ?>
                    <!--<span class="tab" onclick="showTab(2)">Cambia Password</span>-->
                    <?php if (haspermission('Register User')): ?>
                        <span class="tab" onclick="showTab(1)">Register a User</span>
                    <?php endif; ?>
                    <?php if (hasSomeUserPermission()): ?>
                        <span class="tab" onclick="showTab(2)">Handle Users</span>
                    <?php endif; ?>
                    <?php if (hasSomeUserPermission()): ?>
                        <span class="tab" onclick="showTab(3)">Data Visualization</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <!-- Spinner Container -->
            <div id="loading-spinner" class="spinner" style="display: none;">
                <div class="loader"></div>
                <p id="progress-text">Load data... 0%</p>
                <button id="stop-import-btn" style="margin-top: 10px;">Interrompi Importazione</button>
            </div>

            
                <!-- Menu Tabs -->
                <?php if (isset($_SESSION['admin_id']) && hasImportDataPermission()): ?>
                    <div class="tab-content">
                        <h3>Load file</h3>
                        <p>select the data you would like to work with</p>
                        <select id="datasets" name="datasets" onchange="toggleFieldsets();">
                            <option value="Traffic">Traffic</option>
                            <option value="Balloting">Balloting</option>
                        </select>
                        <!-- Section for Application 1 -->
                        <?php if (isset($_SESSION['admin_id'])): ?>
                            <?php if (haspermission('Import Map Data')): ?>
                            <fieldset id="traffic-fieldset" style="display:none;">
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
                        <!-- Section for Application 2 -->
                        <?php if (haspermission('Import Voting Data')): ?>
                            <fieldset id="balloting-fieldset" style="display:none;">
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
                <?php if (isset($_SESSION['admin_id']) && hasPermission('Register User')): ?>
                    <div class="tab-content">
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
                                <button type="submit">Register User</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            <?php if (isset($_SESSION['admin_id']) && hasSomeUserPermission()): ?>
                <div class="tab-content">
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
            <div class="tab-content data-visualization" 
                style="<?php if (!isset($_SESSION['admin_id']) || hasViewPermissions()) echo 'display: block;'; ?>">
                <h3>Data Visualization</h3>
                <p>Select the data you would like to work with</p>
                <form method="POST" action="">
                    <select id="datasetsVisualization" name="datasetsVisualization" onchange="this.form.submit()">
                        <option value="Traffic" <?php if ($selectedDataset == 'Traffic') echo 'selected'; ?>>Traffic</option>
                        <option value="Balloting" <?php if ($selectedDataset == 'Balloting') echo 'selected'; ?>>Balloting</option>
                    </select>
                </form>
                <?php
                    if ($selectedDataset == 'Traffic') {
                        include './mapVisualization.php';
                    } elseif ($selectedDataset == 'Balloting') {
                        include './votazioni/home.html';
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


    <script>
        function closeSummary() {
            document.getElementById('summary').style.display = 'none';
        }
    </script>
</body>
</html>
