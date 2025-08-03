<?php
$itemId = $_GET['id'] ?? null;
if (!$itemId) {
    header('Location: index.php?page=portfolio');
    exit;
}

$item = $portfolio->getItem($itemId);
if (!$item) {
    header('Location: index.php?page=portfolio');
    exit;
}

$categories = $portfolio->getCategories();
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-edit me-2"></i>Edit Portfolio Item
                </h4>
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?page=edit&id=<?= $itemId ?>&action=update">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Item Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       value="<?= htmlspecialchars($item->getName()) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Type *</label>
                                <select class="form-control" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <?php 
                                    $types = ['Stock', 'Bond', 'ETF', 'Mutual Fund', 'Cryptocurrency', 'Real Estate', 'Commodity', 'Art', 'Collectible', 'Cash', 'Other'];
                                    foreach ($types as $type): ?>
                                        <option value="<?= $type ?>" <?= $item->getType() === $type ? 'selected' : '' ?>>
                                            <?= $type ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-control" id="category" name="category">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['name']) ?>" 
                                                <?= $item->getCategory() === $cat['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity *</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       value="<?= $item->getQuantity() ?>" min="1" step="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="purchase_price" class="form-label">Purchase Price (per unit) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="purchase_price" 
                                           name="purchase_price" step="0.01" min="0" required
                                           value="<?= $item->getPurchasePrice() ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="current_value" class="form-label">Current Value (per unit) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="current_value" 
                                           name="current_value" step="0.01" min="0" required
                                           value="<?= $item->getCurrentValue() ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="purchase_date" class="form-label">Purchase Date</label>
                                <input type="date" class="form-control" id="purchase_date" name="purchase_date"
                                       value="<?= $item->getPurchaseDate() ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="image_url" class="form-label">Image URL</label>
                                <input type="url" class="form-control" id="image_url" name="image_url"
                                       value="<?= htmlspecialchars($item->getImageUrl()) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($item->getDescription()) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="tags" class="form-label">Tags</label>
                        <input type="text" class="form-control" id="tags" name="tags"
                               value="<?= htmlspecialchars($item->getTags()) ?>">
                        <small class="form-text text-muted">Separate tags with commas</small>
                    </div>

                    <!-- Current Investment Calculator -->
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Investment Summary</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Total Invested:</strong>
                                    <span id="total-invested" class="text-primary">$<?= number_format($item->getTotalPurchaseValue(), 2) ?></span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Current Total Value:</strong>
                                    <span id="total-current" class="text-info">$<?= number_format($item->getTotalCurrentValue(), 2) ?></span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Gain/Loss:</strong>
                                    <span id="gain-loss" class="fw-bold <?= $item->getGainLoss() >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $item->getGainLoss() >= 0 ? '+' : '' ?>$<?= number_format($item->getGainLoss(), 2) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="id" value="<?= $item->getId() ?>">

                    <div class="d-flex justify-content-between">
                        <a href="index.php?page=portfolio" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate totals in real-time
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity');
    const purchasePriceInput = document.getElementById('purchase_price');
    const currentValueInput = document.getElementById('current_value');
    
    function calculateTotals() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const purchasePrice = parseFloat(purchasePriceInput.value) || 0;
        const currentValue = parseFloat(currentValueInput.value) || 0;
        
        const totalInvested = quantity * purchasePrice;
        const totalCurrent = quantity * currentValue;
        const gainLoss = totalCurrent - totalInvested;
        
        document.getElementById('total-invested').textContent = '$' + totalInvested.toFixed(2);
        document.getElementById('total-current').textContent = '$' + totalCurrent.toFixed(2);
        
        const gainLossElement = document.getElementById('gain-loss');
        gainLossElement.textContent = (gainLoss >= 0 ? '+' : '') + '$' + gainLoss.toFixed(2);
        gainLossElement.className = 'fw-bold ' + (gainLoss >= 0 ? 'text-success' : 'text-danger');
    }
    
    // Add event listeners
    quantityInput.addEventListener('input', calculateTotals);
    purchasePriceInput.addEventListener('input', calculateTotals);
    currentValueInput.addEventListener('input', calculateTotals);
});
</script>