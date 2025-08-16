<?php
// admin/reports.php - Reports and analytics
require_once 'config.php';
requireAdminLogin();
requirePermission('reports', 'view');

$pdo = getDB();

// Date range filter
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Default to start of month
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Employee performance report
$stmt = $pdo->prepare("
    SELECT 
        e.id,
        e.name,
        e.department,
        e.role,
        e.total_points,
        COUNT(DISTINCT t.id) as total_tasks,
        SUM(CASE WHEN t.status = 'finished' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
        SUM(CASE WHEN t.status = 'ongoing' THEN 1 ELSE 0 END) as ongoing_tasks,
        SUM(CASE WHEN t.status = 'overdue' THEN 1 ELSE 0 END) as overdue_tasks,
        SUM(CASE WHEN t.status = 'dropped' THEN 1 ELSE 0 END) as dropped_tasks,
        ROUND(AVG(CASE WHEN t.status = 'finished' THEN 
            TIMESTAMPDIFF(HOUR, t.started_at, t.completed_at) 
        END), 2) as avg_completion_hours
    FROM employees e
    LEFT JOIN tasks t ON e.id = t.employee_id 
        AND DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY e.id
    ORDER BY e.total_points DESC
");
$stmt->execute([$startDate, $endDate]);
$employeeStats = $stmt->fetchAll();

// Department performance
$stmt = $pdo->prepare("
    SELECT 
        e.department,
        COUNT(DISTINCT e.id) as employee_count,
        SUM(e.total_points) as total_points,
        COUNT(DISTINCT t.id) as total_tasks,
        SUM(CASE WHEN t.status = 'finished' THEN 1 ELSE 0 END) as completed_tasks,
        ROUND(SUM(CASE WHEN t.status = 'finished' THEN 1 ELSE 0 END) * 100.0 / 
              NULLIF(COUNT(DISTINCT t.id), 0), 2) as completion_rate
    FROM employees e
    LEFT JOIN tasks t ON e.id = t.employee_id 
        AND DATE(t.created_at) BETWEEN ? AND ?
    WHERE e.department IS NOT NULL
    GROUP BY e.department
    ORDER BY total_points DESC
");
$stmt->execute([$startDate, $endDate]);
$departmentStats = $stmt->fetchAll();

// Task completion trends (last 7 days)
$stmt = $pdo->query("
    SELECT 
        DATE(completed_at) as completion_date,
        COUNT(*) as tasks_completed,
        SUM(points) as points_earned
    FROM tasks
    WHERE status = 'finished' 
    AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(completed_at)
    ORDER BY completion_date DESC
");
$completionTrends = $stmt->fetchAll();

// Overall statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'finished' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
        SUM(CASE WHEN status = 'dropped' THEN 1 ELSE 0 END) as dropped,
        ROUND(SUM(CASE WHEN status = 'finished' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as completion_rate,
        ROUND(AVG(points), 2) as avg_points,
        SUM(points) as total_points
    FROM tasks
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$startDate, $endDate]);
$overallStats = $stmt->fetch();

logAdminActivity('view_reports', null, null, "Date range: $startDate to $endDate");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Date Range Filter -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form method="GET" class="flex gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $startDate; ?>"
                           class="px-4 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" value="<?php echo $endDate; ?>"
                           class="px-4 py-2 border rounded-lg">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <button type="button" onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
            </form>
        </div>
        
        <!-- Overall Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-3xl font-bold text-blue-600"><?php echo $overallStats['total_tasks']; ?></div>
                <div class="text-sm text-gray-600">Total Tasks</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-3xl font-bold text-green-600"><?php echo $overallStats['completion_rate']; ?>%</div>
                <div class="text-sm text-gray-600">Completion Rate</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-3xl font-bold text-purple-600"><?php echo $overallStats['total_points']; ?></div>
                <div class="text-sm text-gray-600">Total Points</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-3xl font-bold text-red-600"><?php echo $overallStats['overdue']; ?></div>
                <div class="text-sm text-gray-600">Overdue Tasks</div>
            </div>
        </div>
        
        <!-- Employee Performance -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-user-chart mr-2"></i>Employee Performance Report
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total Tasks</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Completed</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pending</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Overdue</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Points</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Avg Time (hrs)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($employeeStats as $emp): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium"><?php echo htmlspecialchars($emp['name']); ?></div>
                                <?php echo getRoleBadge($emp['role']); ?>
                            </td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($emp['department'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-center"><?php echo $emp['total_tasks']; ?></td>
                            <td class="px-4 py-3 text-center text-green-600"><?php echo $emp['completed_tasks']; ?></td>
                            <td class="px-4 py-3 text-center text-yellow-600"><?php echo $emp['pending_tasks']; ?></td>
                            <td class="px-4 py-3 text-center text-red-600"><?php echo $emp['overdue_tasks']; ?></td>
                            <td class="px-4 py-3 text-center font-bold"><?php echo $emp['total_points']; ?></td>
                            <td class="px-4 py-3 text-center"><?php echo $emp['avg_completion_hours'] ?: '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Department Performance -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-building mr-2"></i>Department Performance
                    </h2>
                </div>
                <div class="p-6">
                    <?php foreach ($departmentStats as $dept): ?>
                    <div class="mb-4 pb-4 border-b last:border-0">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <div class="font-semibold"><?php echo htmlspecialchars($dept['department']); ?></div>
                                <div class="text-sm text-gray-600"><?php echo $dept['employee_count']; ?> employees</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-purple-600"><?php echo $dept['total_points']; ?> pts</div>
                                <div class="text-sm text-gray-600"><?php echo $dept['completion_rate']; ?>% complete</div>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo $dept['completion_rate']; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Completion Trends -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-chart-line mr-2"></i>7-Day Completion Trend
                    </h2>
                </div>
                <div class="p-6">
                    <?php foreach ($completionTrends as $trend): ?>
                    <div class="flex justify-between items-center py-2 border-b">
                        <div>
                            <div class="font-medium"><?php echo date('M d, Y', strtotime($trend['completion_date'])); ?></div>
                            <div class="text-sm text-gray-600"><?php echo date('l', strtotime($trend['completion_date'])); ?></div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold"><?php echo $trend['tasks_completed']; ?> tasks</div>
                            <div class="text-sm text-purple-600"><?php echo $trend['points_earned']; ?> points</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    @media print {
        .navbar, button, form { display: none !important; }
        body { background: white !important; }
    }
    </style>
</body>
</html>