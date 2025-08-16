<?php
// test-connection-ui.php - Test connection with nice UI
require_once 'config.php';

// Security check
$allowTest = false;
if (env('APP_ENV') === 'development' || env('APP_DEBUG') === true) {
    $allowTest = true;
}

$secretKey = env('TEST_SECRET_KEY', 'test123');
if (isset($_GET['key']) && $_GET['key'] === $secretKey) {
    $allowTest = true;
}

if (!$allowTest) {
    http_response_code(403);
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Access Denied</title>
        <style>
            body { font-family: Arial; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5; }
            .error { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
            .error h1 { color: #e74c3c; }
            .error p { color: #666; }
            code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h1>🔒 Access Denied</h1>
            <p>This endpoint is only available in development mode.</p>
            <p>Or access with: <code>?key=your-secret-key</code></p>
        </div>
    </body>
    </html>
    ');
}

// Run tests
$tests = [];

// Test Environment
$tests[] = [
    'name' => 'Environment File',
    'icon' => '📁',
    'status' => file_exists('.env'),
    'message' => file_exists('.env') ? '.env file found' : '.env file not found',
    'details' => file_exists('.env') ? 'Configuration loaded' : 'Please create .env file'
];

// Test Database
try {
    $pdo = getDB();
    $tests[] = [
        'name' => 'Database Connection',
        'icon' => '🗄️',
        'status' => true,
        'message' => 'Connected successfully',
        'details' => sprintf('Host: %s | Database: %s', env('DB_HOST'), env('DB_NAME'))
    ];
    
    // Count tables
    $stmt = $pdo->query("SHOW TABLES");
    $tableCount = $stmt->rowCount();
    $tests[] = [
        'name' => 'Database Tables',
        'icon' => '📊',
        'status' => $tableCount > 0,
        'message' => $tableCount . ' tables found',
        'details' => $tableCount > 0 ? 'Database is properly initialized' : 'Please import database.sql'
    ];
    
} catch (Exception $e) {
    $tests[] = [
        'name' => 'Database Connection',
        'icon' => '🗄️',
        'status' => false,
        'message' => 'Connection failed',
        'details' => env('APP_DEBUG') ? $e->getMessage() : 'Check your .env configuration'
    ];
}

// Test PHP Version
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '7.2.0', '>=');
$tests[] = [
    'name' => 'PHP Version',
    'icon' => '🐘',
    'status' => $phpOk,
    'message' => 'PHP ' . $phpVersion,
    'details' => $phpOk ? 'Version compatible' : 'Requires PHP 7.2.0 or higher'
];

// Test Extensions
$required = ['pdo', 'pdo_mysql', 'session', 'json', 'fileinfo'];
$missing = array_filter($required, function($ext) { return !extension_loaded($ext); });
$tests[] = [
    'name' => 'PHP Extensions',
    'icon' => '🧩',
    'status' => empty($missing),
    'message' => empty($missing) ? 'All extensions loaded' : count($missing) . ' missing',
    'details' => empty($missing) ? implode(', ', $required) : 'Missing: ' . implode(', ', $missing)
];

// Test Upload Directory
$uploadPath = env('UPLOAD_PATH', 'uploads/tasks/');
$uploadOk = is_dir($uploadPath) && is_writable($uploadPath);
$tests[] = [
    'name' => 'Upload Directory',
    'icon' => '📤',
    'status' => $uploadOk,
    'message' => $uploadOk ? 'Writable' : 'Not writable',
    'details' => $uploadPath
];

// Test Session
$sessionOk = session_status() === PHP_SESSION_ACTIVE || session_status() === PHP_SESSION_NONE;
$tests[] = [
    'name' => 'Session',
    'icon' => '🔐',
    'status' => $sessionOk,
    'message' => $sessionOk ? 'Available' : 'Not available',
    'details' => 'Session handling is ' . ($sessionOk ? 'configured' : 'not configured')
];

// Calculate overall status
$allPassed = !in_array(false, array_column($tests, 'status'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        .status-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .overall-status {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            font-size: 1.3em;
            font-weight: 600;
        }
        .overall-status.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .overall-status.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        .test-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            background: #f9fafb;
            transition: all 0.3s ease;
        }
        .test-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .test-icon {
            font-size: 2em;
            margin-right: 20px;
        }
        .test-content {
            flex: 1;
        }
        .test-name {
            font-weight: 600;
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        .test-message {
            color: #666;
            margin-bottom: 3px;
        }
        .test-details {
            font-size: 0.85em;
            color: #999;
        }
        .test-status {
            width: 60px;
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            border-radius: 50%;
            font-size: 1.5em;
        }
        .status-badge.success {
            background: #10b981;
            color: white;
        }
        .status-badge.error {
            background: #ef4444;
            color: white;
        }
        .refresh-btn {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.2);
        }
        .info-box {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            color: #92400e;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .info-box strong {
            display: block;
            margin-bottom: 5px;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 System Health Check</h1>
            <p><?php echo env('APP_NAME', 'Task Management System'); ?></p>
        </div>
        
        <div class="status-card">
            <div class="overall-status <?php echo $allPassed ? 'success' : 'error'; ?>">
                <?php if ($allPassed): ?>
                    ✅ All Systems Operational
                <?php else: ?>
                    ⚠️ Some Issues Detected
                <?php endif; ?>
            </div>
            
            <?php foreach ($tests as $test): ?>
            <div class="test-item">
                <div class="test-icon"><?php echo $test['icon']; ?></div>
                <div class="test-content">
                    <div class="test-name"><?php echo $test['name']; ?></div>
                    <div class="test-message"><?php echo $test['message']; ?></div>
                    <div class="test-details"><?php echo $test['details']; ?></div>
                </div>
                <div class="test-status">
                    <span class="status-badge <?php echo $test['status'] ? 'success' : 'error'; ?>">
                        <?php echo $test['status'] ? '✓' : '✗'; ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div style="text-align: center;">
                <a href="?key=<?php echo $_GET['key'] ?? ''; ?>" class="refresh-btn">
                    🔄 Refresh Check
                </a>
            </div>
            
            <?php if (env('APP_ENV') === 'development'): ?>
            <div class="info-box">
                <strong>⚠️ Development Mode</strong>
                <p>This page is publicly accessible because <code>APP_ENV=development</code></p>
                <p>In production, set <code>APP_ENV=production</code> and use <code>?key=your-secret</code></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>