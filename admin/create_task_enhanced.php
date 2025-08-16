<?php
// api/create_task_enhanced.php - Complete version with repeat task support and active time
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$employee = getCurrentEmployee();
$pdo = getDB();

// Get active time for repeat tasks
$activeTime = $_POST['active_time'] ?? '09:00'; // Default 9 AM

// Get user role
$stmt = $pdo->prepare("SELECT role FROM employees WHERE id = ?");
$stmt->execute([$employee['id']]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$isAdmin = ($user['role'] === 'admin');
$isManager = ($user['role'] === 'manager');

// Get POST data
$taskType = $_POST['task_type'] ?? 'personal';
$projectId = $_POST['project_id'] ?? null;
$departmentId = $_POST['department_id'] ?? null;
$departmentMembers = $_POST['department_members'] ?? [];

// Get repeat options
$repeatType = $_POST['repeat_type'] ?? 'none';
$customDays = $_POST['custom_days'] ?? '';
$repeatUntil = $_POST['repeat_until'] ?? null;
$repeatCount = (int)($_POST['repeat_count'] ?? 0);

// Permission check for project tasks
if ($taskType === 'project' && $projectId && $departmentId) {
    
    // Check if user can create project tasks
    if (!$isAdmin && !$isManager) {
        echo json_encode(['success' => false, 'message' => 'Only managers and admins can create project tasks']);
        exit;
    }
    
    // Check if user is PROJECT MANAGER
    $stmt = $pdo->prepare("SELECT project_manager_id FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();
    $isProjectManager = ($project && $project['project_manager_id'] == $employee['id']);
    
    // For managers (not admin), verify permissions
    if ($isManager && !$isAdmin) {
        
        // Project Manager has full access to their project
        if (!$isProjectManager) {
            // Not project manager, check if member of project
            $stmt = $pdo->prepare("
                SELECT 1 FROM project_members pm 
                WHERE pm.project_id = ? 
                AND pm.employee_id = ? 
                AND pm.is_active = TRUE
            ");
            $stmt->execute([$projectId, $employee['id']]);
            
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'You are not a member of this project']);
                exit;
            }
            
            // Also verify they are member of the department (if not project manager)
            $stmt = $pdo->prepare("
                SELECT 1 FROM department_members dm
                WHERE dm.department_id = ? 
                AND dm.employee_id = ? 
                AND dm.is_active = TRUE
            ");
            $stmt->execute([$departmentId, $employee['id']]);
            
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'You are not a member of this department']);
                exit;
            }
        }
    }
    
    // Verify department belongs to the project
    $stmt = $pdo->prepare("
        SELECT 1 FROM departments 
        WHERE id = ? AND project_id = ? AND status = 'active'
    ");
    $stmt->execute([$departmentId, $projectId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Department does not belong to this project']);
        exit;
    }
    
    // Verify all selected members belong to the department
    if (!empty($departmentMembers)) {
        $placeholders = str_repeat('?,', count($departmentMembers) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as valid_count
            FROM department_members 
            WHERE department_id = ? 
            AND employee_id IN ($placeholders)
            AND is_active = TRUE
        ");
        $params = array_merge([$departmentId], $departmentMembers);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        if ($result['valid_count'] != count($departmentMembers)) {
            echo json_encode(['success' => false, 'message' => 'Some selected members are not in this department']);
            exit;
        }
    }
}

// Security: Define upload directory
$uploadDir = '../uploads/tasks/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Security: Create .htaccess to prevent direct access
$htaccess = $uploadDir . '.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
}

// Allowed file types and max size
$allowedMimeTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/png',
    'image/jpeg',
    'image/gif',
    'text/plain',
    'application/zip',
    'application/x-rar-compressed'
];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Get remaining POST data
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$dueDate = $_POST['due_date'] ?? '';
$dueTime = $_POST['due_time'] ?? '';
$timeLimitHours = (int)($_POST['time_limit_hours'] ?? 0);
$timeLimitMinutes = (int)($_POST['time_limit_minutes'] ?? 0);
$points = (int)($_POST['points'] ?? 10);
$priority = $_POST['priority'] ?? 'medium';
$assignType = $_POST['assign_type'] ?? 'individual';
$employeeId = (int)($_POST['employee_id'] ?? $employee['id']);

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

