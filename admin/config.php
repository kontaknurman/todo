<?php
// admin/config.php - Simplified admin configuration
require_once '../config.php';

// Admin session prefix
define('ADMIN_SESSION_PREFIX', 'admin_');

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION[ADMIN_SESSION_PREFIX . 'id']) && 
           isset($_SESSION[ADMIN_SESSION_PREFIX . 'role']);
}

// Require admin login
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Check permission based on role
function hasPermission($module, $action) {
    if (!isAdminLoggedIn()) return false;
    
    $role = $_SESSION[ADMIN_SESSION_PREFIX . 'role'];
    
    // Admin has all permissions
    if ($role === 'admin') return true;
    
    // Manager permissions
    if ($role === 'manager') {
        if ($module === 'employees') {
            return in_array($action, ['view', 'edit']);
        }
        if ($module === 'tasks') {
            return in_array($action, ['view', 'create', 'edit']);
        }
        if ($module === 'reports') {
            return $action === 'view';
        }
    }
    
    return false;
}

// Require specific permission
function requirePermission($module, $action) {
    if (!hasPermission($module, $action)) {
        $_SESSION['error'] = 'Access denied. Insufficient permissions.';
        header('Location: dashboard.php');
        exit;
    }
}

// Get current admin data
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) return null;
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$_SESSION[ADMIN_SESSION_PREFIX . 'id']]);
    return $stmt->fetch();
}

// Log admin activity
function logAdminActivity($action, $targetType = null, $targetId = null, $details = null) {
    if (!isAdminLoggedIn()) return;
    
    try {
        $pdo = getDB();
        
        // Check if admin_logs table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'admin_logs'");
        if ($stmt->rowCount() == 0) {
            // Create the table if it doesn't exist
            $pdo->exec("
                CREATE TABLE admin_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    admin_id INT,
                    action VARCHAR(100),
                    target_type VARCHAR(50),
                    target_id INT,
                    details TEXT,
                    ip_address VARCHAR(45),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_admin (admin_id),
                    INDEX idx_created (created_at)
                )
            ");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION[ADMIN_SESSION_PREFIX . 'id'],
            $action,
            $targetType,
            $targetId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        // Silently fail if logging doesn't work
    }
}

// Get role badge HTML
function getRoleBadge($role) {
    $badges = [
        'admin' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Admin</span>',
        'manager' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Manager</span>',
        'employee' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Employee</span>'
    ];
    return $badges[$role] ?? '';
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>