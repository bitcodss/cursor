<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use PortfolioTracker\Config\App;
use PortfolioTracker\Models\Stock;
use PortfolioTracker\Services\YahooFinanceAPI;

// Initialize application
App::init();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$query = $_GET['q'] ?? '';

if (empty($query) || strlen($query) < 1) {
    echo json_encode(['success' => false, 'error' => 'Query parameter required']);
    exit;
}

try {
    $stockModel = new Stock();
    $apiService = new YahooFinanceAPI();
    
    $results = [];
    
    // First, search local database
    $localStocks = $stockModel->search($query, 10);
    foreach ($localStocks as $stock) {
        $results[] = [
            'symbol' => $stock['symbol'],
            'name' => $stock['name'],
            'exchange' => $stock['exchange'],
            'current_price' => $stock['current_price'],
            'change_percent' => $stock['change_percent'],
            'is_etf' => (bool)$stock['is_etf'],
            'source' => 'local'
        ];
    }
    
    // If we have fewer than 10 results, search Yahoo Finance
    if (count($results) < 10) {
        $apiResults = $apiService->searchStocks($query);
        foreach ($apiResults as $apiStock) {
            // Check if we already have this stock in results
            $exists = false;
            foreach ($results as $existing) {
                if ($existing['symbol'] === $apiStock['symbol']) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $results[] = [
                    'symbol' => $apiStock['symbol'],
                    'name' => $apiStock['name'],
                    'exchange' => $apiStock['exchange'],
                    'type' => $apiStock['type'],
                    'is_etf' => $apiStock['is_etf'],
                    'source' => 'api'
                ];
            }
            
            // Limit total results
            if (count($results) >= 20) {
                break;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    App::getLogger()->error("Stock search API error", [
        'query' => $query,
        'error' => $e->getMessage()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Search failed'
    ]);
}