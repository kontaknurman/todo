<?php
// admin/projects.php - Project Management with Manager Access
require_once 'config.php';
requireAdminLogin();

// Check if manager or admin
$isAdmin = $_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'admin';
$isManager = $_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'manager';

if (!$isAdmin && !$isManager) {
    $_SESSION['error'] = 'Access denied';
    header('Location: dashboard.php');
    exit;
}

$pdo = getDB();
$admin = getCurrentEmployee();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Handle actions (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $action = $_POST['action'] ?? '';
    
    if ($_POST['csrf_token'] !== $csrfToken) {
        $_SESSION['error'] = 'Security validation failed';
    } else {
        switch ($action) {
            case 'create':
                $project_name = trim($_POST['project_name']);
                $project_code = trim($_POST['project_code']);
                $project_type = $_POST['project_type'] ?? 'fixed';
                $description = trim($_POST['description']);
                $status = $_POST['status'];
                $priority = $_POST['priority'];
                $start_date = $_POST['start_date'] ?: null;
                $end_date = $_POST['end_date'] ?: null;
                $budget = floatval($_POST['budget'] ?? 0);
                $manager_id = $_POST['project_manager_id'] ?: null;
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO projects (project_name, project_code, project_type, description, 
                                            status, priority, start_date, end_date, budget, 
                                            created_by, project_manager_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$project_name, $project_code, $project_type, $description, 
                                  $status, $priority, $start_date, $end_date, $budget, 
                                  $_SESSION[ADMIN_SESSION_PREFIX . 'id'], $manager_id]);
                    
                    $project_id = $pdo->lastInsertId();
                    
                    // Add selected members
                    if (!empty($_POST['members'])) {
                        $memberStmt = $pdo->prepare("
                            INSERT INTO project_members (project_id, employee_id, role_in_project, assigned_by)
                            VALUES (?, ?, ?, ?)
                        ");
                        foreach ($_POST['members'] as $member_id) {
                            $role = ($member_id == $manager_id) ? 'Project Manager' : 'Member';
                            $memberStmt->execute([$project_id, $member_id, $role, 
                                                $_SESSION[ADMIN_SESSION_PREFIX . 'id']]);
                        }
                    }
                    
                    $_SESSION['success'] = 'Project created successfully';
                    logAdminActivity('create_project', 'project', $project_id, 
                                   "Created project: $project_name");
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error creating project: ' . 
                        (strpos($e->getMessage(), 'Duplicate') !== false ? 
                        'Project code already exists' : 'Database error');
                }
                break;
                
            case 'delete':
                $project_id = (int)$_POST['project_id'];
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
                    $stmt->execute([$project_id]);
                    $_SESSION['success'] = 'Project deleted successfully';
                    logAdminActivity('delete_project', 'project', $project_id);
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error deleting project';
                }
                break;
        }
    }
    header('Location: projects.php');
    exit;
}

// Get filters
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_priority = $_GET['priority'] ?? '';

// Build query based on role
if ($isAdmin) {
    // Admin sees all projects
    $query = "SELECT ps.*, 
              (SELECT GROUP_CONCAT(e.name SEPARATOR ', ') 
               FROM project_members pm 
               JOIN employees e ON pm.employee_id = e.id 
               WHERE pm.project_id = ps.id AND pm.is_active = TRUE) as members_list
              FROM project_summary ps WHERE 1=1";
    $params = [];
} else {
    // Manager sees only their projects
    $query = "SELECT ps.*, 
              (SELECT GROUP_CONCAT(e.name SEPARATOR ', ') 
               FROM project_members pm 
               JOIN employees e ON pm.employee_id = e.id 
               WHERE pm.project_id = ps.id AND pm.is_active = TRUE) as members_list
              FROM project_summary ps 
              WHERE ps.project_manager_id = ? OR ps.id IN (
                  SELECT project_id FROM project_members 
                  WHERE employee_id = ? AND is_active = TRUE
              )";
    $params = [$_SESSION[ADMIN_SESSION_PREFIX . 'id'], $_SESSION[ADMIN_SESSION_PREFIX . 'id']];
}

