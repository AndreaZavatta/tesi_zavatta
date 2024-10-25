<?php
session_start();

// Set the stop flag to true
$_SESSION['stop_import'] = true;

echo json_encode(['message' => 'Import process stopped.']);
?>
