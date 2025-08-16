<?php
// admin/departments.php - Department Management (Managers can create/edit)
require_once 'config.php';
requireAdminLogin();

// Check if coming from a project
$from_project_id = $_GET['project_id'] ?? null;
$from_project_name = $_GET['project_name'] ?? null;
$return_project = $_GET['return_project'] ?? null;
$action = $_GET['action'] ?? '';
$dept_id = $_GET['id'] ?? null;

// If action is create and from project, show create modal with project pre-selected
$show_create_modal = ($action === 'create' && $from_project_id);
$show_edit_modal = ($action === 'edit' && $dept_id);
$show_members_modal = ($action === 'members' && $dept_id);

// Both admins and managers can manage departments
if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] !== 'admin' && $_SESSION[ADMIN_SESSION_PREFIX . 'role'] !== 'manager') {
    $_SESSION['error'] = 'Access denied';
    header('Location: dashboard.php');
    exit;
}

$pdo = getDB();
$admin = getCurrentAdmin();
$isAdmin = $_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'admin';

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
                $dept_name = trim($_POST['department_name']);
                $dept_code = trim($_POST['department_code']);
                $description = trim($_POST['description']);
                $location = trim($_POST['location']);
                $budget = floatval($_POST['budget'] ?? 0);
                $manager_id = $_POST['manager_id'] ?: null;
                $project_id = $_POST['project_id'] ?: null;
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO departments (department_name, department_code, description, 
                                               location, budget, manager_id, project_id, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$dept_name, $dept_code, $description, $location, $budget, 
                                  $manager_id, $project_id, $_SESSION[ADMIN_SESSION_PREFIX . 'id']]);
                    
                    $dept_id = $pdo->lastInsertId();
                    
                    // Add selected members
                    if (!empty($_POST['members'])) {
                        $memberStmt = $pdo->prepare("
                            INSERT INTO department_members (department_id, employee_id, position, assigned_by)
                            VALUES (?, ?, ?, ?)
                        ");
                        foreach ($_POST['members'] as $member_id) {
                            $position = ($member_id == $manager_id) ? 'Department Head' : 'Member';
                            $memberStmt->execute([$dept_id, $member_id, $position, $_SESSION[ADMIN_SESSION_PREFIX . 'id']]);
                        }
                    }
                    
                    $_SESSION['success'] = 'Department created successfully';
                    logAdminActivity('create_department', 'department', $dept_id, "Created department: $dept_name");
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error creating department: ' . 
                        (strpos($e->getMessage(), 'Duplicate') !== false ? 'Department code already exists' : 'Database error');
                }
                break;
                
            case 'edit':
                $dept_id = (int)$_POST['department_id'];
                
                // Check if manager can edit this department
                if (!$isAdmin) {
                    $checkStmt = $pdo->prepare("SELECT manager_id FROM departments WHERE id = ?");
                    $checkStmt->execute([$dept_id]);
                    $dept = $checkStmt->fetch();
                    if ($dept['manager_id'] != $_SESSION[ADMIN_SESSION_PREFIX . 'id']) {
                        $_SESSION['error'] = 'You can only edit departments you manage';
                        header('Location: departments.php');
                        exit;
                    }
                }
                
                $dept_name = trim($_POST['department_name']);
                $description = trim($_POST['description']);
                $location = trim($_POST['location']);
                $budget = floatval($_POST['budget'] ?? 0);
                $manager_id = $_POST['manager_id'] ?: null;
                $status = $_POST['status'];
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE departments 
                        SET department_name = ?, description = ?, location = ?, 
                            budget = ?, manager_id = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$dept_name, $description, $location, $budget, 
                                  $manager_id, $status, $dept_id]);
                    
                    $_SESSION['success'] = 'Department updated successfully';
                    logAdminActivity('edit_department', 'department', $dept_id, "Updated department: $dept_name");
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error updating department';
                }
                break;
                
            case 'assign_members':
                $dept_id = (int)$_POST['department_id'];
                $members = $_POST['members'] ?? [];
                
                // Check permission
                if (!$isAdmin) {
                    $checkStmt = $pdo->prepare("SELECT manager_id FROM departments WHERE id = ?");
                    $checkStmt->execute([$dept_id]);
                    $dept = $checkStmt->fetch();
                    if ($dept['manager_id'] != $_SESSION[ADMIN_SESSION_PREFIX . 'id']) {
                        $_SESSION['error'] = 'You can only manage members of departments you manage';
                        header('Location: departments.php');
                        exit;
                    }
                }
                
                try {
                    // Deactivate all current members
                    $stmt = $pdo->prepare("UPDATE department_members SET is_active = FALSE WHERE department_id = ?");
                    $stmt->execute([$dept_id]);
                    
                    // Add/reactivate selected members
                    if (!empty($members)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO department_members (department_id, employee_id, position, assigned_by, is_active)
                            VALUES (?, ?, 'Member', ?, TRUE)
                            ON DUPLICATE KEY UPDATE is_active = TRUE, left_date = NULL
                        ");
                        foreach ($members as $member_id) {
                            $stmt->execute([$dept_id, $member_id, $_SESSION[ADMIN_SESSION_PREFIX . 'id']]);
                        }
                    }
                    
                    $_SESSION['success'] = 'Department members updated successfully';
                    logAdminActivity('assign_dept_members', 'department', $dept_id);
                } catch (PDOException $e) {
                    $_SESSION['error'] = 'Error updating department members';
                }
                break;
                
            case 'delete':
                if (!$isAdmin) {
                    $_SESSION['error'] = 'Only administrators can delete departments';
                } else {
                    $dept_id = (int)$_POST['department_id'];
                    
                    try {
                        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
                        $stmt->execute([$dept_id]);
                        $_SESSION['success'] = 'Department deleted successfully';
                        logAdminActivity('delete_department', 'department', $dept_id);
                    } catch (PDOException $e) {
                        $_SESSION['error'] = 'Error deleting department';
                    }
                }
                break;
        }
    }
    
    // After successful operation
    $return_project = $_POST['return_project'] ?? null;
    if ($return_project) {
        header('Location: project_details.php?id=' . $return_project . '#departments-tab');
    } else {
        header('Location: departments.php');
    }
    exit;
}

