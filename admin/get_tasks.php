<?php
// api/get_tasks.php - Get tasks for current employee
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$employee = getCurrentEmployee();
$pdo = getDB();

// Get filter parameter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM tasks WHERE employee_id = ?";
$params = [$employee['id']];

// Apply filter
if ($filter !== 'all' && in_array($filter, ['pending', 'ongoing', 'finished', 'dropped', 'overdue'])) {
    $query .= " AND status = ?";
    $params[] = $filter;
}

// Apply search
if ($search) {
    $query .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Order by priority
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

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) as ongoing,
        SUM(CASE WHEN status = 'finished' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
        SUM(CASE WHEN status = 'dropped' THEN 1 ELSE 0 END) as dropped
    FROM tasks 
    WHERE employee_id = ?
");
$stmt->execute([$employee['id']]);
$stats = $stmt->fetch();

echo json_encode([
    'success' => true,
    'tasks' => $tasks,
    'stats' => $stats,
    'filter' => $filter
]);
?>