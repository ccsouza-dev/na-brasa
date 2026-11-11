<?php
include 'legacy/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Mock GET request
$_GET['action'] = 'get_open_orders';

// Capture output
ob_start();
include 'legacy/api.php';
$output = ob_get_clean();

echo "Output length: " . strlen($output) . "\n";
echo "Output start: " . substr($output, 0, 100) . "\n";
echo "Output end: " . substr($output, -100) . "\n";
echo "Is JSON? " . (json_decode($output) ? 'Yes' : 'No') . "\n";
if (!json_decode($output)) {
    echo "JSON Error: " . json_last_error_msg() . "\n";
}
?>