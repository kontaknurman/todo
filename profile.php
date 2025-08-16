<?php
// profile.php - Employee profile with WhatsApp
require_once 'config.php';
requireLogin();

$employee = getCurrentEmployee();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $whatsapp = trim($_POST['whatsapp_number'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email)) {
        $message = 'Name and email are required';
        $messageType = 'error';
    } else {
        $pdo = getDB();
        
        // Check if email exists for another user
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
        $stmt->execute([$email, $employee['id']]);
        if ($stmt->fetch()) {
            $message = 'Email already exists';
            $messageType = 'error';
        } else {
            // Update profile
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $message = 'Passwords do not match';
                    $messageType = 'error';
                } else {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE employees 
                        SET name = ?, email = ?, whatsapp_number = ?, department = ?, password = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $whatsapp, $department, $hashed, $employee['id']]);
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE employees 
                    SET name = ?, email = ?, whatsapp_number = ?, department = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $whatsapp, $department, $employee['id']]);
            }
            
            if ($messageType !== 'error') {
                $message = 'Profile updated successfully';
                $messageType = 'success';
                $_SESSION['employee_name'] = $name;
                $employee = getCurrentEmployee();
            }
        }
    }
}

$pageTitle = 'Profile';
require 'layout-header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">My Profile</h1>
        
        <?php if ($message): ?>
        <div class="mb-4 p-4 rounded-lg <?php echo $messageType == 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
            <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-1"></i> Full Name
                    </label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($employee['name']); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-1"></i> Email Address
                    </label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($employee['email']); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fab fa-whatsapp mr-1"></i> WhatsApp Number
                    </label>
                    <input type="text" name="whatsapp_number" placeholder="+1234567890"
                           value="<?php echo htmlspecialchars($employee['whatsapp_number'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Include country code (e.g., +1 for US)</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-building mr-1"></i> Department
                    </label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars($employee['department'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
            </div>
            
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-1"></i> New Password
                        </label>
                        <input type="password" name="new_password" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-1"></i> Confirm Password
                        </label>
                        <input type="password" name="confirm_password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                </div>
            </div>
            
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistics</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-purple-600"><?php echo $employee['total_points']; ?></p>
                        <p class="text-sm text-gray-600">Total Points</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-green-600">
                            <?php 
                            $pdo = getDB();
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE employee_id = ? AND status = 'finished'");
                            $stmt->execute([$employee['id']]);
                            echo $stmt->fetchColumn();
                            ?>
                        </p>
                        <p class="text-sm text-gray-600">Tasks Completed</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-blue-600">
                            <?php 
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE employee_id = ? AND status IN ('pending', 'ongoing')");
                            $stmt->execute([$employee['id']]);
                            echo $stmt->fetchColumn();
                            ?>
                        </p>
                        <p class="text-sm text-gray-600">Active Tasks</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">Member Since</p>
                        <p class="text-sm font-semibold"><?php echo date('M Y', strtotime($employee['created_at'])); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-4">
                <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-save mr-2"></i> Save Changes
                </button>
                <a href="index.php" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        </form>
    </div>
</div>

</div>
</body>
</html>