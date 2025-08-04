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
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $apiService = new YahooFinanceAPI();
    
    // Update prices for all portfolio stocks
    $updated = $apiService->updatePortfolioStockPrices();
    
    // Clean any buffered output and send JSON
    ob_clean();
    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'message' => "Updated $updated stock prices"
    ]);
    
} catch (Exception $e) {
    App::getLogger()->error("Price update API error", ['error' => $e->getMessage()]);
    
    // Clean any buffered output and send error JSON
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update prices: ' . $e->getMessage()
    ]);
}