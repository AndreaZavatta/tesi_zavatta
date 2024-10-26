<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['json_file'])) {
    // Move uploaded file to a temporary location
    $tempFilePath = $_FILES['json_file']['tmp_name'];
    $destinationPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $_FILES['json_file']['name'];
    
    if (move_uploaded_file($tempFilePath, $destinationPath)) {
        // Run the Python script, passing the uploaded file path as an argument
        $command = escapeshellcmd("python3 ../votazioni/import.py " . escapeshellarg($destinationPath));
        $output = [];
        $return_var = 0;
        
        // Run the Python script and wait for it to complete
        exec($command, $output, $return_var);

        // Check if the Python script executed successfully
        if ($return_var === 0) {
            echo json_encode([
                'message' => 'File caricato e processato con successo!',
                'successful_inserts' => count($output), // Example: Replace with actual values
                'skipped_rows' => 0, // Replace with actual values if available
                'total_rows' => count($output) // Replace with actual values if available
            ]);
        } else {
            echo json_encode(['error' => 'Error executing Python script: ' . implode("\n", $output)]);
        }
    } else {
        echo json_encode(['error' => 'Error moving uploaded file.']);
    }
} else {
    echo json_encode(['error' => 'No file uploaded or invalid request method.']);
}
