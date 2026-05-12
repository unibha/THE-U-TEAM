<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin and Teacher allowed
checkAuth(['Admin', 'Teacher']);

$pageTitle = "Manage Notices";
include_once '../includes/header.php';

$user = $_SESSION;
$permissions = getUserPermissions($user['role']);
?>

<div class="dashboard-container">
    <!-- Top Gradient Header -->
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
        </div>
        <div class="header-tools">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="noticeSearch" placeholder="Search notices...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 20px; align-items: center;">
                <a href="logout.php" class="header-logout" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: all 0.3s ease;">Logout</a>
                <a href="notifications.php" class="icon-btn" style="background:none; border:none; color:#fff; font-size: 1.2rem; cursor:pointer;"><i class="fas fa-bell"></i></a>
            </div>
        </div>
    </header>

    <div style="display: flex; flex: 1;">
        <!-- Left Sidebar -->
        <aside class="sidebar" style="border-radius: 0 40px 0 0; margin-top: -20px; z-index: 5; background: #fff;">
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="profile.php"><i class="fas fa-user"></i> <span>Account</span></a></li>
                    <li><a href="<?php echo $user['role'] == 'Teacher' ? 'teacher_dashboard.php' : 'admin_dashboard.php'; ?>"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                    <?php if ($user['role'] == 'Admin'): ?>
                        <li><a href="admin_manage_users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
                        <li><a href="admin_manage_courses.php"><i class="fas fa-book"></i> <span>Courses</span></a></li>
                    <?php endif; ?>
                    <?php if ($user['role'] == 'Teacher'): ?>
                        <li><a href="manage_assignments.php"><i class="fas fa-file-alt"></i> <span>Assignments</span></a></li>
                        <li><a href="manage_attendance.php"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a></li>
                        <li><a href="manage_exams.php"><i class="fas fa-clipboard-list"></i> <span>Exams</span></a></li>
                    <?php endif; ?>
                    <li><a href="manage_notices.php" class="active"><i class="fas fa-bullhorn"></i> <span>Notices</span></a></li>
                    <li><a href="logout.php" class="logout-link" style="margin-top: 50px; color: #f43f5e;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section class="page-header" style="margin-bottom: 30px;">
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">Manage Notices</h2>
                <?php if (hasPermission('create_notice', $user)): ?>
                    <button id="createNoticeBtn" class="btn btn-primary" style="background: #8b5cf6; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-plus"></i> Create Notice
                    </button>
                <?php endif; ?>
            </section>

            <!-- Notices Table -->
            <div class="table-container" style="background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid #f1f5f9;">
                <table id="noticesTable">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Title</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Type</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Audience</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Posted By</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Posted Date</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Status</th>
                            <th style="padding: 20px; text-align: center; font-weight: 700; color: #475569;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="noticesTableBody">
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 50px; color: #94a3b8;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 20px;"></i>
                                <p>Loading notices...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Create/Edit Notice Modal -->
<div id="noticeModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 0; border-radius: 20px; width: 90%; max-width: 600px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 20px 20px 0 0;">
            <h3 id="modalTitle" style="margin: 0; font-size: 1.5rem; font-weight: 700;">Create New Notice</h3>
        </div>
        <form id="noticeForm" style="padding: 30px;">
            <input type="hidden" id="noticeId" name="id">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="title" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Title *</label>
                <input type="text" id="title" name="title" required style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; transition: border-color 0.3s ease;">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="description" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Description</label>
                <textarea id="description" name="description" rows="4" style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; resize: vertical; transition: border-color 0.3s ease;"></textarea>
            </div>

            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div class="form-group" style="flex: 1;">
                    <label for="type" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Type</label>
                    <select id="type" name="type" style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; background-color: white;">
                        <option value="General">General</option>
                        <option value="Exam">Exam</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Holiday">Holiday</option>
                        <option value="Event">Event</option>
                    </select>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label for="target_audience" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Target Audience</label>
                    <select id="target_audience" name="target_audience" style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; background-color: white;">
                        <option value="All">All</option>
                        <option value="Students">Students</option>
                        <option value="Teachers">Teachers</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
            </div>

            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div class="form-group" style="flex: 1;">
                    <label for="start_date" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Start Date</label>
                    <input type="datetime-local" id="start_date" name="start_date" style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;">
                </div>

                <div class="form-group" style="flex: 1;">
                    <label for="end_date" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">End Date (Optional)</label>
                    <input type="datetime-local" id="end_date" name="end_date" style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; font-weight: 600; color: #374151;">
                    <input type="checkbox" id="is_active" name="is_active" checked style="margin-right: 8px;">
                    Active
                </label>
            </div>

            <div class="form-actions" style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" id="cancelBtn" class="btn btn-secondary" style="background: #6b7280; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background: #8b5cf6; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer;">Save Notice</button>
            </div>
        </form>
    </div>
</div>

<script>
// API Base URL
const API_BASE = '.';

// Load notices on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotices();
    setupEventListeners();
});

function setupEventListeners() {
    // Create notice button
    document.getElementById('createNoticeBtn').addEventListener('click', function() {
        openModal();
    });

    // Cancel button
    document.getElementById('cancelBtn').addEventListener('click', function() {
        closeModal();
    });

    // Form submission
    document.getElementById('noticeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveNotice();
    });

    // Search functionality
    document.getElementById('noticeSearch').addEventListener('input', function() {
        filterNotices(this.value);
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('noticeModal');
        if (event.target === modal) {
            closeModal();
        }
    });
}

async function loadNotices() {
    try {
        const response = await fetch(`${API_BASE}/notices.php`);
        const notices = await response.json();
        
        displayNotices(notices);
    } catch (error) {
        console.error('Error loading notices:', error);
        showError('Failed to load notices');
    }
}