// Time limit validation removed - sekarang optional
// Hapus validation ini:
// if ($timeLimitHours == 0 && $timeLimitMinutes == 0) {
//     echo json_encode(['success' => false, 'message' => 'Please set a time limit']);
//     exit;
// }

// Function to generate repeat dates dengan active time
function generateRepeatDates($startDate, $repeatType, $customDays, $repeatUntil, $repeatCount, $activeTime) {
    $dates = [];
    $currentDate = new DateTime($startDate);
    $endDate = $repeatUntil ? new DateTime($repeatUntil) : null;
    $count = 0;
    $maxIterations = 365; // Safety limit
    
    while ($count < $maxIterations) {
        if ($repeatCount > 0 && count($dates) >= $repeatCount) {
            break;
        }
        
        // Add next date based on repeat type
        switch ($repeatType) {
            case 'daily':
                $currentDate->modify('+1 day');
                break;
                
            case 'weekly':
                $currentDate->modify('+1 week');
                break;
                
            case 'monthly':
                $currentDate->modify('+1 month');
                break;
                
            case 'custom':
                // Handle custom days logic
                if (!empty($customDays)) {
                    $days = array_map('trim', explode(',', strtolower($customDays)));
                    
                    // Check if it's numeric (monthly dates) or text (weekdays)
                    if (is_numeric($days[0])) {
                        // Monthly on specific dates (1,15,30)
                        $currentDate->modify('+1 day');
                        $found = false;
                        $searchCount = 0;
                        
                        while (!$found && $searchCount < 31) {
                            if (in_array($currentDate->format('j'), $days)) {
                                $found = true;
                            } else {
                                $currentDate->modify('+1 day');
                                $searchCount++;
                            }
                        }
                        
                        if (!$found) {
                            // Move to next month if no valid date found
                            $currentDate->modify('first day of next month');
                            continue;
                        }
                    } else {
                        // Weekly on specific days (mon,wed,fri)
                        $weekdayMap = [
                            'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4,
                            'fri' => 5, 'sat' => 6, 'sun' => 0
                        ];
                        
                        $targetDays = [];
                        foreach ($days as $day) {
                            if (isset($weekdayMap[$day])) {
                                $targetDays[] = $weekdayMap[$day];
                            }
                        }
                        
                        if (!empty($targetDays)) {
                            $currentDate->modify('+1 day');
                            while (!in_array($currentDate->format('w'), $targetDays)) {
                                $currentDate->modify('+1 day');
                            }
                        }
                    }
                }
                break;
                
            default:
                return $dates; // No repeat
        }
        
        // Saat create repeat date, set time ke active time
        if ($activeTime) {
            $timeParts = explode(':', $activeTime);
            $currentDate->setTime((int)$timeParts[0], (int)$timeParts[1]);
        }
        
        // Check end date
        if ($endDate && $currentDate > $endDate) {
            break;
        }
        
        $dates[] = $currentDate->format('Y-m-d');
        $count++;
    }
    
    return $dates;
}

// Determine who to assign tasks to
$assignToEmployees = [];
if ($isProjectTask && !empty($departmentMembers)) {
    $assignToEmployees = $departmentMembers;
} else {
    $assignToEmployees = [$employeeId];
}

$pdo->beginTransaction();

