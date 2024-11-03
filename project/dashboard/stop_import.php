<?php
session_start();

// Set the stop flag to true
$_SESSION['stop_import'] = true;
$_SESSION['total_rows'] = 0;
$_SESSION['processed_rows'] = 0;
echo json_encode(['message' => 'Import process stopped.']);
?>
