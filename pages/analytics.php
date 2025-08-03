<?php
$stats = $portfolio->getPortfolioStats();
$categoryStats = $portfolio->getItemsByCategory();
$allItems = $portfolio->getAllItems();
$recentActivity = $portfolio->getRecentActivity(20);

// Calculate additional analytics
$topPerformers = array_slice(
    array_filter($allItems, fn($item) => $item->getGainLoss() > 0),
    0, 5
);
usort($topPerformers, fn($a, $b) => $b->getGainLossPercentage() <=> $a->getGainLossPercentage());

$worstPerformers = array_slice(
    array_filter($allItems, fn($item) => $item->getGainLoss() < 0),
    0, 5
);
usort($worstPerformers, fn($a, $b) => $a->getGainLossPercentage() <=> $b->getGainLossPercentage());
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">
            <i class="fas fa-chart-bar me-2 text-primary"></i>Portfolio Analytics
        </h1>
    </div>
</div>

<!-- Key Metrics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Portfolio Value</h5>
                <h2 class="text-primary">$<?= number_format($stats['total_current'], 2) ?></h2>
                <small class="text-muted">Current Total</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Return</h5>
                <h2 class="<?= $stats['gain_loss'] >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $stats['gain_loss'] >= 0 ? '+' : '' ?><?= number_format($stats['gain_loss_percentage'], 2) ?>%
                </h2>
                <small class="text-muted">$<?= number_format($stats['gain_loss'], 2) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Diversification</h5>
                <h2 class="text-info"><?= count($categoryStats) ?></h2>
                <small class="text-muted">Categories</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Holdings</h5>
                <h2 class="text-secondary"><?= $stats['item_count'] ?></h2>
                <small class="text-muted">Items</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Portfolio Allocation Chart -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-pie-chart me-2"></i>Asset Allocation
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($categoryStats)): ?>
                    <canvas id="allocationChart" width="400" height="300"></canvas>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chart-pie fa-3x mb-3"></i>
                        <p>No data available for allocation chart.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Performance Overview -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>Performance Overview
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($allItems)): ?>
                    <canvas id="performanceChart" width="400" height="300"></canvas>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <p>No data available for performance chart.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Top Performers -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-arrow-up me-2 text-success"></i>Top Performers
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($topPerformers)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($topPerformers as $item): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($item->getName()) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($item->getCategory()) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="text-success fw-bold">
                                        +<?= number_format($item->getGainLossPercentage(), 2) ?>%
                                    </span><br>
                                    <small class="text-muted">+$<?= number_format($item->getGainLoss(), 2) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-arrow-up fa-2x mb-2"></i>
                        <p>No profitable investments yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Worst Performers -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-arrow-down me-2 text-danger"></i>Underperformers
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($worstPerformers)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($worstPerformers as $item): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($item->getName()) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($item->getCategory()) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="text-danger fw-bold">
                                        <?= number_format($item->getGainLossPercentage(), 2) ?>%
                                    </span><br>
                                    <small class="text-muted">$<?= number_format($item->getGainLoss(), 2) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-arrow-down fa-2x mb-2"></i>
                        <p>No losing investments.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Category Breakdown Table -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>Category Breakdown
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($categoryStats)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Items</th>
                                    <th>Total Invested</th>
                                    <th>Current Value</th>
                                    <th>Gain/Loss</th>
                                    <th>% of Portfolio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryStats as $category): ?>
                                    <?php 
                                    $gainLoss = $category['total_current'] - $category['total_purchase'];
                                    $gainLossPercentage = $category['total_purchase'] > 0 ? 
                                        (($gainLoss / $category['total_purchase']) * 100) : 0;
                                    $portfolioPercentage = $stats['total_current'] > 0 ? 
                                        (($category['total_current'] / $stats['total_current']) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($category['category']) ?></strong>
                                        </td>
                                        <td><?= $category['count'] ?></td>
                                        <td>$<?= number_format($category['total_purchase'], 2) ?></td>
                                        <td>$<?= number_format($category['total_current'], 2) ?></td>
                                        <td class="<?= $gainLoss >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $gainLoss >= 0 ? '+' : '' ?>$<?= number_format($gainLoss, 2) ?>
                                            (<?= $gainLoss >= 0 ? '+' : '' ?><?= number_format($gainLossPercentage, 2) ?>%)
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?= $portfolioPercentage ?>%">
                                                    </div>
                                                </div>
                                                <small><?= number_format($portfolioPercentage, 1) ?>%</small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-table fa-3x mb-3"></i>
                        <p>No category data available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Asset Allocation Chart
    <?php if (!empty($categoryStats)): ?>
    const allocationCtx = document.getElementById('allocationChart').getContext('2d');
    const categoryData = <?= json_encode($categoryStats) ?>;
    
    new Chart(allocationCtx, {
        type: 'doughnut',
        data: {
            labels: categoryData.map(item => item.category),
            datasets: [{
                data: categoryData.map(item => parseFloat(item.total_current)),
                backgroundColor: [
                    '#007bff', '#28a745', '#ffc107', '#17a2b8', 
                    '#fd7e14', '#6f42c1', '#20c997', '#6c757d'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    <?php endif; ?>

    // Performance Chart
    <?php if (!empty($allItems)): ?>
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    const itemsData = <?= json_encode(array_map(function($item) {
        return [
            'name' => $item->getName(),
            'gain_loss_percentage' => $item->getGainLossPercentage()
        ];
    }, array_slice($allItems, 0, 10))) ?>;
    
    new Chart(performanceCtx, {
        type: 'bar',
        data: {
            labels: itemsData.map(item => item.name.length > 15 ? item.name.substring(0, 15) + '...' : item.name),
            datasets: [{
                label: 'Performance (%)',
                data: itemsData.map(item => item.gain_loss_percentage),
                backgroundColor: itemsData.map(item => item.gain_loss_percentage >= 0 ? '#28a745' : '#dc3545'),
                borderColor: itemsData.map(item => item.gain_loss_percentage >= 0 ? '#1e7e34' : '#c82333'),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    <?php endif; ?>
});
</script>