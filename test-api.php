<?php

// Simple test to diagnose API issues
ob_start();
header('Content-Type: application/json');

try {
    // Test 1: Basic PHP functionality
    echo json_encode([
        'test' => 'basic_php',
        'status' => 'working',
        'php_version' => PHP_VERSION,
        'message' => 'Basic PHP is working'
    ]);
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'test' => 'basic_php', 
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}