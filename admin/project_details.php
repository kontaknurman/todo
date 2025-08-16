<?php
// admin/project_details.php - Project Details with Team & Stats
require_once 'config.php';
requireAdminLogin();

$project_id = (int)($_GET['id'] ?? 0);
if (!$project_id) {
    header('Location: projects.php');
    exit;
}

$pdo = getDB();
$isAdmin = $_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'admin';
$isManager = $_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'manager';

// Get project details
$stmt = $pdo->prepare("
    SELECT p.*, 
           e.name as manager_name,
           c.name as creator_name
    FROM projects p
    LEFT JOIN employees e ON p.project_manager_id = e.id
    LEFT JOIN employees c ON p.created_by = c.id
    WHERE p.id = ?
");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    $_SESSION['error'] = 'Project not found';
    header('Location: projects.php');
    exit;
}

// Check access for managers
if ($isManager && !$isAdmin) {
    $hasAccess = false;
    
    // Check if manager of this project
    if ($project['project_manager_id'] == $_SESSION[ADMIN_SESSION_PREFIX . 'id']) {
        $hasAccess = true;
    } else {
        // Check if member of this project
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM project_members 
            WHERE project_id = ? AND employee_id = ? AND is_active = TRUE
        ");
        $stmt->execute([$project_id, $_SESSION[ADMIN_SESSION_PREFIX . 'id']]);
        if ($stmt->fetchColumn() > 0) {
            $hasAccess = true;
        }
    }
    
    if (!$hasAccess) {
        $_SESSION['error'] = 'Access denied to this project';
        header('Location: projects.php');
        exit;
    }
}

