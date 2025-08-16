<?php
// create_task.php - Create new task (AJAX version)
require_once 'config.php';
requireLogin();

$employee = getCurrentEmployee();

$pdo = getDB();
$stmt = $pdo->query("SELECT id, name, department, whatsapp_number FROM employees ORDER BY name");
$employees = $stmt->fetchAll();

$pageTitle = 'Create Task';
require 'layout-header.php';
?>

<div class="bg-white rounded-xl shadow-lg p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Create New Task</h1>
    
    <div id="alertMessage" class="hidden mb-4 p-4 rounded-lg">
        <i class="fas fa-info-circle mr-2"></i>
        <span id="alertText"></span>
    </div>
    
    <form id="createTaskForm" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-heading mr-1"></i> Task Title *
                </label>
                <input type="text" name="title" required 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user mr-1"></i> Assign To *
                </label>
                <select name="employee_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?php echo $emp['id']; ?>" <?php echo ($emp['id'] == $employee['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($emp['name']); ?> (<?php echo htmlspecialchars($emp['department']); ?>)
                        <?php if ($emp['whatsapp_number']): ?>
                            - <?php echo htmlspecialchars($emp['whatsapp_number']); ?>
                        <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-align-left mr-1"></i> Description
            </label>
            <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar mr-1"></i> Due Date *
                </label>
                <input type="date" name="due_date" required min="<?php echo date('Y-m-d'); ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-clock mr-1"></i> Due Time
                </label>
                <input type="time" name="due_time"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-star mr-1"></i> Points
                </label>
                <input type="number" name="points" min="1" max="100" value="10"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-hourglass-half mr-1"></i> Time Limit (Hours) *
                </label>
                <input type="number" name="time_limit_hours" min="0" max="72" value="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-hourglass-half mr-1"></i> Time Limit (Minutes) *
                </label>
                <input type="number" name="time_limit_minutes" min="0" max="59" value="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-redo mr-1"></i> Repeat
                </label>
                <select name="repeat_days" id="repeatDays" onchange="toggleCustomDays()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="none">No Repeat</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="custom">Custom Days</option>
                </select>
            </div>
            
            <div id="customDaysDiv" style="display:none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Custom Days</label>
                <input type="text" name="custom_days" placeholder="Mon,Wed,Fri"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>
        
        <div class="flex space-x-4">
            <button type="submit" id="submitBtn" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                <i class="fas fa-save mr-2"></i> Create Task
            </button>
            <button type="button" onclick="resetForm()" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">
                <i class="fas fa-redo mr-2"></i> Reset
            </button>
            <a href="index.php" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition inline-block">
                <i class="fas fa-times mr-2"></i> Cancel
            </a>
        </div>
    </form>
</div>

</div>

<script>
// Toggle custom days input
function toggleCustomDays() {
    var select = document.getElementById('repeatDays');
    var div = document.getElementById('customDaysDiv');
    div.style.display = select.value === 'custom' ? 'block' : 'none';
}

// Show alert message
function showAlert(message, type) {
    const alertDiv = document.getElementById('alertMessage');
    const alertText = document.getElementById('alertText');
    
    alertDiv.className = 'mb-4 p-4 rounded-lg border';
    
    if (type === 'success') {
        alertDiv.className += ' bg-green-50 text-green-700 border-green-200';
    } else if (type === 'error') {
        alertDiv.className += ' bg-red-50 text-red-700 border-red-200';
    } else {
        alertDiv.className += ' bg-yellow-50 text-yellow-700 border-yellow-200';
    }
    
    alertText.textContent = message;
    alertDiv.classList.remove('hidden');
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        alertDiv.classList.add('hidden');
    }, 5000);
}

// Reset form
function resetForm() {
    document.getElementById('createTaskForm').reset();
    document.getElementById('customDaysDiv').style.display = 'none';
}

// Handle form submission with AJAX
document.getElementById('createTaskForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating...';
    
    // Get form data
    const formData = new FormData(this);
    
    try {
        // Send to API
        const response = await fetch('api/create_task.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Task created successfully! Confirmation token: ' + data.confirmation_token, 'success');
            resetForm();
            
            // Optional: Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = 'tasks.php';
            }, 2000);
        } else {
            showAlert(data.message || 'Error creating task', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Network error. Please try again.', 'error');
    } finally {
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});
</script>

<script src="app.js"></script>
</body>
</html>