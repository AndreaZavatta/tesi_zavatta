<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashboard_style.css"> <!-- Collegamento al file CSS -->
    <script src="dashboard.js" defer></script> <!-- Collegamento al file JS separato -->
</head>
<body>
    <div class="container">
        <h2>Dashboard</h2>

        <!-- Spinner Container -->
        <div id="loading-spinner" class="spinner">
            <div class="loader"></div>
            <p id="progress-text">Caricamento dati... 0%</p>
            <button id="stop-import-btn" style="margin-top: 10px;">Interrompi Importazione</button> <!-- Stop Import Button -->
        </div>

        <!-- Menu Tabs -->
        <div class="tab-container">
            <span class="tab active" onclick="showTab(0)">Carica CSV</span>
            <span class="tab" onclick="showTab(1)">Profilo</span>
            <span class="tab" onclick="showTab(2)">Cambia Password</span>
        </div>

        <!-- Tab contenuto: Caricamento CSV -->
<div class="tab-content active">
            <h3>Carica file CSV</h3>
            
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
                <!-- Button to delete the table for Application 1 -->
            </fieldset>

            <!-- Section for Application 2 -->
            <fieldset>
                <legend>Applicazione 2</legend>
                <form id="upload-form-app2" action="upload_json.php" method="POST" enctype="multipart/form-data">
                    <label for="json_file">Seleziona il file JSON per Applicazione 2:</label>
                    <input type="file" id="json_file" name="json_file" accept=".json" required>
                    <button type="submit">Carica File per Applicazione 2</button>
                </form>
                <div id="delete_container">
                    <button id="delete-table-btn-app2" onclick="deleteAllTablesVotazioni();">Elimina Tabelle Applicazione 2</button>
                </div>
            </fieldset>
        </div>

        <!-- Tab contenuto: Profilo -->
        <div class="tab-content">
            <h3>Il tuo profilo</h3>
            <p>Username: 
                <?php
                require "../db_connection.php";
                session_start(); 
                if (isset($_SESSION['admin_id'])) {
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
                } else {
                    echo "Non sei loggato.";
                }
                ?>
            </p>
        </div>

        <!-- Tab contenuto: Modifica password -->
        <div class="tab-content">
            <h3>Cambia la tua password</h3>
            <form action="dashboard.php" method="POST" onsubmit="return validatePasswordForm()">
                <label for="old_password">Vecchia Password:</label>
                <input type="password" id="old_password" name="old_password" required>

                <label for="new_password">Nuova Password:</label>
                <input type="password" id="new_password" name="new_password" required>

                <label for="confirm_new_password">Conferma Nuova Password:</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" required>

                <button type="submit">Aggiorna Password</button>
            </form>
        </div>

        <!-- Elemento per visualizzare il riepilogo -->
        <div id="summary" style="display: none;">
            <h3>Riepilogo Importazione</h3>
            <button onclick="closeSummary()">Chiudi</button>
            <p id="successful-inserts"></p>
            <p id="skipped-rows"></p>
            <p id="total-rows"></p>
            <p id="deleted-rows"></p> <!-- Aggiunto per mostrare le righe eliminate -->
        </div>

        <!-- Logout -->
        <p><a href="logout.php">Logout</a></p>
    </div>

    <!-- Right-side menu for accessing applications -->
    <div class="sidebar">
        <button onclick="window.location.href='../';">Visualizza Mappa</button>
        <button onclick="window.location.href='../votazioni/';">Visualizza Votazioni</button>
    </div>

    <script>
        function closeSummary() {
            document.getElementById('summary').style.display = 'none'; // Nascondi il riepilogo
        }
    </script>
</body>
</html>
