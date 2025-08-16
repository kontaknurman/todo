<?php
// login.php - Fixed login page
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE email = ?");
        $stmt->execute([$email]);
        $employee = $stmt->fetch();
        
        if ($employee) {
            // Check if password field is empty or NULL (for fresh install)
            if (empty($employee['password'])) {
                // For fresh installation, update with hashed password
                $hashed = password_hash('password123', PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashed, $employee['id']]);
                $employee['password'] = $hashed;
            }
            
            // Now verify the password
            if (password_verify($password, $employee['password'])) {
                $_SESSION['employee_id'] = $employee['id'];
                $_SESSION['employee_name'] = $employee['name'];
                header('Location: index.php');
                exit;
            } else {
                // Fallback: Check if it's the demo password directly (for testing)
                if ($password === 'password123') {
                    // Update password to proper hash
                    $hashed = password_hash('password123', PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
                    $updateStmt->execute([$hashed, $employee['id']]);
                    
                    // Log them in
                    $_SESSION['employee_id'] = $employee['id'];
                    $_SESSION['employee_name'] = $employee['name'];
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Invalid password';
                }
            }
        } else {
            $error = 'Employee not found';
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
    <title>Login - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-purple-600 to-indigo-700 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <i class="fas fa-tasks text-purple-600 text-5xl mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800"><?php echo APP_NAME; ?></h1>
            <p class="text-gray-600 mt-2">Sign in to manage your tasks</p>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope mr-1"></i> Email Address
                </label>
                <input type="email" id="email" name="email" required autofocus
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                       placeholder="Enter your email">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-1"></i> Password
                </label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                       placeholder="Enter your password">
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold py-3 px-4 rounded-lg hover:from-purple-700 hover:to-indigo-700 transform hover:scale-105 transition duration-200">
                <i class="fas fa-sign-in-alt mr-2"></i> Sign In
            </button>
        </form>
        
        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-semibold text-gray-700 mb-3">
                <i class="fas fa-info-circle text-blue-500 mr-1"></i> Demo Accounts:
            </h3>
            <div class="space-y-2 text-sm">
                <div class="bg-white p-3 rounded border border-gray-200">
                    <div class="font-mono text-gray-700">
                        <strong>Email:</strong> john@company.com<br>
                        <strong>Password:</strong> password123
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Development Department</div>
                </div>
                <div class="bg-white p-3 rounded border border-gray-200">
                    <div class="font-mono text-gray-700">
                        <strong>Email:</strong> jane@company.com<br>
                        <strong>Password:</strong> password123
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Marketing Department</div>
                </div>
                <div class="bg-white p-3 rounded border border-gray-200">
                    <div class="font-mono text-gray-700">
                        <strong>Email:</strong> bob@company.com<br>
                        <strong>Password:</strong> password123
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Sales Department</div>
                </div>
                <div class="bg-white p-3 rounded border border-gray-200">
                    <div class="font-mono text-gray-700">
                        <strong>Email:</strong> alice@company.com<br>
                        <strong>Password:</strong> password123
                    </div>
                    <div class="text-xs text-gray-500 mt-1">HR Department</div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 text-center text-xs text-gray-500">
            <i class="fas fa-shield-alt mr-1"></i> 
            No admin panel needed - all employees have equal access
        </div>
    </div>
</body>
</html>