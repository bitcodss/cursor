<?php

// Debug test API
ob_start();
header('Content-Type: application/json');

try {
    $test = [
        'success' => true,
        'message' => 'API endpoint is working',
        'php_version' => PHP_VERSION,
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI'],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    ob_clean();
    echo json_encode($test);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}