<?php

// Set page variables
$pageTitle = 'Page Not Found';
$currentPage = '';

// Start output buffering for content
ob_start();
?>

<div class="container-fluid h-100">
    <div class="row h-100 justify-content-center align-items-center">
        <div class="col-md-6 text-center">
            <div class="error-template">
                <h1 class="display-1 text-muted">404</h1>
                <h2 class="h3 mb-3">Page Not Found</h2>
                <div class="error-details mb-4">
                    <p class="text-muted">
                        Sorry, the page you are looking for could not be found.
                    </p>
                </div>
                <div class="error-actions">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="/" class="btn btn-primary">
                            <i class="bi bi-house"></i>
                            Go to Dashboard
                        </a>
                        <a href="/portfolios" class="btn btn-outline-secondary">
                            <i class="bi bi-briefcase"></i>
                            View Portfolios
                        </a>
                        <a href="/transactions" class="btn btn-outline-secondary">
                            <i class="bi bi-list-check"></i>
                            View Transactions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-template {
    padding: 40px 15px;
    text-align: center;
}
.error-actions {
    margin-top: 15px;
    margin-bottom: 15px;
}
.error-actions .btn {
    margin-right: 10px;
}
</style>

<?php
$content = ob_get_clean();

// Include layout
include __DIR__ . '/layout.php';
?>