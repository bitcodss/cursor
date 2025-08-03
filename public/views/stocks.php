<?php

use PortfolioTracker\Models\Stock;
use PortfolioTracker\Services\YahooFinanceAPI;
use PortfolioTracker\Config\App;

$stockModel = new Stock();
$apiService = new YahooFinanceAPI();

// Handle search
$searchQuery = $_GET['search'] ?? '';
$stocks = [];

if ($searchQuery) {
    // Search local database first
    $stocks = $stockModel->search($searchQuery, 50);
    
    // If no local results and query is short, search API
    if (empty($stocks) && strlen($searchQuery) >= 2) {
        $apiResults = $apiService->searchStocks($searchQuery);
        foreach ($apiResults as $apiStock) {
            $stocks[] = [
                'symbol' => $apiStock['symbol'],
                'name' => $apiStock['name'],
                'exchange' => $apiStock['exchange'],
                'is_etf' => $apiStock['is_etf'],
                'current_price' => null,
                'change_percent' => null,
                'source' => 'api'
            ];
        }
    }
} else {
    // Get portfolio stocks by default
    $stocks = $stockModel->getPortfolioStocks();
    
    // Add current price info
    foreach ($stocks as &$stock) {
        $stockDetails = $stockModel->getById($stock['id']);
        $stock['current_price'] = $stockDetails['current_price'];
        $stock['change_percent'] = $stockDetails['change_percent'];
        $stock['exchange'] = $stockDetails['exchange'];
        $stock['is_etf'] = $stockDetails['is_etf'];
        $stock['source'] = 'portfolio';
    }
}

// Set page variables
$pageTitle = 'Stocks';
$currentPage = 'stocks';

// Start output buffering for content
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-graph-up-arrow"></i>
        Stocks & ETFs
        <?php if ($searchQuery): ?>
            <small class="text-muted">search results for "<?php echo htmlspecialchars($searchQuery); ?>"</small>
        <?php endif; ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-primary" onclick="updatePrices()" title="Update Prices">
                <i class="bi bi-arrow-clockwise"></i>
                Update Prices
            </button>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addStockModal" title="Add Stock">
                <i class="bi bi-plus-circle"></i>
                Add Stock
            </button>
        </div>
    </div>
