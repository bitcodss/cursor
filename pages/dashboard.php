<?php
$stats = $portfolio->getPortfolioStats();
$recentActivity = $portfolio->getRecentActivity(5);
$categoryStats = $portfolio->getItemsByCategory();
$recentItems = $portfolio->getAllItems('created_at', 'DESC');
$recentItems = array_slice($recentItems, 0, 5); // Get 5 most recent
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">
            <i class="fas fa-tachometer-alt me-2 text-primary"></i>Portfolio Dashboard
        </h1>
    </div>
</div>

<!-- Portfolio Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Value</h5>
                        <h3 class="mb-0">$<?= number_format($stats['total_current'], 2) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Invested</h5>
                        <h3 class="mb-0">$<?= number_format($stats['total_purchase'], 2) ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-coins fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card <?= $stats['gain_loss'] >= 0 ? 'bg-success' : 'bg-danger' ?> text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Gain/Loss</h5>
                        <h3 class="mb-0">
                            <?= $stats['gain_loss'] >= 0 ? '+' : '' ?>$<?= number_format($stats['gain_loss'], 2) ?>
                        </h3>
                        <small>(<?= $stats['gain_loss'] >= 0 ? '+' : '' ?><?= number_format($stats['gain_loss_percentage'], 2) ?>%)</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Items</h5>
                        <h3 class="mb-0"><?= $stats['item_count'] ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-briefcase fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Portfolio by Category -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-pie-chart me-2"></i>Portfolio by Category
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($categoryStats)): ?>
                    <canvas id="categoryChart" width="400" height="300"></canvas>
                    <div class="mt-3">
                        <?php foreach ($categoryStats as $category): ?>
                            <?php 
                            $percentage = $stats['total_current'] > 0 ? 
                                ($category['total_current'] / $stats['total_current']) * 100 : 0;
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary me-2"><?= htmlspecialchars($category['category']) ?></span>
                                <span>$<?= number_format($category['total_current'], 2) ?> (<?= number_format($percentage, 1) ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chart-pie fa-3x mb-3"></i>
                        <p>No portfolio items yet. <a href="index.php?page=add">Add your first item</a> to see category breakdown.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Items -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>Recent Items
                </h5>
                <a href="index.php?page=portfolio" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recentItems)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentItems as $item): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($item->getName()) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($item->getCategory()) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold">$<?= number_format($item->getTotalCurrentValue(), 2) ?></span>
                                    <br>
                                    <small class="<?= $item->getGainLoss() >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $item->getGainLoss() >= 0 ? '+' : '' ?>$<?= number_format($item->getGainLoss(), 2) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-plus-circle fa-3x mb-3"></i>
                        <p>No portfolio items yet.</p>
                        <a href="index.php?page=add" class="btn btn-primary">Add Your First Item</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recentActivity)): ?>
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title"><?= htmlspecialchars($activity['item_name']) ?></h6>
                                <p class="timeline-text">
                                    Value changed by 
                                    <span class="<?= $activity['value_change'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $activity['value_change'] >= 0 ? '+' : '' ?>$<?= number_format($activity['value_change'], 2) ?>
                                    </span>
                                    to $<?= number_format($activity['new_value'], 2) ?>
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?= date('M j, Y g:i A', strtotime($activity['change_date'])) ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Category Chart
<?php if (!empty($categoryStats)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    const categoryData = <?= json_encode($categoryStats) ?>;
    
    const labels = categoryData.map(item => item.category);
    const data = categoryData.map(item => parseFloat(item.total_current));
    const colors = [
        '#007bff', '#28a745', '#ffc107', '#17a2b8', 
        '#fd7e14', '#6f42c1', '#20c997', '#6c757d'
    ];
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
<?php endif; ?>
</script>