<?php

namespace PortfolioTracker\Services;

use PortfolioTracker\Config\App;
use PortfolioTracker\Models\Stock;

class YahooFinanceAPI
{
    private const BASE_URL = 'https://query1.finance.yahoo.com/v8/finance/chart/';
    private const QUOTE_URL = 'https://query1.finance.yahoo.com/v7/finance/quote';
    private const SEARCH_URL = 'https://query1.finance.yahoo.com/v1/finance/search';
    private const DIVIDEND_URL = 'https://query1.finance.yahoo.com/v7/finance/dividend/';
    
    private Stock $stockModel;
    private array $cache = [];
    private int $cacheExpiry = 900; // 15 minutes

    public function __construct()
    {
        $this->stockModel = new Stock();
        $this->cacheExpiry = App::get('cache_duration', 900);
    }

    /**
     * Get current stock quote
     */
    public function getQuote(string $symbol): ?array
    {
        try {
            $cacheKey = "quote_" . strtoupper($symbol);
            
            // Check cache first
            if ($this->isCached($cacheKey)) {
                return $this->cache[$cacheKey]['data'];
            }

            $url = self::QUOTE_URL . "?symbols=" . urlencode(strtoupper($symbol));
            $response = $this->makeRequest($url);

            if (!$response || !isset($response['quoteResponse']['result'][0])) {
                return null;
            }

            $data = $response['quoteResponse']['result'][0];
            
            $quote = [
                'symbol' => $data['symbol'],
                'name' => $data['longName'] ?? $data['shortName'] ?? $data['symbol'],
                'price' => $data['regularMarketPrice'] ?? 0,
                'change_amount' => $data['regularMarketChange'] ?? 0,
                'change_percent' => $data['regularMarketChangePercent'] ?? 0,
                'volume' => $data['regularMarketVolume'] ?? 0,
                'market_cap' => $data['marketCap'] ?? null,
                'open' => $data['regularMarketOpen'] ?? null,
                'high' => $data['regularMarketDayHigh'] ?? null,
                'low' => $data['regularMarketDayLow'] ?? null,
                'previous_close' => $data['regularMarketPreviousClose'] ?? null,
                'exchange' => $data['fullExchangeName'] ?? $data['exchange'] ?? null,
                'sector' => $data['sector'] ?? null,
                'industry' => $data['industry'] ?? null,
                'is_etf' => isset($data['quoteType']) && $data['quoteType'] === 'ETF'
            ];

            // Cache the result
            $this->setCache($cacheKey, $quote);

            return $quote;
        } catch (\Exception $e) {
            App::getLogger()->error("Failed to get quote for $symbol", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get multiple quotes at once
     */
    public function getMultipleQuotes(array $symbols): array
    {
        try {
            if (empty($symbols)) {
                return [];
            }

            $symbolsString = implode(',', array_map('strtoupper', $symbols));
            $url = self::QUOTE_URL . "?symbols=" . urlencode($symbolsString);
            $response = $this->makeRequest($url);

            if (!$response || !isset($response['quoteResponse']['result'])) {
                return [];
            }

            $quotes = [];
            foreach ($response['quoteResponse']['result'] as $data) {
                $quotes[$data['symbol']] = [
                    'symbol' => $data['symbol'],
                    'name' => $data['longName'] ?? $data['shortName'] ?? $data['symbol'],
                    'price' => $data['regularMarketPrice'] ?? 0,
                    'change_amount' => $data['regularMarketChange'] ?? 0,
                    'change_percent' => $data['regularMarketChangePercent'] ?? 0,
                    'volume' => $data['regularMarketVolume'] ?? 0,
                    'market_cap' => $data['marketCap'] ?? null,
                    'open' => $data['regularMarketOpen'] ?? null,
                    'high' => $data['regularMarketDayHigh'] ?? null,
                    'low' => $data['regularMarketDayLow'] ?? null,
                    'previous_close' => $data['regularMarketPreviousClose'] ?? null,
                    'exchange' => $data['fullExchangeName'] ?? $data['exchange'] ?? null,
                    'sector' => $data['sector'] ?? null,
                    'industry' => $data['industry'] ?? null,
                    'is_etf' => isset($data['quoteType']) && $data['quoteType'] === 'ETF'
                ];
            }

            return $quotes;
        } catch (\Exception $e) {
            App::getLogger()->error("Failed to get multiple quotes", [
                'symbols' => $symbols,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get historical price data
     */
    public function getHistoricalData(string $symbol, string $period = '1y', string $interval = '1d'): array
    {
        try {
            $cacheKey = "historical_" . strtoupper($symbol) . "_" . $period . "_" . $interval;
            
            // Check cache
            if ($this->isCached($cacheKey)) {
                return $this->cache[$cacheKey]['data'];
            }

            $url = self::BASE_URL . urlencode(strtoupper($symbol)) . 
                   "?period1=0&period2=" . time() . "&interval=" . $interval . "&range=" . $period;
            
            $response = $this->makeRequest($url);

            if (!$response || !isset($response['chart']['result'][0])) {
                return [];
            }

            $data = $response['chart']['result'][0];
            $timestamps = $data['timestamp'] ?? [];
            $quotes = $data['indicators']['quote'][0] ?? [];
            $adjClose = $data['indicators']['adjclose'][0]['adjclose'] ?? [];

            $historicalData = [];
            for ($i = 0; $i < count($timestamps); $i++) {
                $date = date('Y-m-d', $timestamps[$i]);
                
                $historicalData[] = [
                    'date' => $date,
                    'open' => $quotes['open'][$i] ?? null,
                    'high' => $quotes['high'][$i] ?? null,
                    'low' => $quotes['low'][$i] ?? null,
                    'close' => $quotes['close'][$i] ?? null,
                    'volume' => $quotes['volume'][$i] ?? null,
                    'adjusted_close' => $adjClose[$i] ?? null
                ];
            }

            // Cache the result
            $this->setCache($cacheKey, $historicalData);

            return $historicalData;
        } catch (\Exception $e) {
            App::getLogger()->error("Failed to get historical data for $symbol", [
                'period' => $period,
                'interval' => $interval,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Search for stocks
     */
    public function searchStocks(string $query): array
    {
        try {
            $url = self::SEARCH_URL . "?q=" . urlencode($query);
            $response = $this->makeRequest($url);

            if (!$response || !isset($response['quotes'])) {
                return [];
            }

            $results = [];
            foreach ($response['quotes'] as $quote) {
                if (isset($quote['symbol']) && isset($quote['shortname'])) {
                    $results[] = [
                        'symbol' => $quote['symbol'],
                        'name' => $quote['longname'] ?? $quote['shortname'],
                        'exchange' => $quote['exchDisp'] ?? null,
                        'type' => $quote['quoteType'] ?? null,
                        'is_etf' => isset($quote['quoteType']) && $quote['quoteType'] === 'ETF'
                    ];
                }
            }

            return array_slice($results, 0, 20); // Limit to 20 results
        } catch (\Exception $e) {
            App::getLogger()->error("Failed to search stocks", [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get dividend data
     */
    public function getDividends(string $symbol): array
    {
        try {
            $url = self::DIVIDEND_URL . urlencode(strtoupper($symbol)) . "?period1=0&period2=" . time();
            $response = $this->makeRequest($url);

            if (!$response || !isset($response['chart']['result'][0]['events']['dividends'])) {
                return [];
            }

            $dividends = [];
            foreach ($response['chart']['result'][0]['events']['dividends'] as $timestamp => $dividend) {
                $dividends[] = [
                    'ex_date' => date('Y-m-d', $timestamp),
                    'amount' => $dividend['amount'],
                    'payment_date' => null, // Yahoo doesn't provide payment date
                    'record_date' => null,
                    'frequency' => 'QUARTERLY', // Default assumption
                    'is_special' => false
                ];
            }

            // Sort by date descending
            usort($dividends, function($a, $b) {
                return strtotime($b['ex_date']) - strtotime($a['ex_date']);
            });

            return $dividends;
        } catch (\Exception $e) {
            App::getLogger()->error("Failed to get dividends for $symbol", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Update stock prices for portfolio holdings
     */
    public function updatePortfolioStockPrices(): int
    {
        try {
            $stocks = $this->stockModel->getPortfolioStocks();
            if (empty($stocks)) {
                return 0;
            }

            $symbols = array_column($stocks, 'symbol');
            $quotes = $this->getMultipleQuotes($symbols);
            
            $updated = 0;
            foreach ($stocks as $stock) {
                if (isset($quotes[$stock['symbol']])) {
                    $quote = $quotes[$stock['symbol']];
                    
                    // Update stock info if needed
                    $this->stockModel->update($stock['id'], [
                        'name' => $quote['name'],
                        'exchange' => $quote['exchange'],
                        'sector' => $quote['sector'],
                        'industry' => $quote['industry'],
                        'market_cap' => $quote['market_cap'],
                        'is_etf' => $quote['is_etf']
                    ]);

                    // Update current price
                    if ($this->stockModel->updateCurrentPrice($stock['id'], $quote)) {
                        $updated++;
                    }
                }
            }

            App::getLogger()->info("Updated portfolio stock prices", [
                'total_stocks' => count($stocks),
                'updated' => $updated
            ]);

            return $updated;
        } catch (\Exception $e) {
            App::getLogger()->error("Failed to update portfolio stock prices", ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Update historical data for a stock
     */
    public function updateHistoricalData(string $symbol, string $period = '1y'): bool
    {
        try {
            $stock = $this->stockModel->getBySymbol($symbol);
            if (!$stock) {
                return false;
            }

            $historicalData = $this->getHistoricalData($symbol, $period);
            if (empty($historicalData)) {
                return false;
            }

            $added = 0;
            foreach ($historicalData as $priceData) {
                if ($this->stockModel->addHistoricalPrice($stock['id'], $priceData)) {
                    $added++;
                }
            }

            App::getLogger()->info("Updated historical data for $symbol", [
                'period' => $period,
                'records_added' => $added
            ]);

            return $added > 0;
        } catch (\Exception $e) {
            App::getLogger()->error("Failed to update historical data for $symbol", [
                'period' => $period,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get or create stock by symbol
     */
    public function getOrCreateStock(string $symbol): ?array
    {
        try {
            // Check if stock exists
            $stock = $this->stockModel->getBySymbol($symbol);
            if ($stock) {
                return $stock;
            }

            // Get quote from Yahoo Finance
            $quote = $this->getQuote($symbol);
            if (!$quote) {
                return null;
            }

            // Create stock
            $stockId = $this->stockModel->createOrUpdate($quote);
            
            // Update current price
            $this->stockModel->updateCurrentPrice($stockId, $quote);

            return $this->stockModel->getById($stockId);
        } catch (\Exception $e) {
            App::getLogger()->error("Failed to get or create stock $symbol", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Make HTTP request with error handling
     */
    private function makeRequest(string $url): ?array
    {
        try {
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Cache-Control: no-cache'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("cURL error: " . $error);
            }

            if ($httpCode !== 200) {
                throw new \Exception("HTTP error: " . $httpCode);
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON decode error: " . json_last_error_msg());
            }

            // Log API call
            $this->logApiCall($url);

            return $data;
        } catch (\Exception $e) {
            App::getLogger()->error("API request failed", [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if data is cached and not expired
     */
    private function isCached(string $key): bool
    {
        return isset($this->cache[$key]) && 
               (time() - $this->cache[$key]['timestamp']) < $this->cacheExpiry;
    }

    /**
     * Set cache data
     */
    private function setCache(string $key, array $data): void
    {
        $this->cache[$key] = [
            'data' => $data,
            'timestamp' => time()
        ];
    }

    /**
     * Log API call for rate limiting monitoring
     */
    private function logApiCall(string $url): void
    {
        // You can implement API call logging here for rate limiting
        // This is useful for monitoring usage across different API providers
    }

    /**
     * Get market status
     */
    public function getMarketStatus(): array
    {
        try {
            // Use SPY as a proxy for market status
            $quote = $this->getQuote('SPY');
            if (!$quote) {
                return ['status' => 'UNKNOWN', 'next_open' => null, 'next_close' => null];
            }

            $isOpen = App::isMarketOpen();
            
            return [
                'status' => $isOpen ? 'OPEN' : 'CLOSED',
                'is_trading' => $isOpen,
                'next_open' => $this->getNextMarketOpen(),
                'next_close' => $this->getNextMarketClose()
            ];
        } catch (\Exception $e) {
            App::getLogger()->error("Failed to get market status", ['error' => $e->getMessage()]);
            return ['status' => 'UNKNOWN', 'next_open' => null, 'next_close' => null];
        }
    }

    /**
     * Get next market open time
     */
    private function getNextMarketOpen(): string
    {
        $now = new \DateTime();
        $marketOpen = clone $now;
        $marketOpen->setTime(9, 30); // 9:30 AM ET

        if ($now > $marketOpen || $now->format('w') == 0 || $now->format('w') == 6) {
            // If past market open today or weekend, move to next weekday
            do {
                $marketOpen->add(new \DateInterval('P1D'));
            } while ($marketOpen->format('w') == 0 || $marketOpen->format('w') == 6);
            
            $marketOpen->setTime(9, 30);
        }

        return $marketOpen->format('Y-m-d H:i:s');
    }

    /**
     * Get next market close time
     */
    private function getNextMarketClose(): string
    {
        $now = new \DateTime();
        $marketClose = clone $now;
        $marketClose->setTime(16, 0); // 4:00 PM ET

        if ($now > $marketClose || $now->format('w') == 0 || $now->format('w') == 6) {
            // If past market close today or weekend, move to next weekday
            do {
                $marketClose->add(new \DateInterval('P1D'));
            } while ($marketClose->format('w') == 0 || $marketClose->format('w') == 6);
            
            $marketClose->setTime(16, 0);
        }

        return $marketClose->format('Y-m-d H:i:s');
    }
}