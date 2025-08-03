<?php

use PortfolioTracker\Models\Transaction;
use PortfolioTracker\Models\Portfolio;
use PortfolioTracker\Config\App;

$transactionModel = new Transaction();
$portfolioModel = new Portfolio();

// Get filter parameters
$portfolioFilter = (int)($_GET['portfolio'] ?? 0);
$typeFilter = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Get all portfolios for filter dropdown
$portfolios = $portfolioModel->getAllActive();

// Get filtered transactions
if ($portfolioFilter) {
    $transactions = $transactionModel->getByPortfolio($portfolioFilter, 100);
} else {
    $transactions = $transactionModel->getRecent(100);
}

// Apply additional filters
if ($typeFilter || $dateFrom || $dateTo) {
    $transactions = array_filter($transactions, function($transaction) use ($typeFilter, $dateFrom, $dateTo) {
        if ($typeFilter && $transaction['transaction_type'] !== $typeFilter) {
            return false;
        }
        if ($dateFrom && $transaction['transaction_date'] < $dateFrom) {
            return false;
        }
        if ($dateTo && $transaction['transaction_date'] > $dateTo) {
            return false;
        }
        return true;
    });
}

// Get transaction statistics
$stats = $transactionModel->getStatistics($portfolioFilter);

// Set page variables
$pageTitle = 'Transactions';
$currentPage = 'transactions';