</div>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-8">
                <label for="search" class="form-label">Search Stocks & ETFs</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Enter symbol or company name (e.g., AAPL, Apple, VTI)" 
                       value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <?php if ($searchQuery): ?>
                        <a href="/stocks" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Stocks Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php if ($searchQuery): ?>
                Search Results
            <?php else: ?>
                Portfolio Stocks
            <?php endif; ?>
            <span class="badge bg-secondary"><?php echo count($stocks); ?> stocks</span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($stocks)): ?>
            <div class="text-center py-5">
                <i class="bi bi-graph-up-arrow display-1 text-muted"></i>
                <h3 class="mt-3">
                    <?php if ($searchQuery): ?>
                        No Stocks Found
                    <?php else: ?>
                        No Portfolio Stocks
                    <?php endif; ?>
                </h3>
                <p class="text-muted mb-4">
                    <?php if ($searchQuery): ?>
                        No stocks found matching "<?php echo htmlspecialchars($searchQuery); ?>". Try a different search term.
                    <?php else: ?>
                        Add some stocks to your portfolios to see them here.
                    <?php endif; ?>
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
                        <i class="bi bi-plus-circle"></i>
                        Add Stock
                    </button>
                    <a href="/portfolios" class="btn btn-outline-secondary">
                        <i class="bi bi-briefcase"></i>
                        View Portfolios
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="stocksTable">
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Company Name</th>
                            <th>Exchange</th>
                            <th>Type</th>
                            <th class="text-end">Current Price</th>
                            <th class="text-end">Change %</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stocks as $stock): ?>
                        <tr>
                            <td>
                                <strong class="text-primary"><?php echo htmlspecialchars($stock['symbol']); ?></strong>
                                <?php if (isset($stock['source']) && $stock['source'] === 'api'): ?>
                                    <br><small class="text-muted">Search result</small>
                                <?php elseif (isset($stock['source']) && $stock['source'] === 'portfolio'): ?>
                                    <br><small class="text-success">In portfolio</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <?php echo htmlspecialchars($stock['name']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($stock['exchange']): ?>
                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($stock['exchange']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($stock['is_etf']): ?>
                                    <span class="badge bg-info">ETF</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Stock</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($stock['current_price']): ?>
                                    <strong><?php echo App::formatCurrency($stock['current_price']); ?></strong>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-primary" onclick="getQuote('<?php echo $stock['symbol']; ?>')">
                                        <i class="bi bi-download"></i> Get Price
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($stock['change_percent'] !== null): ?>
                                    <span class="<?php echo $stock['change_percent'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo ($stock['change_percent'] >= 0 ? '+' : '') . number_format($stock['change_percent'], 2); ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" onclick="viewStockDetails('<?php echo $stock['symbol']; ?>')" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if (isset($stock['source']) && $stock['source'] === 'api'): ?>
                                        <button type="button" class="btn btn-outline-success" onclick="addToWatchlist('<?php echo $stock['symbol']; ?>')" title="Add to Watchlist">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-outline-primary" onclick="getHistoricalData('<?php echo $stock['symbol']; ?>')" title="Get Historical Data">
                                        <i class="bi bi-graph-up"></i>
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

<!-- Quick Actions for Search Results -->
<?php if ($searchQuery && !empty($stocks)): ?>
<div class="card mt-4">
    <div class="card-header">
        <h6 class="card-title mb-0">Quick Actions</h6>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-primary" onclick="updateAllPrices()">
                <i class="bi bi-arrow-clockwise"></i>
                Update All Prices
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportSearchResults()">
                <i class="bi bi-download"></i>
                Export Results
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Stock to Watchlist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStockForm">
                    <div class="mb-3">
                        <label for="stock_symbol" class="form-label">Stock Symbol *</label>
                        <input type="text" class="form-control" id="stock_symbol" required 
                               placeholder="e.g., AAPL, MSFT, VTI" style="text-transform: uppercase;">
                        <div class="form-text">Enter a valid stock or ETF symbol</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        This will fetch the stock information and add it to your watchlist for price tracking.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addStock()">Add Stock</button>
            </div>
        </div>
    </div>
</div>

<!-- Stock Details Modal -->
<div class="modal fade" id="stockDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockDetailsTitle">Stock Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="stockDetailsBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Page-specific scripts
$scripts = '<script>
function viewStockDetails(symbol) {
    document.getElementById("stockDetailsTitle").textContent = symbol + " - Stock Details";
    document.getElementById("stockDetailsBody").innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    new bootstrap.Modal(document.getElementById("stockDetailsModal")).show();
    
    // Fetch stock details
    fetch("/api/stock-quote?symbol=" + symbol)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stock = data.quote;
                document.getElementById("stockDetailsBody").innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h4>${stock.symbol} - ${stock.name}</h4>
                            <p class="text-muted">${stock.exchange || "Unknown Exchange"}</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h3 class="mb-0">${formatCurrency(stock.price)}</h3>
                            <p class="mb-0 ${stock.change_percent >= 0 ? "positive" : "negative"}">
                                ${stock.change_percent >= 0 ? "+" : ""}${stock.change_percent.toFixed(2)}%
                                (${formatCurrency(stock.change_amount)})
                            </p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Open:</strong><br>
                            ${stock.open ? formatCurrency(stock.open) : "—"}
                        </div>
                        <div class="col-md-3">
                            <strong>High:</strong><br>
                            ${stock.high ? formatCurrency(stock.high) : "—"}
                        </div>
                        <div class="col-md-3">
                            <strong>Low:</strong><br>
                            ${stock.low ? formatCurrency(stock.low) : "—"}
                        </div>
                        <div class="col-md-3">
                            <strong>Volume:</strong><br>
                            ${stock.volume ? stock.volume.toLocaleString() : "—"}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Market Cap:</strong><br>
                            ${stock.market_cap ? "$" + (stock.market_cap / 1000000000).toFixed(1) + "B" : "—"}
                        </div>
                        <div class="col-md-6">
                            <strong>Previous Close:</strong><br>
                            ${stock.previous_close ? formatCurrency(stock.previous_close) : "—"}
                        </div>
                    </div>
                `;
            } else {
                document.getElementById("stockDetailsBody").innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Failed to load stock details: ${data.error || "Unknown error"}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById("stockDetailsBody").innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    Error loading stock details: ${error.message}
                </div>
            `;
        });
}

