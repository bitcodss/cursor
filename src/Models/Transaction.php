<?php

namespace PortfolioTracker\Models;

use PDO;
use PDOException;
use PortfolioTracker\Config\Database;
use PortfolioTracker\Config\App;

class Transaction
{
    private PDO $db;
    private Portfolio $portfolioModel;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->portfolioModel = new Portfolio();
    }

    /**
     * Create a new transaction
     */
    public function create(array $data): int
    {
        try {
            Database::beginTransaction();

            // Insert transaction
            $sql = "INSERT INTO transactions (
                        portfolio_id, stock_id, transaction_type, shares, 
                        price_per_share, total_amount, fees, broker, 
                        transaction_date, notes, split_ratio
                    ) VALUES (
                        :portfolio_id, :stock_id, :transaction_type, :shares,
                        :price_per_share, :total_amount, :fees, :broker,
                        :transaction_date, :notes, :split_ratio
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':portfolio_id' => $data['portfolio_id'],
                ':stock_id' => $data['stock_id'] ?? null,
                ':transaction_type' => $data['transaction_type'],
                ':shares' => $data['shares'] ?? 0,
                ':price_per_share' => $data['price_per_share'] ?? 0,
                ':total_amount' => $data['total_amount'],
                ':fees' => $data['fees'] ?? 0,
                ':broker' => $data['broker'] ?? '',
                ':transaction_date' => $data['transaction_date'],
                ':notes' => $data['notes'] ?? '',
                ':split_ratio' => $data['split_ratio'] ?? null
            ]);

            $transactionId = (int)$this->db->lastInsertId();

            // Update portfolio and holdings based on transaction type
            $this->processTransaction($data, $transactionId);

            Database::commit();
            
            App::getLogger()->info("Transaction created", [
                'id' => $transactionId, 
                'type' => $data['transaction_type'],
                'portfolio_id' => $data['portfolio_id']
            ]);
            
            return $transactionId;
        } catch (PDOException $e) {
            Database::rollback();
            App::getLogger()->error("Failed to create transaction", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Process transaction and update portfolio holdings
     */
    private function processTransaction(array $data, int $transactionId): void
    {
        $type = $data['transaction_type'];
        $portfolioId = $data['portfolio_id'];

        switch ($type) {
            case 'BUY':
                $this->processBuyTransaction($data);
                break;
            case 'SELL':
                $this->processSellTransaction($data);
                break;
            case 'DIVIDEND':
                $this->processDividendTransaction($data);
                break;
            case 'DEPOSIT':
            case 'WITHDRAWAL':
                $this->processCashTransaction($data);
                break;
            case 'SPLIT':
            case 'REVERSE_SPLIT':
                $this->processSplitTransaction($data);
                break;
        }
    }

    /**
     * Process buy transaction
     */
    private function processBuyTransaction(array $data): void
    {
        $portfolioId = $data['portfolio_id'];
        $stockId = $data['stock_id'];
        $shares = $data['shares'];
        $pricePerShare = $data['price_per_share'];
        $totalCost = $data['total_amount'] + $data['fees'];

        // Update portfolio cash
        $this->portfolioModel->updateCash($portfolioId, -$totalCost);

        // Update or create holding
        $this->updateHolding($portfolioId, $stockId, $shares, $totalCost);
    }

    /**
     * Process sell transaction
     */
    private function processSellTransaction(array $data): void
    {
        $portfolioId = $data['portfolio_id'];
        $stockId = $data['stock_id'];
        $shares = -$data['shares']; // Negative for sell
        $totalReceived = $data['total_amount'] - $data['fees'];

        // Update portfolio cash
        $this->portfolioModel->updateCash($portfolioId, $totalReceived);

        // Calculate cost basis for the sold shares
        $costBasis = $this->calculateCostBasis($portfolioId, $stockId, abs($shares));

        // Update holding
        $this->updateHolding($portfolioId, $stockId, $shares, -$costBasis);
    }

    /**
     * Process dividend transaction
     */
    private function processDividendTransaction(array $data): void
    {
        $portfolioId = $data['portfolio_id'];
        $dividendAmount = $data['total_amount'];

        // Update portfolio cash
        $this->portfolioModel->updateCash($portfolioId, $dividendAmount);
    }

    /**
     * Process cash transaction (deposit/withdrawal)
     */
    private function processCashTransaction(array $data): void
    {
        $portfolioId = $data['portfolio_id'];
        $amount = $data['total_amount'];
        
        if ($data['transaction_type'] === 'WITHDRAWAL') {
            $amount = -$amount;
        }

        // Update portfolio cash
        $this->portfolioModel->updateCash($portfolioId, $amount);
    }

    /**
     * Process stock split transaction
     */
    private function processSplitTransaction(array $data): void
    {
        $portfolioId = $data['portfolio_id'];
        $stockId = $data['stock_id'];
        $splitRatio = $data['split_ratio'];

        // Update all holdings for this stock in the portfolio
        $sql = "UPDATE portfolio_holdings 
                SET shares = shares * :split_ratio,
                    average_cost = average_cost / :split_ratio
                WHERE portfolio_id = :portfolio_id AND stock_id = :stock_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':split_ratio' => $splitRatio,
            ':portfolio_id' => $portfolioId,
            ':stock_id' => $stockId
        ]);
    }

    /**
     * Update portfolio holding
     */
    private function updateHolding(int $portfolioId, int $stockId, float $shares, float $cost): void
    {
        // Check if holding exists
        $sql = "SELECT * FROM portfolio_holdings 
                WHERE portfolio_id = :portfolio_id AND stock_id = :stock_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':portfolio_id' => $portfolioId, ':stock_id' => $stockId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing holding
            $newShares = $existing['shares'] + $shares;
            $newTotalCost = $existing['total_cost'] + $cost;
            $newAverageCost = $newShares > 0 ? $newTotalCost / $newShares : 0;

            if ($newShares <= 0) {
                // Remove holding if no shares left
                $sql = "DELETE FROM portfolio_holdings 
                        WHERE portfolio_id = :portfolio_id AND stock_id = :stock_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':portfolio_id' => $portfolioId, ':stock_id' => $stockId]);
            } else {
                // Update holding
                $sql = "UPDATE portfolio_holdings 
                        SET shares = :shares, total_cost = :total_cost, average_cost = :average_cost
                        WHERE portfolio_id = :portfolio_id AND stock_id = :stock_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':shares' => $newShares,
                    ':total_cost' => $newTotalCost,
                    ':average_cost' => $newAverageCost,
                    ':portfolio_id' => $portfolioId,
                    ':stock_id' => $stockId
                ]);
            }
        } else if ($shares > 0) {
            // Create new holding (only for positive shares)
            $averageCost = $cost / $shares;
            $sql = "INSERT INTO portfolio_holdings (portfolio_id, stock_id, shares, total_cost, average_cost)
                    VALUES (:portfolio_id, :stock_id, :shares, :total_cost, :average_cost)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':portfolio_id' => $portfolioId,
                ':stock_id' => $stockId,
                ':shares' => $shares,
                ':total_cost' => $cost,
                ':average_cost' => $averageCost
            ]);
        }
    }

    /**
     * Calculate cost basis for sold shares using FIFO method
     */
    private function calculateCostBasis(int $portfolioId, int $stockId, float $soldShares): float
    {
        // Get current holding
        $sql = "SELECT average_cost FROM portfolio_holdings 
                WHERE portfolio_id = :portfolio_id AND stock_id = :stock_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':portfolio_id' => $portfolioId, ':stock_id' => $stockId]);
        $holding = $stmt->fetch();

        if ($holding) {
            return $soldShares * $holding['average_cost'];
        }

        return 0;
    }

    /**
     * Get transaction by ID
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT t.*, s.symbol, s.name as stock_name, p.name as portfolio_name
                    FROM transactions t
                    LEFT JOIN stocks s ON t.stock_id = s.id
                    LEFT JOIN portfolios p ON t.portfolio_id = p.id
                    WHERE t.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get transaction", ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get transactions by portfolio
     */
    public function getByPortfolio(int $portfolioId, int $limit = 50, int $offset = 0): array
    {
        try {
            $sql = "SELECT t.*, s.symbol, s.name as stock_name
                    FROM transactions t
                    LEFT JOIN stocks s ON t.stock_id = s.id
                    WHERE t.portfolio_id = :portfolio_id
                    ORDER BY t.transaction_date DESC, t.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':portfolio_id', $portfolioId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get portfolio transactions", [
                'portfolio_id' => $portfolioId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get recent transactions across all portfolios
     */
    public function getRecent(int $limit = 20): array
    {
        try {
            $sql = "SELECT t.*, s.symbol, s.name as stock_name, p.name as portfolio_name
                    FROM transactions t
                    LEFT JOIN stocks s ON t.stock_id = s.id
                    LEFT JOIN portfolios p ON t.portfolio_id = p.id
                    ORDER BY t.transaction_date DESC, t.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get recent transactions", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Update transaction
     */
    public function update(int $id, array $data): bool
    {
        try {
            // First get original transaction to reverse its effects
            $original = $this->getById($id);
            if (!$original) {
                return false;
            }

            Database::beginTransaction();

            // Reverse original transaction effects
            $this->reverseTransaction($original);

            // Update transaction
            $setParts = [];
            $params = [':id' => $id];

            $allowedFields = [
                'stock_id', 'transaction_type', 'shares', 'price_per_share',
                'total_amount', 'fees', 'broker', 'transaction_date', 'notes', 'split_ratio'
            ];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $setParts[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }

            if (!empty($setParts)) {
                $sql = "UPDATE transactions SET " . implode(', ', $setParts) . " WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);

                // Apply new transaction effects
                $updatedData = array_merge($original, $data);
                $this->processTransaction($updatedData, $id);
            }

            Database::commit();
            
            App::getLogger()->info("Transaction updated", ['id' => $id]);
            return true;
        } catch (PDOException $e) {
            Database::rollback();
            App::getLogger()->error("Failed to update transaction", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Reverse transaction effects
     */
    private function reverseTransaction(array $transaction): void
    {
        $reverseData = $transaction;
        
        // Reverse the transaction type logic
        switch ($transaction['transaction_type']) {
            case 'BUY':
                $reverseData['transaction_type'] = 'SELL';
                break;
            case 'SELL':
                $reverseData['transaction_type'] = 'BUY';
                break;
            case 'DEPOSIT':
                $reverseData['transaction_type'] = 'WITHDRAWAL';
                break;
            case 'WITHDRAWAL':
                $reverseData['transaction_type'] = 'DEPOSIT';
                break;
            case 'DIVIDEND':
                // Reverse dividend by withdrawing the amount
                $this->portfolioModel->updateCash($transaction['portfolio_id'], -$transaction['total_amount']);
                return;
            case 'SPLIT':
                $reverseData['split_ratio'] = 1 / $transaction['split_ratio'];
                break;
            case 'REVERSE_SPLIT':
                $reverseData['split_ratio'] = 1 / $transaction['split_ratio'];
                break;
        }

        $this->processTransaction($reverseData, 0);
    }

    /**
     * Delete transaction
     */
    public function delete(int $id): bool
    {
        try {
            // Get transaction to reverse its effects
            $transaction = $this->getById($id);
            if (!$transaction) {
                return false;
            }

            Database::beginTransaction();

            // Reverse transaction effects
            $this->reverseTransaction($transaction);

            // Delete transaction
            $sql = "DELETE FROM transactions WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $id]);

            Database::commit();
            
            App::getLogger()->info("Transaction deleted", ['id' => $id]);
            return $result;
        } catch (PDOException $e) {
            Database::rollback();
            App::getLogger()->error("Failed to delete transaction", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get transaction statistics
     */
    public function getStatistics(int $portfolioId = null, string $period = 'all'): array
    {
        try {
            $whereClause = $portfolioId ? "WHERE portfolio_id = :portfolio_id" : "";
            $params = $portfolioId ? [':portfolio_id' => $portfolioId] : [];

            // Add date filter based on period
            if ($period !== 'all') {
                $dateFilter = $this->getDateFilter($period);
                if ($dateFilter) {
                    $whereClause = $whereClause ? 
                        $whereClause . " AND transaction_date >= :date_filter" :
                        "WHERE transaction_date >= :date_filter";
                    $params[':date_filter'] = $dateFilter;
                }
            }

            $sql = "SELECT 
                        transaction_type,
                        COUNT(*) as transaction_count,
                        SUM(total_amount) as total_amount,
                        SUM(fees) as total_fees
                    FROM transactions 
                    $whereClause
                    GROUP BY transaction_type";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get transaction statistics", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get date filter for period
     */
    private function getDateFilter(string $period): ?string
    {
        $date = new \DateTime();
        
        switch ($period) {
            case '1d':
                return $date->sub(new \DateInterval('P1D'))->format('Y-m-d');
            case '1w':
                return $date->sub(new \DateInterval('P1W'))->format('Y-m-d');
            case '1m':
                return $date->sub(new \DateInterval('P1M'))->format('Y-m-d');
            case '3m':
                return $date->sub(new \DateInterval('P3M'))->format('Y-m-d');
            case '1y':
                return $date->sub(new \DateInterval('P1Y'))->format('Y-m-d');
            default:
                return null;
        }
    }
}