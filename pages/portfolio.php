<?php
$searchQuery = $_GET['search'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortDir = $_GET['dir'] ?? 'DESC';

if ($searchQuery) {
    $items = $portfolio->searchItems($searchQuery);
} else {
    $items = $portfolio->getAllItems($sortBy, $sortDir);
}

$categories = $portfolio->getCategories();
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-briefcase me-2 text-primary"></i>My Portfolio</h1>
            <a href="index.php?page=add" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add New Item
            </a>
        </div>
    </div>
</div>

<!-- Search and Filter Bar -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="page" value="portfolio">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($searchQuery) ?>"
                                   placeholder="Search by name, description, or tags...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="sort" class="form-label">Sort by</label>
                        <select class="form-control" id="sort" name="sort">
                            <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Date Added</option>
                            <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name</option>
                            <option value="current_value" <?= $sortBy === 'current_value' ? 'selected' : '' ?>>Current Value</option>
                            <option value="purchase_price" <?= $sortBy === 'purchase_price' ? 'selected' : '' ?>>Purchase Price</option>
                            <option value="category" <?= $sortBy === 'category' ? 'selected' : '' ?>>Category</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="dir" class="form-label">Order</label>
                        <select class="form-control" id="dir" name="dir">
                            <option value="DESC" <?= $sortDir === 'DESC' ? 'selected' : '' ?>>Descending</option>
                            <option value="ASC" <?= $sortDir === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary me-2">
                            <i class="fas fa-filter me-1"></i>Apply
                        </button>
                        <a href="index.php?page=portfolio" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($items)): ?>
    <!-- Portfolio Items Grid -->
    <div class="row">
        <?php foreach ($items as $item): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 portfolio-item-card">
                    <?php if ($item->getImageUrl()): ?>
                        <img src="<?= htmlspecialchars($item->getImageUrl()) ?>" 
                             class="card-img-top portfolio-item-image" 
                             alt="<?= htmlspecialchars($item->getName()) ?>"
                             onerror="this.style.display='none'">
                    <?php endif; ?>
                    
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($item->getName()) ?></h5>
                            <span class="badge bg-secondary"><?= htmlspecialchars($item->getType()) ?></span>
                        </div>
                        
                        <?php if ($item->getCategory()): ?>
                            <span class="badge bg-primary mb-2 align-self-start">
                                <?= htmlspecialchars($item->getCategory()) ?>
                            </span>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <div class="row text-sm">
                                <div class="col-6">
                                    <strong>Quantity:</strong> <?= $item->getQuantity() ?>
                                </div>
                                <div class="col-6">
                                    <strong>Purchase Date:</strong><br>
                                    <small><?= $item->getFormattedPurchaseDate() ?: 'N/A' ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Total Invested</small><br>
                                    <strong class="text-info">$<?= number_format($item->getTotalPurchaseValue(), 2) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Current Value</small><br>
                                    <strong class="text-primary">$<?= number_format($item->getTotalCurrentValue(), 2) ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Gain/Loss</small><br>
                            <strong class="<?= $item->getGainLoss() >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $item->getGainLoss() >= 0 ? '+' : '' ?>$<?= number_format($item->getGainLoss(), 2) ?>
                                (<?= $item->getGainLoss() >= 0 ? '+' : '' ?><?= number_format($item->getGainLossPercentage(), 2) ?>%)
                            </strong>
                        </div>
                        
                        <?php if ($item->getDescription()): ?>
                            <p class="card-text text-muted small flex-grow-1">
                                <?= htmlspecialchars(substr($item->getDescription(), 0, 100)) ?>
                                <?= strlen($item->getDescription()) > 100 ? '...' : '' ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($item->getTags()): ?>
                            <div class="mb-3">
                                <?php foreach ($item->getTagsArray() as $tag): ?>
                                    <span class="badge bg-light text-dark me-1">#<?= htmlspecialchars(trim($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-auto">
                            <div class="btn-group w-100" role="group">
                                <a href="index.php?page=edit&id=<?= $item->getId() ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        onclick="confirmDelete(<?= $item->getId() ?>, '<?= htmlspecialchars($item->getName()) ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Portfolio Summary -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">Portfolio Summary</h5>
                    <?php
                    $totalInvested = array_sum(array_map(fn($item) => $item->getTotalPurchaseValue(), $items));
                    $totalCurrent = array_sum(array_map(fn($item) => $item->getTotalCurrentValue(), $items));
                    $totalGainLoss = $totalCurrent - $totalInvested;
                    $totalGainLossPercentage = $totalInvested > 0 ? (($totalGainLoss / $totalInvested) * 100) : 0;
                    ?>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Items Shown:</strong> <?= count($items) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Total Invested:</strong> $<?= number_format($totalInvested, 2) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Current Value:</strong> $<?= number_format($totalCurrent, 2) ?>
                        </div>
                        <div class="col-md-3">
                            <strong class="<?= $totalGainLoss >= 0 ? 'text-success' : 'text-danger' ?>">
                                Total Gain/Loss: 
                                <?= $totalGainLoss >= 0 ? '+' : '' ?>$<?= number_format($totalGainLoss, 2) ?>
                                (<?= $totalGainLoss >= 0 ? '+' : '' ?><?= number_format($totalGainLossPercentage, 2) ?>%)
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Empty State -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                    <h4>No Portfolio Items Found</h4>
                    <p class="text-muted">
                        <?php if ($searchQuery): ?>
                            No items match your search criteria. Try adjusting your search terms.
                        <?php else: ?>
                            Start building your portfolio by adding your first investment.
                        <?php endif; ?>
                    </p>
                    <div class="mt-3">
                        <?php if ($searchQuery): ?>
                            <a href="index.php?page=portfolio" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-1"></i>View All Items
                            </a>
                        <?php endif; ?>
                        <a href="index.php?page=add" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Your First Item
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteItemId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(itemId, itemName) {
    document.getElementById('deleteItemId').value = itemId;
    document.getElementById('deleteItemName').textContent = itemName;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>