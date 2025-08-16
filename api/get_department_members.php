<?php
// api/get_department_members.php - Get members of a specific department
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$department_id = (int)($_GET['department_id'] ?? 0);

if (!$department_id) {
    echo json_encode(['success' => false, 'message' => 'Department ID required']);
    exit;
}

$pdo = getDB();

// Get department members
$stmt = $pdo->prepare("
    SELECT 
        e.id,
        e.name,
        e.email,
        e.role,
        dm.position,
        dm.is_active
    FROM department_members dm
    JOIN employees e ON dm.employee_id = e.id
    WHERE dm.department_id = ? AND dm.is_active = TRUE
    ORDER BY dm.position DESC, e.name
");
$stmt->execute([$department_id]);
$members = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'members' => $members
]);
?>