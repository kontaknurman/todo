<?php
// admin/projects.php - Project Management for Admin
require_once 'config.php';
requireAdminLogin();

// Only admins can manage projects
if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] !== 'admin') {
    $_SESSION['error'] = 'Only administrators can manage projects';
    header('Location: dashboard.php');
    exit;
}

$pdo = getDB();
$admin = getCurrentAdmin();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                        INSERT INTO projects (project_name, project_code, project_type, description, status, priority, 
                                            start_date, end_date, budget, created_by, project_manager_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$project_name, $project_code, $project_type, $description, $status, $priority, 
                                  $start_date, $end_date, $budget, $_SESSION[ADMIN_SESSION_PREFIX . 'id'], $manager_id]);
                    
                    $project_id = $pdo->lastInsertId();
                    
                    // Add selected members
                    if (!empty($_POST['members'])) {
                        $memberStmt = $pdo->prepare("
                            INSERT INTO project_members (project_id, employee_id, role_in_project, assigned_by)
                            VALUES (?, ?, ?, ?)
                        ");
                        foreach ($_POST['members'] as $member_id) {
                            $role = ($member_id == $manager_id) ? 'Project Manager' : 'Member';
                            $memberStmt->execute([$project_id, $member_id, $role, $_SESSION[ADMIN_SESSION_PREFIX . 'id']]);
                        }
                    }
                    
                    $_SESSION['success'] = 'Project created successfully';
                    logAdminActivity('create_project', 'project', $project_id, "Created project: $project_name");
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error creating project: ' . (strpos($e->getMessage(), 'Duplicate') !== false ? 'Project code already exists' : 'Database error');
                }
                break;
                
            case 'edit':
                $project_id = (int)$_POST['project_id'];
                $project_name = trim($_POST['project_name']);
                $description = trim($_POST['description']);
                $status = $_POST['status'];
                $priority = $_POST['priority'];
                $progress = (int)$_POST['progress'];
                $end_date = $_POST['end_date'];
                $manager_id = $_POST['project_manager_id'] ?: null;
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE projects 
                        SET project_name = ?, description = ?, status = ?, priority = ?, 
                            progress = ?, end_date = ?, project_manager_id = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$project_name, $description, $status, $priority, 
                                  $progress, $end_date, $manager_id, $project_id]);
                    
                    $_SESSION['success'] = 'Project updated successfully';
                    logAdminActivity('edit_project', 'project', $project_id, "Updated project: $project_name");
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error updating project';
                }
                break;
                
            case 'assign_members':
                $project_id = (int)$_POST['project_id'];
                $members = $_POST['members'] ?? [];
                
                try {
                    // Deactivate all current members
                    $stmt = $pdo->prepare("UPDATE project_members SET is_active = FALSE WHERE project_id = ?");
                    $stmt->execute([$project_id]);
                    
                    // Add/reactivate selected members
                    if (!empty($members)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO project_members (project_id, employee_id, role_in_project, assigned_by, is_active)
                            VALUES (?, ?, 'Member', ?, TRUE)
                            ON DUPLICATE KEY UPDATE is_active = TRUE, removed_date = NULL
                        ");
                        foreach ($members as $member_id) {
                            $stmt->execute([$project_id, $member_id, $_SESSION[ADMIN_SESSION_PREFIX . 'id']]);
                        }
                    }
                    
                    $_SESSION['success'] = 'Project members updated successfully';
                    logAdminActivity('assign_members', 'project', $project_id, "Updated project members");
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error updating project members';
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

// Get projects with summary
$query = "SELECT ps.*, 
          (SELECT GROUP_CONCAT(e.name SEPARATOR ', ') 
           FROM project_members pm 
           JOIN employees e ON pm.employee_id = e.id 
           WHERE pm.project_id = ps.id AND pm.is_active = TRUE) as members_list
          FROM project_summary ps WHERE 1=1";
$params = [];

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
                        <p class="text-gray-500 text-sm">Completed</p>
                        <p class="text-2xl font-bold text-blue-600">
                            <?php echo count(array_filter($projects, fn($p) => $p['status'] == 'completed')); ?>
                        </p>
                    </div>
                    <i class="fas fa-check-double text-blue-500 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-project-diagram mr-2"></i>Project Management
                </h1>
                <button onclick="openCreateModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>New Project
                </button>
            </div>
            
            <!-- Filters -->
            <div class="px-6 py-4 bg-gray-50 border-b">
                <form method="GET" class="flex gap-4">
                    <input type="text" name="search" placeholder="Search projects..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="flex-1 px-4 py-2 border rounded-lg">
                    <select name="status" class="px-4 py-2 border rounded-lg">
                        <option value="">All Status</option>
                        <option value="planning" <?php echo $filter_status === 'planning' ? 'selected' : ''; ?>>Planning</option>
                        <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="on_hold" <?php echo $filter_status === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                        <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                    <select name="priority" class="px-4 py-2 border rounded-lg">
                        <option value="">All Priority</option>
                        <option value="urgent" <?php echo $filter_priority === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        <option value="high" <?php echo $filter_priority === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $filter_priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $filter_priority === 'low' ? 'selected' : ''; ?>>Low</option>
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
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($project['project_code']); ?></p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full <?php 
                                echo $project['priority'] === 'urgent' ? 'bg-red-100 text-red-800' : 
                                    ($project['priority'] === 'high' ? 'bg-orange-100 text-orange-800' : 
                                    ($project['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                                    'bg-gray-100 text-gray-800')); ?>">
                                <?php echo ucfirst($project['priority']); ?>
                            </span>
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-3">
                            <?php echo htmlspecialchars(substr($project['description'] ?? '', 0, 100)); ?>
                            <?php echo strlen($project['description'] ?? '') > 100 ? '...' : ''; ?>
                        </p>
                        
                        <div class="space-y-2 mb-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Type:</span>
                                <span class="px-2 py-1 text-xs rounded <?php 
                                    echo ($project['project_type'] ?? 'fixed') === 'lifetime' ? 
                                    'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($project['project_type'] ?? 'fixed'); ?>
                                </span>
                            </div>
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Status:</span>
                                <span class="px-2 py-1 text-xs rounded <?php 
                                    echo $project['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                        ($project['status'] === 'planning' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($project['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 
                                        'bg-gray-100 text-gray-800')); ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </div>
                            
                            <?php if (($project['project_type'] ?? 'fixed') === 'lifetime'): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tasks:</span>
                                <span class="font-medium">
                                    <?php 
                                    $completed = $project['completed_task_count'] ?? 0;
                                    $total = $project['task_count'] ?? 0;
                                    echo $completed . '/' . $total . ' completed';
                                    ?>
                                </span>
                            </div>
                            
                            <?php if ($total > 0): ?>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo round(($completed / $total) * 100); ?>%"></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php else: ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Progress:</span>
                                <span><?php echo $project['progress'] ?? 0; ?>%</span>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $project['progress'] ?? 0; ?>%"></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Manager:</span>
                                <span><?php echo htmlspecialchars($project['project_manager_name'] ?? 'Not assigned'); ?></span>
                            </div>
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Team:</span>
                                <span><?php echo $project['member_count'] ?? 0; ?> members</span>
                            </div>
                            
                            <?php if ($project['start_date'] && $project['end_date']): ?>
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-calendar mr-1"></i>
                                <?php echo date('M d', strtotime($project['start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($project['end_date'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex gap-2">
                            <button onclick='viewProject(<?php echo json_encode($project); ?>)' 
                                    class="flex-1 bg-blue-50 text-blue-600 px-3 py-1 rounded text-sm hover:bg-blue-100">
                                <i class="fas fa-eye mr-1"></i> View
                            </button>
                            <button onclick='editProject(<?php echo json_encode($project); ?>)' 
                                    class="flex-1 bg-green-50 text-green-600 px-3 py-1 rounded text-sm hover:bg-green-100">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </button>
                            <button onclick='manageMembers(<?php echo $project['id']; ?>, "<?php echo htmlspecialchars($project['project_name']); ?>")' 
                                    class="flex-1 bg-purple-50 text-purple-600 px-3 py-1 rounded text-sm hover:bg-purple-100">
                                <i class="fas fa-users mr-1"></i> Team
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($projects)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-folder-open text-4xl mb-3"></i>
                    <p>No projects found</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Create/Edit Project Modal -->
    <div id="projectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center overflow-y-auto">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl my-8">
            <h2 id="modalTitle" class="text-xl font-bold mb-4">Create New Project</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="project_id" id="projectId">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Project Name *</label>
                        <input type="text" name="project_name" id="projectName" required
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Project Code *</label>
                        <input type="text" name="project_code" id="projectCode" required
                               class="w-full px-3 py-2 border rounded-lg" placeholder="PRJ-2024-001">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Project Type *</label>
                        <select name="project_type" id="projectType" onchange="toggleProjectType()" class="w-full px-3 py-2 border rounded-lg">
                            <option value="fixed">Fixed Duration</option>
                            <option value="lifetime">Lifetime (Ongoing)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="w-full px-3 py-2 border rounded-lg">
                            <option value="planning">Planning</option>
                            <option value="active">Active</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="3"
                                  class="w-full px-3 py-2 border rounded-lg"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                        <select name="priority" id="priority" class="w-full px-3 py-2 border rounded-lg">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Project Manager</label>
                        <select name="project_manager_id" id="projectManager" class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Select Manager</option>
                            <?php foreach ($managers as $manager): ?>
                            <option value="<?php echo $manager['id']; ?>"><?php echo htmlspecialchars($manager['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="startDateDiv">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" id="startDate"
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div id="endDateDiv">
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" id="endDate"
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Budget</label>
                        <input type="number" name="budget" id="budget" step="0.01"
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div class="col-span-2" id="progressDiv" style="display:none;">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <span id="progressLabel">Progress (%)</span>
                        </label>
                        <input type="number" name="progress" id="progress" min="0" max="100" value="0"
                               class="w-full px-3 py-2 border rounded-lg">
                        <p class="text-xs text-gray-500 mt-1" id="progressHelp">
                            For fixed projects, enter manual progress. For lifetime projects, progress is calculated from task completion.
                        </p>
                    </div>
                    <div class="col-span-2" id="membersDiv">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Team Members</label>
                        <div class="border rounded-lg p-3 max-h-40 overflow-y-auto">
                            <?php foreach ($employees as $emp): ?>
                            <label class="flex items-center p-1">
                                <input type="checkbox" name="members[]" value="<?php echo $emp['id']; ?>" class="mr-2">
                                <?php echo htmlspecialchars($emp['name']); ?> 
                                <span class="text-xs text-gray-500 ml-2">(<?php echo $emp['department'] ?? 'No Dept'; ?>)</span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Project</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Manage Members Modal -->
    <div id="membersModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4">Manage Team Members - <span id="projectNameDisplay"></span></h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="assign_members">
                <input type="hidden" name="project_id" id="memberProjectId">
                
                <div class="border rounded-lg p-3 max-h-60 overflow-y-auto mb-4">
                    <?php foreach ($employees as $emp): ?>
                    <label class="flex items-center p-2 hover:bg-gray-50">
                        <input type="checkbox" name="members[]" value="<?php echo $emp['id']; ?>" 
                               class="member-checkbox mr-2">
                        <?php echo htmlspecialchars($emp['name']); ?>
                        <span class="text-xs ml-2">
                            <span class="px-2 py-1 rounded <?php 
                                echo $emp['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                    ($emp['role'] === 'manager' ? 'bg-blue-100 text-blue-800' : 
                                    'bg-gray-100 text-gray-800'); ?>">
                                <?php echo ucfirst($emp['role']); ?>
                            </span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeMembersModal()" 
                            class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                        Update Team
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Create New Project';
        document.getElementById('formAction').value = 'create';
        document.getElementById('projectModal').classList.remove('hidden');
        document.getElementById('projectCode').readOnly = false;
        document.getElementById('progressDiv').style.display = 'none';
        document.getElementById('membersDiv').style.display = 'block';
        document.querySelector('#projectModal form').reset();
        toggleProjectType(); // Set initial state
    }
    
    function toggleProjectType() {
        const projectType = document.getElementById('projectType').value;
        const startDateDiv = document.getElementById('startDateDiv');
        const endDateDiv = document.getElementById('endDateDiv');
        const progressDiv = document.getElementById('progressDiv');
        const progressInput = document.getElementById('progress');
        const progressLabel = document.getElementById('progressLabel');
        const progressHelp = document.getElementById('progressHelp');
        
        if (projectType === 'lifetime') {
            // Lifetime projects don't need end dates
            startDateDiv.style.display = 'block';
            endDateDiv.style.display = 'none';
            
            // Show progress div in edit mode but make it read-only for lifetime
            if (document.getElementById('formAction').value === 'edit') {
                progressDiv.style.display = 'block';
                progressInput.readOnly = true;
                progressLabel.textContent = 'Task Completion (Auto-calculated)';
                progressHelp.textContent = 'For lifetime projects, progress is automatically calculated from task completion.';
            }
        } else {
            // Fixed projects need both dates
            startDateDiv.style.display = 'block';
            endDateDiv.style.display = 'block';
            
            if (document.getElementById('formAction').value === 'edit') {
                progressDiv.style.display = 'block';
                progressInput.readOnly = false;
                progressLabel.textContent = 'Progress (%)';
                progressHelp.textContent = 'Enter the manual progress percentage for this fixed-duration project.';
            }
        }
    }
    
    function editProject(project) {
        document.getElementById('modalTitle').textContent = 'Edit Project';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('projectId').value = project.id;
        document.getElementById('projectName').value = project.project_name;
        document.getElementById('projectCode').value = project.project_code;
        document.getElementById('projectCode').readOnly = true;
        document.getElementById('projectType').value = project.project_type || 'fixed';
        document.getElementById('description').value = project.description || '';
        document.getElementById('status').value = project.status;
        document.getElementById('priority').value = project.priority;
        document.getElementById('startDate').value = project.start_date;
        document.getElementById('endDate').value = project.end_date;
        document.getElementById('budget').value = project.budget;
        document.getElementById('projectManager').value = project.project_manager_id || '';
        
        // Set progress based on project type
        if (project.project_type === 'lifetime') {
            // Calculate task completion percentage
            const taskCompletion = project.task_count > 0 ? 
                Math.round((project.completed_task_count / project.task_count) * 100) : 0;
            document.getElementById('progress').value = taskCompletion;
        } else {
            document.getElementById('progress').value = project.progress || 0;
        }
        
        document.getElementById('progressDiv').style.display = 'block';
        document.getElementById('membersDiv').style.display = 'none';
        
        toggleProjectType(); // Apply type-specific settings
        document.getElementById('projectModal').classList.remove('hidden');
    }
    
    function viewProject(project) {
        const projectType = project.project_type || 'fixed';
        const progressInfo = projectType === 'lifetime' ? 
            `Tasks: ${project.completed_task_count || 0}/${project.task_count || 0} completed` :
            `Progress: ${project.progress || 0}%`;
            
        alert('Project Details:\n\n' + 
              'Name: ' + project.project_name + '\n' +
              'Code: ' + project.project_code + '\n' +
              'Type: ' + (projectType === 'lifetime' ? 'Lifetime (Ongoing)' : 'Fixed Duration') + '\n' +
              'Status: ' + project.status + '\n' +
              'Priority: ' + project.priority + '\n' +
              progressInfo + '\n' +
              'Team Size: ' + (project.member_count || 0) + ' members');
    }
    
    function manageMembers(projectId, projectName) {
        document.getElementById('memberProjectId').value = projectId;
        document.getElementById('projectNameDisplay').textContent = projectName;
        
        // Load current members via AJAX (simplified for now)
        document.getElementById('membersModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('projectModal').classList.add('hidden');
    }
    
    function closeMembersModal() {
        document.getElementById('membersModal').classList.add('hidden');
    }
    </script>
</body>
</html>