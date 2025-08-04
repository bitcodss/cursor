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
use PortfolioTracker\Models\Portfolio;

// Initialize application
App::init();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$portfolioId = (int)($_GET['id'] ?? 0);

if (!$portfolioId) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Portfolio ID required']);
    exit;
}

try {
    $portfolioModel = new Portfolio();
    
    // Get portfolio summary
    $summary = $portfolioModel->getSummary($portfolioId);
    
    if ($summary) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'summary' => $summary
        ]);
    } else {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Portfolio not found'
        ]);
    }
    
} catch (Exception $e) {
    App::getLogger()->error("Portfolio summary API error", [
        'portfolio_id' => $portfolioId,
        'error' => $e->getMessage()
    ]);
    
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to get portfolio summary: ' . $e->getMessage()
    ]);
}