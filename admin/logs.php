<?php
// admin/logs.php - Admin activity logs
require_once 'config.php';
requireAdminLogin();

$pdo = getDB();
$admin = getCurrentAdmin();

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filters
$filterAdmin = $_GET['admin'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterDate = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT l.*, e.name as admin_name, e.role as admin_role
    FROM admin_logs l
    LEFT JOIN employees e ON l.admin_id = e.id
    WHERE 1=1
";
$countQuery = "SELECT COUNT(*) FROM admin_logs l WHERE 1=1";
$params = [];

if ($filterAdmin) {
    $query .= " AND l.admin_id = ?";
    $countQuery .= " AND l.admin_id = ?";
    $params[] = $filterAdmin;
}

if ($filterAction) {
    $query .= " AND l.action LIKE ?";
    $countQuery .= " AND l.action LIKE ?";
    $params[] = "%$filterAction%";
}

if ($filterDate) {
    $query .= " AND DATE(l.created_at) = ?";
    $countQuery .= " AND DATE(l.created_at) = ?";
    $params[] = $filterDate;
}

if ($search) {
    $query .= " AND (l.action LIKE ? OR l.details LIKE ? OR l.ip_address LIKE ?)";
    $countQuery .= " AND (l.action LIKE ? OR l.details LIKE ? OR l.ip_address LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

// Get total count
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalRows = $stmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Get logs
$query .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get all admins for filter
$stmt = $pdo->query("SELECT id, name FROM employees WHERE role IN ('admin', 'manager') ORDER BY name");
$admins = $stmt->fetchAll();

// Get unique actions for filter
$stmt = $pdo->query("SELECT DISTINCT action FROM admin_logs ORDER BY action");
$actions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Log this view
logAdminActivity('view_logs', null, null, "Page $page");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b">
                <h1 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-history mr-2"></i>Admin Activity Logs
                </h1>
            </div>
            
            <!-- Filters -->
            <div class="px-6 py-4 bg-gray-50 border-b">
                <form method="GET" class="flex flex-wrap gap-3">
                    <input type="text" name="search" placeholder="Search logs..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="flex-1 min-w-[200px] px-4 py-2 border rounded-lg">
                    
                    <select name="admin" class="px-4 py-2 border rounded-lg">
                        <option value="">All Admins</option>
                        <?php foreach ($admins as $adm): ?>
                        <option value="<?php echo $adm['id']; ?>" <?php echo $filterAdmin == $adm['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($adm['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="action" class="px-4 py-2 border rounded-lg">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $act): ?>
                        <option value="<?php echo $act; ?>" <?php echo $filterAction == $act ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($act); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="date" name="date" value="<?php echo $filterDate; ?>"
                           class="px-4 py-2 border rounded-lg">
                    
                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    
                    <a href="logs.php" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500">
                        <i class="fas fa-times mr-1"></i> Clear
                    </a>
                </form>
            </div>
            
            <!-- Logs Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admin</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm">
                                <div><?php echo date('M d, Y', strtotime($log['created_at'])); ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('g:i:s A', strtotime($log['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium"><?php echo htmlspecialchars($log['admin_name'] ?? 'Deleted User'); ?></div>
                                <?php if ($log['admin_role']): ?>
                                    <?php echo getRoleBadge($log['admin_role']); ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($log['target_type']): ?>
                                    <span class="text-gray-600"><?php echo htmlspecialchars($log['target_type']); ?></span>
                                    <?php if ($log['target_id']): ?>
                                        <span class="text-gray-500">#<?php echo $log['target_id']; ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo htmlspecialchars(substr($log['details'] ?? '-', 0, 50)); ?>
                                <?php if (strlen($log['details'] ?? '') > 50): ?>
                                    <span class="text-gray-400">...</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo htmlspecialchars($log['ip_address']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($logs)): ?>
                <div class="text-center py-8 text-gray-500">No logs found</div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalRows); ?> of <?php echo $totalRows; ?> entries
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>"
                           class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>"
                           class="px-3 py-1 rounded <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>"
                           class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Summary Stats -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Total Logs</h3>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($totalRows); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Logs Today</h3>
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM admin_logs WHERE DATE(created_at) = CURDATE()");
                $todayCount = $stmt->fetchColumn();
                ?>
                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($todayCount); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Active Admins Today</h3>
                <?php
                $stmt = $pdo->query("SELECT COUNT(DISTINCT admin_id) FROM admin_logs WHERE DATE(created_at) = CURDATE()");
                $activeAdmins = $stmt->fetchColumn();
                ?>
                <p class="text-2xl font-bold text-gray-800"><?php echo $activeAdmins; ?></p>
            </div>
        </div>
    </div>
</body>
</html>