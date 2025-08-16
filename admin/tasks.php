<?php
// admin/tasks.php - Admin task management
require_once 'config.php';
requireAdminLogin();
requirePermission('tasks', 'view');

$pdo = getDB();
$admin = getCurrentAdmin();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasPermission('tasks', 'edit')) {
    $action = $_POST['action'] ?? '';
    $taskId = (int)($_POST['task_id'] ?? 0);
    
    switch ($action) {
        case 'update_status':
            $newStatus = $_POST['status'];
            $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $taskId]);
            $_SESSION['success'] = 'Task status updated';
            logAdminActivity('update_task_status', 'task', $taskId, "Changed status to: $newStatus");
            break;
            
        case 'delete':
            if (hasPermission('tasks', 'delete')) {
                $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                $stmt->execute([$taskId]);
                $_SESSION['success'] = 'Task deleted';
                logAdminActivity('delete_task', 'task', $taskId);
            }
            break;
            
        case 'reassign':
            $newEmployeeId = (int)$_POST['employee_id'];
            $stmt = $pdo->prepare("UPDATE tasks SET employee_id = ? WHERE id = ?");
            $stmt->execute([$newEmployeeId, $taskId]);
            $_SESSION['success'] = 'Task reassigned';
            logAdminActivity('reassign_task', 'task', $taskId, "Reassigned to employee ID: $newEmployeeId");
            break;
    }
    header('Location: tasks.php');
    exit;
}

// Filters
$search = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterEmployee = $_GET['employee'] ?? '';
$filterDate = $_GET['date'] ?? '';

// Build query
$query = "
    SELECT t.*, e.name as employee_name, e.email as employee_email, e.department
    FROM tasks t
    LEFT JOIN employees e ON t.employee_id = e.id
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($filterStatus) {
    $query .= " AND t.status = ?";
    $params[] = $filterStatus;
}

if ($filterEmployee) {
    $query .= " AND t.employee_id = ?";
    $params[] = $filterEmployee;
}

if ($filterDate) {
    $query .= " AND DATE(t.due_date) = ?";
    $params[] = $filterDate;
}

$query .= " ORDER BY t.status = 'overdue' DESC, t.due_date ASC, t.due_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Get employees for filter dropdown
$stmt = $pdo->query("SELECT id, name, department FROM employees ORDER BY name");
$employees = $stmt->fetchAll();

// Task statistics
$stats = [
    'total' => count($tasks),
    'overdue' => count(array_filter($tasks, fn($t) => $t['status'] == 'overdue')),
    'pending' => count(array_filter($tasks, fn($t) => $t['status'] == 'pending')),
    'ongoing' => count(array_filter($tasks, fn($t) => $t['status'] == 'ongoing')),
    'finished' => count(array_filter($tasks, fn($t) => $t['status'] == 'finished'))
];

