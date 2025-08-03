<?php

use PortfolioTracker\Models\Portfolio;
use PortfolioTracker\Config\App;

$portfolioModel = new Portfolio();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!App::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'create':
                try {
                    $data = [
                        'name' => $_POST['name'] ?? '',
                        'description' => $_POST['description'] ?? '',
                        'initial_cash' => (float)($_POST['initial_cash'] ?? 0)
                    ];
                    
                    if (empty($data['name'])) {
                        $error = 'Portfolio name is required.';
                    } else {
                        $portfolioId = $portfolioModel->create($data);
                        $message = "Portfolio '{$data['name']}' created successfully!";
                    }
                } catch (Exception $e) {
                    $error = 'Failed to create portfolio: ' . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $portfolioId = (int)($_POST['portfolio_id'] ?? 0);
                    $data = [
                        'name' => $_POST['name'] ?? '',
                        'description' => $_POST['description'] ?? '',
                        'current_cash' => (float)($_POST['current_cash'] ?? 0)
                    ];
                    
                    if (empty($data['name'])) {
                        $error = 'Portfolio name is required.';
                    } else {
                        $portfolioModel->update($portfolioId, $data);
                        $message = "Portfolio updated successfully!";
                    }
                } catch (Exception $e) {
                    $error = 'Failed to update portfolio: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $portfolioId = (int)($_POST['portfolio_id'] ?? 0);
                    $portfolioModel->delete($portfolioId);
                    $message = "Portfolio deleted successfully!";
                } catch (Exception $e) {
                    $error = 'Failed to delete portfolio: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all portfolios with summaries
$portfolios = $portfolioModel->getAllActive();
$portfolioSummaries = [];

foreach ($portfolios as $portfolio) {
    $summary = $portfolioModel->getSummary($portfolio['id']);
    $portfolioSummaries[] = $summary;
}

// Set page variables
$pageTitle = 'Portfolios';
$currentPage = 'portfolios';

// Start output buffering for content
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-briefcase"></i>
        Portfolios
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPortfolioModal">
            <i class="bi bi-plus-circle"></i>
            Create Portfolio
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($portfolioSummaries)): ?>
    <div class="text-center py-5">
        <i class="bi bi-briefcase display-1 text-muted"></i>
        <h3 class="mt-3">No Portfolios Yet</h3>
        <p class="text-muted mb-4">Create your first portfolio to start tracking your investments.</p>
        <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createPortfolioModal">
            <i class="bi bi-plus-circle"></i>
            Create Your First Portfolio
        </button>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($portfolioSummaries as $summary): ?>
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($summary['name']); ?></h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/portfolio?id=<?php echo $summary['id']; ?>">
                                <i class="bi bi-eye"></i> View Details
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="editPortfolio(<?php echo htmlspecialchars(json_encode($summary)); ?>)">
                                <i class="bi bi-pencil"></i> Edit
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="deletePortfolio(<?php echo $summary['id']; ?>, '<?php echo htmlspecialchars($summary['name']); ?>')">
                                <i class="bi bi-trash"></i> Delete
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($summary['description']): ?>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($summary['description']); ?></p>
                    <?php endif; ?>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="fs-4 fw-bold"><?php echo App::formatCurrency($summary['total_value']); ?></div>
                                <small class="text-muted">Total Value</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="fs-4 fw-bold <?php echo $summary['total_gain_loss'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo App::formatCurrency($summary['total_gain_loss']); ?>
                                </div>
                                <small class="text-muted">Gain/Loss</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="text-center">
                                <div class="fw-bold"><?php echo App::formatPercentage($summary['total_return_percent']); ?></div>
                                <small class="text-muted">Return</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center">
                                <div class="fw-bold"><?php echo App::formatCurrency($summary['cash_value']); ?></div>
                                <small class="text-muted">Cash</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center">
                                <div class="fw-bold"><?php echo App::formatPercentage($summary['cash_percentage']); ?></div>
                                <small class="text-muted">Cash %</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="/portfolio?id=<?php echo $summary['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <small class="text-muted align-self-center">
                            Created <?php echo date('M j, Y', strtotime($summary['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Create Portfolio Modal -->
<div class="modal fade" id="createPortfolioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Portfolio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo App::generateCsrfToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Portfolio Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="form-text">Choose a descriptive name (e.g., "Long-term Growth", "Dividend Income")</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        <div class="form-text">Optional description of your investment strategy</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="initial_cash" class="form-label">Initial Cash</label>
                        <input type="number" class="form-control" id="initial_cash" name="initial_cash" step="0.01" min="0" value="0">
                        <div class="form-text">Starting cash amount for this portfolio</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Portfolio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Portfolio Modal -->
<div class="modal fade" id="editPortfolioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Portfolio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="portfolio_id" id="edit_portfolio_id">
                    <input type="hidden" name="csrf_token" value="<?php echo App::generateCsrfToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Portfolio Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_current_cash" class="form-label">Current Cash</label>
                        <input type="number" class="form-control" id="edit_current_cash" name="current_cash" step="0.01" min="0">
                        <div class="form-text">Adjust cash amount directly (use transactions for proper tracking)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Portfolio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePortfolioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Portfolio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the portfolio <strong id="delete_portfolio_name"></strong>?</p>
                <p class="text-danger">This action cannot be undone. All transactions and holdings in this portfolio will be permanently deleted.</p>
            </div>
            <div class="modal-footer">
                <form method="post" id="deletePortfolioForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="portfolio_id" id="delete_portfolio_id">
                    <input type="hidden" name="csrf_token" value="<?php echo App::generateCsrfToken(); ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Portfolio</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Page-specific scripts
$scripts = '<script>
function editPortfolio(portfolio) {
    document.getElementById("edit_portfolio_id").value = portfolio.id;
    document.getElementById("edit_name").value = portfolio.name;
    document.getElementById("edit_description").value = portfolio.description || "";
    document.getElementById("edit_current_cash").value = portfolio.cash_value;
    
    new bootstrap.Modal(document.getElementById("editPortfolioModal")).show();
}

function deletePortfolio(portfolioId, portfolioName) {
    document.getElementById("delete_portfolio_id").value = portfolioId;
    document.getElementById("delete_portfolio_name").textContent = portfolioName;
    
    new bootstrap.Modal(document.getElementById("deletePortfolioModal")).show();
}
</script>';

// Include layout
include __DIR__ . '/layout.php';
?>