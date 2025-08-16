<?php
// api/get_project_departments_with_permission.php - Fixed for project managers
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$project_id = (int)($_GET['project_id'] ?? 0);
$user_id = (int)($_GET['user_id'] ?? 0);

if (!$project_id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Project ID and User ID required']);
    exit;
}

$pdo = getDB();

// Check user role
$stmt = $pdo->prepare("SELECT role FROM employees WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$isAdmin = ($user['role'] === 'admin');
$isManager = ($user['role'] === 'manager');

// Check if user is PROJECT MANAGER of this project
$stmt = $pdo->prepare("SELECT project_manager_id FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();
$isProjectManager = ($project && $project['project_manager_id'] == $user_id);

// Check if user is member of the project
$stmt = $pdo->prepare("
    SELECT 1 FROM project_members pm 
    WHERE pm.project_id = ? 
    AND pm.employee_id = ? 
    AND pm.is_active = TRUE
");
$stmt->execute([$project_id, $user_id]);
$isProjectMember = $stmt->fetch() !== false;

// Admin, Project Manager, or Project Member can access
if (!$isAdmin && !$isProjectManager && !$isProjectMember) {
    echo json_encode([
        'success' => false, 
        'message' => 'You are not a member of this project'
    ]);
    exit;
}

// Get departments based on user role
if ($isAdmin || $isProjectManager) {
    // Admin and Project Manager can see ALL departments in the project
    $stmt = $pdo->prepare("
        SELECT 
            d.id, 
            d.department_name, 
            d.department_code, 
            d.location, 
            d.status,
            1 as is_member,
            CASE WHEN ? = ? THEN 1 ELSE 0 END as is_project_manager
        FROM departments d
        WHERE d.project_id = ? AND d.status = 'active'
        ORDER BY d.department_name
    ");
    $stmt->execute([$user_id, $project['project_manager_id'], $project_id]);
} else {
    // Regular Manager (not project manager) can only see departments where they are a member
    $stmt = $pdo->prepare("
        SELECT 
            d.id, 
            d.department_name, 
            d.department_code, 
            d.location, 
            d.status,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM department_members dm 
                    WHERE dm.department_id = d.id 
                    AND dm.employee_id = ? 
                    AND dm.is_active = TRUE
                ) THEN 1 
                ELSE 0 
            END as is_member,
            0 as is_project_manager
        FROM departments d
        WHERE d.project_id = ? 
        AND d.status = 'active'
        AND EXISTS (
            SELECT 1 FROM department_members dm 
            WHERE dm.department_id = d.id 
            AND dm.employee_id = ? 
            AND dm.is_active = TRUE
        )
        ORDER BY d.department_name
    ");
    $stmt->execute([$user_id, $project_id, $user_id]);
}

$departments = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'departments' => $departments,
    'user_role' => $user['role'],
    'is_project_member' => $isProjectMember,
    'is_project_manager' => $isProjectManager
]);
?>