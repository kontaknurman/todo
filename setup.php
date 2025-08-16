<?php
// setup.php - Initial setup script

echo "=================================\n";
echo "Task Management System Setup\n";
echo "=================================\n\n";

// Check if .env exists
if (!file_exists('.env')) {
    echo "❌ .env file not found!\n";
    
    if (file_exists('.env.example')) {
        echo "📋 Copying .env.example to .env...\n";
        
        if (copy('.env.example', '.env')) {
            echo "✅ .env file created successfully!\n";
            echo "⚠️  Please edit .env file with your database credentials.\n\n";
        } else {
            echo "❌ Failed to create .env file. Please copy .env.example manually.\n";
            exit(1);
        }
    } else {
        echo "❌ .env.example not found either!\n";
        echo "Please create .env file manually.\n";
        exit(1);
    }
} else {
    echo "✅ .env file found!\n\n";
}

// Load environment variables
require_once 'env.php';

try {
    Env::load('.env');
    echo "✅ Environment variables loaded successfully!\n\n";
} catch (Exception $e) {
    echo "❌ Failed to load environment variables: " . $e->getMessage() . "\n";
    exit(1);
}

// Test database connection
echo "Testing database connection...\n";

try {
    $dsn = sprintf(
        "mysql:host=%s;port=%d;dbname=%s;charset=%s",
        env('DB_HOST'),
        env('DB_PORT', 3306),
        env('DB_NAME'),
        env('DB_CHARSET', 'utf8mb4')
    );
    
    $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'));
    echo "✅ Database connection successful!\n\n";
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "📊 Found " . count($tables) . " tables in database.\n";
    } else {
        echo "⚠️  No tables found in database. Please import database.sql\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nPlease check your database credentials in .env file.\n";
    exit(1);
}

// Check upload directory
echo "\nChecking upload directory...\n";
$uploadPath = env('UPLOAD_PATH', 'uploads/tasks/');

if (!file_exists($uploadPath)) {
    if (mkdir($uploadPath, 0755, true)) {
        echo "✅ Upload directory created: {$uploadPath}\n";
    } else {
        echo "❌ Failed to create upload directory: {$uploadPath}\n";
    }
} else {
    echo "✅ Upload directory exists: {$uploadPath}\n";
}

// Create .htaccess in upload directory
$htaccessFile = $uploadPath . '/.htaccess';
if (!file_exists($htaccessFile)) {
    file_put_contents($htaccessFile, "Deny from all\n");
    echo "✅ .htaccess created in upload directory\n";
}

// Check PHP version
echo "\n📌 PHP Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '7.2.0', '>=')) {
    echo "✅ PHP version is compatible\n";
} else {
    echo "⚠️  PHP 7.2 or higher is recommended\n";
}

// Check required PHP extensions
echo "\nChecking PHP extensions...\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'session', 'json', 'fileinfo'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ {$ext} extension loaded\n";
    } else {
        echo "❌ {$ext} extension missing\n";
    }
}

echo "\n=================================\n";
echo "Setup complete!\n";
echo "=================================\n";
echo "\n⚠️  IMPORTANT SECURITY STEPS:\n";
echo "1. Delete or rename this setup.php file\n";
echo "2. Set proper file permissions (chmod 600 .env)\n";
echo "3. Never commit .env file to version control\n";
echo "4. Use HTTPS in production\n";
echo "\n";