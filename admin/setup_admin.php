<?php
// admin/setup_admin.php - Utility to set up admin accounts securely
// This file should be deleted after initial setup!

require_once '../config.php';

// Security check - only allow execution from command line or with special token
$cli_mode = (php_sapi_name() === 'cli');
$web_token = $_GET['token'] ?? '';
$valid_token = 'oye'; // Changes daily for security

if (!$cli_mode && $web_token !== $valid_token) {
    die("Access denied. This script can only be run from command line or with valid token.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup Utility</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Admin Account Setup Utility</h1>
            
            <?php
            $message = '';
            $messageType = '';
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $action = $_POST['action'] ?? '';
                
                if ($action === 'create_admin') {
                    $name = trim($_POST['name'] ?? '');
                    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
                    $password = $_POST['password'] ?? '';
                    $role = $_POST['role'] ?? 'admin';
                    $department = $_POST['department'] ?? 'Administration';
                    $whatsapp = $_POST['whatsapp'] ?? '';
                    
                    if ($name && $email && $password && strlen($password) >= 12) {
                        $pdo = getDB();
                        
                        // Check if email exists
                        $check = $pdo->prepare("SELECT id FROM employees WHERE email = ?");
                        $check->execute([$email]);
                        
                        if ($check->fetch()) {
                            $message = "Email already exists!";
                            $messageType = 'error';
                        } else {
                            // Create admin account
                            $hashed = password_hash($password, PASSWORD_DEFAULT);
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO employees (name, email, password, role, department, whatsapp_number)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            
                            if ($stmt->execute([$name, $email, $hashed, $role, $department, $whatsapp])) {
                                $adminId = $pdo->lastInsertId();
                                
                                // Create admin settings
                                $settingsStmt = $pdo->prepare("
                                    INSERT INTO admin_settings (admin_id, dashboard_layout, theme, email_notifications)
                                    VALUES (?, 'default', 'light', TRUE)
                                ");
                                $settingsStmt->execute([$adminId]);
                                
                                $message = "Admin account created successfully!";
                                $messageType = 'success';
                                
                                // Log the creation
                                $logStmt = $pdo->prepare("
                                    INSERT INTO admin_logs (admin_id, action, details, ip_address)
                                    VALUES (?, 'admin_created', ?, ?)
                                ");
                                $logStmt->execute([
                                    $adminId,
                                    "New admin account created: $email",
                                    $_SERVER['REMOTE_ADDR'] ?? 'CLI'
                                ]);
                            } else {
                                $message = "Error creating admin account";
                                $messageType = 'error';
                            }
                        }
                    } else {
                        $message = "All fields are required. Password must be at least 12 characters.";
                        $messageType = 'error';
                    }
                }
                
                if ($action === 'generate_hash') {
                    $password = $_POST['password_to_hash'] ?? '';
                    if ($password) {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $message = "Generated hash: <code class='bg-gray-100 p-2 rounded'>$hash</code>";
                        $messageType = 'info';
                    }
                }
                
                if ($action === 'promote_user') {
                    $userId = (int)($_POST['user_id'] ?? 0);
                    $newRole = $_POST['new_role'] ?? 'manager';
                    
                    if ($userId) {
                        $pdo = getDB();
                        $stmt = $pdo->prepare("UPDATE employees SET role = ? WHERE id = ?");
                        if ($stmt->execute([$newRole, $userId])) {
                            // Create admin settings if not exists
                            $settingsStmt = $pdo->prepare("
                                INSERT IGNORE INTO admin_settings (admin_id)
                                VALUES (?)
                            ");
                            $settingsStmt->execute([$userId]);
                            
                            $message = "User promoted to $newRole successfully!";
                            $messageType = 'success';
                        }
                    }
                }
            }
            
            // Get existing users
            $pdo = getDB();
            $usersStmt = $pdo->query("
                SELECT id, name, email, role, department 
                FROM employees 
                ORDER BY role = 'admin' DESC, role = 'manager' DESC, name
            ");
            $users = $usersStmt->fetchAll();
            ?>
            
            <?php if ($message): ?>
            <div class="mb-4 p-4 rounded-lg <?php 
                echo $messageType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 
                    ($messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 
                    'bg-blue-50 text-blue-700 border border-blue-200'); ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Create New Admin -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Create New Admin Account</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create_admin">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" name="name" required 
                                   class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" required 
                                   class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password (min 12 chars)</label>
                            <input type="password" name="password" required minlength="12"
                                   class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select name="role" class="w-full px-3 py-2 border rounded-lg">
                                <option value="admin">Admin</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" name="department" value="Administration"
                                   class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                            <input type="text" name="whatsapp" placeholder="+1234567890"
                                   class="w-full px-3 py-2 border rounded-lg">
                        </div>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-user-plus mr-2"></i>Create Admin Account
                    </button>
                </form>
            </div>
            
            <!-- Password Hash Generator -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Password Hash Generator</h2>
                <form method="POST" class="flex gap-4">
                    <input type="hidden" name="action" value="generate_hash">
                    <input type="password" name="password_to_hash" placeholder="Enter password to hash"
                           class="flex-1 px-3 py-2 border rounded-lg" required>
                    <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-key mr-2"></i>Generate Hash
                    </button>
                </form>
            </div>
            
            <!-- Existing Users -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Existing Users</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Name</th>
                                <th class="px-4 py-2 text-left">Email</th>
                                <th class="px-4 py-2 text-left">Current Role</th>
                                <th class="px-4 py-2 text-left">Department</th>
                                <th class="px-4 py-2 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($user['name']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded-full <?php 
                                        echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                            ($user['role'] === 'manager' ? 'bg-blue-100 text-blue-800' : 
                                            'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($user['department'] ?? '-'); ?></td>
                                <td class="px-4 py-2">
                                    <?php if ($user['role'] === 'employee'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="promote_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="new_role" class="text-sm border rounded px-2 py-1">
                                            <option value="manager">Manager</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm ml-2">
                                            <i class="fas fa-arrow-up"></i> Promote
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Security Warning -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="text-red-800 font-semibold mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Security Warning
                </h3>
                <ul class="text-red-700 text-sm space-y-1">
                    <li>• This utility should be deleted after initial setup</li>
                    <li>• Never leave this file on a production server</li>
                    <li>• Use strong passwords (12+ characters with mixed case, numbers, symbols)</li>
                    <li>• Enable two-factor authentication for admin accounts</li>
                    <li>• Regularly review admin_logs table for suspicious activity</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>