<?php
// api/update_task_status.php - Update task status
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$employee = getCurrentEmployee();
$pdo = getDB();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$taskId = (int)($input['task_id'] ?? 0);
$newStatus = $input['status'] ?? '';
$notes = $input['notes'] ?? '';

// Validate input
$validStatuses = ['pending', 'ongoing', 'finished', 'dropped'];
if (!$taskId || !in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Get task details
$stmt = $pdo->prepare("
    SELECT * FROM tasks 
    WHERE id = ? AND employee_id = ?
");
$stmt->execute([$taskId, $employee['id']]);
$task = $stmt->fetch();

if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Task not found']);
    exit;
}

// Calculate points
$pointsEarned = 0;
if ($newStatus === 'finished') {
    $pointsEarned = calculatePoints($task, $newStatus);
} elseif ($newStatus === 'dropped') {
    $pointsEarned = POINTS_DROPPED_PENALTY;
}

// Begin transaction
$pdo->beginTransaction();

try {
    // Update task status
    $stmt = $pdo->prepare("
        UPDATE tasks 
        SET status = ?,
            started_at = CASE 
                WHEN ? = 'ongoing' AND started_at IS NULL THEN NOW() 
                ELSE started_at 
            END,
            completed_at = CASE 
                WHEN ? IN ('finished', 'dropped') THEN NOW() 
                ELSE completed_at 
            END,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $newStatus, $newStatus, $taskId]);
    
    // Calculate time taken
    $timeTaken = 0;
    if ($task['started_at'] && in_array($newStatus, ['finished', 'dropped'])) {
        $started = new DateTime($task['started_at']);
        $completed = new DateTime();
        $diff = $completed->diff($started);
        $timeTaken = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    }
    
    // Add to history
    $stmt = $pdo->prepare("
        INSERT INTO task_history 
        (task_id, employee_id, status_changed_from, status_changed_to, 
         points_earned, time_taken_minutes, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $taskId,
        $employee['id'],
        $task['status'],
        $newStatus,
        $pointsEarned,
        $timeTaken,
        $notes
    ]);
    
    // Update employee points
    if ($newStatus === 'finished' || $newStatus === 'dropped') {
        $stmt = $pdo->prepare("
            UPDATE employees 
            SET total_points = total_points + ? 
            WHERE id = ?
        ");
        $stmt->execute([$pointsEarned, $employee['id']]);
    }
    
    // Create notification
    $notificationTitle = 'Task Status Updated';
    $notificationMessage = sprintf(
        'Task "%s" status changed from %s to %s. %s',
        $task['title'],
        $task['status'],
        $newStatus,
        $pointsEarned > 0 ? "You earned $pointsEarned points!" : 
        ($pointsEarned < 0 ? "You lost " . abs($pointsEarned) . " points." : "")
    );
    
    sendNotification(
        $employee['id'],
        $taskId,
        'status_change',
        $notificationTitle,
        $notificationMessage
    );
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Task status updated successfully',
        'points' => $pointsEarned,
        'new_status' => $newStatus
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error updating task: ' . $e->getMessage()
    ]);
}
?>