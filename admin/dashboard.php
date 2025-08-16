<?php
// admin/dashboard.php - Admin dashboard
require_once 'config.php';
requireAdminLogin();

$admin = getCurrentAdmin();
$pdo = getDB();

// Get dashboard statistics
$stmt = $pdo->query("SELECT * FROM admin_dashboard_stats");
$stats = $stmt->fetch();

// Get recent activities
$stmt = $pdo->prepare("
    SELECT al.*, e.name as admin_name 
    FROM admin_logs al
    LEFT JOIN employees e ON al.admin_id = e.id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recentActivities = $stmt->fetchAll();

// Get top performers
$stmt = $pdo->query("
    SELECT name, department, total_points, role
    FROM employees
    ORDER BY total_points DESC
    LIMIT 5
");
$topPerformers = $stmt->fetchAll();

logAdminActivity('view_dashboard');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                Welcome, <?php echo htmlspecialchars($admin['name']); ?>
                <?php echo getRoleBadge($admin['role']); ?>
            </h1>
            <p class="text-gray-600 mt-1">Admin Dashboard - System Overview</p>
        </div>
        
        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Employees</p>
                        <p class="text-2xl font-bold"><?php echo $stats['total_employees']; ?></p>
                    </div>
                    <i class="fas fa-users text-blue-500 text-2xl"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Tasks</p>
                        <p class="text-2xl font-bold"><?php echo $stats['total_tasks']; ?></p>
                    </div>
                    <i class="fas fa-tasks text-purple-500 text-2xl"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending_tasks']; ?></p>
                    </div>
                    <i class="fas fa-clock text-yellow-500 text-2xl"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Completed</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $stats['completed_tasks']; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Overdue</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $stats['overdue_tasks']; ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Performers -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-trophy text-yellow-500 mr-2"></i>Top Performers
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <?php foreach ($topPerformers as $index => $performer): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div class="flex items-center">
                                <span class="text-lg font-bold text-gray-600 mr-3">
                                    #<?php echo $index + 1; ?>
                                </span>
                                <div>
                                    <p class="font-medium text-gray-800">
                                        <?php echo htmlspecialchars($performer['name']); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($performer['department']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-purple-600"><?php echo $performer['total_points']; ?> pts</p>
                                <?php echo getRoleBadge($performer['role']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-history text-blue-500 mr-2"></i>Recent Admin Activities
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="text-sm border-l-2 border-gray-200 pl-3 py-1">
                            <p class="text-gray-800">
                                <span class="font-medium"><?php echo htmlspecialchars($activity['admin_name']); ?></span>
                                - <?php echo htmlspecialchars($activity['action']); ?>
                            </p>
                            <?php if ($activity['details']): ?>
                            <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($activity['details']); ?></p>
                            <?php endif; ?>
                            <p class="text-gray-400 text-xs">
                                <?php echo date('M d, H:i', strtotime($activity['created_at'])); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="mt-6 bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="employees.php" class="bg-blue-50 hover:bg-blue-100 rounded-lg p-4 text-center transition">
                    <i class="fas fa-user-plus text-blue-600 text-2xl mb-2"></i>
                    <p class="text-sm font-medium text-gray-800">Manage Employees</p>
                </a>
                <a href="tasks.php" class="bg-purple-50 hover:bg-purple-100 rounded-lg p-4 text-center transition">
                    <i class="fas fa-tasks text-purple-600 text-2xl mb-2"></i>
                    <p class="text-sm font-medium text-gray-800">Manage Tasks</p>
                </a>
                <a href="reports.php" class="bg-green-50 hover:bg-green-100 rounded-lg p-4 text-center transition">
                    <i class="fas fa-chart-bar text-green-600 text-2xl mb-2"></i>
                    <p class="text-sm font-medium text-gray-800">View Reports</p>
                </a>
                <a href="logs.php" class="bg-yellow-50 hover:bg-yellow-100 rounded-lg p-4 text-center transition">
                    <i class="fas fa-file-alt text-yellow-600 text-2xl mb-2"></i>
                    <p class="text-sm font-medium text-gray-800">Activity Logs</p>
                </a>
            </div>
        </div>
    </div>
</body>
</html>