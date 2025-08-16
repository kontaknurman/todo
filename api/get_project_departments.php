<?php
// api/get_project_departments.php - Get departments for a specific project
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$project_id = (int)($_GET['project_id'] ?? 0);

if (!$project_id) {
    echo json_encode(['success' => false, 'message' => 'Project ID required']);
    exit;
}

$pdo = getDB();

// Get departments assigned to this project
$stmt = $pdo->prepare("
    SELECT id, department_name, department_code, location, status
    FROM departments
    WHERE project_id = ? AND status = 'active'
    ORDER BY department_name
");
$stmt->execute([$project_id]);
$departments = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'departments' => $departments
]);
?>