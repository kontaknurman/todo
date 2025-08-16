<?php
// api/download_attachment.php - Secure file download handler
require_once '../config.php';

if (!isLoggedIn()) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

$attachmentId = (int)($_GET['id'] ?? 0);

if (!$attachmentId) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

$employee = getCurrentEmployee();
$pdo = getDB();

// Get attachment info and verify access
$stmt = $pdo->prepare("
    SELECT a.*, t.employee_id, t.created_by
    FROM task_attachments a
    JOIN tasks t ON a.task_id = t.id
    WHERE a.id = ? AND a.is_deleted = FALSE
");
$stmt->execute([$attachmentId]);
$attachment = $stmt->fetch();

if (!$attachment) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

// Check access permission
$hasAccess = false;

// Owner of task
if ($attachment['employee_id'] == $employee['id']) {
    $hasAccess = true;
}

// Creator of task
if ($attachment['created_by'] == $employee['id']) {
    $hasAccess = true;
}

// Admin/Manager can access project tasks
if (!$hasAccess) {
    $stmt = $pdo->prepare("SELECT role FROM employees WHERE id = ?");
    $stmt->execute([$employee['id']]);
    $user = $stmt->fetch();
    if ($user && in_array($user['role'], ['admin', 'manager'])) {
        $hasAccess = true;
    }
}

if (!$hasAccess) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// Serve file
$filePath = '../uploads/tasks/' . $attachment['file_path'];

if (!file_exists($filePath)) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found on server');
}

// Set headers for download
header('Content-Type: ' . $attachment['file_type']);
header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file
readfile($filePath);
exit;
?>