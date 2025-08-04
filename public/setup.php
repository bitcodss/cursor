<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PortfolioTracker\Config\App;
use PortfolioTracker\Config\Database;

// Initialize application
App::init();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'test_db':
            try {
                if (Database::testConnection()) {
                    $message = "Database connection successful!";
                } else {
                    $error = "Database connection failed. Please check your configuration.";
                }
            } catch (Exception $e) {
                $error = "Database connection error: " . $e->getMessage();
            }
            break;
            
        case 'create_tables':
            try {
                $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
                if ($sql === false) {
                    throw new Exception("Could not read schema.sql file");
                }
                
                $pdo = Database::getConnection();
                $pdo->exec($sql);
                
                $message = "Database tables created successfully!";
            } catch (Exception $e) {
                $error = "Failed to create tables: " . $e->getMessage();
            }
            break;
            
        case 'test_api':
            try {
                $apiService = new \PortfolioTracker\Services\YahooFinanceAPI();
                $quote = $apiService->getQuote('AAPL');
                
                if ($quote) {
                    $message = "Yahoo Finance API test successful! Retrieved quote for AAPL: $" . number_format($quote['price'], 2);
                } else {
                    $error = "Yahoo Finance API test failed. Could not retrieve stock quote.";
                }
            } catch (Exception $e) {
                $error = "API test error: " . $e->getMessage();
            }
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Tracker Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i class="bi bi-gear"></i>
                            Portfolio Tracker Setup
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5>1. Configuration</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Make sure you have:</p>
                                        <ul>
                                            <li>Copied <code>.env.example</code> to <code>.env</code></li>
                                            <li>Updated database credentials in <code>.env</code></li>
                                            <li>Set API keys (optional but recommended)</li>
                                        </ul>
                                        
                                        <h6>Current Configuration:</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <td>Database Host:</td>
                                                <td><?php echo App::get('DB_HOST', 'Not set'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Database Name:</td>
                                                <td><?php echo App::get('DB_NAME', 'Not set'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Environment:</td>
                                                <td><?php echo App::getEnv(); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Debug Mode:</td>
                                                <td><?php echo App::isDebug() ? 'On' : 'Off'; ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5>2. Database Setup</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" class="mb-3">
                                            <input type="hidden" name="action" value="test_db">
                                            <button type="submit" class="btn btn-outline-primary">
                                                <i class="bi bi-database"></i>
                                                Test Database Connection
                                            </button>
                                        </form>
                                        
                                        <form method="post">
                                            <input type="hidden" name="action" value="create_tables">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-table"></i>
                                                Create Database Tables
                                            </button>
                                        </form>
                                        
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                This will create all necessary tables and insert default data.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5>3. API Testing</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post">
                                            <input type="hidden" name="action" value="test_api">
                                            <button type="submit" class="btn btn-outline-success">
                                                <i class="bi bi-cloud"></i>
                                                Test Yahoo Finance API
                                            </button>
                                        </form>
                                        
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                Tests if stock data can be retrieved from Yahoo Finance.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5>4. Complete Setup</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Once all tests pass, you can:</p>
                                        <a href="/" class="btn btn-success">
                                            <i class="bi bi-house"></i>
                                            Go to Dashboard
                                        </a>
                                        
                                        <div class="mt-3">
                                            <h6>Default Login:</h6>
                                            <p class="mb-1"><strong>Username:</strong> admin</p>
                                            <p class="mb-0"><strong>Password:</strong> changeme123</p>
                                            <small class="text-warning">Remember to change the default password!</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5>5. Next Steps</h5>
                            </div>
                            <div class="card-body">
                                <ol>
                                    <li><strong>Create a Portfolio:</strong> Start by creating your first investment portfolio</li>
                                    <li><strong>Add Stocks:</strong> Search and add stocks/ETFs to track</li>
                                    <li><strong>Record Transactions:</strong> Input your buy/sell transactions</li>
                                    <li><strong>Monitor Performance:</strong> Track your portfolio's performance over time</li>
                                    <li><strong>Set Up Automation:</strong> Configure cron jobs for automatic price updates</li>
                                </ol>
                                
                                <div class="alert alert-info mt-3">
                                    <h6><i class="bi bi-lightbulb"></i> Pro Tips:</h6>
                                    <ul class="mb-0">
                                        <li>Set up a cron job to run <code>php cron/update-prices.php</code> every 15 minutes during market hours</li>
                                        <li>Use the CSV import feature to bulk import transactions from your broker</li>
                                        <li>Enable price change alerts to stay informed about your investments</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>