<?php

use PortfolioTracker\Models\Portfolio;
use PortfolioTracker\Models\Transaction;
use PortfolioTracker\Config\App;
use PortfolioTracker\Services\YahooFinanceAPI;

$portfolioModel = new Portfolio();
$transactionModel = new Transaction();
$apiService = new YahooFinanceAPI();

// Get all portfolios with summaries
$portfolios = $portfolioModel->getAllActive();
$portfolioSummaries = [];
$totalValue = 0;
$totalGainLoss = 0;
$totalCash = 0;

foreach ($portfolios as $portfolio) {
    $summary = $portfolioModel->getSummary($portfolio['id']);
    $portfolioSummaries[] = $summary;
    $totalValue += $summary['total_value'] ?? 0;
    $totalGainLoss += $summary['total_gain_loss'] ?? 0;
    $totalCash += $summary['cash_value'] ?? 0;
}

// Get recent transactions
$recentTransactions = $transactionModel->getRecent(10);

// Get market status
$marketStatus = $apiService->getMarketStatus();

// Calculate overall metrics
$totalInvested = $totalValue - $totalGainLoss;
$totalReturnPercent = $totalInvested > 0 ? ($totalGainLoss / $totalInvested) * 100 : 0;

// Set page variables
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// Start output buffering for content
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-speedometer2"></i>
        Dashboard
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-primary" onclick="updatePrices()">
                <i class="bi bi-arrow-clockwise"></i>
                Update Prices
            </button>
            <a href="/portfolios" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-briefcase"></i>
                Manage Portfolios
            </a>
        </div>
    </div>
</div>

<!-- Overall Portfolio Metrics -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-1">Total Portfolio Value</h6>
                        <h4 class="mb-0"><?php echo App::formatCurrency($totalValue); ?></h4>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-wallet2 text-primary fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-1">Total Gain/Loss</h6>
                        <h4 class="mb-0 <?php echo $totalGainLoss >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo App::formatCurrency($totalGainLoss); ?>
                        </h4>
                        <small class="<?php echo $totalReturnPercent >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo App::formatPercentage($totalReturnPercent); ?>
                        </small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-graph-<?php echo $totalGainLoss >= 0 ? 'up' : 'down'; ?> text-<?php echo $totalGainLoss >= 0 ? 'success' : 'danger'; ?> fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-1">Cash Available</h6>
                        <h4 class="mb-0"><?php echo App::formatCurrency($totalCash); ?></h4>
                        <small class="text-muted">
                            <?php echo $totalValue > 0 ? App::formatPercentage(($totalCash / $totalValue) * 100) : '0%'; ?> of portfolio
                        </small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-cash-coin text-success fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="card-title text-muted mb-1">Active Portfolios</h6>
                        <h4 class="mb-0"><?php echo count($portfolios); ?></h4>
                        <small class="text-muted">
                            Market: <span class="<?php echo $marketStatus['status'] === 'OPEN' ? 'market-open' : 'market-closed'; ?>">
                                <?php echo $marketStatus['status']; ?>
                            </span>
                        </small>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="bi bi-briefcase text-info fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Portfolio Overview -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-briefcase"></i>
                    Portfolio Overview
                </h5>
                <a href="/portfolios" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($portfolioSummaries)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-briefcase display-4 text-muted"></i>
                        <p class="mt-3 text-muted">No portfolios found</p>
                        <a href="/portfolios" class="btn btn-primary">Create Your First Portfolio</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Portfolio</th>
                                    <th class="text-end">Value</th>
                                    <th class="text-end">Gain/Loss</th>
                                    <th class="text-end">Return %</th>
                                    <th class="text-end">Cash</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($portfolioSummaries as $summary): ?>
                                <tr>
                                    <td>
                                        <a href="/portfolio?id=<?php echo $summary['id']; ?>" class="text-decoration-none">
                                            <strong><?php echo htmlspecialchars($summary['name']); ?></strong>
                                            <?php if ($summary['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($summary['description']); ?></small>
                                            <?php endif; ?>
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <strong><?php echo App::formatCurrency($summary['total_value']); ?></strong>
                                    </td>
                                    <td class="text-end <?php echo $summary['total_gain_loss'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo App::formatCurrency($summary['total_gain_loss']); ?>
                                    </td>
                                    <td class="text-end <?php echo $summary['total_return_percent'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo App::formatPercentage($summary['total_return_percent']); ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo App::formatCurrency($summary['cash_value']); ?>
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

    <!-- Recent Transactions -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-check"></i>
                    Recent Transactions
                </h5>
                <a href="/transactions" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentTransactions)): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-list-check display-5 text-muted"></i>
                        <p class="mt-2 text-muted">No transactions yet</p>
                        <a href="/transactions" class="btn btn-sm btn-primary">Add Transaction</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentTransactions as $transaction): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <?php if ($transaction['symbol']): ?>
                                            <span class="badge bg-secondary"><?php echo $transaction['symbol']; ?></span>
                                        <?php endif; ?>
                                        <?php echo ucfirst(strtolower($transaction['transaction_type'])); ?>
                                    </h6>
                                    <p class="mb-1 text-muted small">
                                        <?php echo htmlspecialchars($transaction['portfolio_name']); ?>
                                        <?php if ($transaction['shares'] > 0): ?>
                                            • <?php echo number_format($transaction['shares'], 2); ?> shares
                                        <?php endif; ?>
                                    </p>
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

<!-- Portfolio Allocation Chart -->
<?php if (!empty($portfolioSummaries)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pie-chart"></i>
                    Portfolio Allocation
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="portfolioChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();

// Page-specific scripts
$scripts = '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Portfolio allocation chart
    const portfolioData = ' . json_encode(array_map(function($summary) {
        return [
            'name' => $summary['name'],
            'value' => $summary['total_value'],
            'color' => sprintf("#%06x", mt_rand(0, 0xFFFFFF))
        ];
    }, $portfolioSummaries)) . ';
    
    if (portfolioData.length > 0) {
        const ctx = document.getElementById("portfolioChart").getContext("2d");
        new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: portfolioData.map(p => p.name),
                datasets: [{
                    data: portfolioData.map(p => p.value),
                    backgroundColor: portfolioData.map(p => p.color),
                    borderWidth: 2,
                    borderColor: "#fff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "right"
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
    }
});
</script>';

// Include layout
include __DIR__ . '/layout.php';
?>