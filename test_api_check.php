<?php
require_once __DIR__ . '/public/api.php';

// Simulate request
$_GET['action'] = 'get_tables_status';
// We need to capture output
ob_start();
// This might not work if api.php doesn't use a class or function but runs directly.
// Let's check api.php content first.
