<?php

namespace PortfolioTracker\Models;

use PDO;
use PDOException;
use PortfolioTracker\Config\Database;
use PortfolioTracker\Config\App;

class Portfolio
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new portfolio
     */
    public function create(array $data): int
    {
        try {
            $sql = "INSERT INTO portfolios (name, description, initial_cash, current_cash) 
                    VALUES (:name, :description, :initial_cash, :current_cash)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'] ?? '',
                ':initial_cash' => $data['initial_cash'] ?? 0,
                ':current_cash' => $data['initial_cash'] ?? 0
            ]);

            $portfolioId = (int)$this->db->lastInsertId();
            
            // Log the creation
            App::getLogger()->info("Portfolio created", ['id' => $portfolioId, 'name' => $data['name']]);
            
            return $portfolioId;
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to create portfolio", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get portfolio by ID
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM portfolios WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get portfolio", ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get all active portfolios
     */
    public function getAllActive(): array
    {
        try {
            $sql = "SELECT * FROM portfolios WHERE is_active = 1 ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get active portfolios", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Update portfolio
     */
    public function update(int $id, array $data): bool
    {
        try {
            $setParts = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, ['name', 'description', 'current_cash', 'is_active'])) {
                    $setParts[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }

            if (empty($setParts)) {
                return false;
            }

            $sql = "UPDATE portfolios SET " . implode(', ', $setParts) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute($params);
            
            if ($result) {
                App::getLogger()->info("Portfolio updated", ['id' => $id]);
            }
            
            return $result;
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to update portfolio", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Delete portfolio (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "UPDATE portfolios SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([':id' => $id]);
            
            if ($result) {
                App::getLogger()->info("Portfolio deleted", ['id' => $id]);
            }
            
            return $result;
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to delete portfolio", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get portfolio holdings
     */
    public function getHoldings(int $portfolioId): array
    {
        try {
            $sql = "SELECT 
                        h.id,
                        h.shares,
                        h.average_cost,
                        h.total_cost,
                        s.symbol,
                        s.name,
                        s.is_etf,
                        cp.current_price,
                        cp.change_amount,
                        cp.change_percent,
                        (h.shares * cp.current_price) as current_value,
                        ((h.shares * cp.current_price) - h.total_cost) as unrealized_gain_loss,
                        (((h.shares * cp.current_price) - h.total_cost) / h.total_cost * 100) as gain_loss_percent
                    FROM portfolio_holdings h
                    INNER JOIN stocks s ON h.stock_id = s.id
                    LEFT JOIN current_prices cp ON s.id = cp.stock_id
                    WHERE h.portfolio_id = :portfolio_id AND h.shares > 0
                    ORDER BY (h.shares * cp.current_price) DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':portfolio_id' => $portfolioId]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get portfolio holdings", [
                'portfolio_id' => $portfolioId, 
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get portfolio summary
     */
    public function getSummary(int $portfolioId): array
    {
        try {
            // Get basic portfolio info
            $portfolio = $this->getById($portfolioId);
            if (!$portfolio) {
                return [];
            }

            // Get current holdings value
            $holdingsValue = $this->getTotalHoldingsValue($portfolioId);
            
            // Get total invested amount
            $totalInvested = $this->getTotalInvested($portfolioId);
            
            // Get realized gains/losses
            $realizedGainLoss = $this->getRealizedGainLoss($portfolioId);
            
            // Get dividend income
            $dividendIncome = $this->getDividendIncome($portfolioId);
            
            // Calculate totals
            $totalValue = $portfolio['current_cash'] + $holdingsValue;
            $totalCost = $portfolio['initial_cash'] + $totalInvested;
            $unrealizedGainLoss = $holdingsValue - $totalInvested;
            $totalGainLoss = $realizedGainLoss + $unrealizedGainLoss + $dividendIncome;
            $totalReturn = $totalCost > 0 ? ($totalGainLoss / $totalCost) * 100 : 0;

            return [
                'id' => $portfolio['id'],
                'name' => $portfolio['name'],
                'description' => $portfolio['description'],
                'total_value' => $totalValue,
                'cash_value' => $portfolio['current_cash'],
                'holdings_value' => $holdingsValue,
                'total_cost' => $totalCost,
                'total_invested' => $totalInvested,
                'realized_gain_loss' => $realizedGainLoss,
                'unrealized_gain_loss' => $unrealizedGainLoss,
                'total_gain_loss' => $totalGainLoss,
                'total_return_percent' => $totalReturn,
                'dividend_income' => $dividendIncome,
                'cash_percentage' => $totalValue > 0 ? ($portfolio['current_cash'] / $totalValue) * 100 : 0,
                'created_at' => $portfolio['created_at']
            ];
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get portfolio summary", [
                'portfolio_id' => $portfolioId, 
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get total holdings value
     */
    private function getTotalHoldingsValue(int $portfolioId): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(h.shares * cp.current_price), 0) as total_value
                    FROM portfolio_holdings h
                    INNER JOIN current_prices cp ON h.stock_id = cp.stock_id
                    WHERE h.portfolio_id = :portfolio_id AND h.shares > 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':portfolio_id' => $portfolioId]);
            
            $result = $stmt->fetch();
            return (float)($result['total_value'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get total invested amount
     */
    private function getTotalInvested(int $portfolioId): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(total_cost), 0) as total_invested
                    FROM portfolio_holdings
                    WHERE portfolio_id = :portfolio_id AND shares > 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':portfolio_id' => $portfolioId]);
            
            $result = $stmt->fetch();
            return (float)($result['total_invested'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get realized gain/loss
     */
    private function getRealizedGainLoss(int $portfolioId): float
    {
        try {
            $sql = "SELECT 
                        COALESCE(SUM(CASE 
                            WHEN transaction_type = 'SELL' THEN total_amount - fees
                            ELSE 0 
                        END), 0) as total_sales,
                        COALESCE(SUM(CASE 
                            WHEN transaction_type = 'SELL' THEN shares * (
                                SELECT AVG(price_per_share) 
                                FROM transactions t2 
                                WHERE t2.portfolio_id = t1.portfolio_id 
                                AND t2.stock_id = t1.stock_id 
                                AND t2.transaction_type = 'BUY'
                                AND t2.transaction_date <= t1.transaction_date
                            )
                            ELSE 0 
                        END), 0) as cost_basis
                    FROM transactions t1
                    WHERE portfolio_id = :portfolio_id 
                    AND transaction_type = 'SELL'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':portfolio_id' => $portfolioId]);
            
            $result = $stmt->fetch();
            return (float)($result['total_sales'] - $result['cost_basis']);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get dividend income
     */
    private function getDividendIncome(int $portfolioId): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as dividend_income
                    FROM transactions
                    WHERE portfolio_id = :portfolio_id 
                    AND transaction_type = 'DIVIDEND'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':portfolio_id' => $portfolioId]);
            
            $result = $stmt->fetch();
            return (float)($result['dividend_income'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get asset allocation
     */
    public function getAssetAllocation(int $portfolioId): array
    {
        try {
            $sql = "SELECT 
                        s.sector,
                        s.is_etf,
                        SUM(h.shares * cp.current_price) as value,
                        COUNT(*) as holdings_count
                    FROM portfolio_holdings h
                    INNER JOIN stocks s ON h.stock_id = s.id
                    INNER JOIN current_prices cp ON s.id = cp.stock_id
                    WHERE h.portfolio_id = :portfolio_id AND h.shares > 0
                    GROUP BY s.sector, s.is_etf
                    ORDER BY value DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':portfolio_id' => $portfolioId]);
            
            $holdings = $stmt->fetchAll();
            
            // Calculate total value for percentages
            $totalValue = array_sum(array_column($holdings, 'value'));
            
            // Add percentage calculation
            foreach ($holdings as &$holding) {
                $holding['percentage'] = $totalValue > 0 ? ($holding['value'] / $totalValue) * 100 : 0;
            }
            
            return $holdings;
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get asset allocation", [
                'portfolio_id' => $portfolioId, 
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Update portfolio cash
     */
    public function updateCash(int $portfolioId, float $amount): bool
    {
        try {
            $sql = "UPDATE portfolios SET current_cash = current_cash + :amount WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':amount' => $amount,
                ':id' => $portfolioId
            ]);
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to update portfolio cash", [
                'portfolio_id' => $portfolioId, 
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}