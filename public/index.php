<?php

// Try composer autoloader first, fallback to manual
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../autoload.php';
}

use PortfolioTracker\Config\App;
use PortfolioTracker\Config\Database;

// Initialize application
App::init();

// Test database connection
if (!Database::testConnection()) {
    die("Database connection failed. Please check your configuration.");
}

// Basic routing
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove any trailing slashes and normalize
$uri = rtrim($uri, '/') ?: '/';

try {
    switch ($uri) {
        case '/':
            require_once __DIR__ . '/views/dashboard.php';
            break;
        
        case '/portfolios':
            require_once __DIR__ . '/views/portfolios.php';
            break;
            
        case '/portfolio':
            $portfolioId = $_GET['id'] ?? null;
            if (!$portfolioId) {
                header("Location: /portfolios");
                exit;
            }
            require_once __DIR__ . '/views/portfolio-detail.php';
            break;
            
        case '/transactions':
            require_once __DIR__ . '/views/transactions.php';
            break;
            
        case '/stocks':
            require_once __DIR__ . '/views/stocks.php';
            break;
            
        case '/api/search-stocks':
            require_once __DIR__ . '/api/search-stocks.php';
            break;
            
        case '/api/stock-quote':
            require_once __DIR__ . '/api/stock-quote.php';
            break;
            
        case '/api/portfolio-summary':
            require_once __DIR__ . '/api/portfolio-summary.php';
            break;
            
        case '/api/add-transaction':
            require_once __DIR__ . '/api/add-transaction.php';
            break;
            
        case '/api/update-prices':
            require_once __DIR__ . '/api/update-prices.php';
            break;
            
        case '/api/market-status':
            require_once __DIR__ . '/api/market-status.php';
            break;
            
        case '/api/update-historical':
            require_once __DIR__ . '/api/update-historical.php';
            break;
            
        case '/api/debug-test':
            require_once __DIR__ . '/api/debug-test.php';
            break;
            
        case '/setup':
            require_once __DIR__ . '/setup.php';
            break;
            
        default:
            http_response_code(404);
            require_once __DIR__ . '/views/404.php';
            break;
    }
} catch (Exception $e) {
    App::getLogger()->error("Application error", ['error' => $e->getMessage(), 'uri' => $uri]);
    
    if (App::isDebug()) {
        echo "<h1>Error</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        http_response_code(500);
        echo "<h1>Internal Server Error</h1>";
        echo "<p>Something went wrong. Please try again later.</p>";
    }
}