if ($search) {
    $query .= " AND (ps.project_name LIKE ? OR ps.project_code LIKE ? OR ps.description LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($filter_status) {
    $query .= " AND ps.status = ?";
    $params[] = $filter_status;
}

if ($filter_priority) {
    $query .= " AND ps.priority = ?";
    $params[] = $filter_priority;
}

$query .= " ORDER BY ps.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Get all employees for assignment
$stmt = $pdo->query("SELECT id, name, email, role, department FROM employees ORDER BY name");
$employees = $stmt->fetchAll();

// Get managers for dropdown
$stmt = $pdo->query("SELECT id, name FROM employees WHERE role IN ('admin', 'manager') ORDER BY name");
$managers = $stmt->fetchAll();

logAdminActivity('view_projects');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Projects</p>
                        <p class="text-2xl font-bold"><?php echo count($projects); ?></p>
                    </div>
                    <i class="fas fa-project-diagram text-blue-500 text-2xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Active</p>
                        <p class="text-2xl font-bold text-green-600">
                            <?php echo count(array_filter($projects, fn($p) => $p['status'] == 'active')); ?>
                        </p>
                    </div>
                    <i class="fas fa-play-circle text-green-500 text-2xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Planning</p>
                        <p class="text-2xl font-bold text-yellow-600">
                            <?php echo count(array_filter($projects, fn($p) => $p['status'] == 'planning')); ?>
                        </p>
                    </div>
                    <i class="fas fa-clipboard-list text-yellow-500 text-2xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Your Role</p>
                        <p class="text-lg font-bold capitalize">
                            <?php echo $_SESSION[ADMIN_SESSION_PREFIX . 'role']; ?>
                        </p>
                    </div>
                    <i class="fas fa-user-tag text-purple-500 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-project-diagram mr-2"></i>
                    <?php echo $isManager && !$isAdmin ? 'My Projects' : 'Project Management'; ?>
                </h1>
                <?php if ($isAdmin): ?>
                <button onclick="openCreateModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>New Project
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Filters -->
            <div class="px-6 py-4 bg-gray-50 border-b">
                <form method="GET" class="flex gap-4">
                    <input type="text" name="search" placeholder="Search projects..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="flex-1 px-4 py-2 border rounded-lg">
                    <select name="status" class="px-4 py-2 border rounded-lg">
                        <option value="">All Status</option>
                        <option value="planning" <?php echo $filter_status === 'planning' ? 'selected' : ''; ?>>
                            Planning</option>
                        <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>
                            Active</option>
                        <option value="on_hold" <?php echo $filter_status === 'on_hold' ? 'selected' : ''; ?>>
                            On Hold</option>
                        <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>
                            Completed</option>
                    </select>
                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </form>
            </div>
            
            <!-- Projects Grid -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($projects as $project): ?>
                    <div class="border rounded-lg p-4 hover:shadow-lg transition">
                        <!-- Project header content (same as before) -->
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-bold text-lg">
                                    <?php echo htmlspecialchars($project['project_name']); ?></h3>
                                <p class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($project['project_code']); ?></p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full <?php 
                                echo $project['priority'] === 'urgent' ? 'bg-red-100 text-red-800' : 
                                    ($project['priority'] === 'high' ? 'bg-orange-100 text-orange-800' : 
                                    ($project['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                                    'bg-gray-100 text-gray-800')); ?>">
                                <?php echo ucfirst($project['priority']); ?>
                            </span>
                        </div>
                        
                        <!-- Action buttons updated -->
                        <div class="flex gap-2 mt-3">
                            <a href="project_details.php?id=<?php echo $project['id']; ?>" 
                               class="flex-1 bg-blue-50 text-blue-600 px-3 py-1 rounded text-sm hover:bg-blue-100 text-center">
                                <i class="fas fa-eye mr-1"></i> Details
                            </a>
                            <?php if ($isAdmin): ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this project?')">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                <button type="submit" class="px-3 py-1 bg-red-50 text-red-600 rounded text-sm hover:bg-red-100">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>