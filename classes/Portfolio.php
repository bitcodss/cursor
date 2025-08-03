<?php
require_once 'PortfolioItem.php';

class Portfolio {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    public function addItem($data) {
        try {
            $item = new PortfolioItem($data);
            $errors = $item->validate();
            
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => implode(', ', $errors)
                ];
            }
            
            $sql = "INSERT INTO portfolio_items 
                    (name, type, category, purchase_price, current_value, quantity, 
                     purchase_date, description, image_url, tags, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $item->getName(),
                $item->getType(),
                $item->getCategory(),
                $item->getPurchasePrice(),
                $item->getCurrentValue(),
                $item->getQuantity(),
                $item->getPurchaseDate(),
                $item->getDescription(),
                $item->getImageUrl(),
                $item->getTags()
            ]);
            
            return [
                'success' => true,
                'message' => 'Portfolio item added successfully!',
                'id' => $this->pdo->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error adding item: ' . $e->getMessage()
            ];
        }
    }
    
    public function updateItem($id, $data) {
        try {
            $item = new PortfolioItem($data);
            $errors = $item->validate();
            
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => implode(', ', $errors)
                ];
            }
            
            // Track value change for history
            $oldValue = $this->getItemCurrentValue($id);
            $newValue = $item->getCurrentValue() * $item->getQuantity();
            
            $sql = "UPDATE portfolio_items 
                    SET name = ?, type = ?, category = ?, purchase_price = ?, 
                        current_value = ?, quantity = ?, purchase_date = ?, 
                        description = ?, image_url = ?, tags = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $item->getName(),
                $item->getType(),
                $item->getCategory(),
                $item->getPurchasePrice(),
                $item->getCurrentValue(),
                $item->getQuantity(),
                $item->getPurchaseDate(),
                $item->getDescription(),
                $item->getImageUrl(),
                $item->getTags(),
                $id
            ]);
            
            // Add to history if value changed significantly
            if (abs($newValue - $oldValue) > 0.01) {
                $this->addToHistory($id, $newValue - $oldValue, $newValue, 'Value updated');
            }
            
            return [
                'success' => true,
                'message' => 'Portfolio item updated successfully!'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating item: ' . $e->getMessage()
            ];
        }
    }
    
    public function deleteItem($id) {
        try {
            // Delete history first
            $stmt = $this->pdo->prepare("DELETE FROM portfolio_history WHERE portfolio_item_id = ?");
            $stmt->execute([$id]);
            
            // Delete the item
            $stmt = $this->pdo->prepare("DELETE FROM portfolio_items WHERE id = ?");
            $stmt->execute([$id]);
            
            return [
                'success' => true,
                'message' => 'Portfolio item deleted successfully!'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting item: ' . $e->getMessage()
            ];
        }
    }
    
    public function getItem($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM portfolio_items WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? new PortfolioItem($data) : null;
    }
    
    public function getAllItems($orderBy = 'created_at', $orderDir = 'DESC') {
        $allowedColumns = ['name', 'type', 'category', 'purchase_price', 'current_value', 'created_at'];
        $orderBy = in_array($orderBy, $allowedColumns) ? $orderBy : 'created_at';
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
        
        $stmt = $this->pdo->prepare("SELECT * FROM portfolio_items ORDER BY {$orderBy} {$orderDir}");
        $stmt->execute();
        
        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = new PortfolioItem($row);
        }
        
        return $items;
    }
    
    public function getItemsByCategory() {
        $stmt = $this->pdo->prepare("
            SELECT category, COUNT(*) as count, 
                   SUM(purchase_price * quantity) as total_purchase,
                   SUM(current_value * quantity) as total_current
            FROM portfolio_items 
            WHERE category IS NOT NULL AND category != ''
            GROUP BY category
        ");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTotalValue() {
        $stmt = $this->pdo->prepare("
            SELECT SUM(purchase_price * quantity) as total_purchase,
                   SUM(current_value * quantity) as total_current
            FROM portfolio_items
        ");
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getPortfolioStats() {
        $totals = $this->getTotalValue();
        $totalPurchase = $totals['total_purchase'] ?? 0;
        $totalCurrent = $totals['total_current'] ?? 0;
        $gainLoss = $totalCurrent - $totalPurchase;
        $gainLossPercentage = $totalPurchase > 0 ? (($gainLoss / $totalPurchase) * 100) : 0;
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as item_count FROM portfolio_items");
        $stmt->execute();
        $itemCount = $stmt->fetchColumn();
        
        return [
            'total_purchase' => $totalPurchase,
            'total_current' => $totalCurrent,
            'gain_loss' => $gainLoss,
            'gain_loss_percentage' => $gainLossPercentage,
            'item_count' => $itemCount
        ];
    }
    
    public function getCategories() {
        $stmt = $this->pdo->prepare("SELECT * FROM categories ORDER BY name");
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function searchItems($query) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM portfolio_items 
            WHERE name LIKE ? OR description LIKE ? OR tags LIKE ? OR category LIKE ?
            ORDER BY name
        ");
        $searchTerm = "%{$query}%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        
        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = new PortfolioItem($row);
        }
        
        return $items;
    }
    
    private function getItemCurrentValue($id) {
        $stmt = $this->pdo->prepare("SELECT current_value * quantity as total FROM portfolio_items WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() ?? 0;
    }
    
    private function addToHistory($itemId, $valueChange, $newValue, $notes = '') {
        $stmt = $this->pdo->prepare("
            INSERT INTO portfolio_history (portfolio_item_id, value_change, new_value, notes) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$itemId, $valueChange, $newValue, $notes]);
    }
    
    public function getRecentActivity($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT ph.*, pi.name as item_name
            FROM portfolio_history ph
            JOIN portfolio_items pi ON ph.portfolio_item_id = pi.id
            ORDER BY ph.change_date DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>