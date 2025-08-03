<?php

use PortfolioTracker\Models\Portfolio;
use PortfolioTracker\Models\Transaction;
use PortfolioTracker\Config\App;

$portfolioModel = new Portfolio();
$transactionModel = new Transaction();

// Get portfolio ID from URL
$portfolioId = (int)($_GET['id'] ?? 0);

if (!$portfolioId) {
    header("Location: /portfolios");
    exit;
}

// Get portfolio details
$portfolio = $portfolioModel->getById($portfolioId);
if (!$portfolio) {
    header("Location: /portfolios");
    exit;
}

// Get portfolio summary
$summary = $portfolioModel->getSummary($portfolioId);
$holdings = $portfolioModel->getHoldings($portfolioId);
$transactions = $transactionModel->getByPortfolio($portfolioId, 20);
$assetAllocation = $portfolioModel->getAssetAllocation($portfolioId);

// Set page variables
$pageTitle = htmlspecialchars($portfolio['name']);
$currentPage = 'portfolios';

// Start output buffering for content
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/portfolios">Portfolios</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($portfolio['name']); ?></li>
            </ol>
        </nav>
        <h1 class="h2 mb-0">
            <i class="bi bi-briefcase"></i>
            <?php echo htmlspecialchars($portfolio['name']); ?>
        </h1>
        <?php if ($portfolio['description']): ?>
            <p class="text-muted"><?php echo htmlspecialchars($portfolio['description']); ?></p>
        <?php endif; ?>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="bi bi-plus-circle"></i>
                Add Transaction
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="updatePrices()">
                <i class="bi bi-arrow-clockwise"></i>
                Update Prices
            </button>
        </div>
    </div>
</div>

