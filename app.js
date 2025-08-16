// app.js - Main JavaScript file

// Toggle notification panel
function toggleNotifications() {
    const panel = document.getElementById('notificationPanel');
    if (panel) {
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            loadNotifications();
        }
    }
}

// Load notifications
async function loadNotifications() {
    try {
        const response = await fetch('api/get_notifications.php');
        const data = await response.json();
        
        const listDiv = document.getElementById('notificationList');
        if (!listDiv) return;
        
        if (data.notifications && data.notifications.length > 0) {
            listDiv.innerHTML = data.notifications.map(n => `
                <div class="p-4 hover:bg-gray-50 ${!n.is_read ? 'bg-blue-50' : ''}">
                    <div class="font-semibold text-sm">${escapeHtml(n.title)}</div>
                    <div class="text-gray-600 text-xs mt-1">${escapeHtml(n.message)}</div>
                    <div class="text-gray-400 text-xs mt-2">${formatTime(n.created_at)}</div>
                </div>
            `).join('');
            
            // Mark as read
            if (data.unread_ids && data.unread_ids.length > 0) {
                markNotificationsRead(data.unread_ids);
            }
        } else {
            listDiv.innerHTML = '<div class="p-4 text-center text-gray-500">No notifications</div>';
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

// Mark notifications as read
async function markNotificationsRead(ids) {
    try {
        await fetch('api/mark_notifications_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_ids: ids })
        });
        
        // Update badge
        const badge = document.querySelector('.fa-bell').nextElementSibling;
        if (badge) badge.style.display = 'none';
    } catch (error) {
        console.error('Error marking notifications as read:', error);
    }
}

// Update task status
async function updateTaskStatus(taskId, newStatus) {
    if (!confirm(`Change task status to ${newStatus}?`)) return;
    
    try {
        const response = await fetch('api/update_task_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task_id: taskId, status: newStatus })
        });
        
        const data = await response.json();
        if (data.success) {
            showAlert(`Status updated! Points: ${data.points}`, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Error updating task', 'error');
        }
    } catch (error) {
        showAlert('Error updating task status', 'error');
    }
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-20 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    alertDiv.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} mr-2"></i>
            ${escapeHtml(message)}
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        alertDiv.style.transition = 'opacity 0.5s';
        setTimeout(() => alertDiv.remove(), 500);
    }, 3000);
}

// Format time ago
function formatTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
    return date.toLocaleDateString();
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Auto-refresh notifications every 30 seconds
setInterval(() => {
    checkNewNotifications();
}, 30000);

// Check for new notifications
async function checkNewNotifications() {
    try {
        const response = await fetch('api/get_notifications.php?count_only=true');
        const data = await response.json();
        
        const badge = document.querySelector('.fa-bell').nextElementSibling;
        if (badge) {
            if (data.unread_count > 0) {
                badge.style.display = 'flex';
                badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
            } else {
                badge.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error checking notifications:', error);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Close notification panel when clicking outside
    document.addEventListener('click', function(e) {
        const panel = document.getElementById('notificationPanel');
        const bell = document.querySelector('.fa-bell').parentElement;
        
        if (panel && !panel.contains(e.target) && !bell.contains(e.target)) {
            panel.classList.add('hidden');
        }
    });
    
    // Check notifications on load
    checkNewNotifications();
});