<?php

// Start output buffering to prevent any early output
ob_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use PortfolioTracker\Config\App;

// Initialize application
App::init();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $marketStatus = App::isMarketOpen() ? 'OPEN' : 'CLOSED';
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'status' => $marketStatus,
        'timestamp' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ]);
    
} catch (Exception $e) {
    App::getLogger()->error("Market status API error", ['error' => $e->getMessage()]);
    
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to get market status: ' . $e->getMessage()
    ]);
}