function getQuote(symbol) {
    showAlert("Fetching quote for " + symbol + "...", "info");
    
    fetch("/api/stock-quote?symbol=" + symbol)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert("Quote updated for " + symbol, "success");
                location.reload();
            } else {
                showAlert("Failed to get quote: " + (data.error || "Unknown error"), "danger");
            }
        })
        .catch(error => {
            showAlert("Error: " + error.message, "danger");
        });
}

function addStock() {
    const symbol = document.getElementById("stock_symbol").value.toUpperCase().trim();
    if (!symbol) {
        showAlert("Please enter a stock symbol", "warning");
        return;
    }
    
    showAlert("Adding " + symbol + " to watchlist...", "info");
    
    fetch("/api/stock-quote?symbol=" + symbol)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert("Successfully added " + symbol + " to watchlist", "success");
                bootstrap.Modal.getInstance(document.getElementById("addStockModal")).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert("Failed to add stock: " + (data.error || "Unknown error"), "danger");
            }
        })
        .catch(error => {
            showAlert("Error: " + error.message, "danger");
        });
}

function addToWatchlist(symbol) {
    showAlert("Adding " + symbol + " to watchlist...", "info");
    
    fetch("/api/stock-quote?symbol=" + symbol)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert("Successfully added " + symbol + " to watchlist", "success");
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert("Failed to add to watchlist: " + (data.error || "Unknown error"), "danger");
            }
        })
        .catch(error => {
            showAlert("Error: " + error.message, "danger");
        });
}

function getHistoricalData(symbol) {
    showAlert("Fetching historical data for " + symbol + "...", "info");
    
    fetch("/api/update-historical?symbol=" + symbol, { method: "POST" })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert("Historical data updated for " + symbol, "success");
            } else {
                showAlert("Failed to update historical data: " + (data.error || "Unknown error"), "danger");
            }
        })
        .catch(error => {
            showAlert("Error: " + error.message, "danger");
        });
}

function updateAllPrices() {
    const symbols = Array.from(document.querySelectorAll("strong.text-primary")).map(el => el.textContent);
    if (symbols.length === 0) return;
    
    showAlert("Updating prices for " + symbols.length + " stocks...", "info");
    updatePrices();
}

function exportSearchResults() {
    const searchQuery = "<?php echo addslashes($searchQuery); ?>";
    const csv = "Symbol,Name,Exchange,Type,Current Price,Change %\\n";
    // Implementation would go here
    showAlert("Export functionality coming soon!", "info");
}

// Initialize DataTable
function initDataTables() {
    if (document.getElementById("stocksTable")) {
        $("#stocksTable").DataTable({
            pageLength: 25,
            responsive: true,
            order: [[0, "asc"]], // Sort by symbol
            columnDefs: [
                { targets: [4,5], className: "text-end" },
                { targets: [6], orderable: false }
            ]
        });
    }
}

// Auto-uppercase stock symbol input
document.addEventListener("DOMContentLoaded", function() {
    const stockSymbolInput = document.getElementById("stock_symbol");
    if (stockSymbolInput) {
        stockSymbolInput.addEventListener("input", function() {
            this.value = this.value.toUpperCase();
        });
    }
});
</script>';

// Include layout
include __DIR__ . '/layout.php';
?>