<?php
require "./db_connection.php";
session_start();
// Funzione per creare le tabelle se non esistono
function createTables($connection) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS comuni (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL UNIQUE,
            descrizione TEXT
        )",

        "CREATE TABLE IF NOT EXISTS vie (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codice_via VARCHAR(255) NOT NULL,
            nome_via VARCHAR(255),
            codice_arco VARCHAR(255),
            nodo_da VARCHAR(255),
            nodo_a VARCHAR(255),
            direzione VARCHAR(255),
            comune_id INT, 
            FOREIGN KEY (comune_id) REFERENCES comuni(id)
        )",

        "CREATE TABLE IF NOT EXISTS spire (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codice_via INT,
            codimpsem VARCHAR(255),
            longitudine VARCHAR(255),
            latitudine VARCHAR(255),
            geopoint VARCHAR(255),
            ID_univoco_stazione_spira VARCHAR(255),
            FOREIGN KEY (codice_via) REFERENCES vie(id)
        )",


        "CREATE TABLE IF NOT EXISTS rilevazioni_traffico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `data` DATE,
            codice_spira INT,
            giorno_settimana VARCHAR(255),
            giorno VARCHAR(255),
            mese VARCHAR(255),
            anno VARCHAR(255),
            FOREIGN KEY (codice_spira) REFERENCES spire(id)
        )",

        "CREATE TABLE IF NOT EXISTS dettagli_traffico (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_rilevazione INT,
        `00:00-01:00` INT,
        `01:00-02:00` INT,
        `02:00-03:00` INT,
        `03:00-04:00` INT,
        `04:00-05:00` INT,
        `05:00-06:00` INT,
        `06:00-07:00` INT,
        `07:00-08:00` INT,
        `08:00-09:00` INT,
        `09:00-10:00` INT,
        `10:00-11:00` INT,
        `11:00-12:00` INT,
        `12:00-13:00` INT,
        `13:00-14:00` INT,
        `14:00-15:00` INT,
        `15:00-16:00` INT,
        `16:00-17:00` INT,
        `17:00-18:00` INT,
        `18:00-19:00` INT,
        `19:00-20:00` INT,
        `20:00-21:00` INT,
        `21:00-22:00` INT,
        `22:00-23:00` INT,
        `23:00-24:00` INT,
        notte INT,
        mattina INT,
        pomeriggio INT,
        sera INT,
        FOREIGN KEY (id_rilevazione) REFERENCES rilevazioni_traffico(id)
    )",

    "CREATE TABLE IF NOT EXISTS dettagli_generali (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_rilevazione INT,
        livello VARCHAR(255),
        tipologia INT,
        stato VARCHAR(255),
        direzione VARCHAR(255),
        angolo VARCHAR(255),
        FOREIGN KEY (id_rilevazione) REFERENCES rilevazioni_traffico(id)
    )",

    ];

    foreach ($queries as $query) {
        if ($connection->query($query) !== TRUE) {
            echo "Errore nella creazione della tabella: " . $connection->error . "<br>";
        }
    }
}

