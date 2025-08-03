<?php

namespace PortfolioTracker\Config;

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\FileHandler;
use Monolog\Handler\StreamHandler;

class App
{
    private static array $config = [];
    private static ?Logger $logger = null;

    /**
     * Initialize application configuration
     */
    public static function init(): void
    {
        // Load environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        // Set timezone
        date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/New_York');

        // Configure application settings
        self::$config = [
            'name' => $_ENV['APP_NAME'] ?? 'Portfolio Tracker',
            'env' => $_ENV['APP_ENV'] ?? 'development',
            'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/New_York',
            'log_level' => $_ENV['LOG_LEVEL'] ?? 'INFO',
            'log_file' => $_ENV['LOG_FILE'] ?? 'logs/app.log',
            'cache_enabled' => filter_var($_ENV['CACHE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'cache_duration' => (int)($_ENV['CACHE_DURATION'] ?? 900),
            'session_lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 3600),
            'admin_username' => $_ENV['ADMIN_USERNAME'] ?? 'admin',
            'admin_password' => $_ENV['ADMIN_PASSWORD'] ?? 'changeme123',
            'market' => [
                'open_hour' => (int)($_ENV['MARKET_OPEN_HOUR'] ?? 9),
                'open_minute' => (int)($_ENV['MARKET_OPEN_MINUTE'] ?? 30),
                'close_hour' => (int)($_ENV['MARKET_CLOSE_HOUR'] ?? 16),
                'close_minute' => (int)($_ENV['MARKET_CLOSE_MINUTE'] ?? 0),
            ],
            'api_keys' => [
                'alpha_vantage' => $_ENV['ALPHA_VANTAGE_API_KEY'] ?? '',
                'polygon' => $_ENV['POLYGON_API_KEY'] ?? '',
                'finnhub' => $_ENV['FINNHUB_API_KEY'] ?? '',
            ],
            'price_update' => [
                'interval' => (int)($_ENV['PRICE_UPDATE_INTERVAL'] ?? 15),
                'enabled' => filter_var($_ENV['ENABLE_AUTO_UPDATES'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]
        ];

        // Initialize logger
        self::initLogger();

        // Start session
        self::startSession();
    }

    /**
     * Initialize logger
     */
    private static function initLogger(): void
    {
        self::$logger = new Logger('portfolio_tracker');

        // Create logs directory if it doesn't exist
        $logDir = dirname(self::$config['log_file']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Add file handler
        $fileHandler = new FileHandler(self::$config['log_file'], self::$config['log_level']);
        self::$logger->pushHandler($fileHandler);

        // Add console handler for development
        if (self::$config['debug'] && php_sapi_name() === 'cli') {
            $streamHandler = new StreamHandler('php://stdout', self::$config['log_level']);
            self::$logger->pushHandler($streamHandler);
        }
    }

    /**
     * Start session
     */
    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.gc_maxlifetime', self::$config['session_lifetime']);
            session_start();
        }
    }

    /**
     * Get configuration value
     */
    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Get all configuration
     */
    public static function getConfig(): array
    {
        return self::$config;
    }

    /**
     * Get logger instance
     */
    public static function getLogger(): Logger
    {
        if (self::$logger === null) {
            self::initLogger();
        }
        return self::$logger;
    }

    /**
     * Check if market is open
     */
    public static function isMarketOpen(): bool
    {
        $now = new \DateTime();
        $dayOfWeek = (int)$now->format('w'); // 0 = Sunday, 6 = Saturday

        // Check if it's a weekend
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            return false;
        }

        // Check market hours
        $marketOpen = clone $now;
        $marketOpen->setTime(self::$config['market']['open_hour'], self::$config['market']['open_minute']);

        $marketClose = clone $now;
        $marketClose->setTime(self::$config['market']['close_hour'], self::$config['market']['close_minute']);

        return $now >= $marketOpen && $now <= $marketClose;
    }

    /**
     * Get market status
     */
    public static function getMarketStatus(): string
    {
        return self::isMarketOpen() ? 'OPEN' : 'CLOSED';
    }

    /**
     * Check if debug mode is enabled
     */
    public static function isDebug(): bool
    {
        return self::$config['debug'];
    }

    /**
     * Get environment
     */
    public static function getEnv(): string
    {
        return self::$config['env'];
    }

    /**
     * Format currency
     */
    public static function formatCurrency(float $amount, int $decimals = 2): string
    {
        return '$' . number_format($amount, $decimals);
    }

    /**
     * Format percentage
     */
    public static function formatPercentage(float $percentage, int $decimals = 2): string
    {
        return number_format($percentage, $decimals) . '%';
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    /**
     * Get current URL
     */
    public static function getCurrentUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        return $protocol . '://' . $host . $uri;
    }
}