<?php
// config.php - Updated configuration with environment variables

// Load environment variables
require_once __DIR__ . '/env.php';

try {
    Env::load(__DIR__ . '/.env');
} catch (Exception $e) {
    // Fallback jika .env tidak ada (untuk development)
    error_log("Warning: " . $e->getMessage());
}

// Database configuration from environment
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'task_audiensi'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_PORT', env('DB_PORT', 3306));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Application settings from environment
define('APP_NAME', env('APP_NAME', 'Task Management'));
define('APP_URL', env('APP_URL', 'http://localhost/'));
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', false));
define('TIMEZONE', env('TIMEZONE', 'Asia/Jakarta'));

// Points configuration from environment
define('POINTS_ON_TIME', (int)env('POINTS_ON_TIME', 10));
define('POINTS_EARLY_BONUS', (int)env('POINTS_EARLY_BONUS', 5));
define('POINTS_LATE_PENALTY', (int)env('POINTS_LATE_PENALTY', -5));
define('POINTS_DROPPED_PENALTY', (int)env('POINTS_DROPPED_PENALTY', -10));

// Upload configuration
define('MAX_FILE_SIZE', (int)env('MAX_FILE_SIZE', 5242880)); // 5MB
define('UPLOAD_PATH', env('UPLOAD_PATH', 'uploads/tasks/'));

// Session configuration
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 120));
define('SESSION_SECURE', env('SESSION_SECURE', false));
define('SESSION_HTTPONLY', env('SESSION_HTTPONLY', true));

// Set timezone
date_default_timezone_set(TIMEZONE);

// Configure error reporting based on environment
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', SESSION_LIFETIME * 60);
    ini_set('session.cookie_httponly', SESSION_HTTPONLY ? 1 : 0);
    ini_set('session.cookie_secure', SESSION_SECURE ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Database connection with error handling
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Log error securely
            error_log("Database connection failed: " . $e->getMessage());
            
            // Show user-friendly error
            if (APP_DEBUG) {
                die("Connection failed: " . $e->getMessage());
            } else {
                die("Sorry, we're experiencing technical difficulties. Please try again later.");
            }
        }
    }
    
    return $pdo;
}

// ... rest of your existing functions ...