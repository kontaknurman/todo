<?php
// admin/project_details_departments.php - Departments Tab Content (Simplified)
// This file is included in project_details.php

// Check if user can manage departments
$canManageDepts = $isAdmin || 
    ($isManager && $project['project_manager_id'] == $_SESSION[ADMIN_SESSION_PREFIX . 'id']);
?>

<div class="space-y-6">
    <!-- Create New Department Button -->
    <?php if ($canManageDepts): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="font-semibold text-gray-800">
                    <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                    Need a new department for this project?
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    Create a new department and it will be automatically assigned to <?php echo htmlspecialchars($project['project_name']); ?>
                </p>
            </div>
            <a href="departments.php?action=create&project_id=<?php echo $project_id; ?>&project_name=<?php echo urlencode($project['project_name']); ?>" 
               class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center">
                <i class="fas fa-plus mr-2"></i>Create New Department
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Assign Existing Department (Admin Only) -->
    <?php 
    // Get unassigned departments
    $stmt = $pdo->prepare("
        SELECT id, department_name, department_code, location 
        FROM departments 
        WHERE (project_id IS NULL OR project_id = 0) 
        AND status = 'active'
        ORDER BY department_name
    ");
    $stmt->execute();
    $availableDepartments = $stmt->fetchAll();
    ?>
    
    <?php if ($isAdmin && !empty($availableDepartments)): ?>
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="font-semibold text-gray-800 mb-3">
            <i class="fas fa-link text-gray-600 mr-2"></i>
            Assign Existing Department
        </h3>
        <div class="flex gap-3">
            <select id="assign-dept-select" class="flex-1 px-3 py-2 border rounded-lg">
                <option value="">Select Department...</option>
                <?php foreach ($availableDepartments as $dept): ?>
                <option value="<?php echo $dept['id']; ?>">
                    <?php echo htmlspecialchars($dept['department_name']); ?> 
                    (<?php echo htmlspecialchars($dept['department_code']); ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <button onclick="assignDepartment(<?php echo $project_id; ?>)" 
                    class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                <i class="fas fa-link mr-2"></i>Assign to Project
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Project Departments List -->
    <div>
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold text-gray-800">
                <i class="fas fa-building mr-2"></i>
                Project Departments (<?php echo count($departments); ?>)
            </h3>
            <?php if ($canManageDepts && count($departments) > 0): ?>
            <a href="departments.php?filter_project=<?php echo $project_id; ?>" 
               class="text-blue-600 hover:text-blue-700 text-sm">
                <i class="fas fa-cog mr-1"></i>Manage All Departments
            </a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($departments)): ?>
        <div class="text-center py-8 bg-gray-50 rounded-lg">
            <i class="fas fa-building text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-500">No departments assigned to this project yet</p>
            <?php if ($canManageDepts): ?>
            <p class="text-sm text-gray-400 mt-2">Click "Create New Department" to get started</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($departments as $dept): ?>
            <div class="border rounded-lg p-4 hover:shadow-md transition">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h4 class="font-bold text-lg">
                            <?php echo htmlspecialchars($dept['department_name']); ?>
                        </h4>
                        <p class="text-sm text-gray-500">
                            Code: <?php echo htmlspecialchars($dept['department_code']); ?>
                        </p>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full <?php 
                        echo $dept['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                        'bg-gray-100 text-gray-800'; ?>">
                        <?php echo ucfirst($dept['status']); ?>
                    </span>
                </div>
                
                <?php if ($dept['description']): ?>
                <p class="text-sm text-gray-600 mb-3">
                    <?php echo htmlspecialchars(substr($dept['description'], 0, 100)); ?>
                    <?php echo strlen($dept['description']) > 100 ? '...' : ''; ?>
                </p>
                <?php endif; ?>
                
                <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                    <div>
                        <span class="text-gray-500">Location:</span>
                        <span class="ml-1 font-medium">
                            <?php echo htmlspecialchars($dept['location'] ?? 'Not Set'); ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500">Members:</span>
                        <span class="ml-1 font-medium">
                            <?php echo $dept['member_count']; ?> people
                        </span>
                    </div>
                </div>
                
                <!-- Quick Members Preview -->
                <?php
                $memberStmt = $pdo->prepare("
                    SELECT e.name, e.id, dm.position 
                    FROM department_members dm
                    JOIN employees e ON dm.employee_id = e.id
                    WHERE dm.department_id = ? AND dm.is_active = TRUE
                    LIMIT 3
                ");
                $memberStmt->execute([$dept['id']]);
                $deptMembers = $memberStmt->fetchAll();
                ?>
                
                <?php if (!empty($deptMembers)): ?>
                <div class="mb-3">
                    <div class="flex -space-x-2">
                        <?php foreach ($deptMembers as $dm): ?>
                        <div class="w-8 h-8 rounded-full bg-gray-300 border-2 border-white flex items-center justify-center text-xs font-medium" 
                             title="<?php echo htmlspecialchars($dm['name']); ?>">
                            <?php echo strtoupper(substr($dm['name'], 0, 1)); ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($dept['member_count'] > 3): ?>
                        <div class="w-8 h-8 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-xs font-medium">
                            +<?php echo $dept['member_count'] - 3; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="flex gap-2">
                    <a href="departments.php?action=members&id=<?php echo $dept['id']; ?>&return_project=<?php echo $project_id; ?>" 
                       class="flex-1 bg-blue-50 text-blue-600 px-3 py-1 rounded text-sm hover:bg-blue-100 text-center">
                        <i class="fas fa-users mr-1"></i>Members
                    </a>
                    <?php if ($canManageDepts): ?>
                    <a href="departments.php?action=edit&id=<?php echo $dept['id']; ?>&return_project=<?php echo $project_id; ?>" 
                       class="flex-1 bg-green-50 text-green-600 px-3 py-1 rounded text-sm hover:bg-green-100 text-center">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                    <button onclick="unassignDepartment(<?php echo $project_id; ?>, <?php echo $dept['id']; ?>)" 
                            class="flex-1 bg-red-50 text-red-600 px-3 py-1 rounded text-sm hover:bg-red-100">
                        <i class="fas fa-unlink mr-1"></i>Remove
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function assignDepartment(projectId) {
    const deptId = document.getElementById('assign-dept-select').value;
    if (!deptId) {
        alert('Please select a department');
        return;
    }
    
    if (confirm('Assign this department to the project?')) {
        fetch('api/project_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'assign_department',
                project_id: projectId,
                department_id: deptId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error assigning department');
            }
        });
    }
}

function unassignDepartment(projectId, deptId) {
    if (!confirm('Remove this department from the project? The department will not be deleted.')) return;
    
    fetch('api/project_api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'unassign_department',
            project_id: projectId,
            department_id: deptId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error removing department');
        }
    });
}
</script>