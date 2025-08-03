<?php

/**
 * Cron script to update stock prices
 * 
 * Usage: php cron/update-prices.php
 * 
 * Recommended cron schedule:
 * */15 9-16 * * 1-5 /usr/bin/php /path/to/portfolio-tracker/cron/update-prices.php
 * (Every 15 minutes from 9 AM to 4 PM, Monday to Friday)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PortfolioTracker\Config\App;
use PortfolioTracker\Config\Database;
use PortfolioTracker\Services\YahooFinanceAPI;
use PortfolioTracker\Models\Stock;

// Initialize application
App::init();

$logger = App::getLogger();
$logger->info("Starting price update cron job");

try {
    // Check if we should run during current time
    if (!App::isMarketOpen() && !shouldForceUpdate()) {
        $logger->info("Market is closed and no force update flag, skipping price update");
        exit(0);
    }
    
    $apiService = new YahooFinanceAPI();
    $stockModel = new Stock();
    
    // Get stocks that need updating
    $stocks = $stockModel->getPortfolioStocks();
    if (empty($stocks)) {
        $logger->info("No portfolio stocks found, nothing to update");
        exit(0);
    }
    
    $logger->info("Found " . count($stocks) . " stocks to update");
    
    // Update prices
    $updated = $apiService->updatePortfolioStockPrices();
    
    $logger->info("Price update completed", [
        'total_stocks' => count($stocks),
        'updated' => $updated
    ]);
    
    // Update last run time
    updateLastRunTime();
    
    echo "Updated $updated out of " . count($stocks) . " stocks\n";
    
} catch (Exception $e) {
    $logger->error("Price update cron job failed", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Check if update should be forced (command line flag)
 */
function shouldForceUpdate(): bool
{
    global $argv;
    return in_array('--force', $argv ?? []);
}

/**
 * Update last run time in settings
 */
function updateLastRunTime(): void
{
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, description) 
            VALUES ('last_price_update', ?, 'Last time prices were updated')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([date('Y-m-d H:i:s')]);
    } catch (Exception $e) {
        App::getLogger()->warning("Failed to update last run time", ['error' => $e->getMessage()]);
    }
}