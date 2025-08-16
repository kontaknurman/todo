<?php
// config.php - Configuration and utility functions

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'task_audiensi');
define('DB_USER', 'task_audiensi');
define('DB_PASS', 'fKGkNtM00^k^wopK');

// Application settings
define('APP_NAME', 'Tugas');
define('APP_URL', 'http://task.audiensi.com/');
define('TIMEZONE', 'Indonesia/Jakarta');

// Points configuration
define('POINTS_ON_TIME', 10);
define('POINTS_EARLY_BONUS', 5);
define('POINTS_LATE_PENALTY', -5);
define('POINTS_DROPPED_PENALTY', -10);

date_default_timezone_set(TIMEZONE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
function getDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Check if logged in
function isLoggedIn() {
    return isset($_SESSION['employee_id']) && !empty($_SESSION['employee_id']);
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Get current employee
function getCurrentEmployee() {
    if (!isLoggedIn()) return null;
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$_SESSION['employee_id']]);
    return $stmt->fetch();
}

// Calculate time remaining
function getTimeRemaining($dueDate, $dueTime = null) {
    $dueDateTime = $dueDate . ' ' . ($dueTime ?: '23:59:59');
    $due = new DateTime($dueDateTime);
    $now = new DateTime();
    
    if ($due < $now) {
        return ['overdue' => true, 'text' => 'Overdue', 'class' => 'text-red-600'];
    }
    
    $interval = $now->diff($due);
    
    if ($interval->days > 0) {
        return [
            'overdue' => false, 
            'text' => $interval->days . ' days',
            'class' => 'text-green-600'
        ];
    } elseif ($interval->h > 0) {
        return [
            'overdue' => false, 
            'text' => $interval->h . 'h ' . $interval->i . 'm',
            'class' => $interval->h < 3 ? 'text-yellow-600' : 'text-green-600'
        ];
    } else {
        return [
            'overdue' => false, 
            'text' => $interval->i . ' minutes',
            'class' => 'text-yellow-600'
        ];
    }
}

// Format time limit
function formatTimeLimit($hours, $minutes) {
    $parts = [];
    if ($hours > 0) $parts[] = $hours . 'h';
    if ($minutes > 0) $parts[] = $minutes . 'm';
    return !empty($parts) ? implode(' ', $parts) : 'No limit';
}

// Calculate points
function calculatePoints($task, $status) {
    $basePoints = $task['points'];
    $finalPoints = $basePoints;
    
    if ($status === 'finished') {
        $dueDateTime = new DateTime($task['due_date'] . ' ' . ($task['due_time'] ?: '23:59:59'));
        $now = new DateTime();
        
        if ($now <= $dueDateTime) {
            $finalPoints += POINTS_EARLY_BONUS;
        } else {
            $finalPoints += POINTS_LATE_PENALTY;
        }
    } elseif ($status === 'dropped') {
        $finalPoints = POINTS_DROPPED_PENALTY;
    }
    
    return max(0, $finalPoints);
}

// Send notification
function sendNotification($employeeId, $taskId, $type, $title, $message) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO notifications (employee_id, task_id, type, title, message)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$employeeId, $taskId, $type, $title, $message]);
}

// Get unread notifications count
function getUnreadCount($employeeId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE employee_id = ? AND is_read = FALSE
    ");
    $stmt->execute([$employeeId]);
    $result = $stmt->fetch();
    return $result['count'];
}

// Check overdue tasks
function checkOverdueTasks() {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("
        UPDATE tasks 
        SET status = 'overdue' 
        WHERE status IN ('pending', 'ongoing') 
        AND TIMESTAMP(due_date, IFNULL(due_time, '23:59:59')) < NOW()
    ");
    $stmt->execute();
}
?>