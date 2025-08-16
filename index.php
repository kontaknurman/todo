<?php
// index.php - Dashboard
require_once 'config.php';
requireLogin();

$employee = getCurrentEmployee();
checkOverdueTasks();

$pdo = getDB();

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) as ongoing,
        SUM(CASE WHEN status = 'finished' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue
    FROM tasks 
    WHERE employee_id = ?
");
$stmt->execute([$employee['id']]);
$stats = $stmt->fetch();

// Get recent tasks
$stmt = $pdo->prepare("
    SELECT * FROM tasks 
    WHERE employee_id = ? 
    ORDER BY 
        CASE 
            WHEN status = 'overdue' THEN 1
            WHEN status = 'ongoing' THEN 2
            WHEN status = 'pending' THEN 3
            ELSE 4
        END,
        due_date ASC, due_time ASC
    LIMIT 5
");
$stmt->execute([$employee['id']]);
$recentTasks = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require 'layout-header.php';
?>

<!-- Welcome Section -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h1 class="text-3xl font-bold text-gray-800">
        Welcome back, <?php echo htmlspecialchars($employee['name']); ?>! 👋
    </h1>
    <p class="text-gray-600 mt-2">Here's your task overview for today</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Tasks</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
            </div>
            <i class="fas fa-tasks text-purple-500 text-3xl"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Pending</p>
                <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p>
            </div>
            <i class="fas fa-clock text-yellow-500 text-3xl"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Ongoing</p>
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['ongoing']; ?></p>
            </div>
            <i class="fas fa-spinner text-blue-500 text-3xl"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Completed</p>
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['completed']; ?></p>
            </div>
            <i class="fas fa-check-circle text-green-500 text-3xl"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Overdue</p>
                <p class="text-3xl font-bold text-red-600"><?php echo $stats['overdue']; ?></p>
            </div>
            <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
        </div>
    </div>
</div>

<!-- Recent Tasks -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Recent Tasks</h2>
        <a href="tasks.php" class="text-purple-600 hover:text-purple-700 font-medium">
            View All <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    
    <?php if (empty($recentTasks)): ?>
    <div class="text-center py-8">
        <i class="fas fa-clipboard-list text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500">No tasks yet. Create your first task!</p>
        <a href="create_task.php" class="mt-4 inline-block bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
            <i class="fas fa-plus mr-2"></i> Create Task
        </a>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($recentTasks as $task): ?>
            <?php $timeRemaining = getTimeRemaining($task['due_date'], $task['due_time']); ?>
            <div class="border-l-4 <?php echo $task['status'] == 'overdue' ? 'border-red-500' : ($task['status'] == 'ongoing' ? 'border-blue-500' : 'border-gray-300'); ?> bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($task['title']); ?></h3>
                        <p class="text-gray-600 text-sm mt-1"><?php echo htmlspecialchars($task['description'] ?: 'No description'); ?></p>
                        <div class="flex items-center space-x-4 mt-3 text-sm">
                            <span class="<?php echo $timeRemaining['class']; ?>">
                                <i class="fas fa-clock mr-1"></i> <?php echo $timeRemaining['text']; ?>
                            </span>
                            <span class="text-gray-500">
                                <i class="fas fa-hourglass-half mr-1"></i> <?php echo formatTimeLimit($task['time_limit_hours'], $task['time_limit_minutes']); ?>
                            </span>
                            <span class="text-gray-500">
                                <i class="fas fa-star mr-1"></i> <?php echo $task['points']; ?> pts
                            </span>
                        </div>
                    </div>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full 
                        <?php echo $task['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                            ($task['status'] == 'ongoing' ? 'bg-blue-100 text-blue-800' : 
                            ($task['status'] == 'finished' ? 'bg-green-100 text-green-800' : 
                            ($task['status'] == 'overdue' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'))); ?>">
                        <?php echo ucfirst($task['status']); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</div><!-- End container -->

<script src="app.js"></script>
</body>
</html>