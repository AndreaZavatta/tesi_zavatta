<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['json_file'])) {
    // Read the uploaded JSON file content
    $jsonData = file_get_contents($_FILES['json_file']['tmp_name']);
    
    if ($jsonData === false) {
        echo "Error reading file content.";
        exit;
    }

    // Create a temporary file to store JSON data
    $tempFile = tempnam(sys_get_temp_dir(), 'json_data');
    file_put_contents($tempFile, $jsonData);

    // Run the Python script with the path to the temporary file
    $command = escapeshellcmd("python3 ../votazioni/import.py $tempFile");
    $output = shell_exec($command);

    // Remove the temporary file after the script runs
    unlink($tempFile);
} else {
    echo "No file uploaded.";
}
?>