// Funzione per importare dati nelle tabelle
// Your importData function
function importData($connection) {

    $successful_inserts = 0;
    $skipped_rows = 0;
    $processed_rows = 0;

    $comune_id = '';
    if (isset($_FILES['csv_file']['tmp_name'])) {
        $csv_file_path = $_FILES['csv_file']['tmp_name'];

        if (($handle = fopen($csv_file_path, "r")) !== FALSE) {
            fgetcsv($handle); // Skip header
            $total_rows = 0;
            while (fgetcsv($handle, 1000, ";") !== FALSE) {
                $total_rows++;
            }
            $_SESSION['total_rows'] = $total_rows;
            $_SESSION['processed_rows'] = 0;
            $_SESSION['stop_import'] = false;
            session_write_close();

            rewind($handle);
            fgetcsv($handle);

            $nome_comune = 'Bologna'; // Assign 'Bologna' to a variable
            $query_comuni_check = "SELECT id FROM comuni WHERE nome = ?";
            $stmt_comuni_check = $connection->prepare($query_comuni_check);
            $stmt_comuni_check->bind_param('s', $nome_comune); // Pass the variable, not a string literal
            $stmt_comuni_check->execute();
            $result_comuni = $stmt_comuni_check->get_result();


                if ($result_comuni->num_rows > 0) {
                    // Comune exists, fetch its ID
                    $comune_id = $result_comuni->fetch_assoc()['id'];
                } else {
                    // Comune does not exist, insert it
                    $nome_comune = 'Bologna';
                    $descrizione_comune = 'Prova'; 

                    $query_comuni = "INSERT INTO comuni (nome, descrizione) VALUES (?, ?)";
                    $stmt_comuni = $connection->prepare($query_comuni);
                    $stmt_comuni->bind_param('ss', $nome_comune, $descrizione_comune); // Bind variables instead of literals
                    $stmt_comuni->execute();


                    $comune_id = $connection->insert_id; // Fetch the newly inserted comune's ID
                }
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                session_start();  // Reopen the session to check the stop flag
                if ($_SESSION['stop_import']) {
                    // Stop the import if the flag is set
                    session_write_close();
                    break;
                }
                session_write_close();

                $data_rilevazione = $data[0];
                $date_parts = explode('-', $data_rilevazione);
                $giorno = (int) $date_parts[2]; 
                $mese = (int) $date_parts[1];   
                $anno = (int) $date_parts[0]; 
                $codice_spira = $data[1];
                $id_uni = $data[26];
                $livello = $data[27];
                $tipologia = $data[28];
                $codice = $data[29];
                $codice_arco = $data[30];
                $codice_via = $data[31];
                $nome_via = $data[32];
                $nodo_da = $data[33];
                $nodo_a = $data[34];
                $ordinanza = $data[35];
                $stato = $data[36];
                $codimpsem = $data[37];
                $direzione = $data[38];
                $angolo = $data[39];
                $longitudine = $data[40];
                $latitudine = $data[41];
                $geopoint = $data[42];
                $ID_univoco_stazione_spira = $data[43];
                $giorno_settimana = $data[44];

                                /*        
            "CREATE TABLE IF NOT EXISTS vie (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codice_via VARCHAR(255) NOT NULL,
        Nome_via VARCHAR(255),
        codice_arco VARCHAR(255),
        Nodo_da VARCHAR(255),
        Nodo_a VARCHAR(255),
        direzione VARCHAR(255),
        comune_id INT, 
        FOREIGN KEY (comune_id) REFERENCES comuni(id)
    )",*/
            $query_spira = "INSERT INTO vie (codice_via, nome_via, codice_arco, nodo_da, nodo_a, direzione, comune_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_spira = $connection->prepare($query_spira);
            $stmt_spira->bind_param('ssssssi', $codice_via, $nome_via, $codice_arco, $nodo_da, $nodo_a, $direzione, $comune_id);
            $stmt_spira->execute();
            $via_id = $connection->insert_id; // Fetch the newly inserted station's ID

                /*
                        "CREATE TABLE IF NOT EXISTS spire (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codice_spira VARCHAR(255) NOT NULL,
            codimpsem VARCHAR(255),
            longitudine VARCHAR(255),
            latitudine VARCHAR(255),
            geopoint VARCHAR(255),
            ID_univoco_stazione_spira VARCHAR(255)
        )",
                */
                // Station does not exist, insert it
                $query_spira = "INSERT INTO spire (codice_via, codimpsem, longitudine, latitudine, geopoint, ID_univoco_stazione_spira) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_spira = $connection->prepare($query_spira);
                $stmt_spira->bind_param('ssssss', $via_id, $codimpsem, $longitudine, $latitudine, $geopoint, $ID_univoco_stazione_spira);
                $stmt_spira->execute();
                $spira_id = $connection->insert_id; // Fetch the newly inserted station's ID


        /*
        
        "CREATE TABLE IF NOT EXISTS rilevazioni_traffico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `data` DATE,
            codice_spira INT,
            giorno_settimana VARCHAR(255),
            giorno VARCHAR(255),
            mese VARCHAR(255),
            anno VARCHAR(255),
            FOREIGN KEY (codice_spira) REFERENCES spire(id)
        )",
        */

                $query_spira = "INSERT INTO rilevazioni_traffico (`data`,codice_spira, giorno_settimana, giorno, mese, anno) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_spira = $connection->prepare($query_spira);
                $stmt_spira->bind_param('sissss', $data_rilevazione,$spira_id, $giorno_settimana, $giorno, $mese, $anno);
                $stmt_spira->execute();
                $rilevazioni_traffico_id = $connection->insert_id; // Fetch the newly inserted station's ID

        /*
            "CREATE TABLE IF NOT EXISTS dettagli_generali (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_rilevazione INT,
        livello VARCHAR(255),
        tipologia INT,
        stato VARCHAR(255),
        direzione VARCHAR(255),
        angolo VARCHAR(255),
        FOREIGN KEY (id_rilevazione) REFERENCES rilevazioni_traffico(id)
    )",
        */
        
                $query_spira = "INSERT INTO dettagli_generali (id_rilevazione, livello, tipologia, stato, direzione, angolo) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_spira = $connection->prepare($query_spira);
                $stmt_spira->bind_param('isisss',$rilevazioni_traffico_id, $livello, $tipologia, $stato, $direzione, $angolo);
                $stmt_spira->execute();
                $dettagli_generali_id = $connection->insert_id; // Fetch the newly inserted station's ID
        
        
                /*
        
            "CREATE TABLE IF NOT EXISTS dettagli_traffico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_rilevazione INT,
            `00:00-01:00` INT,
            `01:00-02:00` INT,
            `02:00-03:00` INT,
            `03:00-04:00` INT,
            `04:00-05:00` INT,
            `05:00-06:00` INT,
            `06:00-07:00` INT,
            `07:00-08:00` INT,
            `08:00-09:00` INT,
            `09:00-10:00` INT,
            `10:00-11:00` INT,
            `11:00-12:00` INT,
            `12:00-13:00` INT,
            `13:00-14:00` INT,
            `14:00-15:00` INT,
            `15:00-16:00` INT,
            `16:00-17:00` INT,
            `17:00-18:00` INT,
            `18:00-19:00` INT,
            `19:00-20:00` INT,
            `20:00-21:00` INT,
            `21:00-22:00` INT,
            `22:00-23:00` INT,
            `23:00-24:00` INT,
            notte INT,
            mattina INT,
            pomeriggio INT,
            sera INT,
            FOREIGN KEY (id_rilevazione) REFERENCES rilevazioni_traffico(id)
        )",
        
        */


                // Prepare the hourly data for insertion
                $hourly_data = array_map('intval', array_slice($data, 5, 24));

                // Calculate nighttime, morning, afternoon, evening
                $notte = array_sum(array_slice($hourly_data, 0, 6));
                $mattina = array_sum(array_slice($hourly_data, 6, 6));
                $pomeriggio = array_sum(array_slice($hourly_data, 12, 6));
                $sera = array_sum(array_slice($hourly_data, 18, 6));

                // Insert data into 'rilevazione_flusso_veicoli' table
                $query_flusso = "INSERT INTO dettagli_traffico (
                    id_rilevazione, `00:00-01:00`, `01:00-02:00`, 
                    `02:00-03:00`, `03:00-04:00`, `04:00-05:00`, `05:00-06:00`, `06:00-07:00`, 
                    `07:00-08:00`, `08:00-09:00`, `09:00-10:00`, `10:00-11:00`, `11:00-12:00`, 
                    `12:00-13:00`, `13:00-14:00`, `14:00-15:00`, `15:00-16:00`, `16:00-17:00`, 
                    `17:00-18:00`, `18:00-19:00`, `19:00-20:00`, `20:00-21:00`, `21:00-22:00`, 
                    `22:00-23:00`, `23:00-24:00`, notte, mattina, pomeriggio, sera
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt_flusso = $connection->prepare($query_flusso);

                // Bind the parameters for the query
                $params = array_merge(
                    [$rilevazioni_traffico_id], // Adjust the index for `codice_spira`
                    $hourly_data,
                    [$notte, $mattina, $pomeriggio, $sera]
                );

                // Create the types string
                $types = 'i' . str_repeat('i', 24) . 'iiii';

                // Bind the parameters and execute
                $stmt_flusso->bind_param($types, ...$params);

                if ($stmt_flusso->execute()) {
                    $successful_inserts++;
                } else {
                    $skipped_rows++;
                }

                $processed_rows++;
                session_start();
                $_SESSION['processed_rows'] = $processed_rows;
                session_write_close();
            }

            fclose($handle);
        } else {
            echo "Errore nell'apertura del file CSV.";
        }
    } else {
        echo "Nessun file CSV fornito.";
    }

    return [
        "successful_inserts" => $successful_inserts,
        "skipped_rows" => $skipped_rows
    ];
}








// Creare le tabelle
createTables($connection);

// Importare i dati e ottenere il riepilogo
importData($connection);

// Chiudere la connessione
$connection->close();
?>
