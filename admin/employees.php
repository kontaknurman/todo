<?php
// admin/employees.php - Employee management (following existing design)
require_once 'config.php';
requireAdminLogin();

// Only allow admin and manager roles to view this page
if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] !== 'admin' && $_SESSION[ADMIN_SESSION_PREFIX . 'role'] !== 'manager') {
    $_SESSION['error'] = 'Access denied. Insufficient permissions.';
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
    $submittedToken = $_POST['csrf_token'] ?? '';
    
    if ($submittedToken !== $csrfToken) {
        $_SESSION['error'] = 'Security validation failed';
    } else {
        switch ($action) {
            case 'add':
                if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'admin') {
                    $name = trim($_POST['name']);
                    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $role = $_POST['role'];
                    $department = trim($_POST['department']);
                    $whatsapp = trim($_POST['whatsapp_number']);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO employees (name, email, password, role, department, whatsapp_number)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    if ($stmt->execute([$name, $email, $password, $role, $department, $whatsapp])) {
                        $_SESSION['success'] = 'Employee added successfully';
                        logAdminActivity('add_employee', 'employee', $pdo->lastInsertId(), "Added: $name");
                    }
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                
                // Check if manager is trying to edit an admin
                if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'manager') {
                    $checkStmt = $pdo->prepare("SELECT role FROM employees WHERE id = ?");
                    $checkStmt->execute([$id]);
                    $targetEmployee = $checkStmt->fetch();
                    
                    if ($targetEmployee && $targetEmployee['role'] === 'admin') {
                        $_SESSION['error'] = 'Managers cannot edit administrator accounts';
                        header('Location: employees.php');
                        exit;
                    }
                }
                
                $name = trim($_POST['name']);
                $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
                $role = $_POST['role'];
                $department = trim($_POST['department']);
                $whatsapp = trim($_POST['whatsapp_number']);
                
                // Managers cannot change roles to admin
                if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'manager' && $role === 'admin') {
                    $_SESSION['error'] = 'Managers cannot promote users to administrator';
                    header('Location: employees.php');
                    exit;
                }
                
                $sql = "UPDATE employees SET name=?, email=?, role=?, department=?, whatsapp_number=?";
                $params = [$name, $email, $role, $department, $whatsapp];
                
                if (!empty($_POST['password'])) {
                    $sql .= ", password=?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE id=?";
                $params[] = $id;
                
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    $_SESSION['success'] = 'Employee updated successfully';
                    logAdminActivity('edit_employee', 'employee', $id, "Updated: $name");
                }
                break;
                
            case 'delete':
                if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'admin') {
                    $id = (int)$_POST['id'];
                    if ($id != $_SESSION[ADMIN_SESSION_PREFIX . 'id']) {
                        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
                        if ($stmt->execute([$id])) {
                            $_SESSION['success'] = 'Employee deleted successfully';
                            logAdminActivity('delete_employee', 'employee', $id);
                        }
                    } else {
                        $_SESSION['error'] = 'Cannot delete your own account';
                    }
                } else {
                    $_SESSION['error'] = 'Only administrators can delete employees';
                }
                break;
        }
    }
    header('Location: employees.php');
    exit;
}

// Get all employees
$search = $_GET['search'] ?? '';
$filter_role = $_GET['role'] ?? '';

$query = "SELECT * FROM employees WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR department LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($filter_role) {
    $query .= " AND role = ?";
    $params[] = $filter_role;
}

$query .= " ORDER BY role='admin' DESC, role='manager' DESC, name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$employees = $stmt->fetchAll();

logAdminActivity('view_employees');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-users mr-2"></i>Employee Management
                </h1>
                <?php if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'admin'): ?>
                <button onclick="openAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Add Employee
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Search and Filter -->
            <div class="px-6 py-4 bg-gray-50 border-b">
                <form method="GET" class="flex gap-4">
                    <input type="text" name="search" placeholder="Search employees..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="flex-1 px-4 py-2 border rounded-lg">
                    <select name="role" class="px-4 py-2 border rounded-lg">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="manager" <?php echo $filter_role === 'manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="employee" <?php echo $filter_role === 'employee' ? 'selected' : ''; ?>>Employee</option>
                    </select>
                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <!-- Employee Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">WhatsApp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($emp['name']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td class="px-6 py-4"><?php echo getRoleBadge($emp['role']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($emp['department'] ?? '-'); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($emp['whatsapp_number'] ?? '-'); ?></td>
                            <td class="px-6 py-4"><?php echo $emp['total_points']; ?></td>
                            <td class="px-6 py-4">
                                <?php 
                                // Managers cannot edit admin accounts
                                $canEdit = true;
                                if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'manager' && $emp['role'] === 'admin') {
                                    $canEdit = false;
                                }
                                ?>
                                
                                <?php if ($canEdit): ?>
                                <button onclick='editEmployee(<?php echo json_encode($emp); ?>)' 
                                        class="text-blue-600 hover:text-blue-800 mr-2" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php else: ?>
                                <span class="text-gray-400 mr-2" title="Cannot edit admin accounts">
                                    <i class="fas fa-edit"></i>
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'admin' && $emp['id'] != $_SESSION[ADMIN_SESSION_PREFIX . 'id']): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this employee?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
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
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="employeeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 id="modalTitle" class="text-xl font-bold mb-4">Add Employee</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="employeeId">
                
                <div class="space-y-4">
                    <input type="text" name="name" id="name" placeholder="Name" required
                           class="w-full px-4 py-2 border rounded-lg">
                    <input type="email" name="email" id="email" placeholder="Email" required
                           class="w-full px-4 py-2 border rounded-lg">
                    <input type="password" name="password" id="password" placeholder="Password"
                           class="w-full px-4 py-2 border rounded-lg">
                    <select name="role" id="role" class="w-full px-4 py-2 border rounded-lg">
                        <option value="employee">Employee</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                    <input type="text" name="department" id="department" placeholder="Department"
                           class="w-full px-4 py-2 border rounded-lg">
                    <input type="text" name="whatsapp_number" id="whatsapp" placeholder="WhatsApp Number"
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add Employee';
        document.getElementById('formAction').value = 'add';
        document.getElementById('employeeModal').classList.remove('hidden');
        document.getElementById('password').required = true;
    }
    
    function editEmployee(emp) {
        // Check if manager is trying to edit an admin
        <?php if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'manager'): ?>
        if (emp.role === 'admin') {
            alert('Managers cannot edit administrator accounts');
            return;
        }
        <?php endif; ?>
        
        document.getElementById('modalTitle').textContent = 'Edit Employee';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('employeeId').value = emp.id;
        document.getElementById('name').value = emp.name;
        document.getElementById('email').value = emp.email;
        document.getElementById('role').value = emp.role;
        document.getElementById('department').value = emp.department || '';
        document.getElementById('whatsapp').value = emp.whatsapp_number || '';
        document.getElementById('password').required = false;
        document.getElementById('password').placeholder = 'Leave blank to keep current';
        
        // Disable admin option for managers
        <?php if ($_SESSION[ADMIN_SESSION_PREFIX . 'role'] === 'manager'): ?>
        var roleSelect = document.getElementById('role');
        for (var i = 0; i < roleSelect.options.length; i++) {
            if (roleSelect.options[i].value === 'admin') {
                roleSelect.options[i].disabled = true;
                roleSelect.options[i].text = 'Admin (Restricted)';
            }
        }
        <?php endif; ?>
        
        document.getElementById('employeeModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('employeeModal').classList.add('hidden');
        document.querySelector('#employeeModal form').reset();
    }
    </script>
</body>
</html>