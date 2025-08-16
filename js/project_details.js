// admin/js/project_details.js - Project Details Interactions

// Tab switching
function showTab(tabName) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active state from all tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-blue-500', 'text-blue-600');
        btn.classList.add('border-transparent', 'text-gray-600');
    });
    
    // Show selected content
    document.getElementById(tabName + '-content').classList.remove('hidden');
    
    // Set active tab
    const activeTab = document.getElementById(tabName + '-tab');
    activeTab.classList.remove('border-transparent', 'text-gray-600');
    activeTab.classList.add('border-blue-500', 'text-blue-600');
}

// Add team member
async function addTeamMember(projectId) {
    const employeeId = document.getElementById('new-member-select').value;
    const role = document.getElementById('new-member-role').value;
    
    if (!employeeId) {
        alert('Please select an employee');
        return;
    }
    
    try {
        const response = await fetch('api/project_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'add_member',
                project_id: projectId,
                employee_id: employeeId,
                role: role
            })
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error adding member');
        }
    } catch (error) {
        alert('Network error');
    }
}

// Remove team member
async function removeMember(projectId, memberId) {
    if (!confirm('Remove this member from the project?')) return;
    
    try {
        const response = await fetch('api/project_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'remove_member',
                project_id: projectId,
                member_id: memberId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error removing member');
        }
    } catch (error) {
        alert('Network error');
    }
}

// Update member role
async function updateMemberRole(projectId, memberId, newRole) {
    try {
        const response = await fetch('api/project_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'update_member_role',
                project_id: projectId,
                member_id: memberId,
                role: newRole
            })
        });
        
        const data = await response.json();
        if (data.success) {
            showNotification('Role updated successfully', 'success');
        } else {
            alert(data.message || 'Error updating role');
        }
    } catch (error) {
        alert('Network error');
    }
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} mr-2"></i>
            ${message}
        </div>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.5s';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

// Initialize charts when stats tab is shown
let chartsInitialized = false;
document.getElementById('stats-tab').addEventListener('click', () => {
    if (!chartsInitialized) {
        initializeCharts();
        chartsInitialized = true;
    }
});

function initializeCharts() {
    // Member performance chart
    const ctx = document.getElementById('memberPerformanceChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: memberNames, // Passed from PHP
                datasets: [{
                    label: 'Tasks Completed',
                    data: memberTaskCounts,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}
