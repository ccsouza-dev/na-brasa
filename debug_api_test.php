<?php
// debug_api_test.php

// Mock environment for CLI
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api.php';
$_SERVER['SCRIPT_NAME'] = '/api.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_GET['action'] = 'get_tables_status';
$_REQUEST['action'] = 'get_tables_status';

// Prevent header() calls from breaking CLI
// function header($str) { echo "[HEADER] $str\n"; }

// Capture output
ob_start();

try {
    require __DIR__ . '/public/api.php';
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage();
}

$output = ob_get_clean();
echo "--- OUTPUT START ---\n";
echo $output;
echo "\n--- OUTPUT END ---\n";
