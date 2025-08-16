<?php
// admin/project_details_tasks.php - Tasks Tab Content
// This file is included in project_details.php

// Get project tasks with employee info
$stmt = $pdo->prepare("
    SELECT 
        pt.*,
        t.title,
        t.description,
        t.due_date,
        t.due_time,
        t.status as task_status,
        t.points,
        t.employee_id,
        e.name as assigned_to_name
    FROM project_tasks pt
    LEFT JOIN tasks t ON pt.task_id = t.id
    LEFT JOIN employees e ON pt.assigned_to = e.id
    WHERE pt.project_id = ?
    ORDER BY pt.priority DESC, pt.due_date ASC
");
$stmt->execute([$project_id]);
$projectTasks = $stmt->fetchAll();
?>

<div class="space-y-6">
    <!-- Add Task Section -->
    <?php if ($isAdmin || ($isManager && $project['project_manager_id'] == $_SESSION[ADMIN_SESSION_PREFIX . 'id'])): ?>
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Add New Task</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <input type="text" id="new-task-name" placeholder="Task Name" 
                   class="px-3 py-2 border rounded-lg">
            <select id="new-task-assignee" class="px-3 py-2 border rounded-lg">
                <option value="">Assign To...</option>
                <?php foreach ($members as $member): ?>
                <option value="<?php echo $member['id']; ?>">
                    <?php echo htmlspecialchars($member['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select id="new-task-priority" class="px-3 py-2 border rounded-lg">
                <option value="low">Low Priority</option>
                <option value="medium" selected>Medium Priority</option>
                <option value="high">High Priority</option>
                <option value="urgent">Urgent</option>
            </select>
            <input type="date" id="new-task-due" class="px-3 py-2 border rounded-lg" 
                   min="<?php echo date('Y-m-d'); ?>">
            <button onclick="addProjectTask(<?php echo $project_id; ?>)" 
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 md:col-span-2">
                <i class="fas fa-plus mr-2"></i>Add Task
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tasks List -->
    <div>
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold text-gray-800">
                Project Tasks (<?php echo count($projectTasks); ?>)
            </h3>
            <div class="flex gap-2">
                <button onclick="filterTasks('all')" class="px-3 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200">
                    All
                </button>
                <button onclick="filterTasks('pending')" class="px-3 py-1 text-sm rounded bg-yellow-100 hover:bg-yellow-200">
                    Pending
                </button>
                <button onclick="filterTasks('in_progress')" class="px-3 py-1 text-sm rounded bg-blue-100 hover:bg-blue-200">
                    In Progress
                </button>
                <button onclick="filterTasks('completed')" class="px-3 py-1 text-sm rounded bg-green-100 hover:bg-green-200">
                    Completed
                </button>
            </div>
        </div>
        
        <?php if (empty($projectTasks)): ?>
        <div class="text-center py-8 bg-gray-50 rounded-lg">
            <i class="fas fa-tasks text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-500">No tasks assigned to this project yet</p>
        </div>
        <?php else: ?>
        <div class="space-y-3" id="tasks-list">
            <?php foreach ($projectTasks as $task): ?>
            <div class="task-item border rounded-lg p-4 hover:shadow-md transition" 
                 data-status="<?php echo $task['status']; ?>">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h4 class="font-semibold text-gray-800">
                                <?php echo htmlspecialchars($task['task_name']); ?>
                            </h4>
                            <span class="px-2 py-1 text-xs rounded-full <?php 
                                echo $task['priority'] === 'urgent' ? 'bg-red-100 text-red-800' : 
                                    ($task['priority'] === 'high' ? 'bg-orange-100 text-orange-800' : 
                                    ($task['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                                    'bg-gray-100 text-gray-800')); ?>">
                                <?php echo ucfirst($task['priority']); ?>
                            </span>
                            <span class="px-2 py-1 text-xs rounded-full <?php 
                                echo $task['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                    ($task['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' : 
                                    'bg-yellow-100 text-yellow-800'); ?>">
                                <?php echo str_replace('_', ' ', ucfirst($task['status'])); ?>
                            </span>
                        </div>
                        
                        <?php if ($task['title']): ?>
                        <p class="text-sm text-gray-600 mt-1">
                            <strong>Linked Task:</strong> <?php echo htmlspecialchars($task['title']); ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                            <span>
                                <i class="fas fa-user mr-1"></i>
                                <?php echo htmlspecialchars($task['assigned_to_name'] ?? 'Unassigned'); ?>
                            </span>
                            <?php if ($task['due_date']): ?>
                            <span>
                                <i class="fas fa-calendar mr-1"></i>
                                <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($task['task_status']): ?>
                            <span>
                                <i class="fas fa-flag mr-1"></i>
                                Task: <?php echo ucfirst($task['task_status']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($task['points']): ?>
                            <span class="text-purple-600 font-medium">
                                <i class="fas fa-star mr-1"></i>
                                <?php echo $task['points']; ?> pts
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <?php if ($task['status'] !== 'completed'): ?>
                        <button onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'completed')" 
                                class="text-green-600 hover:text-green-800" title="Mark Complete">
                            <i class="fas fa-check-circle"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($isAdmin || ($isManager && $project['project_manager_id'] == $_SESSION[ADMIN_SESSION_PREFIX . 'id'])): ?>
                        <button onclick="removeTask(<?php echo $task['id']; ?>)" 
                                class="text-red-600 hover:text-red-800" title="Remove Task">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Task Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <?php
        $taskStats = [
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0
        ];
        foreach ($projectTasks as $task) {
            if (isset($taskStats[$task['status']])) {
                $taskStats[$task['status']]++;
            }
        }
        ?>
        <div class="bg-yellow-50 rounded-lg p-4">
            <div class="text-yellow-600 text-sm font-medium">Pending</div>
            <div class="text-2xl font-bold text-yellow-800"><?php echo $taskStats['pending']; ?></div>
        </div>
        <div class="bg-blue-50 rounded-lg p-4">
            <div class="text-blue-600 text-sm font-medium">In Progress</div>
            <div class="text-2xl font-bold text-blue-800"><?php echo $taskStats['in_progress']; ?></div>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
            <div class="text-green-600 text-sm font-medium">Completed</div>
            <div class="text-2xl font-bold text-green-800"><?php echo $taskStats['completed']; ?></div>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="text-gray-600 text-sm font-medium">Cancelled</div>
            <div class="text-2xl font-bold text-gray-800"><?php echo $taskStats['cancelled']; ?></div>
        </div>
    </div>
</div>

<script>
function addProjectTask(projectId) {
    const name = document.getElementById('new-task-name').value;
    const assignee = document.getElementById('new-task-assignee').value;
    const priority = document.getElementById('new-task-priority').value;
    const dueDate = document.getElementById('new-task-due').value;
    
    if (!name) {
        alert('Please enter task name');
        return;
    }
    
    fetch('api/project_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'add_task',
            project_id: projectId,
            task_name: name,
            assigned_to: assignee,
            priority: priority,
            due_date: dueDate
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error adding task');
        }
    });
}

function updateTaskStatus(taskId, newStatus) {
    fetch('api/project_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'update_task_status',
            task_id: taskId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error updating task');
        }
    });
}

function filterTasks(status) {
    const tasks = document.querySelectorAll('.task-item');
    tasks.forEach(task => {
        if (status === 'all' || task.dataset.status === status) {
            task.style.display = 'block';
        } else {
            task.style.display = 'none';
        }
    });
}
</script>