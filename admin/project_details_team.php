<?php
// admin/project_details_team.php - Team Members Tab Content
// This file is included in project_details.php

// Get available employees not in project
$stmt = $pdo->prepare("
    SELECT id, name, email, department, role 
    FROM employees 
    WHERE id NOT IN (
        SELECT employee_id FROM project_members 
        WHERE project_id = ? AND is_active = TRUE
    )
    ORDER BY name
");
$stmt->execute([$project_id]);
$availableEmployees = $stmt->fetchAll();
?>

<div class="space-y-6">
    <!-- Add Member Section -->
    <?php if ($isAdmin || ($isManager && $project['project_manager_id'] == $_SESSION[ADMIN_SESSION_PREFIX . 'id'])): ?>
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Add Team Member</h3>
        <div class="flex gap-3">
            <select id="new-member-select" class="flex-1 px-3 py-2 border rounded-lg">
                <option value="">Select Employee...</option>
                <?php foreach ($availableEmployees as $emp): ?>
                <option value="<?php echo $emp['id']; ?>">
                    <?php echo htmlspecialchars($emp['name']); ?> 
                    (<?php echo htmlspecialchars($emp['department'] ?? 'No Dept'); ?>) - 
                    <?php echo ucfirst($emp['role']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select id="new-member-role" class="px-3 py-2 border rounded-lg">
                <option value="Member">Member</option>
                <option value="Developer">Developer</option>
                <option value="Designer">Designer</option>
                <option value="Tester">Tester</option>
                <option value="Lead">Team Lead</option>
            </select>
            <button onclick="addTeamMember(<?php echo $project_id; ?>)" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add Member
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Team Members Table -->
    <div>
        <h3 class="font-semibold text-gray-800 mb-3">Current Team Members (<?php echo count($members); ?>)</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role in Project</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tasks</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performance</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($members as $member): ?>
                    <?php 
                    $completionRate = $member['total_tasks'] > 0 ? 
                        round(($member['completed_tasks'] / $member['total_tasks']) * 100) : 0;
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div>
                                <div class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($member['name']); ?>
                                    <?php if ($member['id'] == $project['project_manager_id']): ?>
                                    <span class="ml-2 px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">
                                        <i class="fas fa-crown mr-1"></i>Manager
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($member['email']); ?></div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?php echo htmlspecialchars($member['department'] ?? '-'); ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($isAdmin || ($isManager && $project['project_manager_id'] == $_SESSION[ADMIN_SESSION_PREFIX . 'id'])): ?>
                            <select onchange="updateMemberRole(<?php echo $project_id; ?>, <?php echo $member['id']; ?>, this.value)"
                                    class="text-sm border rounded px-2 py-1">
                                <option value="Member" <?php echo $member['role_in_project'] == 'Member' ? 'selected' : ''; ?>>Member</option>
                                <option value="Developer" <?php echo $member['role_in_project'] == 'Developer' ? 'selected' : ''; ?>>Developer</option>
                                <option value="Designer" <?php echo $member['role_in_project'] == 'Designer' ? 'selected' : ''; ?>>Designer</option>
                                <option value="Tester" <?php echo $member['role_in_project'] == 'Tester' ? 'selected' : ''; ?>>Tester</option>
                                <option value="Lead" <?php echo $member['role_in_project'] == 'Lead' ? 'selected' : ''; ?>>Team Lead</option>
                                <option value="Project Manager" <?php echo $member['role_in_project'] == 'Project Manager' ? 'selected' : ''; ?>>Project Manager</option>
                            </select>
                            <?php else: ?>
                            <span class="text-sm"><?php echo htmlspecialchars($member['role_in_project']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-center"><?php echo $member['total_tasks']; ?></td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="text-green-600 font-medium"><?php echo $member['completed_tasks']; ?></span>
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="font-bold text-purple-600"><?php echo $member['points_earned'] ?? 0; ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="w-full">
                                <div class="flex justify-between text-xs mb-1">
                                    <span>Completion</span>
                                    <span><?php echo $completionRate; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full <?php 
                                        echo $completionRate >= 80 ? 'bg-green-500' : 
                                            ($completionRate >= 50 ? 'bg-yellow-500' : 'bg-red-500'); ?>" 
                                         style="width: <?php echo $completionRate; ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <?php if ($isAdmin || ($isManager && $project['project_manager_id'] == $_SESSION[ADMIN_SESSION_PREFIX . 'id'])): ?>
                                <?php if ($member['id'] != $project['project_manager_id']): ?>
                                <button onclick="removeMember(<?php echo $project_id; ?>, <?php echo $member['id']; ?>)" 
                                        class="text-red-600 hover:text-red-800" title="Remove from project">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Team Performance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="bg-blue-50 rounded-lg p-4">
            <div class="text-blue-600 text-sm font-medium">Total Team Tasks</div>
            <div class="text-2xl font-bold text-blue-800">
                <?php echo array_sum(array_column($members, 'total_tasks')); ?>
            </div>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
            <div class="text-green-600 text-sm font-medium">Completed Tasks</div>
            <div class="text-2xl font-bold text-green-800">
                <?php echo array_sum(array_column($members, 'completed_tasks')); ?>
            </div>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
            <div class="text-purple-600 text-sm font-medium">Total Points Earned</div>
            <div class="text-2xl font-bold text-purple-800">
                <?php echo array_sum(array_column($members, 'points_earned')); ?>
            </div>
        </div>
    </div>
</div>