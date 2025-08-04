<?php

// Start output buffering to prevent any early output
ob_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use PortfolioTracker\Config\App;
use PortfolioTracker\Models\Transaction;
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

// Verify CSRF token
if (!App::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

try {
    $transactionModel = new Transaction();
    $apiService = new YahooFinanceAPI();
    
    // Validate required fields
    $portfolioId = (int)($_POST['portfolio_id'] ?? 0);
    $transactionType = $_POST['transaction_type'] ?? '';
    $transactionDate = $_POST['transaction_date'] ?? '';
    
    if (!$portfolioId || !$transactionType || !$transactionDate) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Prepare transaction data
    $data = [
        'portfolio_id' => $portfolioId,
        'transaction_type' => $transactionType,
        'transaction_date' => $transactionDate,
        'broker' => $_POST['broker'] ?? '',
        'notes' => $_POST['notes'] ?? '',
        'fees' => (float)($_POST['fees'] ?? 0)
    ];
    
    // Handle stock-related transactions
    if (in_array($transactionType, ['BUY', 'SELL', 'DIVIDEND', 'SPLIT'])) {
        $symbol = strtoupper(trim($_POST['symbol'] ?? ''));
        if (empty($symbol)) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Stock symbol is required']);
            exit;
        }
        
        // Get or create stock
        $stock = $apiService->getOrCreateStock($symbol);
        if (!$stock) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Invalid stock symbol']);
            exit;
        }
        
        $data['stock_id'] = $stock['id'];
        
        if (in_array($transactionType, ['BUY', 'SELL'])) {
            $shares = (float)($_POST['shares'] ?? 0);
            $pricePerShare = (float)($_POST['price_per_share'] ?? 0);
            
            if ($shares <= 0 || $pricePerShare <= 0) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Shares and price must be greater than zero']);
                exit;
            }
            
            $data['shares'] = $shares;
            $data['price_per_share'] = $pricePerShare;
            $data['total_amount'] = $shares * $pricePerShare;
        } elseif ($transactionType === 'DIVIDEND') {
            $amount = (float)($_POST['amount'] ?? 0);
            if ($amount <= 0) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Dividend amount must be greater than zero']);
                exit;
            }
            $data['total_amount'] = $amount;
        } elseif ($transactionType === 'SPLIT') {
            $splitRatio = (float)($_POST['split_ratio'] ?? 0);
            if ($splitRatio <= 0) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Split ratio must be greater than zero']);
                exit;
            }
            $data['split_ratio'] = $splitRatio;
            $data['total_amount'] = 0; // No monetary amount for splits
        }
    } else {
        // Cash transactions
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Amount must be greater than zero']);
            exit;
        }
        $data['total_amount'] = $amount;
    }
    
    // Create transaction
    $transactionId = $transactionModel->create($data);
    
    if ($transactionId) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'transaction_id' => $transactionId,
            'message' => 'Transaction added successfully'
        ]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Failed to create transaction']);
    }
    
} catch (Exception $e) {
    App::getLogger()->error("Add transaction API error", [
        'error' => $e->getMessage(),
        'data' => $_POST
    ]);
    
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to add transaction: ' . $e->getMessage()
    ]);
}