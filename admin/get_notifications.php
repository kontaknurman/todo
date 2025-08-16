<?php
// api/get_notifications.php - Get notifications for current user
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$employee = getCurrentEmployee();
$pdo = getDB();

// Check if only count is requested
if (isset($_GET['count_only'])) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE employee_id = ? AND is_read = FALSE
    ");
    $stmt->execute([$employee['id']]);
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'unread_count' => (int)$result['count']
    ]);
    exit;
}

// Get all notifications
$stmt = $pdo->prepare("
    SELECT n.*, t.title as task_title
    FROM notifications n
    LEFT JOIN tasks t ON n.task_id = t.id
    WHERE n.employee_id = ?
    ORDER BY n.created_at DESC
    LIMIT 20
");
$stmt->execute([$employee['id']]);
$notifications = $stmt->fetchAll();

// Get unread IDs
$unreadIds = [];
foreach ($notifications as $notif) {
    if (!$notif['is_read']) {
        $unreadIds[] = $notif['id'];
    }
}

// Get unread count
$unreadCount = count($unreadIds);

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unreadCount,
    'unread_ids' => $unreadIds
]);
?>