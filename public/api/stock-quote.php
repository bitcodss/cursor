<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use PortfolioTracker\Config\App;
use PortfolioTracker\Services\YahooFinanceAPI;

// Initialize application
App::init();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$symbol = $_GET['symbol'] ?? '';

if (empty($symbol)) {
    echo json_encode(['success' => false, 'error' => 'Symbol parameter required']);
    exit;
}

try {
    $apiService = new YahooFinanceAPI();
    
    // Get or create stock
    $stock = $apiService->getOrCreateStock($symbol);
    
    if ($stock) {
        echo json_encode([
            'success' => true,
            'quote' => [
                'symbol' => $stock['symbol'],
                'name' => $stock['name'],
                'price' => (float)$stock['current_price'],
                'change_amount' => (float)$stock['change_amount'],
                'change_percent' => (float)$stock['change_percent'],
                'volume' => (int)$stock['volume'],
                'market_cap' => $stock['market_cap'] ? (int)$stock['market_cap'] : null,
                'exchange' => $stock['exchange'],
                'sector' => $stock['sector'],
                'industry' => $stock['industry'],
                'is_etf' => (bool)$stock['is_etf'],
                'open' => null, // Would need to be added to current_prices table
                'high' => null,
                'low' => null,
                'previous_close' => null
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Could not fetch stock data'
        ]);
    }
    
} catch (Exception $e) {
    App::getLogger()->error("Stock quote API error", [
        'symbol' => $symbol,
        'error' => $e->getMessage()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch stock quote'
    ]);
}