logAdminActivity('view_tasks');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></div>
                <div class="text-sm text-gray-600">Total Tasks</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-red-600"><?php echo $stats['overdue']; ?></div>
                <div class="text-sm text-gray-600">Overdue</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></div>
                <div class="text-sm text-gray-600">Pending</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-blue-600"><?php echo $stats['ongoing']; ?></div>
                <div class="text-sm text-gray-600">Ongoing</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['finished']; ?></div>
                <div class="text-sm text-gray-600">Completed</div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-tasks mr-2"></i>Task Management
                </h1>
            </div>
            
            <!-- Filters -->
            <div class="px-6 py-4 bg-gray-50 border-b">
                <form method="GET" class="flex flex-wrap gap-3">
                    <input type="text" name="search" placeholder="Search tasks..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="flex-1 min-w-[200px] px-4 py-2 border rounded-lg">
                    
                    <select name="status" class="px-4 py-2 border rounded-lg">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="ongoing" <?php echo $filterStatus === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="finished" <?php echo $filterStatus === 'finished' ? 'selected' : ''; ?>>Finished</option>
                        <option value="overdue" <?php echo $filterStatus === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        <option value="dropped" <?php echo $filterStatus === 'dropped' ? 'selected' : ''; ?>>Dropped</option>
                    </select>
                    
                    <select name="employee" class="px-4 py-2 border rounded-lg">
                        <option value="">All Employees</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" <?php echo $filterEmployee == $emp['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="date" name="date" value="<?php echo $filterDate; ?>"
                           class="px-4 py-2 border rounded-lg">
                    
                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    
                    <a href="tasks.php" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500">
                        <i class="fas fa-times mr-1"></i> Clear
                    </a>
                </form>
            </div>
            
            <!-- Tasks Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time Limit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($tasks as $task): ?>
                        <tr class="<?php echo $task['status'] == 'overdue' ? 'bg-red-50' : ''; ?>">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($task['title']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($task['description'] ?? '', 0, 50)); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm"><?php echo htmlspecialchars($task['employee_name'] ?? 'Unassigned'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($task['department'] ?? ''); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                <?php if ($task['due_time']): ?>
                                    <br><span class="text-xs text-gray-500"><?php echo date('g:i A', strtotime($task['due_time'])); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php echo formatTimeLimit($task['time_limit_hours'], $task['time_limit_minutes']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo $task['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($task['status'] == 'ongoing' ? 'bg-blue-100 text-blue-800' : 
                                        ($task['status'] == 'finished' ? 'bg-green-100 text-green-800' : 
                                        ($task['status'] == 'overdue' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'))); ?>">
                                    <?php echo ucfirst($task['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium"><?php echo $task['points']; ?></td>
                            <td class="px-6 py-4 text-sm">
                                <?php if (hasPermission('tasks', 'edit')): ?>
                                <button onclick="openStatusModal(<?php echo $task['id']; ?>, '<?php echo $task['status']; ?>')"
                                        class="text-blue-600 hover:text-blue-800 mr-2" title="Change Status">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="openReassignModal(<?php echo $task['id']; ?>, <?php echo $task['employee_id'] ?? 'null'; ?>)"
                                        class="text-purple-600 hover:text-purple-800 mr-2" title="Reassign">
                                    <i class="fas fa-user-edit"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (hasPermission('tasks', 'delete')): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this task?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <a href="../confirm.php?token=<?php echo $task['confirmation_token']; ?>" 
                                   target="_blank" class="text-gray-600 hover:text-gray-800 ml-2" title="View Public Link">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (empty($tasks)): ?>
                <div class="text-center py-8 text-gray-500">No tasks found</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Status Modal -->
    <div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-sm">
            <h3 class="text-lg font-bold mb-4">Change Task Status</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="task_id" id="statusTaskId">
                <select name="status" id="statusSelect" class="w-full px-4 py-2 border rounded-lg mb-4">
                    <option value="pending">Pending</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="finished">Finished</option>
                    <option value="dropped">Dropped</option>
                    <option value="overdue">Overdue</option>
                </select>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeStatusModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reassign Modal -->
    <div id="reassignModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-sm">
            <h3 class="text-lg font-bold mb-4">Reassign Task</h3>
            <form method="POST">
                <input type="hidden" name="action" value="reassign">
                <input type="hidden" name="task_id" id="reassignTaskId">
                <select name="employee_id" id="reassignSelect" class="w-full px-4 py-2 border rounded-lg mb-4">
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeReassignModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Reassign</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function openStatusModal(taskId, currentStatus) {
        document.getElementById('statusTaskId').value = taskId;
        document.getElementById('statusSelect').value = currentStatus;
        document.getElementById('statusModal').classList.remove('hidden');
    }
    
    function closeStatusModal() {
        document.getElementById('statusModal').classList.add('hidden');
    }
    
    function openReassignModal(taskId, currentEmployeeId) {
        document.getElementById('reassignTaskId').value = taskId;
        if (currentEmployeeId) {
            document.getElementById('reassignSelect').value = currentEmployeeId;
        }
        document.getElementById('reassignModal').classList.remove('hidden');
    }
    
    function closeReassignModal() {
        document.getElementById('reassignModal').classList.add('hidden');
    }
    </script>
</body>
</html>