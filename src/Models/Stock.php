<?php

namespace PortfolioTracker\Models;

use PDO;
use PDOException;
use PortfolioTracker\Config\Database;
use PortfolioTracker\Config\App;

class Stock
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create or update stock
     */
    public function createOrUpdate(array $data): int
    {
        try {
            // Check if stock exists
            $existing = $this->getBySymbol($data['symbol']);
            
            if ($existing) {
                // Update existing stock
                $this->update($existing['id'], $data);
                return $existing['id'];
            } else {
                // Create new stock
                return $this->create($data);
            }
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to create or update stock", [
                'symbol' => $data['symbol'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a new stock
     */
    public function create(array $data): int
    {
        try {
            $sql = "INSERT INTO stocks (symbol, name, exchange, sector, industry, market_cap, is_etf)
                    VALUES (:symbol, :name, :exchange, :sector, :industry, :market_cap, :is_etf)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':symbol' => strtoupper($data['symbol']),
                ':name' => $data['name'],
                ':exchange' => $data['exchange'] ?? null,
                ':sector' => $data['sector'] ?? null,
                ':industry' => $data['industry'] ?? null,
                ':market_cap' => $data['market_cap'] ?? null,
                ':is_etf' => $data['is_etf'] ?? false
            ]);

            $stockId = (int)$this->db->lastInsertId();
            
            App::getLogger()->info("Stock created", ['id' => $stockId, 'symbol' => $data['symbol']]);
            
            return $stockId;
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to create stock", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get stock by ID
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT s.*, cp.current_price, cp.change_amount, cp.change_percent, 
                           cp.volume, cp.last_updated as price_updated
                    FROM stocks s
                    LEFT JOIN current_prices cp ON s.id = cp.stock_id
                    WHERE s.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get stock", ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get stock by symbol
     */
    public function getBySymbol(string $symbol): ?array
    {
        try {
            $sql = "SELECT s.*, cp.current_price, cp.change_amount, cp.change_percent, 
                           cp.volume, cp.last_updated as price_updated
                    FROM stocks s
                    LEFT JOIN current_prices cp ON s.id = cp.stock_id
                    WHERE s.symbol = :symbol";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':symbol' => strtoupper($symbol)]);
            
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get stock by symbol", [
                'symbol' => $symbol, 
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Search stocks by symbol or name
     */
    public function search(string $query, int $limit = 20): array
    {
        try {
            $searchTerm = '%' . strtoupper($query) . '%';
            
            $sql = "SELECT s.*, cp.current_price, cp.change_amount, cp.change_percent
                    FROM stocks s
                    LEFT JOIN current_prices cp ON s.id = cp.stock_id
                    WHERE (s.symbol LIKE :query OR UPPER(s.name) LIKE :query) 
                    AND s.is_active = 1
                    ORDER BY 
                        CASE WHEN s.symbol = :exact_query THEN 1 ELSE 2 END,
                        s.symbol
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':query', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':exact_query', strtoupper($query), PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to search stocks", ['query' => $query, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get all active stocks
     */
    public function getAllActive(int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT s.*, cp.current_price, cp.change_amount, cp.change_percent
                    FROM stocks s
                    LEFT JOIN current_prices cp ON s.id = cp.stock_id
                    WHERE s.is_active = 1
                    ORDER BY s.symbol
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get active stocks", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Update stock
     */
    public function update(int $id, array $data): bool
    {
        try {
            $setParts = [];
            $params = [':id' => $id];

            $allowedFields = ['name', 'exchange', 'sector', 'industry', 'market_cap', 'is_etf', 'is_active'];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $setParts[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }

            if (empty($setParts)) {
                return false;
            }

            $sql = "UPDATE stocks SET " . implode(', ', $setParts) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute($params);
            
            if ($result) {
                App::getLogger()->info("Stock updated", ['id' => $id]);
            }
            
            return $result;
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to update stock", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Update or insert current price
     */
    public function updateCurrentPrice(int $stockId, array $priceData): bool
    {
        try {
            $sql = "INSERT INTO current_prices (
                        stock_id, current_price, change_amount, change_percent, volume, market_cap
                    ) VALUES (
                        :stock_id, :current_price, :change_amount, :change_percent, :volume, :market_cap
                    )
                    ON DUPLICATE KEY UPDATE
                        current_price = VALUES(current_price),
                        change_amount = VALUES(change_amount),
                        change_percent = VALUES(change_percent),
                        volume = VALUES(volume),
                        market_cap = VALUES(market_cap),
                        last_updated = CURRENT_TIMESTAMP";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':stock_id' => $stockId,
                ':current_price' => $priceData['price'],
                ':change_amount' => $priceData['change_amount'] ?? 0,
                ':change_percent' => $priceData['change_percent'] ?? 0,
                ':volume' => $priceData['volume'] ?? 0,
                ':market_cap' => $priceData['market_cap'] ?? null
            ]);
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to update current price", [
                'stock_id' => $stockId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Add historical price data
     */
    public function addHistoricalPrice(int $stockId, array $priceData): bool
    {
        try {
            $sql = "INSERT INTO stock_prices (
                        stock_id, price_date, open_price, high_price, low_price,
                        close_price, volume, adjusted_close
                    ) VALUES (
                        :stock_id, :price_date, :open_price, :high_price, :low_price,
                        :close_price, :volume, :adjusted_close
                    )
                    ON DUPLICATE KEY UPDATE
                        open_price = VALUES(open_price),
                        high_price = VALUES(high_price),
                        low_price = VALUES(low_price),
                        close_price = VALUES(close_price),
                        volume = VALUES(volume),
                        adjusted_close = VALUES(adjusted_close)";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':stock_id' => $stockId,
                ':price_date' => $priceData['date'],
                ':open_price' => $priceData['open'] ?? null,
                ':high_price' => $priceData['high'] ?? null,
                ':low_price' => $priceData['low'] ?? null,
                ':close_price' => $priceData['close'],
                ':volume' => $priceData['volume'] ?? null,
                ':adjusted_close' => $priceData['adjusted_close'] ?? $priceData['close']
            ]);
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to add historical price", [
                'stock_id' => $stockId,
                'date' => $priceData['date'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get historical prices
     */
    public function getHistoricalPrices(int $stockId, string $period = '1Y', int $limit = 365): array
    {
        try {
            $dateFilter = $this->getDateFilter($period);
            $whereClause = $dateFilter ? "AND price_date >= :date_filter" : "";
            
            $sql = "SELECT * FROM stock_prices 
                    WHERE stock_id = :stock_id $whereClause
                    ORDER BY price_date DESC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':stock_id', $stockId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            
            if ($dateFilter) {
                $stmt->bindValue(':date_filter', $dateFilter, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get historical prices", [
                'stock_id' => $stockId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get price change over period
     */
    public function getPriceChange(int $stockId, string $period = '1D'): array
    {
        try {
            $dateFilter = $this->getDateFilter($period);
            if (!$dateFilter) {
                return ['change_amount' => 0, 'change_percent' => 0];
            }

            // Get current price
            $currentSql = "SELECT current_price FROM current_prices WHERE stock_id = :stock_id";
            $stmt = $this->db->prepare($currentSql);
            $stmt->execute([':stock_id' => $stockId]);
            $current = $stmt->fetch();

            if (!$current) {
                return ['change_amount' => 0, 'change_percent' => 0];
            }

            // Get historical price
            $historicalSql = "SELECT close_price FROM stock_prices 
                             WHERE stock_id = :stock_id AND price_date >= :date_filter
                             ORDER BY price_date ASC LIMIT 1";
            $stmt = $this->db->prepare($historicalSql);
            $stmt->execute([
                ':stock_id' => $stockId,
                ':date_filter' => $dateFilter
            ]);
            $historical = $stmt->fetch();

            if (!$historical) {
                return ['change_amount' => 0, 'change_percent' => 0];
            }

            $changeAmount = $current['current_price'] - $historical['close_price'];
            $changePercent = $historical['close_price'] > 0 ? 
                ($changeAmount / $historical['close_price']) * 100 : 0;

            return [
                'change_amount' => $changeAmount,
                'change_percent' => $changePercent
            ];
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get price change", [
                'stock_id' => $stockId,
                'period' => $period,
                'error' => $e->getMessage()
            ]);
            return ['change_amount' => 0, 'change_percent' => 0];
        }
    }

    /**
     * Get stocks needing price updates
     */
    public function getStocksNeedingUpdate(int $maxAge = 900): array
    {
        try {
            $sql = "SELECT s.id, s.symbol, s.name
                    FROM stocks s
                    LEFT JOIN current_prices cp ON s.id = cp.stock_id
                    WHERE s.is_active = 1 
                    AND (cp.last_updated IS NULL OR cp.last_updated < DATE_SUB(NOW(), INTERVAL :max_age SECOND))
                    ORDER BY cp.last_updated ASC NULLS FIRST";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':max_age' => $maxAge]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get stocks needing update", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get stocks in portfolios
     */
    public function getPortfolioStocks(): array
    {
        try {
            $sql = "SELECT DISTINCT s.id, s.symbol, s.name
                    FROM stocks s
                    INNER JOIN portfolio_holdings ph ON s.id = ph.stock_id
                    WHERE ph.shares > 0 AND s.is_active = 1
                    ORDER BY s.symbol";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get portfolio stocks", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Add dividend data
     */
    public function addDividend(int $stockId, array $dividendData): bool
    {
        try {
            $sql = "INSERT INTO dividends (
                        stock_id, ex_date, payment_date, record_date, amount, frequency, is_special
                    ) VALUES (
                        :stock_id, :ex_date, :payment_date, :record_date, :amount, :frequency, :is_special
                    )
                    ON DUPLICATE KEY UPDATE
                        payment_date = VALUES(payment_date),
                        record_date = VALUES(record_date),
                        amount = VALUES(amount),
                        frequency = VALUES(frequency),
                        is_special = VALUES(is_special)";
            
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':stock_id' => $stockId,
                ':ex_date' => $dividendData['ex_date'],
                ':payment_date' => $dividendData['payment_date'] ?? null,
                ':record_date' => $dividendData['record_date'] ?? null,
                ':amount' => $dividendData['amount'],
                ':frequency' => $dividendData['frequency'] ?? 'QUARTERLY',
                ':is_special' => $dividendData['is_special'] ?? false
            ]);
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to add dividend", [
                'stock_id' => $stockId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get dividends for stock
     */
    public function getDividends(int $stockId, int $limit = 20): array
    {
        try {
            $sql = "SELECT * FROM dividends 
                    WHERE stock_id = :stock_id 
                    ORDER BY ex_date DESC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':stock_id', $stockId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to get dividends", [
                'stock_id' => $stockId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get date filter for period
     */
    private function getDateFilter(string $period): ?string
    {
        $date = new \DateTime();
        
        switch (strtoupper($period)) {
            case '1D':
                return $date->sub(new \DateInterval('P1D'))->format('Y-m-d');
            case '1W':
                return $date->sub(new \DateInterval('P1W'))->format('Y-m-d');
            case '1M':
                return $date->sub(new \DateInterval('P1M'))->format('Y-m-d');
            case '3M':
                return $date->sub(new \DateInterval('P3M'))->format('Y-m-d');
            case '6M':
                return $date->sub(new \DateInterval('P6M'))->format('Y-m-d');
            case '1Y':
                return $date->sub(new \DateInterval('P1Y'))->format('Y-m-d');
            case '5Y':
                return $date->sub(new \DateInterval('P5Y'))->format('Y-m-d');
            default:
                return null;
        }
    }

    /**
     * Delete stock (soft delete)
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "UPDATE stocks SET is_active = 0 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            $result = $stmt->execute([':id' => $id]);
            
            if ($result) {
                App::getLogger()->info("Stock deleted", ['id' => $id]);
            }
            
            return $result;
        } catch (PDOException $e) {
            App::getLogger()->error("Failed to delete stock", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }
}