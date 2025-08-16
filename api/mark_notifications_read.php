<?php
// api/mark_notifications_read.php - Mark notifications as read
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
$notificationIds = $input['notification_ids'] ?? [];

if (empty($notificationIds)) {
    echo json_encode(['success' => false, 'message' => 'No notification IDs provided']);
    exit;
}

// Validate that notifications belong to current user
$placeholders = str_repeat('?,', count($notificationIds) - 1) . '?';
$params = array_merge([$employee['id']], $notificationIds);

$stmt = $pdo->prepare("
    UPDATE notifications 
    SET is_read = TRUE, read_at = NOW()
    WHERE employee_id = ? 
    AND id IN ($placeholders)
    AND is_read = FALSE
");

$success = $stmt->execute($params);

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Notifications marked as read' : 'Error updating notifications'
]);
?>