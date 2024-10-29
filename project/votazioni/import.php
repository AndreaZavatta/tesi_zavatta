<?php
function processJsonFile($filePath) {
    // Load database configuration from JSON file
    $configFilePath = '../db_config.json';

    if (!file_exists($configFilePath)) {
        return ['error' => 'Configuration file not found.'];
    }

    $configData = file_get_contents($configFilePath);
    $dbConfig = json_decode($configData, true);

    if ($dbConfig === null) {
        return ['error' => 'Failed to parse database configuration JSON.'];
    }

    // Load JSON data from the provided file path
    $jsonData = file_get_contents($filePath);
    $data = json_decode($jsonData, true);

    if ($data === null) {
        return ['error' => 'Failed to parse JSON data.'];
    }

    // Connect to MySQL database
    $conn = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['password'], $dbConfig['database']);

    if ($conn->connect_error) {
        return ['error' => "Connection failed: " . $conn->connect_error];
    }

    // Ensure tables exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS politici (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nominativo VARCHAR(255) UNIQUE,
            gruppo_politico VARCHAR(255)
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS sedute (
            id INT AUTO_INCREMENT PRIMARY KEY,
            data_seduta DATE UNIQUE
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS presenze (
            id INT AUTO_INCREMENT PRIMARY KEY,
            politico_id INT,
            seduta_id INT,
            presenza VARCHAR(255),
            FOREIGN KEY (politico_id) REFERENCES politici(id),
            FOREIGN KEY (seduta_id) REFERENCES sedute(id)
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS votazioni (
            id INT AUTO_INCREMENT PRIMARY KEY,
            presenza_id INT,
            num_votazioni INT,
            percentuale_presenza_alle_votazioni FLOAT,
            FOREIGN KEY (presenza_id) REFERENCES presenze(id)
        )
    ");

    // Initialize counters
    $successfulInserts = 0;
    $skippedRows = 0;
    $totalRows = count($data);

    // Process each record in the JSON data
    foreach ($data as $record) {
        $nominativo = trim($record['nominativo']);
        $gruppo_politico = trim($record['gruppo_politico']);
        $data_seduta = $record['data_seduta'];
        $presenza = $record['presenza'];
        $num_votazioni = $record['num_votazioni'];
        $percentuale_presenza_alle_votazioni = $record['percentuale_presenza_alle_votazioni'];

        // Insert or fetch politician ID
        $stmt = $conn->prepare("SELECT id FROM politici WHERE nominativo = ?");
        $stmt->bind_param("s", $nominativo);
        $stmt->execute();
        $result = $stmt->get_result();
        $politico_id = $result->fetch_assoc()['id'] ?? null;

        if (!$politico_id) {
            $stmt = $conn->prepare("INSERT INTO politici (nominativo, gruppo_politico) VALUES (?, ?)");
            $stmt->bind_param("ss", $nominativo, $gruppo_politico);
            $stmt->execute();
            $politico_id = $stmt->insert_id;
        }

        // Insert or fetch session ID
        $stmt = $conn->prepare("SELECT id FROM sedute WHERE data_seduta = ?");
        $stmt->bind_param("s", $data_seduta);
        $stmt->execute();
        $result = $stmt->get_result();
        $seduta_id = $result->fetch_assoc()['id'] ?? null;

        if (!$seduta_id) {
            $stmt = $conn->prepare("INSERT INTO sedute (data_seduta) VALUES (?)");
            $stmt->bind_param("s", $data_seduta);
            $stmt->execute();
            $seduta_id = $stmt->insert_id;
        }

        // Insert presence record
        $stmt = $conn->prepare("INSERT INTO presenze (politico_id, seduta_id, presenza) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $politico_id, $seduta_id, $presenza);
        $stmt->execute();
        $presenza_id = $stmt->insert_id;

        // Insert voting details
        $stmt = $conn->prepare("INSERT INTO votazioni (presenza_id, num_votazioni, percentuale_presenza_alle_votazioni) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $presenza_id, $num_votazioni, $percentuale_presenza_alle_votazioni);
        $stmt->execute();

        // Commit after each record
        if ($stmt->affected_rows > 0) {
            $successfulInserts++;
        } else {
            $skippedRows++;
        }
    }

    // Close the database connection
    $conn->close();

    // Return the result as an array
    return [
        'message' => 'Data processed successfully!',
        'successful_inserts' => $successfulInserts,
        'skipped_rows' => $skippedRows,
        'total_rows' => $totalRows
    ];
}
?>
