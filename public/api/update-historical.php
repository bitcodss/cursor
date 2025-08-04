<?php

// Start output buffering to prevent any early output
ob_start();

header('Content-Type: application/json');

// Try composer autoloader first, fallback to manual
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../../autoload.php';
}

use PortfolioTracker\Config\App;
use PortfolioTracker\Services\YahooFinanceAPI;

// Initialize application
App::init();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$symbol = $_POST['symbol'] ?? $_GET['symbol'] ?? '';

if (empty($symbol)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Symbol parameter required']);
    exit;
}

try {
    $apiService = new YahooFinanceAPI();
    
    // Update historical data for the specific symbol
    $updated = $apiService->updateHistoricalData($symbol);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'symbol' => $symbol,
        'updated' => $updated,
        'message' => "Updated historical data for $symbol"
    ]);
    
} catch (Exception $e) {
    App::getLogger()->error("Historical data update API error", [
        'symbol' => $symbol,
        'error' => $e->getMessage()
    ]);
    
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update historical data: ' . $e->getMessage()
    ]);
}