function displayNotices(notices) {
    const tbody = document.getElementById('noticesTableBody');
    
    if (notices.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 50px; color: #94a3b8;">
                    <i class="fas fa-bullhorn" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                    <p style="font-weight: 600;">No notices found</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = notices.map(notice => `
        <tr class="notice-row searchable-item" data-id="${notice.id}">
            <td style="padding: 20px;">
                <div style="font-weight: 700; color: #1e293b; margin-bottom: 5px;">${notice.title}</div>
                ${notice.description ? `<div style="color: #64748b; font-size: 0.9rem;">${notice.description.substring(0, 100)}${notice.description.length > 100 ? '...' : ''}</div>` : ''}
            </td>
            <td style="padding: 20px;">
                <span class="badge badge-${notice.type.toLowerCase()}" style="padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: ${getTypeColor(notice.type)}; color: white;">
                    ${notice.type}
                </span>
            </td>
            <td style="padding: 20px; color: #475569;">${notice.target_audience}</td>
            <td style="padding: 20px; color: #475569;">${notice.first_name} ${notice.last_name}</td>
            <td style="padding: 20px; color: #475569;">${new Date(notice.created_at).toLocaleDateString()}</td>
            <td style="padding: 20px;">
                <span class="status-badge" style="padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: ${notice.is_active ? '#dcfce7' : '#fee2e2'}; color: ${notice.is_active ? '#166534' : '#991b1b'};">
                    ${notice.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td style="padding: 20px; text-align: center;">
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button onclick="editNotice(${notice.id})" class="btn btn-sm" style="background: #3b82f6; color: white; border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 0.8rem;">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteNotice(${notice.id})" class="btn btn-sm" style="background: #ef4444; color: white; border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 0.8rem;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function getTypeColor(type) {
    const colors = {
        'General': '#6b7280',
        'Exam': '#dc2626',
        'Assignment': '#2563eb',
        'Holiday': '#16a34a',
        'Event': '#9333ea'
    };
    return colors[type] || '#6b7280';
}

function openModal(notice = null) {
    const modal = document.getElementById('noticeModal');
    const form = document.getElementById('noticeForm');
    const modalTitle = document.getElementById('modalTitle');
    
    if (notice) {
        modalTitle.textContent = 'Edit Notice';
        form.elements['id'].value = notice.id;
        form.elements['title'].value = notice.title;
        form.elements['description'].value = notice.description || '';
        form.elements['type'].value = notice.type;
        form.elements['target_audience'].value = notice.target_audience;
        form.elements['start_date'].value = notice.start_date ? new Date(notice.start_date).toISOString().slice(0, 16) : '';
        form.elements['end_date'].value = notice.end_date ? new Date(notice.end_date).toISOString().slice(0, 16) : '';
        form.elements['is_active'].checked = notice.is_active;
    } else {
        modalTitle.textContent = 'Create New Notice';
        form.reset();
        form.elements['start_date'].value = new Date().toISOString().slice(0, 16);
    }
    
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('noticeModal').style.display = 'none';
    document.getElementById('noticeForm').reset();
}

async function saveNotice() {
    const form = document.getElementById('noticeForm');
    const formData = new FormData(form);
    const noticeId = formData.get('id');
    
    const data = {
        title: formData.get('title'),
        description: formData.get('description'),
        type: formData.get('type'),
        target_audience: formData.get('target_audience'),
        start_date: formData.get('start_date'),
        end_date: formData.get('end_date') || null,
        is_active: formData.get('is_active') ? 1 : 0
    };
    
    try {
        const url = noticeId ? `${API_BASE}/notices.php?id=${noticeId}` : `${API_BASE}/notices.php`;
        const method = noticeId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getCookie('jwt_token')}`
            },
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            closeModal();
            loadNotices();
            showSuccess(noticeId ? 'Notice updated successfully' : 'Notice created successfully');
        } else {
            const error = await response.json();
            showError(error.error || 'Failed to save notice');
        }
    } catch (error) {
        console.error('Error saving notice:', error);
        showError('Failed to save notice');
    }
}

async function deleteNotice(noticeId) {
    if (!confirm('Are you sure you want to delete this notice?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/notices.php?id=${noticeId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${getCookie('jwt_token')}`
            }
        });
        
        if (response.ok) {
            loadNotices();
            showSuccess('Notice deleted successfully');
        } else {
            const error = await response.json();
            showError(error.error || 'Failed to delete notice');
        }
    } catch (error) {
        console.error('Error deleting notice:', error);
        showError('Failed to delete notice');
    }
}

async function editNotice(noticeId) {
    try {
        const response = await fetch(`${API_BASE}/notices.php?id=${noticeId}`);
        const notice = await response.json();
        
        if (notice) {
            openModal(notice);
        } else {
            showError('Notice not found');
        }
    } catch (error) {
        console.error('Error loading notice:', error);
        showError('Failed to load notice');
    }
}

function filterNotices(searchTerm) {
    const rows = document.querySelectorAll('.notice-row');
    const term = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
}

// Utility functions
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function showSuccess(message) {
    // Create a success notification
    const notification = document.createElement('div');
    notification.className = 'notification success';
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        background: #10b981; color: white; padding: 16px 24px;
        border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        font-weight: 600; animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.remove(), 3000);
}

function showError(message) {
    // Create an error notification
    const notification = document.createElement('div');
    notification.className = 'notification error';
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        background: #ef4444; color: white; padding: 16px 24px;
        border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        font-weight: 600; animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.remove(), 3000);
}

// Add animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);
</script>

<?php include_once '../includes/footer.php'; ?>
