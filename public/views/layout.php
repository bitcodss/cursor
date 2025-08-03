<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Portfolio Tracker'; ?> - <?php echo PortfolioTracker\Config\App::get('name'); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1rem;
            border-radius: 0.375rem;
            margin: 0.2rem 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.075);
        }
        .metric-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .positive {
            color: #198754;
        }
        .negative {
            color: #dc3545;
        }
        .market-open {
            color: #198754;
        }
        .market-closed {
            color: #dc3545;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-3">
                        <h5 class="text-white">
                            <i class="bi bi-graph-up"></i>
                            Portfolio Tracker
                        </h5>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="/">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'portfolios' ? 'active' : ''; ?>" href="/portfolios">
                                <i class="bi bi-briefcase"></i>
                                Portfolios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'transactions' ? 'active' : ''; ?>" href="/transactions">
                                <i class="bi bi-list-check"></i>
                                Transactions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'stocks' ? 'active' : ''; ?>" href="/stocks">
                                <i class="bi bi-graph-up-arrow"></i>
                                Stocks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="updatePrices()">
                                <i class="bi bi-arrow-clockwise"></i>
                                Update Prices
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-white-50">
                    
                    <!-- Market Status -->
                    <div class="px-3 py-2">
                        <small class="text-white-50">Market Status</small>
                        <div id="market-status" class="text-white">
                            <i class="bi bi-circle-fill"></i>
                            <span>Loading...</span>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Top navigation bar for mobile -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom d-md-none">
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target=".sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="h2"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                </div>

                <!-- Alert container -->
                <div id="alert-container"></div>

                <!-- Page content -->
                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // Global JavaScript functions
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        }

        function formatPercentage(percent) {
            return new Intl.NumberFormat('en-US', {
                style: 'percent',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(percent / 100);
        }

        function showAlert(message, type = 'info') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.getElementById('alert-container').innerHTML = alertHtml;
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }

        function updatePrices() {
            showAlert('Updating stock prices...', 'info');
            
            fetch('/api/update-prices', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(`Updated ${data.updated} stock prices`, 'success');
                        // Reload page to show updated data
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert('Failed to update prices: ' + (data.error || 'Unknown error'), 'danger');
                    }
                })
                .catch(error => {
                    showAlert('Error updating prices: ' + error.message, 'danger');
                });
        }

        function updateMarketStatus() {
            fetch('/api/market-status')
                .then(response => response.json())
                .then(data => {
                    const statusElement = document.getElementById('market-status');
                    if (statusElement) {
                        const isOpen = data.status === 'OPEN';
                        statusElement.innerHTML = `
                            <i class="bi bi-circle-fill ${isOpen ? 'market-open' : 'market-closed'}"></i>
                            <span>${data.status}</span>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Failed to update market status:', error);
                });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateMarketStatus();
            
            // Update market status every 5 minutes
            setInterval(updateMarketStatus, 300000);
            
            // Initialize DataTables if present
            if (typeof initDataTables === 'function') {
                initDataTables();
            }
        });
    </script>

    <!-- Page-specific scripts -->
    <?php if (isset($scripts)): ?>
        <?php echo $scripts; ?>
    <?php endif; ?>
</body>
</html>