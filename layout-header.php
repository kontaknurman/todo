<?php
// layout-header.php - Reusable header template
if (!isset($pageTitle)) $pageTitle = APP_NAME;
if (!isset($employee)) $employee = getCurrentEmployee();
if (!isset($unreadCount)) $unreadCount = $employee ? getUnreadCount($employee['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-purple-50 to-indigo-100 min-h-screen">
    <?php if (isLoggedIn()): ?>
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <i class="fas fa-tasks text-purple-600 text-2xl mr-2"></i>
                        <span class="font-bold text-xl text-gray-800"><?php echo APP_NAME; ?></span>
                    </a>
                    <div class="hidden md:flex ml-10 space-x-4">
                        <a href="index.php" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium transition">
                            <i class="fas fa-dashboard mr-1"></i> Dashboard
                        </a>
                        <a href="tasks.php" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium transition">
                            <i class="fas fa-list mr-1"></i> My Tasks
                        </a>
                        <a href="create_task.php" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium transition">
                            <i class="fas fa-plus-circle mr-1"></i> New Task
                        </a>
                        <a href="profile.php" class="text-gray-700 hover:text-purple-600 px-3 py-2 rounded-md text-sm font-medium transition">
                            <i class="fas fa-user mr-1"></i> Profile
                        </a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <button onclick="toggleNotifications()" class="relative p-2 text-gray-600 hover:text-purple-600 transition">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if ($unreadCount > 0): ?>
                        <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- User Info -->
                    <div class="flex items-center space-x-3">
                        <div class="hidden md:block text-right">
                            <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($employee['name']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($employee['department']); ?></p>
                        </div>
                        <div class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                            <i class="fas fa-star mr-1"></i><?php echo $employee['total_points']; ?>
                        </div>
                        <a href="logout.php" class="text-gray-500 hover:text-red-600 transition">
                            <i class="fas fa-sign-out-alt text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Notifications Panel -->
    <div id="notificationPanel" class="hidden fixed right-4 top-20 w-96 bg-white rounded-lg shadow-2xl z-40 max-h-96 overflow-y-auto">
        <div class="p-4 border-b">
            <h3 class="font-semibold text-gray-800">Notifications</h3>
        </div>
        <div id="notificationList" class="divide-y">
            <!-- Notifications will be loaded here -->
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">