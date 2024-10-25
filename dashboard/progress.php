<?php
session_start();

// Check if an import process has started
if (!isset($_SESSION['total_rows']) || $_SESSION['total_rows'] == 0) {
    echo json_encode([
        'error' => 'The import process has not started yet.'
    ]);
    exit;
}

// Proceed with progress reporting if import has started
if (isset($_SESSION['total_rows']) && isset($_SESSION['processed_rows'])) {
    $total_rows = $_SESSION['total_rows'];
    $processed_rows = $_SESSION['processed_rows'];

    // Calculate the percentage of completion
    $percentage = ($processed_rows / $total_rows) * 100;

    echo json_encode([
        'percentage' => round($percentage),
        'processed' => $processed_rows,
        'total' => $total_rows
    ]);
}
?>
