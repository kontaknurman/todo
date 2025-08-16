<?php
// create_task.php - Updated with Active Time for repeat tasks
require_once 'config.php';
requireLogin();

$employee = getCurrentEmployee();
$pdo = getDB();

// Check if user is admin/manager
$canCreateProjectTask = false;
$isManager = false;
$isAdmin = false;

$stmt = $pdo->prepare("SELECT role FROM employees WHERE id = ?");
$stmt->execute([$employee['id']]);
$user = $stmt->fetch();

if ($user) {
    if ($user['role'] === 'admin') {
        $canCreateProjectTask = true;
        $isAdmin = true;
    } elseif ($user['role'] === 'manager') {
        $canCreateProjectTask = true;
        $isManager = true;
    }
}

// Get all employees (for personal task assignment only)
$stmt = $pdo->query("SELECT id, name, department, whatsapp_number FROM employees ORDER BY name");
$employees = $stmt->fetchAll();

// Get projects based on role
$projects = [];
if ($canCreateProjectTask) {
    if ($isAdmin) {
        $stmt = $pdo->query("
            SELECT id, project_name, project_code, status 
            FROM projects 
            WHERE status IN ('active', 'planning') 
            ORDER BY project_name
        ");
        $projects = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.id, p.project_name, p.project_code, p.status
            FROM projects p
            WHERE p.status IN ('active', 'planning')
            AND (
                p.project_manager_id = ? 
                OR p.id IN (
                    SELECT project_id FROM project_members 
                    WHERE employee_id = ? AND is_active = TRUE
                )
            )
            ORDER BY p.project_name
        ");
        $stmt->execute([$employee['id'], $employee['id']]);
        $projects = $stmt->fetchAll();
    }
}

// Define allowed file types and max size (5MB)
$allowedFileTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'zip', 'rar'];
$maxFileSize = 5 * 1024 * 1024; // 5MB in bytes

$pageTitle = 'Create Task';
require 'layout-header.php';
?>