<!-- Portfolio Summary Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-1">Total Value</h6>
                        <h4 class="mb-0"><?php echo App::formatCurrency($summary['total_value']); ?></h4>
                        <small class="text-muted">Cash: <?php echo App::formatCurrency($summary['cash_value']); ?></small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-wallet2 text-primary fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-1">Total Gain/Loss</h6>
                        <h4 class="mb-0 <?php echo $summary['total_gain_loss'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo App::formatCurrency($summary['total_gain_loss']); ?>
                        </h4>
                        <small class="<?php echo $summary['total_return_percent'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo App::formatPercentage($summary['total_return_percent']); ?>
                        </small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-graph-<?php echo $summary['total_gain_loss'] >= 0 ? 'up' : 'down'; ?> text-<?php echo $summary['total_gain_loss'] >= 0 ? 'success' : 'danger'; ?> fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-1">Unrealized G/L</h6>
                        <h4 class="mb-0 <?php echo $summary['unrealized_gain_loss'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo App::formatCurrency($summary['unrealized_gain_loss']); ?>
                        </h4>
                        <small class="text-muted">Holdings value change</small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-graph-up-arrow text-info fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-1">Dividend Income</h6>
                        <h4 class="mb-0 positive">
                            <?php echo App::formatCurrency($summary['dividend_income']); ?>
                        </h4>
                        <small class="text-muted">All time dividends</small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-cash-coin text-success fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Holdings -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-graph-up-arrow"></i>
                    Current Holdings
                </h5>
                <span class="badge bg-primary"><?php echo count($holdings); ?> positions</span>
            </div>
            <div class="card-body">
                <?php if (empty($holdings)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-graph-up-arrow display-4 text-muted"></i>
                        <p class="mt-3 text-muted">No holdings yet</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                            Add Your First Transaction
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="holdingsTable">
                            <thead>
                                <tr>
                                    <th>Symbol</th>
                                    <th class="text-end">Shares</th>
                                    <th class="text-end">Avg Cost</th>
                                    <th class="text-end">Current Price</th>
                                    <th class="text-end">Market Value</th>
                                    <th class="text-end">Gain/Loss</th>
                                    <th class="text-end">Return %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($holdings as $holding): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($holding['symbol']); ?></strong>
                                            <?php if ($holding['is_etf']): ?>
                                                <span class="badge bg-secondary ms-1">ETF</span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($holding['name']); ?></small>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <?php echo number_format($holding['shares'], 3); ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo App::formatCurrency($holding['average_cost']); ?>
                                    </td>
                                    <td class="text-end">
                                        <div>
                                            <?php echo App::formatCurrency($holding['current_price']); ?>
                                            <?php if ($holding['change_percent'] != 0): ?>
                                                <br>
                                                <small class="<?php echo $holding['change_percent'] >= 0 ? 'positive' : 'negative'; ?>">
                                                    <?php echo ($holding['change_percent'] >= 0 ? '+' : '') . number_format($holding['change_percent'], 2); ?>%
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <strong><?php echo App::formatCurrency($holding['current_value']); ?></strong>
                                    </td>
                                    <td class="text-end <?php echo $holding['unrealized_gain_loss'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo App::formatCurrency($holding['unrealized_gain_loss']); ?>
                                    </td>
                                    <td class="text-end <?php echo $holding['gain_loss_percent'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo number_format($holding['gain_loss_percent'], 2); ?>%
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Asset Allocation & Recent Transactions -->
    <div class="col-lg-4 mb-4">
        <!-- Asset Allocation -->
        <?php if (!empty($assetAllocation)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="bi bi-pie-chart"></i>
                    Asset Allocation
                </h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 200px;">
                    <canvas id="allocationChart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="bi bi-list-check"></i>
                    Recent Transactions
                </h6>
                <a href="/transactions?portfolio=<?php echo $portfolioId; ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($transactions)): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-list-check display-6 text-muted"></i>
                        <p class="mt-2 text-muted">No transactions yet</p>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                            Add Transaction
                        </button>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($transactions, 0, 5) as $transaction): ?>
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <?php if ($transaction['symbol']): ?>
                                            <span class="badge bg-secondary"><?php echo $transaction['symbol']; ?></span>
                                        <?php endif; ?>
                                        <?php echo ucfirst(strtolower($transaction['transaction_type'])); ?>
                                    </h6>
                                    <?php if ($transaction['shares'] > 0): ?>
                                        <p class="mb-1 text-muted small">
                                            <?php echo number_format($transaction['shares'], 2); ?> shares
                                            @ <?php echo App::formatCurrency($transaction['price_per_share']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?></small>
                                </div>
                                <div class="text-end">
                                    <strong class="<?php 
                                        echo in_array($transaction['transaction_type'], ['BUY', 'WITHDRAWAL']) ? 'negative' : 'positive'; 
                                    ?>">
                                        <?php 
                                        $amount = $transaction['total_amount'];
                                        if (in_array($transaction['transaction_type'], ['BUY', 'WITHDRAWAL'])) {
                                            $amount = -$amount;
                                        }
                                        echo App::formatCurrency($amount); 
                                        ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/api/add-transaction" method="post" id="transactionForm">
                <div class="modal-body">
                    <input type="hidden" name="portfolio_id" value="<?php echo $portfolioId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo App::generateCsrfToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="transaction_type" class="form-label">Transaction Type *</label>
                            <select class="form-select" id="transaction_type" name="transaction_type" required onchange="toggleTransactionFields()">
                                <option value="">Select type...</option>
                                <option value="BUY">Buy Stock/ETF</option>
                                <option value="SELL">Sell Stock/ETF</option>
                                <option value="DIVIDEND">Dividend Payment</option>
                                <option value="DEPOSIT">Cash Deposit</option>
                                <option value="WITHDRAWAL">Cash Withdrawal</option>
                                <option value="SPLIT">Stock Split</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="transaction_date" class="form-label">Date *</label>
                            <input type="date" class="form-control" id="transaction_date" name="transaction_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div id="stockFields" style="display: none;">
                        <div class="mb-3">
                            <label for="symbol" class="form-label">Stock Symbol *</label>
                            <input type="text" class="form-control" id="symbol" name="symbol" placeholder="e.g., AAPL, TSLA, VTI" style="text-transform: uppercase;">
                            <div class="form-text">Enter the stock or ETF symbol</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="shares" class="form-label">Shares *</label>
                                <input type="number" class="form-control" id="shares" name="shares" step="0.001" min="0">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="price_per_share" class="form-label">Price per Share *</label>
                                <input type="number" class="form-control" id="price_per_share" name="price_per_share" step="0.01" min="0">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="fees" class="form-label">Fees</label>
                                <input type="number" class="form-control" id="fees" name="fees" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div id="cashFields" style="display: none;">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount *</label>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="broker" class="form-label">Broker</label>
                        <input type="text" class="form-control" id="broker" name="broker" placeholder="e.g., Fidelity, Charles Schwab">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Optional notes about this transaction"></textarea>
                    </div>
                    
                    <div id="totalDisplay" class="alert alert-info" style="display: none;">
                        <strong>Total: <span id="totalAmount">$0.00</span></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Page-specific scripts
$scripts = '<script>
function toggleTransactionFields() {
    const type = document.getElementById("transaction_type").value;
    const stockFields = document.getElementById("stockFields");
    const cashFields = document.getElementById("cashFields");
    const symbolField = document.getElementById("symbol");
    const sharesField = document.getElementById("shares");
    const priceField = document.getElementById("price_per_share");
    const amountField = document.getElementById("amount");
    
    // Hide all fields first
    stockFields.style.display = "none";
    cashFields.style.display = "none";
    
    // Clear required attributes
    symbolField.required = false;
    sharesField.required = false;
    priceField.required = false;
    amountField.required = false;
    
    if (["BUY", "SELL", "DIVIDEND", "SPLIT"].includes(type)) {
        stockFields.style.display = "block";
        symbolField.required = true;
        
        if (["BUY", "SELL"].includes(type)) {
            sharesField.required = true;
            priceField.required = true;
        }
    } else if (["DEPOSIT", "WITHDRAWAL"].includes(type)) {
        cashFields.style.display = "block";
        amountField.required = true;
    }
    
    calculateTotal();
}

function calculateTotal() {
    const shares = parseFloat(document.getElementById("shares").value) || 0;
    const price = parseFloat(document.getElementById("price_per_share").value) || 0;
    const fees = parseFloat(document.getElementById("fees").value) || 0;
    const amount = parseFloat(document.getElementById("amount").value) || 0;
    const type = document.getElementById("transaction_type").value;
    
    let total = 0;
    
    if (["BUY", "SELL"].includes(type)) {
        total = (shares * price) + fees;
    } else if (["DEPOSIT", "WITHDRAWAL", "DIVIDEND"].includes(type)) {
        total = amount;
    }
    
    if (total > 0) {
        document.getElementById("totalAmount").textContent = formatCurrency(total);
        document.getElementById("totalDisplay").style.display = "block";
    } else {
        document.getElementById("totalDisplay").style.display = "none";
    }
}

// Add event listeners for total calculation
document.addEventListener("DOMContentLoaded", function() {
    ["shares", "price_per_share", "fees", "amount"].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener("input", calculateTotal);
        }
    });
});';

