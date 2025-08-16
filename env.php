<?php
// env.php - Environment variable loader

class Env {
    private static $variables = [];
    private static $loaded = false;
    
    /**
     * Load environment variables from .env file
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($path === null) {
            $path = self::findEnvFile();
        }
        
        if (!file_exists($path)) {
            throw new Exception(".env file not found at: {$path}");
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                $value = self::stripQuotes($value);
                
                // Parse boolean values
                $value = self::parseBoolean($value);
                
                // Set environment variable
                self::$variables[$key] = $value;
                
                // Also set in $_ENV and putenv for compatibility
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Get environment variable value
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        // Check multiple sources
        if (isset(self::$variables[$key])) {
            return self::$variables[$key];
        }
        
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Check if environment variable exists
     */
    public static function has($key) {
        return self::get($key) !== null;
    }
    
    /**
     * Find .env file location
     */
    private static function findEnvFile() {
        $locations = [
            __DIR__ . '/.env',
            dirname(__DIR__) . '/.env',
            $_SERVER['DOCUMENT_ROOT'] . '/.env',
        ];
        
        foreach ($locations as $location) {
            if (file_exists($location)) {
                return $location;
            }
        }
        
        return __DIR__ . '/.env';
    }
    
    /**
     * Strip quotes from value
     */
    private static function stripQuotes($value) {
        if (strlen($value) > 1) {
            $firstChar = $value[0];
            $lastChar = $value[strlen($value) - 1];
            
            if (($firstChar === '"' && $lastChar === '"') || 
                ($firstChar === "'" && $lastChar === "'")) {
                return substr($value, 1, -1);
            }
        }
        
        return $value;
    }
    
    /**
     * Parse boolean values
     */
    private static function parseBoolean($value) {
        $lower = strtolower($value);
        
        if ($lower === 'true' || $lower === '(true)') {
            return true;
        }
        
        if ($lower === 'false' || $lower === '(false)') {
            return false;
        }
        
        if ($lower === 'null' || $lower === '(null)') {
            return null;
        }
        
        if ($lower === 'empty' || $lower === '(empty)') {
            return '';
        }
        
        return $value;
    }
    
    /**
     * Reload environment variables
     */
    public static function reload() {
        self::$loaded = false;
        self::$variables = [];
        self::load();
    }
}

// Helper function for easy access
if (!function_exists('env')) {
    function env($key, $default = null) {
        return Env::get($key, $default);
    }
}