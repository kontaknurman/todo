<?php
// admin/login.php - Fixed version with error handling
session_start();
require_once '../config.php';

// Error reporting disabled for production
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Admin session prefix
define('ADMIN_SESSION_PREFIX', 'admin_');

// Check if already logged in
if (isset($_SESSION[ADMIN_SESSION_PREFIX . 'id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$login_attempts_exceeded = false;

// Function to check if table exists
function tableExists($pdo, $tableName) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Function to create admin_settings table if it doesn't exist
function createAdminSettingsTable($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_settings (
                id int(11) NOT NULL AUTO_INCREMENT,
                admin_id int(11) NOT NULL UNIQUE,
                dashboard_layout varchar(50) DEFAULT 'default',
                items_per_page int(11) DEFAULT 20,
                email_notifications boolean DEFAULT TRUE,
                whatsapp_notifications boolean DEFAULT FALSE,
                theme varchar(20) DEFAULT 'light',
                language varchar(10) DEFAULT 'en',
                two_factor_enabled boolean DEFAULT FALSE,
                two_factor_secret varchar(255) DEFAULT NULL,
                last_login datetime DEFAULT NULL,
                last_ip varchar(45) DEFAULT NULL,
                login_attempts int(11) DEFAULT 0,
                locked_until datetime DEFAULT NULL,
                api_key varchar(255) DEFAULT NULL,
                permissions JSON DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (id),
                KEY idx_admin (admin_id),
                CONSTRAINT fk_settings_admin FOREIGN KEY (admin_id) REFERENCES employees (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Function to create admin_logs table if it doesn't exist
function createAdminLogsTable($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_logs (
                id int(11) NOT NULL AUTO_INCREMENT,
                admin_id int(11) DEFAULT NULL,
                action varchar(100) NOT NULL,
                target_type varchar(50) DEFAULT NULL,
                target_id int(11) DEFAULT NULL,
                details text DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (id),
                KEY idx_admin (admin_id),
                KEY idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        try {
            $pdo = getDB();
            
            // Create tables if they don't exist
            if (!tableExists($pdo, 'admin_settings')) {
                createAdminSettingsTable($pdo);
            }
            if (!tableExists($pdo, 'admin_logs')) {
                createAdminLogsTable($pdo);
            }
            
            // Simple query first - just check if admin exists
            $stmt = $pdo->prepare("
                SELECT * FROM employees 
                WHERE email = ? AND role IN ('admin', 'manager')
            ");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                // Check password
                $validPassword = false;
                
                if (!empty($admin['password']) && password_verify($password, $admin['password'])) {
                    $validPassword = true;
                }

                
                if ($validPassword) {
                    // Set admin session
                    $_SESSION[ADMIN_SESSION_PREFIX . 'id'] = $admin['id'];
                    $_SESSION[ADMIN_SESSION_PREFIX . 'name'] = $admin['name'];
                    $_SESSION[ADMIN_SESSION_PREFIX . 'role'] = $admin['role'];
                    $_SESSION[ADMIN_SESSION_PREFIX . 'email'] = $admin['email'];
                    
                    // Try to update admin settings (but don't fail if table doesn't exist)
                    try {
                        $updateSettings = $pdo->prepare("
                            INSERT INTO admin_settings (admin_id, last_login, last_ip, login_attempts) 
                            VALUES (?, NOW(), ?, 0)
                            ON DUPLICATE KEY UPDATE 
                                last_login = NOW(), 
                                last_ip = ?, 
                                login_attempts = 0
                        ");
                        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                        $updateSettings->execute([$admin['id'], $ip, $ip]);
                    } catch (Exception $e) {
                        // Settings table doesn't exist, but login is still valid
                    }
                    
                    // Try to log the login (but don't fail if table doesn't exist)
                    try {
                        $logStmt = $pdo->prepare("
                            INSERT INTO admin_logs (admin_id, action, details, ip_address)
                            VALUES (?, 'admin_login', 'Successful admin login', ?)
                        ");
                        $logStmt->execute([
                            $admin['id'],
                            $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                        ]);
                    } catch (Exception $e) {
                        // Logs table doesn't exist, but login is still valid
                    }
                    
                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid password';
                }
            } else {
                $error = 'Invalid credentials or insufficient privileges. Only admins and managers can access this area.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $error = 'System error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please enter valid email and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-4">
                <i class="fas fa-user-shield text-red-600 text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Admin Portal</h1>
            <p class="text-gray-600 mt-2">Restricted Access - Authorized Personnel Only</p>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope mr-1"></i> Admin Email
                </label>
                <input type="email" id="email" name="email" required autofocus
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition"
                       placeholder="admin@company.com">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-1"></i> Password
                </label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition"
                       placeholder="Enter admin password">
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-red-600 to-red-700 text-white font-bold py-3 px-4 rounded-lg hover:from-red-700 hover:to-red-800 transform hover:scale-105 transition duration-200">
                <i class="fas fa-sign-in-alt mr-2"></i> Secure Login
            </button>
        </form>
        
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-between text-sm">
                <a href="../login.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-1"></i> Employee Login
                </a>
                <span class="text-gray-500">
                    <i class="fas fa-shield-alt mr-1"></i> Secured Area
                </span>
            </div>
        </div>
    </div>
</body>
</html>