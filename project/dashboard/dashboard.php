<?php
    session_start();
    require "../db_connection.php";
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
        <div class="tab-container">
            <span class="tab" onclick="showTab(0)">Carica File</span>
            <span class="tab" onclick="showTab(1)">Profilo</span>
            <!--<span class="tab" onclick="showTab(2)">Cambia Password</span>-->
            <span class="tab" onclick="showTab(2)">Register a User</span>
            <span class="tab" onclick="showTab(3)">Handle Users</span>
        </div>

    <div class="container">
        <h2>Dashboard</h2>

        <!-- Spinner Container -->
        <div id="loading-spinner" class="spinner" style="display: none;">
            <div class="loader"></div>
            <p id="progress-text">Caricamento dati... 0%</p>
            <button id="stop-import-btn" style="margin-top: 10px;">Interrompi Importazione</button>
        </div>

        <?php if (isset($_SESSION['admin_id'])): ?>
            <!-- Menu Tabs -->

            <div class="tab-content">
                <h3>Carica file</h3>
                <!-- Section for Application 1 -->
                <fieldset>
                    <legend>Mappa</legend>
                    <form id="upload-form" onsubmit="event.preventDefault(); uploadFile();">
                        <label for="csv_file_app1">Seleziona il file CSV per visualizzare i dati sulla mappa</label>
                        <input type="file" id="csv_file_app1" name="csv_file" accept=".csv" required>
                        <button type="submit">Carica File per mappa</button>
                    </form>
                    <div id="delete_container">
                        <button id="delete-table-btn" onclick="deleteAllTablesMap();">Elimina Tabelle per mappa</button>
                    </div>
                </fieldset>

                <!-- Section for Application 2 -->
                <fieldset>
                    <legend>Votazioni</legend>
                    <form id="upload-form-app2" method="POST" enctype="multipart/form-data">
                        <label for="json_file">Seleziona il file JSON per visualizzare le votazioni:</label>
                        <input type="file" id="json_file" name="json_file" accept=".json" required>
                        <button type="submit">Carica File per votazioni</button>
                    </form>
                    <div id="delete_container">
                        <button id="delete-table-btn-app2" onclick="deleteAllTablesVotazioni();">Elimina Tabelle votazioni</button>
                    </div>
                </fieldset>
            </div>

            <!-- Tab contenuto: Profilo -->
            <div class="tab-content">
                <h3>Il tuo profilo</h3>
                <p>Username:
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
                        $connection->close();
                    ?>
                </p>
            </div>
            <!--
            <div class="tab-content">
                <h3>Cambia la tua password</h3>
                <form onsubmit="event.preventDefault(); updateUserPassword();">
                    <label for="old_password">Vecchia Password:</label>
                    <input type="password" id="old_password" required>

                    <label for="new_password">Nuova Password:</label>
                    <input type="password" id="new_password" required>

                    <label for="confirm_new_password">Conferma Nuova Password:</label>
                    <input type="password" id="confirm_new_password" required>

                    <div id="password-error" style="color: red; display: none; margin-top: 10px;"></div>

                    <button type="submit">Aggiorna Password</button>
                </form>
            </div>
                    -->
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

                    <button type="submit">Register User</button>
                </form>
            </div>

            
            <!-- Modal for Editing User -->
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

        <!-- Logout -->
        <?php if (isset($_SESSION['admin_id'])): ?>
            <div class="logout_button">
                <button onclick="window.location.href='logout.php';">Logout</button>
            </div>
        <?php else: ?>
            <div class="dashboard-without-login">
                <button onclick="window.location.href='logout.php';">Login</button>
                <button onclick="window.location.href='../';">Visualizza Mappa</button>
                <button onclick="window.location.href='../votazioni/';">Visualizza Votazioni</button>
            </div>
        <?php endif; ?>
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

    <!-- Right-side menu for accessing applications -->
    <?php if (isset($_SESSION['admin_id'])): ?>
        <div class="sidebar">
            <button onclick="window.location.href='../';">Visualizza Mappa</button>
            <button onclick="window.location.href='../votazioni/';">Visualizza Votazioni</button>
        </div>
    <?php endif; ?>

    <script>
        function closeSummary() {
            document.getElementById('summary').style.display = 'none';
        }
    </script>
</body>
</html>
