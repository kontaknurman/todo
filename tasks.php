<?php
// tasks.php - View all tasks
require_once 'config.php';
requireLogin();

$employee = getCurrentEmployee();
checkOverdueTasks();

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$pdo = getDB();

// Build query
$query = "SELECT * FROM tasks WHERE employee_id = ?";
$params = [$employee['id']];

if ($filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $filter;
}

if ($search) {
    $query .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY 
    CASE 
        WHEN status = 'overdue' THEN 1
        WHEN status = 'ongoing' THEN 2
        WHEN status = 'pending' THEN 3
        ELSE 4
    END,
    due_date ASC, due_time ASC";

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
                <input type="text" name="search" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search); ?>"
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
    
    <!-- Filter Tabs -->
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
        <a href="?filter=<?php echo $key; ?>" 
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
        <?php if ($filter !== 'all' || $search): ?>
        <a href="tasks.php" class="mt-4 inline-block text-purple-600 hover:text-purple-700">
            <i class="fas fa-arrow-left mr-1"></i> View all tasks
        </a>
        <?php endif; ?>
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
                                <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($task['title']); ?></h3>
                                <p class="text-gray-600 text-sm mt-1"><?php echo htmlspecialchars($task['description'] ?: 'No description'); ?></p>
                            </div>
                            <span class="ml-2 px-3 py-1 text-xs font-semibold rounded-full 
                                <?php echo $task['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                    ($task['status'] == 'ongoing' ? 'bg-blue-100 text-blue-800' : 
                                    ($task['status'] == 'finished' ? 'bg-green-100 text-green-800' : 
                                    ($task['status'] == 'overdue' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'))); ?>">
                                <?php echo ucfirst($task['status']); ?>
                            </span>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-4 mt-3 text-sm">
                            <span class="text-gray-600">
                                <i class="fas fa-calendar mr-1"></i> 
                                <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                <?php echo $task['due_time'] ? date('g:i A', strtotime($task['due_time'])) : ''; ?>
                            </span>
                            <span class="<?php echo $timeRemaining['class']; ?>">
                                <i class="fas fa-clock mr-1"></i> <?php echo $timeRemaining['text']; ?>
                            </span>
                            <span class="text-gray-600">
                                <i class="fas fa-hourglass-half mr-1"></i> 
                                <?php echo formatTimeLimit($task['time_limit_hours'], $task['time_limit_minutes']); ?>
                            </span>
                            <span class="text-purple-600 font-semibold">
                                <i class="fas fa-star mr-1"></i> <?php echo $task['points']; ?> pts
                            </span>
                            <?php if ($task['repeat_days'] !== 'none'): ?>
                            <span class="text-gray-600">
                                <i class="fas fa-redo mr-1"></i> <?php echo ucfirst($task['repeat_days']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($task['status'] === 'ongoing' && $task['started_at']): ?>
                            <?php
                            $started = new DateTime($task['started_at']);
                            $now = new DateTime();
                            $elapsed = $now->diff($started);
                            $totalMinutes = ($task['time_limit_hours'] * 60) + $task['time_limit_minutes'];
                            $elapsedMinutes = ($elapsed->days * 24 * 60) + ($elapsed->h * 60) + $elapsed->i;
                            $progress = $totalMinutes > 0 ? min(100, round(($elapsedMinutes / $totalMinutes) * 100)) : 0;
                            ?>
                            <div class="mt-3">
                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                    <span>Progress</span>
                                    <span><?php echo $progress; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center space-x-2 mt-4 md:mt-0 md:ml-4">
                        <?php if ($task['status'] === 'pending'): ?>
                        <button onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'ongoing')" 
                                class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition">
                            <i class="fas fa-play mr-1"></i> Start
                        </button>
                        <?php elseif ($task['status'] === 'ongoing'): ?>
                        <button onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'finished')" 
                                class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition">
                            <i class="fas fa-check mr-1"></i> Complete
                        </button>
                        <?php endif; ?>
                        
                        <?php if (!in_array($task['status'], ['finished', 'dropped'])): ?>
                        <button onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'dropped')" 
                                class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition">
                            <i class="fas fa-times mr-1"></i> Drop
                        </button>
                        <?php endif; ?>
                        
                        <a href="confirm.php?token=<?php echo $task['confirmation_token']; ?>" target="_blank"
                           class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700 transition">
                            <i class="fas fa-link mr-1"></i> Link
                        </a>
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