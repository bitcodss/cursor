<?php
class PortfolioItem {
    private $id;
    private $name;
    private $type;
    private $category;
    private $purchasePrice;
    private $currentValue;
    private $quantity;
    private $purchaseDate;
    private $description;
    private $imageUrl;
    private $tags;
    private $createdAt;
    private $updatedAt;
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->setFromArray($data);
        }
    }
    
    public function setFromArray($data) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->type = $data['type'] ?? '';
        $this->category = $data['category'] ?? '';
        $this->purchasePrice = $data['purchase_price'] ?? 0;
        $this->currentValue = $data['current_value'] ?? 0;
        $this->quantity = $data['quantity'] ?? 1;
        $this->purchaseDate = $data['purchase_date'] ?? null;
        $this->description = $data['description'] ?? '';
        $this->imageUrl = $data['image_url'] ?? '';
        $this->tags = $data['tags'] ?? '';
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }
    
    public function validate() {
        $errors = [];
        
        if (empty($this->name)) {
            $errors[] = 'Name is required';
        }
        
        if (empty($this->type)) {
            $errors[] = 'Type is required';
        }
        
        if (!is_numeric($this->purchasePrice) || $this->purchasePrice < 0) {
            $errors[] = 'Purchase price must be a valid positive number';
        }
        
        if (!is_numeric($this->currentValue) || $this->currentValue < 0) {
            $errors[] = 'Current value must be a valid positive number';
        }
        
        if (!is_numeric($this->quantity) || $this->quantity < 1) {
            $errors[] = 'Quantity must be at least 1';
        }
        
        return $errors;
    }
    
    public function getTotalPurchaseValue() {
        return $this->purchasePrice * $this->quantity;
    }
    
    public function getTotalCurrentValue() {
        return $this->currentValue * $this->quantity;
    }
    
    public function getGainLoss() {
        return $this->getTotalCurrentValue() - $this->getTotalPurchaseValue();
    }
    
    public function getGainLossPercentage() {
        if ($this->getTotalPurchaseValue() == 0) {
            return 0;
        }
        return (($this->getTotalCurrentValue() - $this->getTotalPurchaseValue()) / $this->getTotalPurchaseValue()) * 100;
    }
    
    public function getFormattedPurchaseDate() {
        return $this->purchaseDate ? date('M j, Y', strtotime($this->purchaseDate)) : '';
    }
    
    public function getTagsArray() {
        return $this->tags ? explode(',', $this->tags) : [];
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getCategory() { return $this->category; }
    public function getPurchasePrice() { return $this->purchasePrice; }
    public function getCurrentValue() { return $this->currentValue; }
    public function getQuantity() { return $this->quantity; }
    public function getPurchaseDate() { return $this->purchaseDate; }
    public function getDescription() { return $this->description; }
    public function getImageUrl() { return $this->imageUrl; }
    public function getTags() { return $this->tags; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    
    // Setters
    public function setName($name) { $this->name = $name; }
    public function setType($type) { $this->type = $type; }
    public function setCategory($category) { $this->category = $category; }
    public function setPurchasePrice($price) { $this->purchasePrice = $price; }
    public function setCurrentValue($value) { $this->currentValue = $value; }
    public function setQuantity($quantity) { $this->quantity = $quantity; }
    public function setPurchaseDate($date) { $this->purchaseDate = $date; }
    public function setDescription($description) { $this->description = $description; }
    public function setImageUrl($url) { $this->imageUrl = $url; }
    public function setTags($tags) { $this->tags = $tags; }
}
?>