<div class="bg-white rounded-xl shadow-lg p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">
        <?php echo $canCreateProjectTask ? 'Create New Task' : 'Create Personal Task'; ?>
    </h1>
    
    <?php if ($canCreateProjectTask): ?>
    <!-- Task Type Selection for Managers/Admins -->
    <div class="mb-6 p-4 bg-blue-50 rounded-lg">
        <label class="block text-sm font-medium text-gray-700 mb-2">Task Type</label>
        <div class="flex gap-4">
            <label class="flex items-center">
                <input type="radio" name="task_type_radio" value="project" checked 
                       onchange="toggleTaskType()" class="mr-2">
                <span class="font-medium">Project Task</span>
                <span class="text-sm text-gray-500 ml-2">(Assign to department members)</span>
            </label>
            <label class="flex items-center">
                <input type="radio" name="task_type_radio" value="personal" 
                       onchange="toggleTaskType()" class="mr-2">
                <span class="font-medium">Personal Task</span>
                <span class="text-sm text-gray-500 ml-2">(Assign to individual)</span>
            </label>
        </div>
        
        <?php if ($isManager && !$isAdmin): ?>
        <div class="mt-2 p-2 bg-yellow-50 rounded">
            <p class="text-xs text-yellow-700">
                <i class="fas fa-info-circle mr-1"></i>
                You can only create tasks for projects and departments where you are a member or project manager.
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="mb-6 p-4 bg-yellow-50 rounded-lg">
        <p class="text-sm text-yellow-800">
            <i class="fas fa-info-circle mr-2"></i>
            You are creating a personal task. This task will be private and not included in project reports.
        </p>
    </div>
    <?php endif; ?>
    
    <div id="alertMessage" class="hidden mb-4 p-4 rounded-lg">
        <i class="fas fa-info-circle mr-2"></i>
        <span id="alertText"></span>
    </div>
    
    <form id="createTaskForm" class="space-y-6" enctype="multipart/form-data">
        <input type="hidden" name="task_type" id="taskTypeInput" 
               value="<?php echo $canCreateProjectTask ? 'project' : 'personal'; ?>">
        <input type="hidden" name="user_role" value="<?php echo $user['role'] ?? 'employee'; ?>">
        
        <!-- Project Selection (Only for Project Tasks) -->
        <?php if ($canCreateProjectTask): ?>
        <div id="projectSection" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-project-diagram mr-1"></i> Project <span class="text-red-500">*</span>
                    </label>
                    <select name="project_id" id="projectSelect" onchange="loadProjectDepartments()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Select Project...</option>
                        <?php foreach ($projects as $proj): ?>
                        <option value="<?php echo $proj['id']; ?>">
                            <?php echo htmlspecialchars($proj['project_name']); ?> 
                            (<?php echo htmlspecialchars($proj['project_code']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($projects)): ?>
                    <p class="text-xs text-red-500 mt-1">You are not assigned to any active projects.</p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-building mr-1"></i> Department <span class="text-red-500">*</span>
                    </label>
                    <select name="department_id" id="departmentSelect" disabled onchange="loadDepartmentMembers()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Select Project First...</option>
                    </select>
                </div>
            </div>
            
            <!-- Department Members for Project Task -->
            <div id="departmentMembersSection" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-users mr-1"></i> Assign to Department Members <span class="text-red-500">*</span>
                </label>
                <div class="border rounded-lg p-3 max-h-60 overflow-y-auto bg-gray-50">
                    <div id="departmentMembersList">
                        <p class="text-sm text-gray-500">Select department to see members</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Basic Task Fields -->
        <div id="taskTitleSection" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="<?php echo $canCreateProjectTask ? 'md:col-span-2' : ''; ?>" id="titleFieldWrapper">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-heading mr-1"></i> Task Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" required maxlength="255"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            
            <!-- Assign To Section - Only for Personal Tasks -->
            <div id="individualAssignment" class="<?php echo $canCreateProjectTask ? 'hidden' : ''; ?>">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user mr-1"></i> Assign To <span class="text-red-500">*</span>
                </label>
                <select name="employee_id" id="employeeSelect"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <?php if ($canCreateProjectTask): ?>
                        <option value="">Select Employee...</option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>">
                            <?php echo htmlspecialchars($emp['name']); ?> 
                            (<?php echo htmlspecialchars($emp['department'] ?? 'No Dept'); ?>)
                        </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="<?php echo $employee['id']; ?>" selected>
                            <?php echo htmlspecialchars($employee['name']); ?> (Me)
                        </option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-align-left mr-1"></i> Description
            </label>
            <textarea name="description" rows="3" maxlength="1000"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
        </div>
        
        <!-- File Attachments -->
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-paperclip mr-1"></i> Attachments
            </label>
            <div class="space-y-2">
                <input type="file" name="attachments[]" id="fileInput" multiple 
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.gif,.txt,.zip,.rar"
                       class="hidden" onchange="handleFileSelect(this)">
                <button type="button" onclick="document.getElementById('fileInput').click()" 
                        class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-upload mr-2"></i> Choose Files
                </button>
                <p class="text-xs text-gray-500">
                    Allowed types: PDF, DOC, DOCX, XLS, XLSX, PNG, JPG, GIF, TXT, ZIP, RAR (Max 5MB per file)
                </p>
                <div id="fileList" class="mt-2 space-y-1"></div>
            </div>
        </div>
        
        <!-- Date and Time Fields -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar mr-1"></i> Due Date <span class="text-red-500">*</span>
                </label>
                <input type="date" name="due_date" id="dueDate" required min="<?php echo date('Y-m-d'); ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            
            <!-- Due Time - Hidden for repeat tasks -->
            <div id="dueTimeDiv">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-clock mr-1"></i> Due Time
                </label>
                <input type="time" name="due_time" id="dueTime"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            
            <!-- Active Time - Only for repeat tasks -->
            <div id="activeTimeDiv" style="display:none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-bell mr-1"></i> Active Time <span class="text-red-500">*</span>
                    <span class="text-xs text-gray-500">(When task becomes active)</span>
                </label>
                <input type="time" name="active_time" id="activeTime" value="09:00"
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
        
        <!-- Time Limit - Now Optional -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-hourglass-half mr-1"></i> Time Limit (Hours)
                    <span class="text-xs text-gray-500">(Optional - 0 for no limit)</span>
                </label>
                <input type="number" name="time_limit_hours" min="0" max="72" value="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-hourglass-half mr-1"></i> Time Limit (Minutes)
                    <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="number" name="time_limit_minutes" min="0" max="59" value="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>
        
        <!-- Priority for Project Tasks -->
        <?php if ($canCreateProjectTask): ?>
        <div id="prioritySection">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-flag mr-1"></i> Priority
            </label>
            <select name="priority" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>
        <?php endif; ?>
        
        <!-- Repeat Options -->
        <div class="border rounded-lg p-4 bg-gray-50">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-redo mr-1"></i> Repeat Task
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Repeat Type</label>
                    <select name="repeat_type" id="repeatType" onchange="toggleRepeatOptions()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="none">No Repeat</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="custom">Custom Days</option>
                    </select>
                </div>
                
                <div id="customDaysDiv" style="display:none;">
                    <label class="block text-xs text-gray-600 mb-1">Custom Days (comma separated)</label>
                    <input type="text" name="custom_days" id="customDays" placeholder="Mon,Wed,Fri or 1,15,30"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">For weekly: Mon,Tue,Wed | For monthly: 1,15,30</p>
                </div>
                
                <div id="repeatEndDiv" style="display:none;">
                    <label class="block text-xs text-gray-600 mb-1">Repeat Until (Optional)</label>
                    <input type="date" name="repeat_until" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div id="repeatCountDiv" style="display:none;">
                    <label class="block text-xs text-gray-600 mb-1">Number of Occurrences (Optional)</label>
                    <input type="number" name="repeat_count" min="2" max="365" placeholder="Leave empty for max 365"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            
            <div id="repeatSummary" class="mt-3 p-2 bg-blue-50 rounded hidden">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    <span id="repeatSummaryText"></span>
                </p>
            </div>
        </div>
        
        <div class="flex space-x-4">
            <button type="submit" id="submitBtn" 
                    class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                <i class="fas fa-save mr-2"></i> Create Task
            </button>
            <button type="button" onclick="resetForm()" 
                    class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">
                <i class="fas fa-redo mr-2"></i> Reset
            </button>
            <a href="index.php" 
               class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition inline-block">
                <i class="fas fa-times mr-2"></i> Cancel
            </a>
        </div>
    </form>
</div>

</div>

<script>
// Pass user info to JavaScript
const currentUserId = <?php echo $employee['id']; ?>;
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
const isManager = <?php echo $isManager ? 'true' : 'false'; ?>;

// File handling code
const maxFileSize = <?php echo $maxFileSize; ?>;
const allowedTypes = <?php echo json_encode($allowedFileTypes); ?>;
let selectedFiles = [];

function handleFileSelect(input) {
    const files = input.files;
    const fileList = document.getElementById('fileList');
    fileList.innerHTML = '';
    selectedFiles = [];
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileExt = file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(fileExt)) {
            showAlert(`File ${file.name} has invalid type. Allowed: ${allowedTypes.join(', ')}`, 'error');
            continue;
        }
        
        if (file.size > maxFileSize) {
            showAlert(`File ${file.name} exceeds 5MB limit`, 'error');
            continue;
        }
        
        selectedFiles.push(file);
        
        const fileDiv = document.createElement('div');
        fileDiv.className = 'flex items-center justify-between bg-gray-50 p-2 rounded';
        fileDiv.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-file mr-2 text-gray-500"></i>
                <span class="text-sm">${file.name}</span>
                <span class="text-xs text-gray-400 ml-2">(${formatFileSize(file.size)})</span>
            </div>
            <button type="button" onclick="removeFile(${i})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
            </button>
        `;
        fileList.appendChild(fileDiv);
    }
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    document.getElementById('fileInput').files = dt.files;
    handleFileSelect(document.getElementById('fileInput'));
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Toggle repeat options with Active Time
function toggleRepeatOptions() {
    const repeatType = document.getElementById('repeatType').value;
    const customDaysDiv = document.getElementById('customDaysDiv');
    const repeatEndDiv = document.getElementById('repeatEndDiv');
    const repeatCountDiv = document.getElementById('repeatCountDiv');
    const repeatSummary = document.getElementById('repeatSummary');
    const repeatSummaryText = document.getElementById('repeatSummaryText');
    
    // Toggle Due Time vs Active Time
    const dueTimeDiv = document.getElementById('dueTimeDiv');
    const activeTimeDiv = document.getElementById('activeTimeDiv');
    const activeTime = document.getElementById('activeTime');
    const dueTime = document.getElementById('dueTime');
    
    // Reset all
    customDaysDiv.style.display = 'none';
    repeatEndDiv.style.display = 'none';
    repeatCountDiv.style.display = 'none';
    repeatSummary.classList.add('hidden');
    
    if (repeatType === 'none') {
        // Show Due Time, Hide Active Time for non-repeat
        dueTimeDiv.style.display = 'block';
        activeTimeDiv.style.display = 'none';
        dueTime.required = false;
        activeTime.required = false;
        return;
    }
    
    // For repeat tasks - Show Active Time, Hide Due Time
    dueTimeDiv.style.display = 'none';
    activeTimeDiv.style.display = 'block';
    dueTime.required = false;
    activeTime.required = true;
    
    // Show end date and count options for all repeat types
    repeatEndDiv.style.display = 'block';
    repeatCountDiv.style.display = 'block';
    
    // Show custom days input for custom type
    if (repeatType === 'custom') {
        customDaysDiv.style.display = 'block';
        repeatSummaryText.textContent = 'Task will repeat on specified days at the active time';
    } else if (repeatType === 'daily') {
        repeatSummaryText.textContent = 'Task will repeat every day at the active time';
    } else if (repeatType === 'weekly') {
        const dueDate = document.getElementById('dueDate').value;
        if (dueDate) {
            const date = new Date(dueDate);
            const dayName = date.toLocaleDateString('en-US', { weekday: 'long' });
            repeatSummaryText.textContent = `Task will repeat every ${dayName} at the active time`;
        } else {
            repeatSummaryText.textContent = 'Task will repeat weekly on the same day at the active time';
        }
    } else if (repeatType === 'monthly') {
        const dueDate = document.getElementById('dueDate').value;
        if (dueDate) {
            const date = new Date(dueDate);
            const dayNum = date.getDate();
            repeatSummaryText.textContent = `Task will repeat on day ${dayNum} of each month at the active time`;
        } else {
            repeatSummaryText.textContent = 'Task will repeat monthly on the same date at the active time';
        }
    }
    
    repeatSummary.classList.remove('hidden');
}

// Update summary when due date changes for weekly/monthly
document.getElementById('dueDate').addEventListener('change', function() {
    const repeatType = document.getElementById('repeatType').value;
    if (repeatType !== 'none') {
        toggleRepeatOptions();
    }
});

// Validate custom days input
function validateCustomDays() {
    const repeatType = document.getElementById('repeatType').value;
    if (repeatType !== 'custom') return true;
    
    const customDays = document.getElementById('customDays').value.trim();
    if (!customDays) {
        showAlert('Please specify custom days for repeat', 'error');
        return false;
    }
    
    const weekDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    const parts = customDays.toLowerCase().split(',').map(d => d.trim());
    
    for (let part of parts) {
        if (weekDays.includes(part.toLowerCase())) {
            continue;
        }
        const num = parseInt(part);
        if (isNaN(num) || num < 1 || num > 31) {
            showAlert('Invalid custom days format. Use weekday names (Mon,Wed) or dates (1,15,30)', 'error');
            return false;
        }
    }
    
    return true;
}

<?php if ($canCreateProjectTask): ?>
// Toggle task type (Project vs Personal)
function toggleTaskType() {
    const taskType = document.querySelector('input[name="task_type_radio"]:checked').value;
    document.getElementById('taskTypeInput').value = taskType;
    
    const projectSection = document.getElementById('projectSection');
    const individualAssignment = document.getElementById('individualAssignment');
    const departmentMembersSection = document.getElementById('departmentMembersSection');
    const employeeSelect = document.getElementById('employeeSelect');
    const titleFieldWrapper = document.getElementById('titleFieldWrapper');
    
    if (taskType === 'project') {
        projectSection.style.display = 'block';
        individualAssignment.style.display = 'none';
        employeeSelect.required = false;
        titleFieldWrapper.classList.add('md:col-span-2');
        titleFieldWrapper.classList.remove('md:col-span-1');
    } else {
        projectSection.style.display = 'none';
        individualAssignment.style.display = 'block';
        departmentMembersSection.classList.add('hidden');
        employeeSelect.required = true;
        titleFieldWrapper.classList.remove('md:col-span-2');
        titleFieldWrapper.classList.add('md:col-span-1');
    }
}

// Load project departments with permission check
async function loadProjectDepartments() {
    const projectId = document.getElementById('projectSelect').value;
    const deptSelect = document.getElementById('departmentSelect');
    const departmentMembersSection = document.getElementById('departmentMembersSection');
    
    if (!projectId) {
        deptSelect.innerHTML = '<option value="">Select Project First...</option>';
        deptSelect.disabled = true;
        departmentMembersSection.classList.add('hidden');
        return;
    }
    
    deptSelect.innerHTML = '<option value="">Loading departments...</option>';
    deptSelect.disabled = true;
    
    try {
        const response = await fetch(`api/get_project_departments_with_permission.php?project_id=${projectId}&user_id=${currentUserId}`);
        const data = await response.json();
        
        if (data.success) {
            deptSelect.innerHTML = '<option value="">Select Department...</option>';
            
            if (data.departments.length === 0) {
                deptSelect.innerHTML = '<option value="">No departments available</option>';
                if (!isAdmin && !data.is_project_manager) {
                    showAlert('You are not a member of any department in this project', 'warning');
                }
            } else {
                if (data.is_project_manager && !isAdmin) {
                    showAlert('As Project Manager, you have access to all departments in this project', 'info');
                }
                
                data.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = `${dept.department_name} (${dept.department_code})`;
                    
                    if (data.is_project_manager) {
                        option.textContent += ' ✓';
                    } else if (dept.is_member) {
                        option.textContent += ' ✓';
                    }
                    
                    deptSelect.appendChild(option);
                });
                deptSelect.disabled = false;
            }
        } else {
            deptSelect.innerHTML = '<option value="">Error loading departments</option>';
            showAlert(data.message || 'Error loading departments', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        deptSelect.innerHTML = '<option value="">Error loading departments</option>';
    }
}

// Load department members
async function loadDepartmentMembers() {
    const deptId = document.getElementById('departmentSelect').value;
    const membersList = document.getElementById('departmentMembersList');
    const departmentMembersSection = document.getElementById('departmentMembersSection');
    
    if (!deptId) {
        membersList.innerHTML = '<p class="text-sm text-gray-500">Select department to see members</p>';
        departmentMembersSection.classList.add('hidden');
        return;
    }
    
    departmentMembersSection.classList.remove('hidden');
    membersList.innerHTML = '<p class="text-sm text-gray-500">Loading members...</p>';
    
    try {
        const response = await fetch(`api/get_department_members.php?department_id=${deptId}`);
        const data = await response.json();
        
        if (data.success) {
            if (data.members.length === 0) {
                membersList.innerHTML = '<p class="text-sm text-gray-500">No members in this department</p>';
            } else {
                membersList.innerHTML = '';
                
                const countDiv = document.createElement('div');
                countDiv.className = 'mb-2 p-2 bg-blue-50 rounded';
                countDiv.innerHTML = `
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        ${data.members.length} member(s) in this department. Task will be assigned to selected members.
                    </p>
                `;
                membersList.appendChild(countDiv);
                
                data.members.forEach(member => {
                    const label = document.createElement('label');
                    label.className = 'flex items-center p-2 hover:bg-white rounded cursor-pointer';
                    
                    if (member.id == currentUserId) {
                        label.className += ' bg-blue-50';
                    }
                    
                    label.innerHTML = `
                        <input type="checkbox" name="department_members[]" value="${member.id}" 
                               class="mr-2 department-member-checkbox">
                        <span class="text-sm">${member.name}</span>
                        <span class="text-xs text-gray-500 ml-2">(${member.position || 'Member'})</span>
                        ${member.id == currentUserId ? '<span class="text-xs text-blue-600 ml-2">(You)</span>' : ''}
                    `;
                    membersList.appendChild(label);
                });
                
                const selectAllDiv = document.createElement('div');
                selectAllDiv.className = 'border-t pt-2 mt-2';
                selectAllDiv.innerHTML = `
                    <label class="flex items-center text-sm font-medium text-blue-600 cursor-pointer">
                        <input type="checkbox" id="selectAllMembers" onchange="toggleAllMembers()" class="mr-2">
                        Select All Members
                    </label>
                `;
                membersList.appendChild(selectAllDiv);
            }
        } else {
            membersList.innerHTML = '<p class="text-sm text-red-500">Error loading members</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        membersList.innerHTML = '<p class="text-sm text-red-500">Network error</p>';
    }
}

function toggleAllMembers() {
    const selectAll = document.getElementById('selectAllMembers');
    const checkboxes = document.querySelectorAll('.department-member-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}
<?php endif; ?>

// Show alert message
function showAlert(message, type) {
    const alertDiv = document.getElementById('alertMessage');
    const alertText = document.getElementById('alertText');
    
    alertDiv.className = 'mb-4 p-4 rounded-lg border';
    
    if (type === 'success') {
        alertDiv.className += ' bg-green-50 text-green-700 border-green-200';
    } else if (type === 'error') {
        alertDiv.className += ' bg-red-50 text-red-700 border-red-200';
    } else if (type === 'warning') {
        alertDiv.className += ' bg-yellow-50 text-yellow-700 border-yellow-200';
    } else {
        alertDiv.className += ' bg-blue-50 text-blue-700 border-blue-200';
    }
    
    alertText.textContent = message;
    alertDiv.classList.remove('hidden');
    
    setTimeout(() => {
        alertDiv.classList.add('hidden');
    }, 5000);
}

// Reset form
function resetForm() {
    document.getElementById('createTaskForm').reset();
    document.getElementById('fileList').innerHTML = '';
    selectedFiles = [];
    toggleRepeatOptions(); // Reset repeat options
    <?php if ($canCreateProjectTask): ?>
    document.getElementById('departmentSelect').innerHTML = '<option value="">Select Project First...</option>';
    document.getElementById('departmentSelect').disabled = true;
    document.getElementById('departmentMembersList').innerHTML = '<p class="text-sm text-gray-500">Select department to see members</p>';
    document.getElementById('departmentMembersSection').classList.add('hidden');
    toggleTaskType();
    <?php endif; ?>
}

// Handle form submission
document.getElementById('createTaskForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating...';
    
    const formData = new FormData(this);
    
    // Validate repeat options
    if (!validateCustomDays()) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        return;
    }
    
    const repeatType = formData.get('repeat_type');
    if (repeatType && repeatType !== 'none') {
        const repeatUntil = formData.get('repeat_until');
        const repeatCount = formData.get('repeat_count');
        
        // Validate active time for repeat tasks
        const activeTime = formData.get('active_time');
        if (!activeTime) {
            showAlert('Please set Active Time for repeat tasks', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
        }
        
        if (!repeatUntil && !repeatCount) {
            const confirmRepeat = confirm('Task will repeat indefinitely (max 365 occurrences). Continue?');
            if (!confirmRepeat) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }
        }
    }
    
    <?php if ($canCreateProjectTask): ?>
    // Validate based on task type
    const taskType = formData.get('task_type');
    
    if (taskType === 'project') {
        const projectId = formData.get('project_id');
        const deptId = formData.get('department_id');
        
        if (!projectId || !deptId) {
            showAlert('Please select both Project and Department for project tasks', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
        }
        
        const checkedMembers = document.querySelectorAll('.department-member-checkbox:checked');
        if (checkedMembers.length === 0) {
            showAlert('Please select at least one department member to assign the task', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
        }
        
        formData.append('assign_type', 'department');
    } else {
        const employeeId = formData.get('employee_id');
        if (!employeeId) {
            showAlert('Please select an employee to assign the task', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
        }
        
        formData.append('assign_type', 'individual');
    }
    <?php else: ?>
    formData.append('assign_type', 'individual');
    <?php endif; ?>
    
    // Time limit is now optional - no validation needed
    
    try {
        const response = await fetch('api/create_task_enhanced.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(`Task created successfully! ${data.tasks_created || 1} task(s) created.`, 'success');
            resetForm();
            
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
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

// Initialize on page load
<?php if ($canCreateProjectTask): ?>
document.addEventListener('DOMContentLoaded', function() {
    toggleTaskType();
});
<?php endif; ?>
</script>

<script src="app.js"></script>
</body>
</html>