// Get project members with stats
$stmt = $pdo->prepare("
    SELECT 
        e.id,
        e.name,
        e.email,
        e.department,
        e.role as employee_role,
        pm.role_in_project,
        pm.assigned_date,
        pm.hours_allocated,
        pm.is_active,
        (SELECT COUNT(*) FROM tasks t 
         WHERE t.employee_id = e.id 
         AND t.id IN (SELECT task_id FROM project_tasks WHERE project_id = ?)) as total_tasks,
        (SELECT COUNT(*) FROM tasks t 
         WHERE t.employee_id = e.id 
         AND t.status = 'finished'
         AND t.id IN (SELECT task_id FROM project_tasks WHERE project_id = ?)) as completed_tasks,
        (SELECT SUM(points) FROM tasks t 
         WHERE t.employee_id = e.id 
         AND t.status = 'finished'
         AND t.id IN (SELECT task_id FROM project_tasks WHERE project_id = ?)) as points_earned
    FROM project_members pm
    JOIN employees e ON pm.employee_id = e.id
    WHERE pm.project_id = ? AND pm.is_active = TRUE
    ORDER BY pm.role_in_project DESC, e.name
");
$stmt->execute([$project_id, $project_id, $project_id, $project_id]);
$members = $stmt->fetchAll();

// Get project departments
$stmt = $pdo->prepare("
    SELECT d.*, 
           COUNT(DISTINCT dm.employee_id) as member_count
    FROM departments d
    LEFT JOIN department_members dm ON d.id = dm.department_id AND dm.is_active = TRUE
    WHERE d.project_id = ?
    GROUP BY d.id
");
$stmt->execute([$project_id]);
$departments = $stmt->fetchAll();

// Get project statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT pm.employee_id) as total_members,
        COUNT(DISTINCT pt.id) as total_tasks,
        COUNT(DISTINCT CASE WHEN pt.status = 'completed' THEN pt.id END) as completed_tasks,
        COUNT(DISTINCT CASE WHEN pt.status = 'pending' THEN pt.id END) as pending_tasks,
        COUNT(DISTINCT CASE WHEN pt.status = 'in_progress' THEN pt.id END) as in_progress_tasks,
        COUNT(DISTINCT d.id) as total_departments
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.is_active = TRUE
    LEFT JOIN project_tasks pt ON p.id = pt.project_id
    LEFT JOIN departments d ON p.id = d.project_id
    WHERE p.id = ?
");
$stmt->execute([$project_id]);
$stats = $stmt->fetch();

// Calculate progress
$progress = 0;
if ($project['project_type'] === 'lifetime' && $stats['total_tasks'] > 0) {
    $progress = round(($stats['completed_tasks'] / $stats['total_tasks']) * 100);
} else {
    $progress = $project['progress'];
}

logAdminActivity('view_project_details', 'project', $project_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['project_name']); ?> - Project Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Project Header -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <?php echo htmlspecialchars($project['project_name']); ?>
                        </h1>
                        <p class="text-gray-500">
                            <span class="font-medium">Code:</span> <?php echo htmlspecialchars($project['project_code']); ?>
                            <?php if ($project['project_type'] === 'lifetime'): ?>
                            <span class="ml-3 px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">
                                <i class="fas fa-infinity mr-1"></i>Lifetime Project
                            </span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <span class="px-3 py-1 text-sm rounded-full <?php 
                            echo $project['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                ($project['status'] === 'planning' ? 'bg-yellow-100 text-yellow-800' : 
                                'bg-gray-100 text-gray-800'); ?>">
                            <?php echo ucfirst($project['status']); ?>
                        </span>
                        <span class="px-3 py-1 text-sm rounded-full <?php 
                            echo $project['priority'] === 'urgent' ? 'bg-red-100 text-red-800' : 
                                ($project['priority'] === 'high' ? 'bg-orange-100 text-orange-800' : 
                                'bg-gray-100 text-gray-800'); ?>">
                            <?php echo ucfirst($project['priority']); ?> Priority
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Stats Overview -->
            <div class="px-6 py-4">
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Manager</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($project['manager_name'] ?? 'Not Assigned'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Team Size</p>
                        <p class="font-semibold"><?php echo $stats['total_members']; ?> members</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Departments</p>
                        <p class="font-semibold"><?php echo $stats['total_departments']; ?> depts</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tasks</p>
                        <p class="font-semibold"><?php echo $stats['total_tasks']; ?> total</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Budget</p>
                        <p class="font-semibold">$<?php echo number_format($project['budget'], 2); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Progress</p>
                        <p class="font-semibold"><?php echo $progress; ?>%</p>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full transition-all duration-500" 
                             style="width: <?php echo $progress; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow">
            <div class="border-b">
                <nav class="flex -mb-px">
                    <button onclick="showTab('team')" id="team-tab" 
                            class="tab-btn px-6 py-3 border-b-2 border-blue-500 text-blue-600 font-medium">
                        <i class="fas fa-users mr-2"></i>Team Members
                    </button>
                    <button onclick="showTab('departments')" id="departments-tab" 
                            class="tab-btn px-6 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
                        <i class="fas fa-building mr-2"></i>Departments
                    </button>
                    <button onclick="showTab('stats')" id="stats-tab" 
                            class="tab-btn px-6 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
                        <i class="fas fa-chart-bar mr-2"></i>Statistics
                    </button>
                    <button onclick="showTab('tasks')" id="tasks-tab" 
                            class="tab-btn px-6 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
                        <i class="fas fa-tasks mr-2"></i>Tasks
                    </button>
                </nav>
            </div>
            
            <!-- Tab Contents -->
            <div id="team-content" class="tab-content p-6">
                <!-- Team members table and management -->
                <?php include 'project_details_team.php'; ?>
            </div>
            
            <div id="departments-content" class="tab-content p-6 hidden">
                <!-- Departments management -->
                <?php include 'project_details_departments.php'; ?>
            </div>
            
            <div id="stats-content" class="tab-content p-6 hidden">
                <!-- Statistics and charts -->
                <?php include 'project_details_stats.php'; ?>
            </div>
            
            <div id="tasks-content" class="tab-content p-6 hidden">
                <!-- Tasks list -->
                <?php include 'project_details_tasks.php'; ?>
            </div>
        </div>
    </div>
    
    <script src="/js/project_details.js"></script>
</body>
</html>