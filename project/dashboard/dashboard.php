<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashboard_style.css"> <!-- Collegamento al file CSS -->
    <script src="dashboard.js" defer></script> <!-- Collegamento al file JS separato -->
<style>
    /* Set the background color for the dashboard */
    body {
        background-color: #faebd7; /* Light brown color */
    }

    .container {
        background-color: #f9f3e7; /* Light beige for a warm, soft look */
        padding: 20px; /* Add some padding for aesthetics */
        border-radius: 8px; /* Rounded corners for the container */
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Subtle shadow effect */
    }

    /* Text Color */
    h2, h3, p {
        color: #333333; /* Dark gray for headings and text */
    }

    /* Tab styles */
    .tab {
        padding: 10px 15px; /* Padding for tabs */
        cursor: pointer; /* Pointer cursor for better UX */
        background-color: #f0f0f0; /* Light gray background */
        color: #333; /* Dark text color */
        border: 1px solid #ccc; /* Border for the tabs */
        border-radius: 5px; /* Rounded corners */
        margin-right: 5px; /* Spacing between tabs */
        transition: background-color 0.3s, color 0.3s; /* Transition for smooth effect */
    }

    .tab.active {
        background-color: #3498db; /* Active tab color */
        color: white; /* White text for the active tab */
    }

    .tab:hover {
        background-color: #d1e9ff; /* Light blue on hover */
        color: #333; /* Keep text dark on hover */
    }

    /* Spinner Styles */
    .spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8);
        z-index: 9999; /* Assicura che sia in cima a tutto */
        display: none; /* Inizialmente nascosto */
    }

    .loader {
        border: 8px solid #f3f3f3; /* Grigio chiaro */
        border-top: 8px solid #3498db; /* Blu */
        border-radius: 50%;
        width: 60px;
        height: 60px;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Stili per il pulsante di eliminazione */
    #delete-table-btn {
        background-color: #e74c3c; /* Rosso */
        color: white; /* Testo bianco */
        border: none; /* Nessun bordo */
        padding: 5px 10px; /* Spaziatura interna */
        font-size: 12px; /* Dimensione del testo più piccola */
        cursor: pointer; /* Indicatore di puntatore */
        border-radius: 5px; /* Angoli arrotondati */
    }

    #delete-table-btn:hover {
        background-color: #c0392b; /* Colore più scuro al passaggio del mouse */
    }

    /* Pulsante Carica File */
    button {
        background-color: #3498db; /* Blu */
        color: white; /* Testo bianco */
        border: none; /* Nessun bordo */
        padding: 8px 15px; /* Spaziatura interna */
        font-size: 14px; /* Dimensione del testo */
        cursor: pointer; /* Indicatore di puntatore */
        border-radius: 5px; /* Angoli arrotondati */
        transition: background-color 0.3s; /* Transizione per effetto hover */
    }

    button:hover {
        background-color: #2980b9; /* Colore più scuro al passaggio del mouse */
    }

    /* Stile per il riepilogo */
    #summary {
        margin-top: 20px;
        border: 1px solid #ccc;
        padding: 10px;
        display: none; /* Inizialmente nascosto */
        background-color: #ffffff; /* White background for summary */
        color: #333333; /* Dark gray text color */
    }
</style>


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
            <form id="upload-form" onsubmit="event.preventDefault(); uploadFile();">
                <label for="csv_file">Seleziona il file CSV:</label>
                <input type="file" name="csv_file" accept=".csv" required>
                <button type="submit">Carica File</button>
            </form>

            <!-- Button to delete the table -->
            <button id="delete-table-btn" onclick="deleteAllTables();">Elimina Tabelle</button>
        </div>

        <!-- Tab contenuto: Profilo -->
        <div class="tab-content">
            <h3>Il tuo profilo</h3>
            <p>Username: 
                <?php
                require "../db_connection.php";
                // Verifica se l'utente è loggato
                if (isset($_SESSION['admin_id'])) {
                    $adminId = $_SESSION['admin_id'];
                    // Query per ottenere l'username
                    $query = "SELECT username FROM admin WHERE id = ?";
                    $stmt = $connection->prepare($query);
                    $stmt->bind_param('i', $adminId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        echo htmlspecialchars($row['username']); // Visualizza l'username
                    } else {
                        echo "Errore nel recupero del profilo.";
                    }

                    $stmt->close(); // Chiudi lo statement
                    $connection->close(); // Chiudi la connessione
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
            <button onclick="closeSummary()">Chiudi</button> <!-- Pulsante per chiudere il riepilogo -->
            <p id="successful-inserts"></p>
            <p id="skipped-rows"></p>
            <p id="total-rows"></p>
            <p id="deleted-rows"></p> <!-- Aggiunto per mostrare le righe eliminate -->
        </div>

        <!-- Logout -->
        <p><a href="logout.php">Logout</a></p>
    </div>

    <script>

        function closeSummary() {
            document.getElementById('summary').style.display = 'none'; // Nascondi il riepilogo
        }
    </script>
</body>
</html>