// Get filters
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Get departments based on role
if ($isAdmin) {
    // Admins see all departments
    $query = "SELECT ds.* FROM department_summary ds WHERE 1=1";
} else {
    // Managers see only departments they manage
    $query = "SELECT ds.* FROM department_summary ds WHERE ds.manager_id = " . $_SESSION[ADMIN_SESSION_PREFIX . 'id'];
}

$params = [];

if ($search) {
    $query .= " AND (ds.department_name LIKE ? OR ds.department_code LIKE ? OR ds.location LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($filter_status) {
    $query .= " AND ds.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY ds.department_name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$departments = $stmt->fetchAll();

// Get all employees for assignment
$stmt = $pdo->query("SELECT id, name, email, role, department FROM employees ORDER BY name");
$employees = $stmt->fetchAll();

// Get managers for dropdown
$stmt = $pdo->query("SELECT id, name FROM employees WHERE role IN ('admin', 'manager') ORDER BY name");
$managers = $stmt->fetchAll();

// Get projects for parent dropdown
$stmt = $pdo->query("SELECT id, project_name, project_code FROM projects WHERE status IN ('active', 'planning') ORDER BY project_name");
$projects = $stmt->fetchAll();

logAdminActivity('view_departments');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Departments</p>
                        <p class="text-2xl font-bold"><?php echo count($departments); ?></p>
                    </div>
                    <i class="fas fa-building text-purple-500 text-2xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Active</p>
                        <p class="text-2xl font-bold text-green-600">
                            <?php echo count(array_filter($departments, fn($d) => $d['status'] == 'active')); ?>
                        </p>
                    </div>
                    <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Employees</p>
                        <p class="text-2xl font-bold">
                            <?php echo array_sum(array_column($departments, 'member_count')); ?>
                        </p>
                    </div>
                    <i class="fas fa-users text-blue-500 text-2xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Your Role</p>
                        <p class="text-lg font-bold capitalize"><?php echo $_SESSION[ADMIN_SESSION_PREFIX . 'role']; ?></p>
                    </div>
                    <i class="fas fa-user-tag text-orange-500 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-building mr-2"></i>Department Management
                </h1>
                <button onclick="openCreateModal()" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                    <i class="fas fa-plus mr-2"></i>New Department
                </button>
            </div>
            
            <!-- Filters -->
            <div class="px-6 py-4 bg-gray-50 border-b">
                <form method="GET" class="flex gap-4">
                    <input type="text" name="search" placeholder="Search departments..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="flex-1 px-4 py-2 border rounded-lg">
                    <select name="status" class="px-4 py-2 border rounded-lg">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </form>
            </div>
            
            <!-- Departments Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Manager</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Members</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Budget</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-medium"><?php echo htmlspecialchars($dept['department_name']); ?></div>
                                    <?php if ($dept['project_name']): ?>
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-project-diagram mr-1"></i>
                                        Project: <?php echo htmlspecialchars($dept['project_name']); ?> (<?php echo htmlspecialchars($dept['project_code']); ?>)
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($dept['department_code']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($dept['manager_name'] ?? 'Not assigned'); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($dept['location'] ?? '-'); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                    <?php echo $dept['member_count'] ?? 0; ?> members
                                </span>
                            </td>
                            <td class="px-6 py-4">$<?php echo number_format($dept['budget'], 2); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full <?php 
                                    echo $dept['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($dept['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick='editDepartment(<?php echo json_encode($dept); ?>)' 
                                        class="text-blue-600 hover:text-blue-800 mr-2" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick='manageMembers(<?php echo $dept['id']; ?>, "<?php echo htmlspecialchars($dept['department_name']); ?>")' 
                                        class="text-purple-600 hover:text-purple-800 mr-2" title="Manage Members">
                                    <i class="fas fa-users"></i>
                                </button>
                                <?php if ($isAdmin): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this department?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($departments)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-building text-4xl mb-3"></i>
                    <p>No departments found</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Create/Edit Department Modal -->
    <div id="departmentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center overflow-y-auto">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl my-8">
            <h2 id="modalTitle" class="text-xl font-bold mb-4">Create New Department</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="department_id" id="departmentId">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department Name *</label>
                        <input type="text" name="department_name" id="departmentName" required
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department Code *</label>
                        <input type="text" name="department_code" id="departmentCode" required
                               class="w-full px-3 py-2 border rounded-lg" placeholder="IT, HR, MKT">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="2"
                                  class="w-full px-3 py-2 border rounded-lg"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input type="text" name="location" id="location"
                               class="w-full px-3 py-2 border rounded-lg" placeholder="Building A, Floor 2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Budget</label>
                        <input type="number" name="budget" id="budget" step="0.01"
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department Manager</label>
                        <select name="manager_id" id="managerId" class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Select Manager</option>
                            <?php foreach ($managers as $manager): ?>
                            <option value="<?php echo $manager['id']; ?>"><?php echo htmlspecialchars($manager['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Project (Optional)</label>
                        <select name="project_id" id="projectId" class="w-full px-3 py-2 border rounded-lg">
                            <option value="">No Project (Independent Department)</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>">
                                <?php echo htmlspecialchars($project['project_name']); ?> (<?php echo htmlspecialchars($project['project_code']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="statusDiv" style="display:none;">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="w-full px-3 py-2 border rounded-lg">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-span-2" id="membersDiv">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department Members</label>
                        <div class="border rounded-lg p-3 max-h-40 overflow-y-auto">
                            <?php foreach ($employees as $emp): ?>
                            <label class="flex items-center p-1">
                                <input type="checkbox" name="members[]" value="<?php echo $emp['id']; ?>" class="mr-2">
                                <?php echo htmlspecialchars($emp['name']); ?> 
                                <span class="text-xs text-gray-500 ml-2">(<?php echo ucfirst($emp['role']); ?>)</span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Save Department</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Manage Members Modal -->
    <div id="membersModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4">Manage Members - <span id="deptNameDisplay"></span></h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="assign_members">
                <input type="hidden" name="department_id" id="memberDeptId">
                
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
                        Update Members
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Create New Department';
        document.getElementById('formAction').value = 'create';
        document.getElementById('departmentModal').classList.remove('hidden');
        document.getElementById('departmentCode').readOnly = false;
        document.getElementById('statusDiv').style.display = 'none';
        document.getElementById('membersDiv').style.display = 'block';
        document.querySelector('#departmentModal form').reset();
    }
    
    function editDepartment(dept) {
        document.getElementById('modalTitle').textContent = 'Edit Department';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('departmentId').value = dept.id;
        document.getElementById('departmentName').value = dept.department_name;
        document.getElementById('departmentCode').value = dept.department_code;
        document.getElementById('departmentCode').readOnly = true;
        document.getElementById('description').value = dept.description || '';
        document.getElementById('location').value = dept.location || '';
        document.getElementById('budget').value = dept.budget || 0;
        document.getElementById('managerId').value = dept.manager_id || '';
        document.getElementById('projectId').value = dept.project_id || '';
        document.getElementById('status').value = dept.status;
        document.getElementById('statusDiv').style.display = 'block';
        document.getElementById('membersDiv').style.display = 'none';
        document.getElementById('departmentModal').classList.remove('hidden');
    }
    
    function manageMembers(deptId, deptName) {
        document.getElementById('memberDeptId').value = deptId;
        document.getElementById('deptNameDisplay').textContent = deptName;
        document.getElementById('membersModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('departmentModal').classList.add('hidden');
    }
    
    function closeMembersModal() {
        document.getElementById('membersModal').classList.add('hidden');
    }
    </script>
    
    <!-- Add this script at the bottom of departments.php -->
    <script>
    <?php if ($show_create_modal): ?>
    // Auto-open create modal if coming from project
    document.addEventListener('DOMContentLoaded', function() {
        openCreateModal();
        
        // Pre-select the project if provided
        <?php if ($from_project_id): ?>
        document.getElementById('projectId').value = '<?php echo $from_project_id; ?>';
        
        // Update modal title to show project context
        document.getElementById('modalTitle').innerHTML = 
            'Create New Department for <?php echo htmlspecialchars($from_project_name); ?>';
        <?php endif; ?>
    });
    <?php endif; ?>
    
    <?php if ($show_edit_modal): ?>
    // Auto-open edit modal
    document.addEventListener('DOMContentLoaded', function() {
        <?php
        // Get department data
        $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
        $stmt->execute([$dept_id]);
        $dept = $stmt->fetch();
        ?>
        editDepartment(<?php echo json_encode($dept); ?>);
    });
    <?php endif; ?>
    
    <?php if ($show_members_modal): ?>
    // Auto-open members modal
    document.addEventListener('DOMContentLoaded', function() {
        <?php
        $stmt = $pdo->prepare("SELECT department_name FROM departments WHERE id = ?");
        $stmt->execute([$dept_id]);
        $dept = $stmt->fetch();
        ?>
        manageMembers(<?php echo $dept_id; ?>, "<?php echo htmlspecialchars($dept['department_name']); ?>");
    });
    <?php endif; ?>
    
    // Add back button if coming from project
    <?php if ($return_project): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Add back to project button
        const header = document.querySelector('.px-6.py-4.border-b');
        if (header) {
            const backBtn = document.createElement('a');
            backBtn.href = 'project_details.php?id=<?php echo $return_project; ?>#departments-tab';
            backBtn.className = 'bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 mr-2';
            backBtn.innerHTML = '<i class="fas fa-arrow-left mr-2"></i>Back to Project';
            header.querySelector('.flex').insertBefore(backBtn, header.querySelector('.flex').firstChild);
        }
    });
    <?php endif; ?>
    
    // Update form submission to redirect back to project if needed
    const originalFormSubmit = document.querySelector('#departmentModal form');
    if (originalFormSubmit) {
        originalFormSubmit.addEventListener('submit', function(e) {
            <?php if ($return_project): ?>
            // Add hidden field to redirect back to project
            const returnField = document.createElement('input');
            returnField.type = 'hidden';
            returnField.name = 'return_project';
            returnField.value = '<?php echo $return_project; ?>';
            this.appendChild(returnField);
            <?php endif; ?>
        });
    }
    </script>
</body>
</html>