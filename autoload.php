<?php

/**
 * Simple PSR-4 Autoloader for Portfolio Tracker
 * Use this as a fallback if Composer is not available
 */

// Define the base directory for our classes
$baseDir = __DIR__ . '/src/';

// Map of namespace prefixes to base directories
$prefixes = [
    'PortfolioTracker\\' => $baseDir,
];

// Register the autoloader function
spl_autoload_register(function ($class) use ($prefixes) {
    // Work backwards through the namespace to find a match
    $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';
    
    foreach ($prefixes as $prefix => $baseDir) {
        // Does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        
        // Get the relative class name
        $relativeClass = substr($class, $len);
        
        // Replace the namespace prefix with the base directory
        $file = $baseDir . strtr($relativeClass, '\\', DIRECTORY_SEPARATOR) . '.php';
        
        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    
    return false;
});

// Load required dependencies manually if needed
if (!class_exists('Dotenv\Dotenv')) {
    // Simple environment loader fallback
    function loadEnvFile($file) {
        if (!file_exists($file)) {
            return;
        }
        
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // Skip comments
            }
            
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^([\'"]).*(\\1)$/', $value, $matches)) {
                $value = substr($value, 1, -1);
            }
            
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
            }
        }
    }
    
    // Load .env file
    loadEnvFile(__DIR__ . '/.env');
}

// Basic logger fallback
if (!class_exists('Monolog\Logger')) {
    class SimpleLogger {
        public function error($message, $context = []) {
            error_log("ERROR: $message " . json_encode($context));
        }
        
        public function info($message, $context = []) {
            error_log("INFO: $message " . json_encode($context));
        }
        
        public function debug($message, $context = []) {
            error_log("DEBUG: $message " . json_encode($context));
        }
        
        public function pushHandler($handler) {
            // Dummy method
        }
    }
}