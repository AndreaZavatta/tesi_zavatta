<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Load database configuration from db_config.json
$configPath = __DIR__ . '/../db_config.json';
if (!file_exists($configPath)) {
    echo json_encode(["error" => "Configuration file not found"]);
    exit;
}

$configData = file_get_contents($configPath);
$dbConfig = json_decode($configData, true);
if (!$dbConfig) {
    echo json_encode(["error" => "Error parsing the configuration file"]);
    exit;
}

// Database connection
try {
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};port={$dbConfig['port']}", $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => "Database connection error: " . $e->getMessage()]);
    exit;
}

// Get request path
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Parse route based on URL
if (strpos($requestUri, 'api/data') !== false && $requestMethod === 'GET') {
    // Handle /api/data
    try {
        $sql = "
            SELECT 
                p.nominativo,
                p.gruppo_politico,
                s.data_seduta,
                pr.presenza,
                v.num_votazioni,
                v.percentuale_presenza_alle_votazioni
            FROM 
                presenze AS pr
            JOIN 
                politici AS p ON pr.politico_id = p.id
            JOIN 
                sedute AS s ON pr.seduta_id = s.id
            JOIN 
                votazioni AS v ON v.presenza_id = pr.id
        ";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($results);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} elseif (strpos($requestUri, 'api/seduta') !== false && $requestMethod === 'GET') {
    // Handle /api/seduta with query parameter `s`
    $sedDay = $_GET['s'] ?? null;
    if (!$sedDay) {
        echo json_encode(["error" => "Missing sedDay query parameter"]);
        exit;
    }

    try {
        $sql = "
            SELECT 
                p.nominativo,
                p.gruppo_politico,
                s.data_seduta,
                pr.presenza,
                v.num_votazioni,
                v.percentuale_presenza_alle_votazioni
            FROM 
                presenze AS pr
            JOIN 
                politici AS p ON pr.politico_id = p.id
            JOIN 
                sedute AS s ON pr.seduta_id = s.id
            JOIN 
                votazioni AS v ON v.presenza_id = pr.id
            WHERE 
                s.data_seduta = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sedDay]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($results);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Error fetching data for the specified seduta: " . $e->getMessage()]);
    }
} else {
    // Handle 404 Not Found for unrecognized routes
    http_response_code(404);
    echo json_encode(["error" => "Endpoint not found"]);
}
?>
