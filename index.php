<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Portfolio.php';
require_once 'classes/PortfolioItem.php';

// Simple routing
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';

// Initialize portfolio manager
$portfolio = new Portfolio();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-chart-line me-2"></i>Portfolio Tracker
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link <?= $page === 'portfolio' ? 'active' : '' ?>" href="index.php?page=portfolio">
                    <i class="fas fa-briefcase me-1"></i>Portfolio
                </a>
                <a class="nav-link <?= $page === 'add' ? 'active' : '' ?>" href="index.php?page=add">
                    <i class="fas fa-plus me-1"></i>Add Item
                </a>
                <a class="nav-link <?= $page === 'analytics' ? 'active' : '' ?>" href="index.php?page=analytics">
                    <i class="fas fa-chart-bar me-1"></i>Analytics
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'add') {
                $result = $portfolio->addItem($_POST);
                if ($result['success']) {
                    echo '<div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>' . $result['message'] . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                } else {
                    echo '<div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i>' . $result['message'] . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                }
            } elseif ($action === 'update') {
                $result = $portfolio->updateItem($_POST['id'], $_POST);
                if ($result['success']) {
                    echo '<div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>' . $result['message'] . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                }
            } elseif ($action === 'delete') {
                $result = $portfolio->deleteItem($_POST['id']);
                if ($result['success']) {
                    echo '<div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>' . $result['message'] . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                }
            }
        }

        // Include the appropriate page
        switch ($page) {
            case 'dashboard':
                include 'pages/dashboard.php';
                break;
            case 'portfolio':
                include 'pages/portfolio.php';
                break;
            case 'add':
                include 'pages/add.php';
                break;
            case 'edit':
                include 'pages/edit.php';
                break;
            case 'analytics':
                include 'pages/analytics.php';
                break;
            default:
                include 'pages/dashboard.php';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>