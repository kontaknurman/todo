<?php
// admin/header.php - Admin navigation header
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}
?>
<nav class="bg-gray-900 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="dashboard.php" class="flex items-center">
                    <i class="fas fa-user-shield text-red-500 text-2xl mr-2"></i>
                    <span class="font-bold text-xl text-white">Admin Panel</span>
                </a>
                <div class="hidden md:flex ml-10 space-x-4">
                    <a href="dashboard.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    <?php if (hasPermission('employees', 'view')): ?>
                    <a href="employees.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition">
                        <i class="fas fa-users mr-1"></i> Employees
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('tasks', 'view')): ?>
                    <a href="tasks.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition">
                        <i class="fas fa-tasks mr-1"></i> Tasks
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('reports', 'view')): ?>
                    <a href="reports.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition">
                        <i class="fas fa-chart-bar mr-1"></i> Reports
                    </a>
                    <?php endif; ?>
                    <a href="logs.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition">
                        <i class="fas fa-history mr-1"></i> Logs
                    </a>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="text-white text-sm">
                    <span class="hidden md:inline"><?php echo htmlspecialchars($_SESSION[ADMIN_SESSION_PREFIX . 'name']); ?></span>
                    <?php echo getRoleBadge($_SESSION[ADMIN_SESSION_PREFIX . 'role']); ?>
                </div>
                <a href="logout.php" class="text-gray-300 hover:text-red-400 transition">
                    <i class="fas fa-sign-out-alt text-xl"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile menu -->
<div class="md:hidden bg-gray-800 px-4 py-2" id="mobile-menu">
    <div class="space-y-1">
        <a href="dashboard.php" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
        </a>
        <?php if (hasPermission('employees', 'view')): ?>
        <a href="employees.php" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
            <i class="fas fa-users mr-2"></i> Employees
        </a>
        <?php endif; ?>
        <?php if (hasPermission('tasks', 'view')): ?>
        <a href="tasks.php" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
            <i class="fas fa-tasks mr-2"></i> Tasks
        </a>
        <?php endif; ?>
        <?php if (hasPermission('reports', 'view')): ?>
        <a href="reports.php" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
            <i class="fas fa-chart-bar mr-2"></i> Reports
        </a>
        <?php endif; ?>
        <a href="logs.php" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
            <i class="fas fa-history mr-2"></i> Logs
        </a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); ?>
    </div>
</div>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); ?>
    </div>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>