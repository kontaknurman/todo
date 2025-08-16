<?php
// admin/login.php - Fixed admin login without CSRF issues
session_start();
require_once '../config.php';

// Admin session prefix
define('ADMIN_SESSION_PREFIX', 'admin_');

// Check if already logged in
if (isset($_SESSION[ADMIN_SESSION_PREFIX . 'id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $pdo = getDB();
        
        // Check for admin or manager
        $stmt = $pdo->prepare("
            SELECT * FROM employees 
            WHERE email = ? AND role IN ('admin', 'manager')
        ");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            // For testing/demo - accept both hashed and plain password
            $validPassword = false;
            
            // First try password_verify for hashed passwords
            if (!empty($admin['password']) && password_verify($password, $admin['password'])) {
                $validPassword = true;
            }
            // Fallback for demo accounts
            elseif ($password === 'admin123' && in_array($email, ['admin@company.com', 'manager@company.com'])) {
                // Update to proper hash for next time
                $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashedPassword, $admin['id']]);
                $validPassword = true;
            }
            
            if ($validPassword) {
                // Set admin session
                $_SESSION[ADMIN_SESSION_PREFIX . 'id'] = $admin['id'];
                $_SESSION[ADMIN_SESSION_PREFIX . 'name'] = $admin['name'];
                $_SESSION[ADMIN_SESSION_PREFIX . 'role'] = $admin['role'];
                $_SESSION[ADMIN_SESSION_PREFIX . 'email'] = $admin['email'];
                
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Invalid credentials or insufficient privileges. Only admins and managers can access this area.';
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
        
        <div class="mt-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
            <p class="text-sm font-semibold text-yellow-800 mb-2">Demo Admin Accounts:</p>
            <div class="text-xs text-yellow-700 space-y-1">
                <div class="bg-white p-2 rounded">
                    <strong>Super Admin:</strong><br>
                    Email: admin@company.com<br>
                    Password: admin123
                </div>
                <div class="bg-white p-2 rounded">
                    <strong>Manager:</strong><br>
                    Email: manager@company.com<br>
                    Password: admin123
                </div>
            </div>
        </div>
    </div>
</body>
</html>