// Asset allocation chart data
if (!empty($assetAllocation)) {
    $scripts .= '
// Asset allocation chart
const allocationData = ' . json_encode($assetAllocation) . ';

if (allocationData.length > 0) {
    const ctx = document.getElementById("allocationChart").getContext("2d");
    new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: allocationData.map(item => item.sector || "Other"),
            datasets: [{
                data: allocationData.map(item => item.value),
                backgroundColor: [
                    "#FF6384", "#36A2EB", "#FFCE56", "#4BC0C0",
                    "#9966FF", "#FF9F40", "#FF6384", "#C9CBCF"
                ],
                borderWidth: 2,
                borderColor: "#fff"
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "bottom",
                    labels: {
                        padding: 10,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return context.label + ": " + formatCurrency(value) + " (" + percentage + "%)";
                        }
                    }
                }
            }
        }
    });
}';
}

$scripts .= '
// Initialize DataTable for holdings
function initDataTables() {
    if (document.getElementById("holdingsTable")) {
        $("#holdingsTable").DataTable({
            pageLength: 25,
            responsive: true,
            order: [[4, "desc"]], // Sort by market value
            columnDefs: [
                { targets: [1,2,3,4,5,6], className: "text-end" }
            ]
        });
    }
}
</script>';

// Include layout
include __DIR__ . '/layout.php';
?>