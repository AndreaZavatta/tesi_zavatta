<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

error_log("Prova zava!");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['json_file'])) {
    // Move the uploaded file to a temporary location
    $tempFilePath = $_FILES['json_file']['tmp_name'];
    $destinationPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $_FILES['json_file']['name'];

    if (move_uploaded_file($tempFilePath, $destinationPath)) {
        // Include `import.php` to access the `processJsonFile` function
        include_once '../votazioni/import.php';

        // Call the function to process the JSON file
        $result = processJsonFile($destinationPath);


        // Check if there was an error in processing
        if (isset($result['error'])) {
            echo json_encode(['error' => $result['error']]);
        } else {
            echo json_encode([
                'message' => $result['message'],
                'successful_inserts' => $result['successful_inserts'],
                'skipped_rows' => $result['skipped_rows'],
                'total_rows' => $result['total_rows']
            ]);
        }
    } else {
        echo json_encode(['error' => 'Error moving uploaded file.']);
    }
} else {
    echo json_encode(['error' => 'No file uploaded or invalid request method.']);
}
?>
