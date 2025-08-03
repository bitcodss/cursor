<?php
class Database {
    private $pdo;
    private $dbFile = 'data/portfolio.db';
    
    public function __construct() {
        // Create data directory if it doesn't exist
        $dataDir = dirname($this->dbFile);
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        try {
            $this->pdo = new PDO('sqlite:' . $this->dbFile);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTables();
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    private function createTables() {
        $sql = "
            CREATE TABLE IF NOT EXISTS portfolio_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(100) NOT NULL,
                category VARCHAR(100),
                purchase_price DECIMAL(10,2),
                current_value DECIMAL(10,2),
                quantity INTEGER DEFAULT 1,
                purchase_date DATE,
                description TEXT,
                image_url VARCHAR(500),
                tags VARCHAR(500),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS portfolio_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                portfolio_item_id INTEGER,
                value_change DECIMAL(10,2),
                new_value DECIMAL(10,2),
                change_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                notes TEXT,
                FOREIGN KEY (portfolio_item_id) REFERENCES portfolio_items(id)
            );
            
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL UNIQUE,
                color VARCHAR(7) DEFAULT '#6c757d',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ";
        
        $this->pdo->exec($sql);
        
        // Insert default categories
        $this->insertDefaultCategories();
    }
    
    private function insertDefaultCategories() {
        $categories = [
            ['Stocks', '#28a745'],
            ['Bonds', '#007bff'],
            ['Real Estate', '#ffc107'],
            ['Cryptocurrency', '#17a2b8'],
            ['Commodities', '#fd7e14'],
            ['Art & Collectibles', '#6f42c1'],
            ['Cash & Savings', '#20c997'],
            ['Other', '#6c757d']
        ];
        
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO categories (name, color) VALUES (?, ?)");
        foreach ($categories as $category) {
            $stmt->execute($category);
        }
    }
}

// Global database instance
$db = new Database();
$pdo = $db->getConnection();
?>