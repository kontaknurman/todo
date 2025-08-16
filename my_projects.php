<?php
// my_projects.php - Employee view of their projects and departments
require_once 'config.php';
requireLogin();

$employee = getCurrentEmployee();
$pdo = getDB();

// Get employee's active projects with task counts
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        pm.role_in_project,
        pm.assigned_date,
        pm.hours_allocated,
        mgr.name as project_manager_name,
        (SELECT COUNT(DISTINCT employee_id) FROM project_members WHERE project_id = p.id AND is_active = TRUE) as team_size,
        (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id) as total_tasks,
        (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
    FROM projects p
    INNER JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN employees mgr ON p.project_manager_id = mgr.id
    WHERE pm.employee_id = ? AND pm.is_active = TRUE
    ORDER BY p.priority DESC, p.status ASC, p.project_name ASC
");
$stmt->execute([$employee['id']]);
$myProjects = $stmt->fetchAll();

// Get employee's active departments with project info
$stmt = $pdo->prepare("
    SELECT 
        d.*,
        dm.position,
        dm.joined_date,
        dm.is_head,
        mgr.name as manager_name,
        p.project_name,
        p.project_code,
        (SELECT COUNT(DISTINCT employee_id) FROM department_members WHERE department_id = d.id AND is_active = TRUE) as member_count
    FROM departments d
    INNER JOIN department_members dm ON d.id = dm.department_id
    LEFT JOIN employees mgr ON d.manager_id = mgr.id
    LEFT JOIN projects p ON d.project_id = p.id
    WHERE dm.employee_id = ? AND dm.is_active = TRUE AND d.status = 'active'
    ORDER BY dm.is_head DESC, d.department_name ASC
");
$stmt->execute([$employee['id']]);
$myDepartments = $stmt->fetchAll();

// Get project tasks assigned to employee
$stmt = $pdo->prepare("
    SELECT 
        pt.*,
        p.project_name,
        p.project_code
    FROM project_tasks pt
    INNER JOIN projects p ON pt.project_id = p.id
    WHERE pt.assigned_to = ? AND pt.status IN ('pending', 'in_progress')
    ORDER BY pt.priority DESC, pt.due_date ASC
    LIMIT 5
");
$stmt->execute([$employee['id']]);
$projectTasks = $stmt->fetchAll();

$pageTitle = 'My Projects & Departments';
require 'layout-header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Projects Section -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600 rounded-t-xl">
            <h2 class="text-xl font-bold text-white">
                <i class="fas fa-project-diagram mr-2"></i>My Projects
                <span class="ml-2 px-2 py-1 bg-white bg-opacity-20 rounded text-sm">
                    <?php echo count($myProjects); ?> Active
                </span>
            </h2>
        </div>
        
        <div class="p-6">
            <?php if (empty($myProjects)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-folder-open text-4xl mb-3"></i>
                <p>You are not assigned to any projects yet</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($myProjects as $project): ?>
                <div class="border rounded-lg p-4 hover:shadow-md transition">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="font-bold text-lg"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                            <p class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($project['project_code']); ?>
                                <?php if (($project['project_type'] ?? 'fixed') === 'lifetime'): ?>
                                <span class="ml-2 px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">
                                    <i class="fas fa-infinity mr-1"></i>Lifetime
                                </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 text-xs rounded-full <?php 
                                echo $project['priority'] === 'urgent' ? 'bg-red-100 text-red-800' : 
                                    ($project['priority'] === 'high' ? 'bg-orange-100 text-orange-800' : 
                                    ($project['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                                    'bg-gray-100 text-gray-800')); ?>">
                                <?php echo ucfirst($project['priority']); ?>
                            </span>
                            <div class="mt-1">
                                <span class="px-2 py-1 text-xs rounded <?php 
                                    echo $project['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                        ($project['status'] === 'planning' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($project['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 
                                        'bg-gray-100 text-gray-800')); ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-3">
                        <?php echo htmlspecialchars(substr($project['description'] ?? '', 0, 100)); ?>
                        <?php echo strlen($project['description'] ?? '') > 100 ? '...' : ''; ?>
                    </p>
                    
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500">Your Role:</span>
                            <span class="ml-2 font-medium text-blue-600"><?php echo htmlspecialchars($project['role_in_project']); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Team Size:</span>
                            <span class="ml-2 font-medium"><?php echo $project['team_size']; ?> members</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Manager:</span>
                            <span class="ml-2 font-medium"><?php echo htmlspecialchars($project['project_manager_name'] ?? 'Not assigned'); ?></span>
                        </div>
                        <div>
                            <?php if (($project['project_type'] ?? 'fixed') === 'lifetime'): ?>
                                <span class="text-gray-500">Tasks:</span>
                                <span class="ml-2 font-medium">
                                    <?php 
                                    $completed = $project['completed_tasks'] ?? 0;
                                    $total = $project['total_tasks'] ?? 0;
                                    echo $completed . '/' . $total;
                                    ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-500">Progress:</span>
                                <span class="ml-2 font-medium"><?php echo $project['progress'] ?? 0; ?>%</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php 
                    // Calculate progress for display
                    $displayProgress = 0;
                    $progressColor = 'blue';
                    
                    if (($project['project_type'] ?? 'fixed') === 'lifetime') {
                        // For lifetime projects, calculate from tasks
                        if ($project['total_tasks'] > 0) {
                            $displayProgress = round(($project['completed_tasks'] / $project['total_tasks']) * 100);
                            $progressColor = 'green';
                        }
                    } else {
                        // For fixed projects, use manual progress
                        $displayProgress = $project['progress'] ?? 0;
                    }
                    ?>
                    
                    <?php if (($project['project_type'] ?? 'fixed') === 'lifetime' && $project['total_tasks'] > 0): ?>
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Task Completion</span>
                            <span><?php echo $displayProgress; ?>% (<?php echo $project['completed_tasks']; ?>/<?php echo $project['total_tasks']; ?> tasks)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full transition-all duration-300" style="width: <?php echo $displayProgress; ?>%"></div>
                        </div>
                    </div>
                    <?php elseif (($project['project_type'] ?? 'fixed') === 'fixed'): ?>
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Project Progress</span>
                            <span><?php echo $displayProgress; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: <?php echo $displayProgress; ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($project['end_date']): ?>
                    <div class="mt-3 text-sm text-gray-500">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        Due: <?php echo date('M d, Y', strtotime($project['end_date'])); ?>
                        <?php 
                        $daysLeft = (strtotime($project['end_date']) - time()) / 86400;
                        if ($daysLeft > 0 && $daysLeft < 30): ?>
                            <span class="ml-2 text-orange-600 font-medium">(<?php echo round($daysLeft); ?> days left)</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3 flex gap-2">
                        <button onclick="viewProjectDetails(<?php echo $project['id']; ?>)" 
                                class="flex-1 bg-blue-50 text-blue-600 px-3 py-1 rounded text-sm hover:bg-blue-100">
                            <i class="fas fa-eye mr-1"></i> View Details
                        </button>
                        <a href="project_tasks.php?project_id=<?php echo $project['id']; ?>" 
                           class="flex-1 bg-green-50 text-green-600 px-3 py-1 rounded text-sm hover:bg-green-100 text-center">
                            <i class="fas fa-tasks mr-1"></i> Tasks
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Departments Section -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-500 to-purple-600 rounded-t-xl">
            <h2 class="text-xl font-bold text-white">
                <i class="fas fa-building mr-2"></i>My Departments
                <span class="ml-2 px-2 py-1 bg-white bg-opacity-20 rounded text-sm">
                    <?php echo count($myDepartments); ?> Active
                </span>
            </h2>
        </div>
        
        <div class="p-6">
            <?php if (empty($myDepartments)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-building text-4xl mb-3"></i>
                <p>You are not assigned to any department yet</p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($myDepartments as $dept): ?>
                <div class="border rounded-lg p-4 hover:shadow-md transition <?php echo $dept['is_head'] ? 'border-purple-300 bg-purple-50' : ''; ?>">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="font-bold text-lg"><?php echo htmlspecialchars($dept['department_name']); ?></h3>
                            <p class="text-sm text-gray-500">Code: <?php echo htmlspecialchars($dept['department_code']); ?></p>
                        </div>
                        <?php if ($dept['is_head']): ?>
                        <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full">
                            <i class="fas fa-crown mr-1"></i>Department Head
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($dept['description']): ?>
                    <p class="text-sm text-gray-600 mb-3">
                        <?php echo htmlspecialchars($dept['description']); ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($dept['project_name']): ?>
                    <div class="mb-3 p-2 bg-blue-50 rounded text-sm">
                        <i class="fas fa-project-diagram text-blue-600 mr-1"></i>
                        <span class="text-blue-700">Part of: <strong><?php echo htmlspecialchars($dept['project_name']); ?></strong> (<?php echo htmlspecialchars($dept['project_code']); ?>)</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500">Your Position:</span>
                            <span class="ml-2 font-medium text-purple-600"><?php echo htmlspecialchars($dept['position']); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Members:</span>
                            <span class="ml-2 font-medium"><?php echo $dept['member_count']; ?> people</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Manager:</span>
                            <span class="ml-2 font-medium"><?php echo htmlspecialchars($dept['manager_name'] ?? 'Not assigned'); ?></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Joined:</span>
                            <span class="ml-2 font-medium"><?php echo date('M d, Y', strtotime($dept['joined_date'])); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($dept['location']): ?>
                    <div class="mt-3 text-sm text-gray-500">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        <?php echo htmlspecialchars($dept['location']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3 flex gap-2">
                        <button onclick="viewDepartmentDetails(<?php echo $dept['id']; ?>)" 
                                class="flex-1 bg-purple-50 text-purple-600 px-3 py-1 rounded text-sm hover:bg-purple-100">
                            <i class="fas fa-eye mr-1"></i> View Details
                        </button>
                        <button onclick="viewDepartmentMembers(<?php echo $dept['id']; ?>)" 
                                class="flex-1 bg-gray-50 text-gray-600 px-3 py-1 rounded text-sm hover:bg-gray-100">
                            <i class="fas fa-users mr-1"></i> Members
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Project Tasks Section -->
<?php if (!empty($projectTasks)): ?>
<div class="bg-white rounded-xl shadow-lg">
    <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 rounded-t-xl">
        <h2 class="text-xl font-bold text-white">
            <i class="fas fa-tasks mr-2"></i>My Project Tasks
        </h2>
    </div>
    
    <div class="p-6">
        <div class="space-y-3">
            <?php foreach ($projectTasks as $task): ?>
            <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                <div class="flex-1">
                    <div class="font-medium"><?php echo htmlspecialchars($task['task_name']); ?></div>
                    <div class="text-sm text-gray-500">
                        Project: <?php echo htmlspecialchars($task['project_name']); ?> (<?php echo htmlspecialchars($task['project_code']); ?>)
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-2 py-1 text-xs rounded-full <?php 
                        echo $task['priority'] === 'urgent' ? 'bg-red-100 text-red-800' : 
                            ($task['priority'] === 'high' ? 'bg-orange-100 text-orange-800' : 
                            ($task['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                            'bg-gray-100 text-gray-800')); ?>">
                        <?php echo ucfirst($task['priority']); ?>
                    </span>
                    <?php if ($task['due_date']): ?>
                    <span class="text-sm text-gray-500">
                        <i class="fas fa-calendar mr-1"></i>
                        <?php echo date('M d', strtotime($task['due_date'])); ?>
                    </span>
                    <?php endif; ?>
                    <button onclick="updateTaskStatus(<?php echo $task['id']; ?>)" 
                            class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                        <i class="fas fa-check mr-1"></i> Complete
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

</div><!-- End container -->

<script>
function viewProjectDetails(projectId) {
    // You can expand this to show a modal with full project details
    window.location.href = 'project_details.php?id=' + projectId;
}

function viewDepartmentDetails(deptId) {
    // You can expand this to show a modal with full department details
    alert('Department details view - ID: ' + deptId);
}

function viewDepartmentMembers(deptId) {
    // You can expand this to show department members
    window.location.href = 'department_members.php?id=' + deptId;
}

function updateTaskStatus(taskId) {
    if (confirm('Mark this task as completed?')) {
        // AJAX call to update task status
        alert('Task completion feature to be implemented');
    }
}
</script>

<script src="app.js"></script>
</body>
</html>