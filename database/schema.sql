-- Portfolio Tracker Database Schema
-- MySQL 5.7+ or 8.0+

CREATE DATABASE IF NOT EXISTS portfolio_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portfolio_tracker;

-- Users table (for admin access)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Portfolios table
CREATE TABLE portfolios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    initial_cash DECIMAL(15,2) DEFAULT 0.00,
    current_cash DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_created (created_at)
);

-- Stocks/ETFs table
CREATE TABLE stocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    exchange VARCHAR(10),
    sector VARCHAR(100),
    industry VARCHAR(100),
    market_cap BIGINT,
    is_etf BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_symbol (symbol),
    INDEX idx_sector (sector),
    INDEX idx_active (is_active)
);

-- Transaction types: BUY, SELL, DIVIDEND, DEPOSIT, WITHDRAWAL, SPLIT, REVERSE_SPLIT
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NOT NULL,
    stock_id INT NULL, -- NULL for cash transactions
    transaction_type ENUM('BUY', 'SELL', 'DIVIDEND', 'DEPOSIT', 'WITHDRAWAL', 'SPLIT', 'REVERSE_SPLIT') NOT NULL,
    shares DECIMAL(12,6) DEFAULT 0,
    price_per_share DECIMAL(10,4) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    fees DECIMAL(10,2) DEFAULT 0,
    broker VARCHAR(100),
    transaction_date DATE NOT NULL,
    notes TEXT,
    split_ratio DECIMAL(10,6) DEFAULT NULL, -- For stock splits (e.g., 2.0 for 2:1 split)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE SET NULL,
    INDEX idx_portfolio (portfolio_id),
    INDEX idx_stock (stock_id),
    INDEX idx_type (transaction_type),
    INDEX idx_date (transaction_date),
    INDEX idx_portfolio_date (portfolio_id, transaction_date)
);

-- Historical stock prices
CREATE TABLE stock_prices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stock_id INT NOT NULL,
    price_date DATE NOT NULL,
    open_price DECIMAL(10,4),
    high_price DECIMAL(10,4),
    low_price DECIMAL(10,4),
    close_price DECIMAL(10,4) NOT NULL,
    volume BIGINT,
    adjusted_close DECIMAL(10,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_stock_date (stock_id, price_date),
    INDEX idx_stock_date (stock_id, price_date DESC),
    INDEX idx_date (price_date)
);

-- Current stock prices (real-time cache)
CREATE TABLE current_prices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stock_id INT NOT NULL,
    current_price DECIMAL(10,4) NOT NULL,
    change_amount DECIMAL(10,4) DEFAULT 0,
    change_percent DECIMAL(8,4) DEFAULT 0,
    volume BIGINT DEFAULT 0,
    market_cap BIGINT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_stock (stock_id),
    INDEX idx_updated (last_updated)
);

-- Dividend information
CREATE TABLE dividends (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stock_id INT NOT NULL,
    ex_date DATE,
    payment_date DATE,
    record_date DATE,
    amount DECIMAL(8,4) NOT NULL,
    frequency VARCHAR(20), -- QUARTERLY, MONTHLY, ANNUAL, etc.
    is_special BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE,
    INDEX idx_stock (stock_id),
    INDEX idx_ex_date (ex_date),
    INDEX idx_payment_date (payment_date)
);

-- Portfolio holdings (calculated from transactions)
CREATE TABLE portfolio_holdings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NOT NULL,
    stock_id INT NOT NULL,
    shares DECIMAL(12,6) NOT NULL,
    average_cost DECIMAL(10,4) NOT NULL,
    total_cost DECIMAL(15,2) NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_portfolio_stock (portfolio_id, stock_id),
    INDEX idx_portfolio (portfolio_id),
    INDEX idx_stock (stock_id)
);

-- Portfolio history (daily snapshots)
CREATE TABLE portfolio_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NOT NULL,
    snapshot_date DATE NOT NULL,
    total_value DECIMAL(15,2) NOT NULL,
    cash_value DECIMAL(15,2) NOT NULL,
    stock_value DECIMAL(15,2) NOT NULL,
    total_cost DECIMAL(15,2) NOT NULL,
    realized_gain_loss DECIMAL(15,2) DEFAULT 0,
    unrealized_gain_loss DECIMAL(15,2) DEFAULT 0,
    dividend_income DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_portfolio_date (portfolio_id, snapshot_date),
    INDEX idx_portfolio_date (portfolio_id, snapshot_date DESC)
);

-- Alerts system
CREATE TABLE alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    portfolio_id INT NULL,
    stock_id INT NULL,
    alert_type ENUM('PRICE_CHANGE', 'DIVIDEND_PAYMENT', 'CASH_THRESHOLD', 'CUSTOM') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    threshold_value DECIMAL(15,4) NULL,
    is_triggered BOOLEAN DEFAULT FALSE,
    is_read BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    triggered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE,
    INDEX idx_portfolio (portfolio_id),
    INDEX idx_stock (stock_id),
    INDEX idx_type (alert_type),
    INDEX idx_active (is_active),
    INDEX idx_triggered (is_triggered)
);

-- Application settings
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- API call logs (for rate limiting)
CREATE TABLE api_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    api_provider VARCHAR(50) NOT NULL,
    endpoint VARCHAR(255),
    request_count INT DEFAULT 1,
    call_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider_date (api_provider, call_date),
    INDEX idx_date (call_date)
);

-- Insert default admin user (password: changeme123)
INSERT INTO users (username, password_hash, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('app_version', '1.0.0', 'Current application version'),
('last_price_update', '', 'Timestamp of last price update'),
('market_status', 'CLOSED', 'Current market status'),
('default_currency', 'USD', 'Default currency for calculations'),
('price_update_enabled', '1', 'Enable automatic price updates'),
('dividend_update_enabled', '1', 'Enable automatic dividend updates');

-- Create indexes for better performance
CREATE INDEX idx_transactions_portfolio_type_date ON transactions(portfolio_id, transaction_type, transaction_date);
CREATE INDEX idx_stock_prices_symbol_date ON stock_prices(stock_id, price_date DESC);
CREATE INDEX idx_portfolio_history_date ON portfolio_history(snapshot_date DESC);