// Start output buffering for content
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-list-check"></i>
        Transactions
        <?php if ($portfolioFilter): ?>
            <?php 
            $selectedPortfolio = array_filter($portfolios, fn($p) => $p['id'] == $portfolioFilter);
            $selectedPortfolio = reset($selectedPortfolio);
            ?>
            <small class="text-muted">for <?php echo htmlspecialchars($selectedPortfolio['name']); ?></small>
        <?php endif; ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="bi bi-plus-circle"></i>
                Add Transaction
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i>
                Print
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<?php if (!empty($stats)): ?>
<div class="row mb-4">
    <?php 
    $totalTransactions = array_sum(array_column($stats, 'transaction_count'));
    $totalAmount = array_sum(array_column($stats, 'total_amount'));
    $totalFees = array_sum(array_column($stats, 'total_fees'));
    ?>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body text-center">
                <h4 class="mb-1"><?php echo number_format($totalTransactions); ?></h4>
                <p class="text-muted mb-0">Total Transactions</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body text-center">
                <h4 class="mb-1"><?php echo App::formatCurrency($totalAmount); ?></h4>
                <p class="text-muted mb-0">Transaction Volume</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body text-center">
                <h4 class="mb-1"><?php echo App::formatCurrency($totalFees); ?></h4>
                <p class="text-muted mb-0">Total Fees Paid</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body text-center">
                <h4 class="mb-1">
                    <?php 
                    $buyCount = 0;
                    foreach ($stats as $stat) {
                        if (in_array($stat['transaction_type'], ['BUY', 'SELL'])) {
                            $buyCount += $stat['transaction_count'];
                        }
                    }
                    echo number_format($buyCount);
                    ?>
                </h4>
                <p class="text-muted mb-0">Stock Transactions</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label for="portfolio" class="form-label">Portfolio</label>
                <select class="form-select" id="portfolio" name="portfolio">
                    <option value="">All Portfolios</option>
                    <?php foreach ($portfolios as $portfolio): ?>
                        <option value="<?php echo $portfolio['id']; ?>" <?php echo $portfolioFilter == $portfolio['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($portfolio['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <option value="BUY" <?php echo $typeFilter === 'BUY' ? 'selected' : ''; ?>>Buy</option>
                    <option value="SELL" <?php echo $typeFilter === 'SELL' ? 'selected' : ''; ?>>Sell</option>
                    <option value="DIVIDEND" <?php echo $typeFilter === 'DIVIDEND' ? 'selected' : ''; ?>>Dividend</option>
                    <option value="DEPOSIT" <?php echo $typeFilter === 'DEPOSIT' ? 'selected' : ''; ?>>Deposit</option>
                    <option value="WITHDRAWAL" <?php echo $typeFilter === 'WITHDRAWAL' ? 'selected' : ''; ?>>Withdrawal</option>
                    <option value="SPLIT" <?php echo $typeFilter === 'SPLIT' ? 'selected' : ''; ?>>Split</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="/transactions" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            Transaction History
            <span class="badge bg-secondary"><?php echo count($transactions); ?> transactions</span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <div class="text-center py-5">
                <i class="bi bi-list-check display-1 text-muted"></i>
                <h3 class="mt-3">No Transactions Found</h3>
                <p class="text-muted mb-4">
                    <?php if ($portfolioFilter || $typeFilter || $dateFrom || $dateTo): ?>
                        No transactions match your current filters.
                    <?php else: ?>
                        Start by adding your first transaction.
                    <?php endif; ?>
                </p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                    <i class="bi bi-plus-circle"></i>
                    Add Transaction
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="transactionsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Portfolio</th>
                            <th>Type</th>
                            <th>Symbol</th>
                            <th class="text-end">Shares</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Fees</th>
                            <th>Broker</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td>
                                <div>
                                    <?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?>
                                    <br>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                                </div>
                            </td>
                            <td>
                                <a href="/portfolio?id=<?php echo $transaction['portfolio_id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($transaction['portfolio_name']); ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                $typeColors = [
                                    'BUY' => 'danger',
                                    'SELL' => 'success',
                                    'DIVIDEND' => 'success',
                                    'DEPOSIT' => 'info',
                                    'WITHDRAWAL' => 'warning',
                                    'SPLIT' => 'secondary'
                                ];
                                $color = $typeColors[$transaction['transaction_type']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?>">
                                    <?php echo $transaction['transaction_type']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($transaction['symbol']): ?>
                                    <strong><?php echo htmlspecialchars($transaction['symbol']); ?></strong>
                                    <?php if ($transaction['stock_name']): ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($transaction['stock_name']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($transaction['shares'] > 0): ?>
                                    <?php echo number_format($transaction['shares'], 3); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($transaction['price_per_share'] > 0): ?>
                                    <?php echo App::formatCurrency($transaction['price_per_share']); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <strong class="<?php 
                                    echo in_array($transaction['transaction_type'], ['BUY', 'WITHDRAWAL']) ? 'negative' : 'positive'; 
                                ?>">
                                    <?php 
                                    $amount = $transaction['total_amount'];
                                    if (in_array($transaction['transaction_type'], ['BUY', 'WITHDRAWAL'])) {
                                        echo '-' . App::formatCurrency($amount);
                                    } else {
                                        echo '+' . App::formatCurrency($amount);
                                    }
                                    ?>
                                </strong>
                            </td>
                            <td class="text-end">
                                <?php if ($transaction['fees'] > 0): ?>
                                    <?php echo App::formatCurrency($transaction['fees']); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($transaction['broker']): ?>
                                    <?php echo htmlspecialchars($transaction['broker']); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" onclick="viewTransaction(<?php echo $transaction['id']; ?>)" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="editTransaction(<?php echo $transaction['id']; ?>)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="deleteTransaction(<?php echo $transaction['id']; ?>)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Transaction Modal (Basic Version) -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    For detailed transaction entry, please go to a specific portfolio page.
                </div>
                <div class="d-grid gap-2">
                    <?php foreach ($portfolios as $portfolio): ?>
                        <a href="/portfolio?id=<?php echo $portfolio['id']; ?>" class="btn btn-outline-primary">
                            Add to <?php echo htmlspecialchars($portfolio['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Page-specific scripts
$scripts = '<script>
function viewTransaction(transactionId) {
    // Implement transaction details view
    alert("View transaction details for ID: " + transactionId);
}

function editTransaction(transactionId) {
    // Implement transaction editing
    alert("Edit transaction ID: " + transactionId);
}

function deleteTransaction(transactionId) {
    if (confirm("Are you sure you want to delete this transaction? This action cannot be undone.")) {
        // Implement transaction deletion
        alert("Delete transaction ID: " + transactionId);
    }
}

// Initialize DataTable
function initDataTables() {
    if (document.getElementById("transactionsTable")) {
        $("#transactionsTable").DataTable({
            pageLength: 25,
            responsive: true,
            order: [[0, "desc"]], // Sort by date descending
            columnDefs: [
                { targets: [4,5,6,7], className: "text-end" },
                { targets: [9], orderable: false }
            ]
        });
    }
}
</script>';

// Include layout
include __DIR__ . '/layout.php';
?>