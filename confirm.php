<?php
// confirm.php - Task confirmation page (public access via token)
require_once 'config.php';

$message = '';
$messageType = '';
$task = null;

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'Invalid confirmation link';
    $messageType = 'error';
} else {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT t.*, e.name as employee_name, e.whatsapp_number 
        FROM tasks t
        JOIN employees e ON t.employee_id = e.id
        WHERE t.confirmation_token = ?
    ");
    $stmt->execute([$token]);
    $task = $stmt->fetch();
    
    if (!$task) {
        $message = 'Task not found or invalid token';
        $messageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $task) {
    $new_status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if (!in_array($new_status, ['ongoing', 'finished', 'dropped'])) {
        $message = 'Invalid status';
        $messageType = 'error';
    } else {
        $points_earned = calculatePoints($task, $new_status);
        
        $pdo->beginTransaction();
        try {
            // Update task
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET status = ?, 
                    started_at = CASE WHEN ? = 'ongoing' AND started_at IS NULL THEN NOW() ELSE started_at END,
                    completed_at = CASE WHEN ? IN ('finished', 'dropped') THEN NOW() ELSE completed_at END
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $new_status, $new_status, $task['id']]);
            
            // Add history
            $stmt = $pdo->prepare("
                INSERT INTO task_history (task_id, employee_id, status_changed_from, status_changed_to, points_earned, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$task['id'], $task['employee_id'], $task['status'], $new_status, $points_earned, $notes]);
            
            // Update points
            if ($new_status === 'finished' || $new_status === 'dropped') {
                $stmt = $pdo->prepare("UPDATE employees SET total_points = total_points + ? WHERE id = ?");
                $stmt->execute([$points_earned, $task['employee_id']]);
            }
            
            $pdo->commit();
            $message = "Status updated! Points: $points_earned";
            $messageType = 'success';
            
            // Refresh task
            $stmt = $pdo->prepare("SELECT t.*, e.name as employee_name FROM tasks t JOIN employees e ON t.employee_id = e.id WHERE t.id = ?");
            $stmt->execute([$task['id']]);
            $task = $stmt->fetch();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Error updating task';
            $messageType = 'error';
        }
    }
}

$timeRemaining = $task ? getTimeRemaining($task['due_date'], $task['due_time']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Confirmation - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-purple-50 to-indigo-100 min-h-screen p-4">
    <div class="max-w-2xl mx-auto mt-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-check-circle text-purple-600 mr-2"></i> Task Confirmation
            </h1>
            
            <?php if ($message): ?>
            <div class="mb-4 p-4 rounded-lg <?php echo $messageType == 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($task): ?>
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h2 class="font-semibold text-lg text-gray-800 mb-3"><?php echo htmlspecialchars($task['title']); ?></h2>
                
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Assigned To:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($task['employee_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Current Status:</span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            <?php echo $task['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                ($task['status'] == 'ongoing' ? 'bg-blue-100 text-blue-800' : 
                                ($task['status'] == 'finished' ? 'bg-green-100 text-green-800' : 
                                ($task['status'] == 'overdue' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'))); ?>">
                            <?php echo ucfirst($task['status']); ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Due Date:</span>
                        <span class="font-medium"><?php echo date('M d, Y', strtotime($task['due_date'])); ?> <?php echo $task['due_time'] ? date('g:i A', strtotime($task['due_time'])) : ''; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Time Limit:</span>
                        <span class="font-medium"><?php echo formatTimeLimit($task['time_limit_hours'], $task['time_limit_minutes']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Time Remaining:</span>
                        <span class="font-medium <?php echo $timeRemaining['class']; ?>"><?php echo $timeRemaining['text']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Points:</span>
                        <span class="font-medium text-purple-600"><?php echo $task['points']; ?> points</span>
                    </div>
                </div>
            </div>
            
            <?php if (!in_array($task['status'], ['finished', 'dropped'])): ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Update Status</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Select Status</option>
                        <?php if ($task['status'] === 'pending'): ?>
                        <option value="ongoing">Start Working</option>
                        <?php endif; ?>
                        <option value="finished">Mark Complete</option>
                        <option value="dropped">Drop Task</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-save mr-2"></i> Update Status
                </button>
            </form>
            <?php else: ?>
            <div class="bg-blue-50 text-blue-700 p-4 rounded-lg">
                <i class="fas fa-info-circle mr-2"></i>
                This task has been <?php echo $task['status']; ?> and cannot be modified.
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-exclamation-triangle text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-600 mb-4">The task could not be found. Please check your link.</p>
            </div>
            <?php endif; ?>
            
            <div class="mt-6 text-center">
                <a href="login.php" class="text-purple-600 hover:text-purple-700">
                    <i class="fas fa-sign-in-alt mr-1"></i> Go to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>