<?php
    require_once "../db_connection.php";
    require_once "./checkPermissions.php";
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
                            $query = "SELECT username FROM admin WHERE id = ?";
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
        <div class="tab-container">
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
        </div>
    <?php endif; ?>
    <div class="center_column cover_full">
        <h2>Dashboard</h2>
        <div class="container">
            <!-- Spinner Container -->
            <div id="loading-spinner" class="spinner" style="display: none;">
                <div class="loader"></div>
                <p id="progress-text">Load data... 0%</p>
                <button id="stop-import-btn" style="margin-top: 10px;">Interrompi Importazione</button>
            </div>

            
                <!-- Menu Tabs -->
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
                        <fieldset id="traffic-fieldset">
                            <legend>Mappa</legend>
                            <form id="upload-form" onsubmit="event.preventDefault(); uploadFile();">
                                <label for="csv_file_app1">Seleziona il file CSV per visualizzare i dati sulla mappa</label>
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
                        <fieldset id="balloting-fieldset">
                            <legend>Votazioni</legend>
                            <form id="upload-form-app2" method="POST" enctype="multipart/form-data">
                                <label for="json_file">Seleziona il file JSON per visualizzare le votazioni:</label>
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

                    <button onclick="window.location.href='../votazioni/';" class="data_visualization_button" id="visualization_button_votazioni">Data Visualization</button>
                    <button onclick="window.location.href='../';" class="data_visualization_button" id="visualization_button_traffic">Data Visualization</button>
                </div>
            <?php if (isset($_SESSION['admin_id']) && haspermission('Register User')): ?>
                <div class="tab-content">
                    <h3>Register a User</h3>
                    <form onsubmit="event.preventDefault(); registerUser();">
                        <label for="username">Username:</label>
                        <input type="text" id="username" required>

                        <label for="password">Password:</label>
                        <input type="password" id="password" required>

                        <label for="confirm_password">Conferma Password:</label>
                        <input type="password" id="confirm_password" required>

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