try {
    $createdTasks = [];
    $uploadedFiles = [];
    $totalTasksCreated = 0;
    
    // Create task for each assigned employee
    foreach ($assignToEmployees as $assigneeId) {
        // Generate unique confirmation token
        $confirmationToken = md5(uniqid($title . $assigneeId . time(), true));
        
        // Insert main task
        $stmt = $pdo->prepare("
            INSERT INTO tasks (
                title, description, employee_id, department_id, due_date, due_time,
                time_limit_hours, time_limit_minutes, points,
                confirmation_token, status,
                is_project_task, created_by, parent_task_id, is_repeat, repeat_config
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NULL, 0, ?)
        ");
        
        $repeatConfig = json_encode([
            'type' => $repeatType,
            'custom_days' => $customDays,
            'until' => $repeatUntil,
            'count' => $repeatCount
        ]);
        
        $success = $stmt->execute([
            $title, $description, $assigneeId, 
            $isProjectTask ? $departmentId : null,
            $dueDate, $dueTime,
            $timeLimitHours, $timeLimitMinutes, $points,
            $confirmationToken,
            $isProjectTask ? 1 : 0,
            $employee['id'],
            $repeatType !== 'none' ? $repeatConfig : null
        ]);
        
        if ($success) {
            $taskId = $pdo->lastInsertId();
            $createdTasks[] = $taskId;
            $totalTasksCreated++;
            
            // If project task, create project_tasks entry
            if ($isProjectTask) {
                $stmt = $pdo->prepare("
                    INSERT INTO project_tasks (
                        project_id, task_id, task_name, assigned_to,
                        priority, status, due_date, department_id
                    ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
                ");
                
                $stmt->execute([
                    $projectId, $taskId, $title, $assigneeId,
                    $priority, $dueDate, $departmentId
                ]);
            }
            
            // Handle file uploads (only for first task, then link to others)
            if ($assigneeId == $assignToEmployees[0] && !empty($_FILES['attachments']['name'][0])) {
                foreach ($_FILES['attachments']['name'] as $key => $fileName) {
                    if (empty($fileName)) continue;
                    
                    $fileTmpName = $_FILES['attachments']['tmp_name'][$key];
                    $fileSize = $_FILES['attachments']['size'][$key];
                    $fileError = $_FILES['attachments']['error'][$key];
                    
                    // Security checks
                    if ($fileError !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    
                    if ($fileSize > $maxFileSize) {
                        continue;
                    }
                    
                    // Verify MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $realMimeType = finfo_file($finfo, $fileTmpName);
                    finfo_close($finfo);
                    
                    if (!in_array($realMimeType, $allowedMimeTypes)) {
                        continue;
                    }
                    
                    // Generate secure filename
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $safeFileName = uniqid('task_' . $taskId . '_') . '.' . $fileExt;
                    $filePath = $uploadDir . $safeFileName;
                    
                    // Move uploaded file
                    if (move_uploaded_file($fileTmpName, $filePath)) {
                        // Store file info in database
                        $stmt = $pdo->prepare("
                            INSERT INTO task_attachments (
                                task_id, file_name, file_path, file_size, file_type, uploaded_by
                            ) VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $taskId, $fileName, $safeFileName, $fileSize, $realMimeType, $employee['id']
                        ]);
                        
                        $uploadedFiles[] = $fileName;
                    }
                }
            }
            
            // Create repeat tasks if configured
            if ($repeatType !== 'none') {
                // Use active_time instead of due_time for repeats
                $repeatDates = generateRepeatDates($dueDate, $repeatType, $customDays, $repeatUntil, $repeatCount, $activeTime);
                
                foreach ($repeatDates as $repeatDate) {
                    // Generate token for repeat task
                    $repeatToken = md5(uniqid($title . $assigneeId . $repeatDate . time(), true));
                    
                    // Calculate new due date time dengan active time
                    $newDueDateTime = new DateTime($repeatDate);
                    if ($activeTime) {
                        $timeParts = explode(':', $activeTime);
                        $newDueDateTime->setTime((int)$timeParts[0], (int)$timeParts[1]);
                    }
                    
                    // Insert repeat task dengan active_time
                    $stmt = $pdo->prepare("
                        INSERT INTO tasks (
                            title, description, employee_id, department_id, due_date, due_time,
                            time_limit_hours, time_limit_minutes, points,
                            confirmation_token, status,
                            is_project_task, created_by, parent_task_id, is_repeat
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, 1)
                    ");
                    
                    $stmt->execute([
                        $title . ' (Repeat)', $description, $assigneeId, 
                        $isProjectTask ? $departmentId : null,
                        $repeatDate, 
                        $activeTime, // Use active time instead of due time
                        $timeLimitHours, $timeLimitMinutes, $points,
                        $repeatToken,
                        $isProjectTask ? 1 : 0,
                        $employee['id'],
                        $taskId // Reference to parent task
                    ]);
                    
                    $repeatTaskId = $pdo->lastInsertId();
                    $totalTasksCreated++;
                    
                    // If project task, create project_tasks entry for repeat
                    if ($isProjectTask) {
                        $stmt = $pdo->prepare("
                            INSERT INTO project_tasks (
                                project_id, task_id, task_name, assigned_to,
                                priority, status, due_date, department_id
                            ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
                        ");
                        
                        $stmt->execute([
                            $projectId, $repeatTaskId, $title . ' (Repeat)', $assigneeId,
                            $priority, $repeatDate, $departmentId
                        ]);
                    }
                    
                    // Copy attachments to repeat task
                    if (!empty($uploadedFiles)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO task_attachments (
                                task_id, file_name, file_path, file_size, file_type, uploaded_by
                            )
                            SELECT ?, file_name, file_path, file_size, file_type, uploaded_by
                            FROM task_attachments
                            WHERE task_id = ?
                        ");
                        $stmt->execute([$repeatTaskId, $taskId]);
                    }
                }
            }
            
            // Copy attachments to other assignees' tasks
            if (count($createdTasks) > 1 && !empty($uploadedFiles)) {
                $stmt = $pdo->prepare("
                    INSERT INTO task_attachments (
                        task_id, file_name, file_path, file_size, file_type, uploaded_by
                    )
                    SELECT ?, file_name, file_path, file_size, file_type, uploaded_by
                    FROM task_attachments
                    WHERE task_id = ?
                ");
                
                for ($i = 1; $i < count($createdTasks); $i++) {
                    $stmt->execute([$createdTasks[$i], $createdTasks[0]]);
                }
            }
            
            // Send notification
            $notificationMessage = sprintf(
                'You have been assigned a new %s task. Due: %s %s. Time limit: %dh %dm. Points: %d. Files: %d',
                $isProjectTask ? 'project' : 'personal',
                $dueDate,
                $dueTime ?: '',
                $timeLimitHours,
                $timeLimitMinutes,
                $points,
                count($uploadedFiles)
            );
            
            if ($repeatType !== 'none') {
                $notificationMessage .= sprintf(
                    '. This task repeats %s with %d occurrences.',
                    $repeatType,
                    count($repeatDates)
                );
            }
            
            sendNotification(
                $assigneeId,
                $taskId,
                'new_task',
                ($isProjectTask ? 'New Project Task: ' : 'New Personal Task: ') . $title,
                $notificationMessage
            );
        }
    }
    
    $pdo->commit();
    
    // Log activity if function exists
    if (function_exists('logAdminActivity')) {
        logAdminActivity('create_task', 'task', $createdTasks[0], 
            "Created $totalTasksCreated task(s): $title");
    }
    
    // Prepare success message
    $successMessage = 'Task(s) created successfully';
    if ($repeatType !== 'none') {
        $repeatCount = $totalTasksCreated - count($assignToEmployees);
        $successMessage .= ". Created " . count($assignToEmployees) . " main task(s) with $repeatCount repeat(s)";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $successMessage,
        'tasks_created' => $totalTasksCreated,
        'files_uploaded' => count($uploadedFiles),
        'repeat_type' => $repeatType,
        'main_tasks' => count($assignToEmployees)
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    // Clean up any uploaded files on error
    foreach ($createdTasks as $taskId) {
        $files = glob($uploadDir . 'task_' . $taskId . '_*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>