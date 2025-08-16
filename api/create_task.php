<?php
// api/create_task.php - Create new task via API
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$employee = getCurrentEmployee();
$pdo = getDB();

// Get POST data
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$employeeId = (int)($_POST['employee_id'] ?? $employee['id']);
$dueDate = $_POST['due_date'] ?? '';
$dueTime = $_POST['due_time'] ?? '';
$timeLimitHours = (int)($_POST['time_limit_hours'] ?? 0);
$timeLimitMinutes = (int)($_POST['time_limit_minutes'] ?? 0);
$points = (int)($_POST['points'] ?? 10);
$repeatDays = $_POST['repeat_days'] ?? 'none';
$customDays = $_POST['custom_days'] ?? '';

// Validation
if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Task title is required']);
    exit;
}

if (empty($dueDate)) {
    echo json_encode(['success' => false, 'message' => 'Due date is required']);
    exit;
}

if ($timeLimitHours == 0 && $timeLimitMinutes == 0) {
    echo json_encode(['success' => false, 'message' => 'Please set a time limit for the task']);
    exit;
}

// Generate confirmation token
$confirmationToken = md5(uniqid($title . time(), true));

try {
    // Insert task
    $stmt = $pdo->prepare("
        INSERT INTO tasks (
            title, description, employee_id, due_date, due_time,
            time_limit_hours, time_limit_minutes, points,
            repeat_days, custom_days, confirmation_token, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $success = $stmt->execute([
        $title, $description, $employeeId, $dueDate, $dueTime,
        $timeLimitHours, $timeLimitMinutes, $points,
        $repeatDays, $customDays, $confirmationToken
    ]);
    
    if ($success) {
        $taskId = $pdo->lastInsertId();
        
        // Send notification
        sendNotification(
            $employeeId,
            $taskId,
            'new_task',
            'New Task Assigned: ' . $title,
            sprintf(
                'You have been assigned a new task. Due: %s %s. Time limit: %dh %dm. Points: %d',
                $dueDate,
                $dueTime ?: '',
                $timeLimitHours,
                $timeLimitMinutes,
                $points
            )
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Task created successfully',
            'task_id' => $taskId,
            'confirmation_token' => $confirmationToken
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating task']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
