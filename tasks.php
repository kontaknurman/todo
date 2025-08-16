<?php
// tasks.php - Updated to show task types
require_once 'config.php';
requireLogin();

$employee = getCurrentEmployee();
checkOverdueTasks();

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$taskType = $_GET['type'] ?? 'all'; // all, project, personal

$pdo = getDB();

// Build query
$query = "SELECT t.*, 
          CASE WHEN t.is_project_task = 1 THEN 'Project' ELSE 'Personal' END as task_category,
          p.project_name, d.department_name
          FROM tasks t
          LEFT JOIN project_tasks pt ON t.id = pt.task_id
          LEFT JOIN projects p ON pt.project_id = p.id
          LEFT JOIN departments d ON pt.department_id = d.id
          WHERE t.employee_id = ?";
$params = [$employee['id']];

// Filter by task type
if ($taskType === 'project') {
    $query .= " AND t.is_project_task = 1";
} elseif ($taskType === 'personal') {
    $query .= " AND (t.is_project_task = 0 OR t.is_project_task IS NULL)";
}

if ($filter !== 'all') {
    $query .= " AND t.status = ?";
    $params[] = $filter;
}

if ($search) {
    $query .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY 
    CASE 
        WHEN t.status = 'overdue' THEN 1
        WHEN t.status = 'ongoing' THEN 2
        WHEN t.status = 'pending' THEN 3
        ELSE 4
    END,
    t.due_date ASC, t.due_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

$pageTitle = 'My Tasks';
require 'layout-header.php';
?>

<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
        <h1 class="text-2xl font-bold text-gray-800">My Tasks</h1>
        
        <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4 w-full md:w-auto">
            <form method="GET" class="flex space-x-2">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($taskType); ?>">
                <input type="text" name="search" placeholder="Search tasks..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            
            <a href="create_task.php" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-center">
                <i class="fas fa-plus mr-2"></i> New Task
            </a>
        </div>
    </div>
    
    <!-- Task Type Filter -->
    <div class="flex gap-2 mb-4">
        <a href="?type=all&filter=<?php echo $filter; ?>" 
           class="px-4 py-2 rounded-lg <?php echo $taskType === 'all' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-600'; ?>">
            All Tasks
        </a>
        <a href="?type=project&filter=<?php echo $filter; ?>" 
           class="px-4 py-2 rounded-lg <?php echo $taskType === 'project' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'; ?>">
            <i class="fas fa-project-diagram mr-1"></i> Project Tasks
        </a>
        <a href="?type=personal&filter=<?php echo $filter; ?>" 
           class="px-4 py-2 rounded-lg <?php echo $taskType === 'personal' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'; ?>">
            <i class="fas fa-user mr-1"></i> Personal Tasks
        </a>
    </div>
    
    <!-- Status Filter Tabs -->
    <div class="flex flex-wrap gap-2 mb-6">
        <?php
        $filters = [
            'all' => ['label' => 'All', 'icon' => 'list', 'color' => 'gray'],
            'pending' => ['label' => 'Pending', 'icon' => 'clock', 'color' => 'yellow'],
            'ongoing' => ['label' => 'Ongoing', 'icon' => 'spinner', 'color' => 'blue'],
            'finished' => ['label' => 'Completed', 'icon' => 'check-circle', 'color' => 'green'],
            'overdue' => ['label' => 'Overdue', 'icon' => 'exclamation-triangle', 'color' => 'red'],
            'dropped' => ['label' => 'Dropped', 'icon' => 'times-circle', 'color' => 'gray']
        ];
        
        foreach ($filters as $key => $info):
        ?>
        <a href="?filter=<?php echo $key; ?>&type=<?php echo $taskType; ?>" 
           class="px-4 py-2 rounded-lg transition <?php echo $filter === $key ? 
               "bg-{$info['color']}-100 text-{$info['color']}-800 font-semibold" : 
               'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
            <i class="fas fa-<?php echo $info['icon']; ?> mr-1"></i> <?php echo $info['label']; ?>
        </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Tasks List -->
    <?php if (empty($tasks)): ?>
    <div class="text-center py-12">
        <i class="fas fa-clipboard-list text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500 text-lg">No tasks found</p>
    </div>
    <?php else: ?>
    <div class="grid gap-4">
        <?php foreach ($tasks as $task): ?>
            <?php $timeRemaining = getTimeRemaining($task['due_date'], $task['due_time']); ?>
            <div class="border rounded-lg p-4 hover:shadow-md transition 
                <?php echo $task['status'] == 'overdue' ? 'border-red-300 bg-red-50' : 
                    ($task['status'] == 'ongoing' ? 'border-blue-300 bg-blue-50' : 
                    ($task['status'] == 'finished' ? 'border-green-300 bg-green-50' : 'border-gray-200')); ?>">
                
                <div class="flex flex-col md:flex-row justify-between">
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-semibold text-lg text-gray-800">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </h3>
                                
                                <!-- Show project/department info for project tasks -->
                                <?php if ($task['is_project_task'] && $task['project_name']): ?>
                                <div class="text-sm text-gray-500 mt-1">
                                    <i class="fas fa-project-diagram mr-1"></i>
                                    <?php echo htmlspecialchars($task['project_name']); ?>
                                    <?php if ($task['department_name']): ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-building mr-1"></i>
                                        <?php echo htmlspecialchars($task['department_name']); ?>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <p class="text-gray-600 text-sm mt-1">
                                    <?php echo htmlspecialchars($task['description'] ?: 'No description'); ?>
                                </p>
                            </div>
                            
                            <div class="flex items-center gap-2 ml-2">
                                <span class="px-2 py-1 text-xs rounded-full <?php 
                                    echo $task['is_project_task'] ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo $task['task_category']; ?>
                                </span>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $task['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($task['status'] == 'ongoing' ? 'bg-blue-100 text-blue-800' : 
                                        ($task['status'] == 'finished' ? 'bg-green-100 text-green-800' : 
                                        ($task['status'] == 'overdue' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'))); ?>">
                                    <?php echo ucfirst($task['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Task details... (rest of the existing task display code) -->
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</div>
<script src="app.js"></script>
</body>
</html>