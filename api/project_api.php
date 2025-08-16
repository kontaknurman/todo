<?php
// admin/api/project_api.php - API endpoints for project operations
require_once '../config.php';
requireAdminLogin();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$pdo = getDB();
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'add_member':
            $project_id = (int)$input['project_id'];
            $employee_id = (int)$input['employee_id'];
            $role = $input['role'] ?? 'Member';
            
            // Check if already member
            $check = $pdo->prepare("SELECT id FROM project_members WHERE project_id = ? AND employee_id = ?");
            $check->execute([$project_id, $employee_id]);
            
            if ($check->fetch()) {
                // Reactivate if exists
                $stmt = $pdo->prepare("
                    UPDATE project_members 
                    SET is_active = TRUE, role_in_project = ?, removed_date = NULL 
                    WHERE project_id = ? AND employee_id = ?
                ");
                $stmt->execute([$role, $project_id, $employee_id]);
            } else {
                // Insert new
                $stmt = $pdo->prepare("
                    INSERT INTO project_members (project_id, employee_id, role_in_project, assigned_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$project_id, $employee_id, $role, $_SESSION[ADMIN_SESSION_PREFIX . 'id']]);
            }
            
            logAdminActivity('add_project_member', 'project', $project_id, "Added member ID: $employee_id");
            $response = ['success' => true, 'message' => 'Member added successfully'];
            break;
            
        case 'remove_member':
            $project_id = (int)$input['project_id'];
            $member_id = (int)$input['member_id'];
            
            $stmt = $pdo->prepare("
                UPDATE project_members 
                SET is_active = FALSE, removed_date = CURDATE() 
                WHERE project_id = ? AND employee_id = ?
            ");
            $stmt->execute([$project_id, $member_id]);
            
            logAdminActivity('remove_project_member', 'project', $project_id, "Removed member ID: $member_id");
            $response = ['success' => true, 'message' => 'Member removed successfully'];
            break;
            
        case 'update_member_role':
            $project_id = (int)$input['project_id'];
            $member_id = (int)$input['member_id'];
            $role = $input['role'];
            
            $stmt = $pdo->prepare("
                UPDATE project_members 
                SET role_in_project = ? 
                WHERE project_id = ? AND employee_id = ?
            ");
            $stmt->execute([$role, $project_id, $member_id]);
            
            logAdminActivity('update_member_role', 'project', $project_id, "Updated role for member ID: $member_id");
            $response = ['success' => true, 'message' => 'Role updated successfully'];
            break;
            
        case 'assign_department':
            $project_id = (int)$input['project_id'];
            $department_id = (int)$input['department_id'];
            
            $stmt = $pdo->prepare("UPDATE departments SET project_id = ? WHERE id = ?");
            $stmt->execute([$project_id, $department_id]);
            
            logAdminActivity('assign_department', 'project', $project_id, "Assigned department ID: $department_id");
            $response = ['success' => true, 'message' => 'Department assigned successfully'];
            break;
            
        case 'create_department':
            $project_id = (int)$input['project_id'];
            $name = trim($input['name']);
            $code = trim($input['code']);
            $location = trim($input['location'] ?? '');
            
            $stmt = $pdo->prepare("
                INSERT INTO departments (department_name, department_code, location, project_id, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $code, $location, $project_id, $_SESSION[ADMIN_SESSION_PREFIX . 'id']]);
            
            logAdminActivity('create_department', 'department', $pdo->lastInsertId(), "Created for project ID: $project_id");
            $response = ['success' => true, 'message' => 'Department created successfully'];
            break;
            
        case 'unassign_department':
            $department_id = (int)$input['department_id'];
            
            $stmt = $pdo->prepare("UPDATE departments SET project_id = NULL WHERE id = ?");
            $stmt->execute([$department_id]);
            
            logAdminActivity('unassign_department', 'department', $department_id);
            $response = ['success' => true, 'message' => 'Department unassigned successfully'];
            break;
            
        case 'add_task':
            $project_id = (int)$input['project_id'];
            $task_name = trim($input['task_name']);
            $assigned_to = $input['assigned_to'] ? (int)$input['assigned_to'] : null;
            $priority = $input['priority'] ?? 'medium';
            $due_date = $input['due_date'] ?: null;
            
            $stmt = $pdo->prepare("
                INSERT INTO project_tasks (project_id, task_name, assigned_to, priority, due_date, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$project_id, $task_name, $assigned_to, $priority, $due_date]);
            
            logAdminActivity('add_project_task', 'project', $project_id, "Added task: $task_name");
            $response = ['success' => true, 'message' => 'Task added successfully'];
            break;
            
        case 'update_task_status':
            $task_id = (int)$input['task_id'];
            $status = $input['status'];
            
            $stmt = $pdo->prepare("
                UPDATE project_tasks 
                SET status = ?, completed_date = CASE WHEN ? = 'completed' THEN CURDATE() ELSE NULL END
                WHERE id = ?
            ");
            $stmt->execute([$status, $status, $task_id]);
            
            logAdminActivity('update_task_status', 'task', $task_id, "Changed to: $status");
            $response = ['success' => true, 'message' => 'Task status updated'];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
}

echo json_encode($response);
?>