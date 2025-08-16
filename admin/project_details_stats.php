<?php
// admin/project_details_stats.php - Statistics Tab Content
// This file is included in project_details.php

// Get detailed statistics
$stmt = $pdo->prepare("
    SELECT 
        e.name,
        e.id,
        COUNT(DISTINCT t.id) as total_tasks,
        COUNT(DISTINCT CASE WHEN t.status = 'finished' THEN t.id END) as completed_tasks,
        COUNT(DISTINCT CASE WHEN t.status = 'ongoing' THEN t.id END) as ongoing_tasks,
        COUNT(DISTINCT CASE WHEN t.status = 'pending' THEN t.id END) as pending_tasks,
        COUNT(DISTINCT CASE WHEN t.status = 'overdue' THEN t.id END) as overdue_tasks,
        COALESCE(SUM(CASE WHEN t.status = 'finished' THEN t.points END), 0) as points_earned,
        AVG(CASE WHEN t.status = 'finished' THEN 
            TIMESTAMPDIFF(HOUR, t.started_at, t.completed_at) END) as avg_completion_hours
    FROM project_members pm
    JOIN employees e ON pm.employee_id = e.id
    LEFT JOIN tasks t ON e.id = t.employee_id
    WHERE pm.project_id = ? AND pm.is_active = TRUE
    GROUP BY e.id, e.name
    ORDER BY points_earned DESC
");
$stmt->execute([$project_id]);
$memberStats = $stmt->fetchAll();

// Get task distribution by status
$stmt = $pdo->prepare("
    SELECT 
        pt.status,
        COUNT(*) as count
    FROM project_tasks pt
    WHERE pt.project_id = ?
    GROUP BY pt.status
");
$stmt->execute([$project_id]);
$taskDistribution = $stmt->fetchAll();

// Calculate project health score
$healthScore = 0;
if ($stats['total_tasks'] > 0) {
    $completionRate = ($stats['completed_tasks'] / $stats['total_tasks']) * 100;
    $overdueRate = (($stats['total_tasks'] - $stats['completed_tasks']) > 0) ? 0 : 0;
    $healthScore = min(100, $completionRate - $overdueRate);
}
?>

<div class="space-y-6">
    <!-- Project Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
            <div class="text-sm opacity-80">Project Health</div>
            <div class="text-3xl font-bold"><?php echo round($healthScore); ?>%</div>
            <div class="text-xs mt-1">
                <?php 
                if ($healthScore >= 80) echo "Excellent";
                elseif ($healthScore >= 60) echo "Good";
                elseif ($healthScore >= 40) echo "Needs Attention";
                else echo "Critical";
                ?>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
            <div class="text-sm opacity-80">Completion Rate</div>
            <div class="text-3xl font-bold">
                <?php echo $stats['total_tasks'] > 0 ? 
                    round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0; ?>%
            </div>
            <div class="text-xs mt-1">
                <?php echo $stats['completed_tasks']; ?> of <?php echo $stats['total_tasks']; ?> tasks
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
            <div class="text-sm opacity-80">Team Productivity</div>
            <div class="text-3xl font-bold">
                <?php echo $stats['total_members'] > 0 ? 
                    round($stats['completed_tasks'] / $stats['total_members'], 1) : 0; ?>
            </div>
            <div class="text-xs mt-1">Tasks per member</div>
        </div>
        
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-4 text-white">
            <div class="text-sm opacity-80">Total Points</div>
            <div class="text-3xl font-bold">
                <?php echo array_sum(array_column($memberStats, 'points_earned')); ?>
            </div>
            <div class="text-xs mt-1">Points earned</div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Member Performance Chart -->
        <div class="bg-white border rounded-lg p-4">
            <h3 class="font-semibold text-gray-800 mb-4">Member Performance</h3>
            <canvas id="memberPerformanceChart" width="400" height="300"></canvas>
        </div>
        
        <!-- Task Status Distribution -->
        <div class="bg-white border rounded-lg p-4">
            <h3 class="font-semibold text-gray-800 mb-4">Task Distribution</h3>
            <canvas id="taskDistributionChart" width="400" height="300"></canvas>
        </div>
    </div>
    
    <!-- Member Performance Table -->
    <div>
        <h3 class="font-semibold text-gray-800 mb-3">Individual Performance Metrics</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Completed</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ongoing</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pending</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Overdue</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Points</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Avg Time</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Efficiency</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($memberStats as $ms): ?>
                    <?php 
                    $efficiency = $ms['total_tasks'] > 0 ? 
                        round(($ms['completed_tasks'] / $ms['total_tasks']) * 100) : 0;
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($ms['name']); ?></td>
                        <td class="px-4 py-3 text-center"><?php echo $ms['total_tasks']; ?></td>
                        <td class="px-4 py-3 text-center text-green-600 font-medium">
                            <?php echo $ms['completed_tasks']; ?>
                        </td>
                        <td class="px-4 py-3 text-center text-blue-600">
                            <?php echo $ms['ongoing_tasks']; ?>
                        </td>
                        <td class="px-4 py-3 text-center text-yellow-600">
                            <?php echo $ms['pending_tasks']; ?>
                        </td>
                        <td class="px-4 py-3 text-center text-red-600">
                            <?php echo $ms['overdue_tasks']; ?>
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-purple-600">
                            <?php echo $ms['points_earned']; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php echo $ms['avg_completion_hours'] ? 
                                round($ms['avg_completion_hours'], 1) . 'h' : '-'; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center">
                                <div class="w-20">
                                    <div class="text-xs text-gray-600 mb-1"><?php echo $efficiency; ?>%</div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full <?php 
                                            echo $efficiency >= 80 ? 'bg-green-500' : 
                                                ($efficiency >= 50 ? 'bg-yellow-500' : 'bg-red-500'); ?>" 
                                             style="width: <?php echo $efficiency; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Project Timeline -->
    <?php if ($project['start_date'] && $project['end_date']): ?>
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Project Timeline</h3>
        <?php
        $start = new DateTime($project['start_date']);
        $end = new DateTime($project['end_date']);
        $today = new DateTime();
        $totalDays = $start->diff($end)->days;
        $elapsedDays = $start->diff($today)->days;
        $timeProgress = min(100, ($elapsedDays / $totalDays) * 100);
        ?>
        <div class="space-y-2">
            <div class="flex justify-between text-sm">
                <span>Start: <?php echo $start->format('M d, Y'); ?></span>
                <span>End: <?php echo $end->format('M d, Y'); ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 relative">
                <div class="bg-blue-500 h-3 rounded-full" style="width: <?php echo $timeProgress; ?>%"></div>
                <div class="absolute top-0 left-0 w-full h-3 flex items-center justify-center">
                    <span class="text-xs text-white font-medium">
                        <?php echo round($timeProgress); ?>% Time Elapsed
                    </span>
                </div>
            </div>
            <div class="text-center text-sm text-gray-600">
                <?php 
                $remaining = $today->diff($end)->days;
                echo $remaining > 0 ? "$remaining days remaining" : "Project overdue by " . abs($remaining) . " days";
                ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Prepare data for charts
const memberNames = <?php echo json_encode(array_column($memberStats, 'name')); ?>;
const memberTaskCounts = <?php echo json_encode(array_column($memberStats, 'completed_tasks')); ?>;
const memberPoints = <?php echo json_encode(array_column($memberStats, 'points_earned')); ?>;

const taskStatuses = <?php echo json_encode(array_column($taskDistribution, 'status')); ?>;
const taskCounts = <?php echo json_encode(array_column($taskDistribution, 'count')); ?>;
</script>