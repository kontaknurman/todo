<?php
// api/create_task.php - Create new task with project/department support
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$employee = getCurrentEmployee();
$pdo = getDB();

// Get POST data
$taskType = $_POST['task_type'] ?? 'personal';
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$employeeId = (int)($_POST['employee_id'] ?? $employee['id']);
$dueDate = $_POST['due_date'] ?? '';
$dueTime = $_POST['due_time'] ?? '';
$timeLimitHours = (int)($_POST['time_limit_hours'] ?? 0);
$timeLimitMinutes = (int)($_POST['time_limit_minutes'] ?? 0);
$points = (int)($_POST['points'] ?? 10);
$priority = $_POST['priority'] ?? 'medium';

// Project task specific fields
$projectId = $_POST['project_id'] ?? null;
$departmentId = $_POST['department_id'] ?? null;
$isProjectTask = ($taskType === 'project' && $projectId && $departmentId);

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
    echo json_encode(['success' => false, 'message' => 'Please set a time limit']);
    exit;
}

// Check if user can create project tasks
if ($isProjectTask) {
    $stmt = $pdo->prepare("SELECT role FROM employees WHERE id = ?");
    $stmt->execute([$employee['id']]);
    $user = $stmt->fetch();
    
    if (!in_array($user['role'], ['admin', 'manager'])) {
        echo json_encode(['success' => false, 'message' => 'Only managers can create project tasks']);
        exit;
    }
}

// Generate confirmation token
$confirmationToken = md5(uniqid($title . time(), true));

$pdo->beginTransaction();

try {
    // Insert task with is_project_task flag
    $stmt = $pdo->prepare("
        INSERT INTO tasks (
            title, description, employee_id, due_date, due_time,
            time_limit_hours, time_limit_minutes, points,
            confirmation_token, status,
            is_project_task, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
    ");
    
    $success = $stmt->execute([
        $title, $description, $employeeId, $dueDate, $dueTime,
        $timeLimitHours, $timeLimitMinutes, $points,
        $confirmationToken,
        $isProjectTask ? 1 : 0,
        $employee['id']
    ]);
    
    if ($success) {
        $taskId = $pdo->lastInsertId();
        
        // If project task, create project_tasks entry
        if ($isProjectTask) {
            $stmt = $pdo->prepare("
                INSERT INTO project_tasks (
                    project_id, task_id, task_name, assigned_to,
                    priority, status, due_date, department_id
                ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
            ");
            
            $stmt->execute([
                $projectId, $taskId, $title, $employeeId,
                $priority, $dueDate, $departmentId
            ]);
            
            // Log in admin logs if available
            if (function_exists('logAdminActivity')) {
                logAdminActivity('create_project_task', 'task', $taskId, 
                    "Created project task: $title for project ID: $projectId");
            }
        }
        
        $pdo->commit();
        
        // Send notification
        sendNotification(
            $employeeId,
            $taskId,
            'new_task',
            ($isProjectTask ? 'New Project Task: ' : 'New Personal Task: ') . $title,
            sprintf(
                'You have been assigned a new %s task. Due: %s %s. Time limit: %dh %dm. Points: %d',
                $isProjectTask ? 'project' : 'personal',
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
            'task_type' => $isProjectTask ? 'project' : 'personal',
            'confirmation_token' => $confirmationToken
        ]);
    } else {
        throw new Exception('Error creating task');
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>