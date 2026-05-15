<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin and Teacher allowed
checkAuth(['Admin', 'Teacher']);

$pageTitle = "Manage Exams";
include_once '../includes/header.php';

$user = $_SESSION;
$permissions = getUserPermissions($user['role']);

// Get courses for dropdown
$courses = [];
try {
    if ($user['role'] === 'Admin') {
        $stmt = $pdo->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name");
    } else {
        // Teachers can only see their own courses
        $stmt = $pdo->prepare("
            SELECT c.id, c.course_name, c.course_code 
            FROM courses c 
            JOIN teachers t ON c.teacher_id = t.id 
            WHERE t.user_id = ?
            ORDER BY c.course_name
        ");
        $stmt->execute([$user['user_id']]);
    }
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $courses = [];
}
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
                <input type="text" id="examSearch" placeholder="Search exams...">
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
                    <?php endif; ?>
                    <li><a href="manage_exams.php" class="active"><i class="fas fa-clipboard-list"></i> <span>Exams</span></a></li>
                    <li><a href="manage_notices.php"><i class="fas fa-bullhorn"></i> <span>Notices</span></a></li>
                    <li><a href="logout.php" class="logout-link" style="margin-top: 50px; color: #f43f5e;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section class="page-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">Manage Exams</h2>
                <div style="display: flex; gap: 15px;">
                    <?php if (hasPermission('create_exam', $user)): ?>
                        <button id="createExamBtn" class="btn btn-primary" style="background: #8b5cf6; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                            <i class="fas fa-plus"></i> Create Exam
                        </button>
                    <?php endif; ?>
                    <button id="checkConflictsBtn" class="btn btn-warning" style="background: #f59e0b; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        <i class="fas fa-exclamation-triangle"></i> Check Conflicts
                    </button>
                </div>
            </section>

            <!-- Conflicts Alert (Hidden by default) -->
            <div id="conflictsAlert" class="alert alert-warning" style="display: none; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                <h4 style="color: #92400e; margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Scheduling Conflicts Detected</h4>
                <div id="conflictsList"></div>
            </div>

            <!-- Exams Table -->
            <div class="table-container" style="background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid #f1f5f9;">
                <table id="examsTable">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Title</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Course</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Type</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Date & Time</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Duration</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Venue</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Max Marks</th>
                            <th style="padding: 20px; text-align: center; font-weight: 700; color: #475569;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="examsTableBody">
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 50px; color: #94a3b8;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 20px;"></i>
                                <p>Loading exams...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Create/Edit Exam Modal -->
<div id="examModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: #fefefe; margin: 3% auto; padding: 0; border-radius: 20px; width: 90%; max-width: 700px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); max-height: 90vh; overflow-y: auto;">
        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 20px 20px 0 0;">
            <h3 id="modalTitle" style="margin: 0; font-size: 1.5rem; font-weight: 700;">Create New Exam</h3>
        </div>
        <form id="examForm" style="padding: 30px;">
            <input type="hidden" id="examId" name="id">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="course_id" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Course *</label>
                <select id="course_id" name="course_id" required style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; background-color: white;">
                    <option value="">Select a course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="title" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Title *</label>
                <input type="text" id="title" name="title" required style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="description" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Description</label>
                <textarea id="description" name="description" rows="3" style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; resize: vertical;"></textarea>
            </div>

            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div class="form-group" style="flex: 1;">
                    <label for="exam_type" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Exam Type</label>
                    <select id="exam_type" name="exam_type" style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; background-color: white;">
                        <option value="Midterm">Midterm</option>
                        <option value="Final">Final</option>
                        <option value="Quiz">Quiz</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Practical">Practical</option>
                    </select>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label for="max_marks" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Max Marks</label>
                    <input type="number" id="max_marks" name="max_marks" value="100" min="1" step="0.01" style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;">
                </div>
            </div>

            <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div class="form-group" style="flex: 1;">
                    <label for="exam_date" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Date & Time *</label>
                    <input type="datetime-local" id="exam_date" name="exam_date" required style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;">
                </div>

                <div class="form-group" style="flex: 1;">
                    <label for="duration" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Duration (minutes)</label>
                    <input type="number" id="duration" name="duration" value="60" min="15" max="480" style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="venue" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Venue</label>
                <input type="text" id="venue" name="venue" placeholder="e.g., Room 101, Lab A" style="width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem;">
            </div>

            <div class="form-actions" style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" id="cancelBtn" class="btn btn-secondary" style="background: #6b7280; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background: #8b5cf6; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer;">Save Exam</button>
            </div>
        </form>
    </div>
</div>

<script>
// API Base URL
const API_BASE = '.';

// Load exams on page load
document.addEventListener('DOMContentLoaded', function() {
    loadExams();
    setupEventListeners();
});

function setupEventListeners() {
    // Create exam button
    document.getElementById('createExamBtn').addEventListener('click', function() {
        openModal();
    });

    // Check conflicts button
    document.getElementById('checkConflictsBtn').addEventListener('click', function() {
        checkConflicts();
    });

    // Cancel button
    document.getElementById('cancelBtn').addEventListener('click', function() {
        closeModal();
    });

    // Form submission
    document.getElementById('examForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveExam();
    });

    // Search functionality
    document.getElementById('examSearch').addEventListener('input', function() {
        filterExams(this.value);
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('examModal');
        if (event.target === modal) {
            closeModal();
        }
    });
}

async function loadExams() {
    try {
        const response = await fetch(`${API_BASE}/exams.php`);
        const result = await response.json();
        
        if (result.exams && result.conflicts) {
            displayExams(result.exams);
            if (result.conflicts.length > 0) {
                showConflicts(result.conflicts);
            }
        } else {
            displayExams(result);
        }
    } catch (error) {
        console.error('Error loading exams:', error);
        showError('Failed to load exams');
    }
}

async function checkConflicts() {
    try {
        const response = await fetch(`${API_BASE}/exams.php?check_conflicts=1`);
        const result = await response.json();
        
        if (result.conflicts && result.conflicts.length > 0) {
            showConflicts(result.conflicts);
        } else {
            showSuccess('No scheduling conflicts detected');
        }
    } catch (error) {
        console.error('Error checking conflicts:', error);
        showError('Failed to check conflicts');
    }
}

function showConflicts(conflicts) {
    const alert = document.getElementById('conflictsAlert');
    const list = document.getElementById('conflictsList');
    
    list.innerHTML = conflicts.map(conflict => `
        <div style="background: #fed7aa; border-radius: 8px; padding: 15px; margin-bottom: 10px;">
            <strong style="color: #9a3412;">${conflict.conflict_type} Conflict:</strong><br>
            <span style="color: #9a3412;">
                "${conflict.exam1.title}" and "${conflict.exam2.title}" are scheduled at the same time on ${new Date(conflict.exam_date).toLocaleString()}
            </span>
        </div>
    `).join('');
    
    alert.style.display = 'block';
}

function displayExams(exams) {
    const tbody = document.getElementById('examsTableBody');
    
    if (exams.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 50px; color: #94a3b8;">
                    <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                    <p style="font-weight: 600;">No exams found</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = exams.map(exam => `
        <tr class="exam-row searchable-item" data-id="${exam.id}">
            <td style="padding: 20px;">
                <div style="font-weight: 700; color: #1e293b; margin-bottom: 5px;">${exam.title}</div>
                ${exam.description ? `<div style="color: #64748b; font-size: 0.9rem;">${exam.description.substring(0, 100)}${exam.description.length > 100 ? '...' : ''}</div>` : ''}
            </td>
            <td style="padding: 20px; color: #475569;">
                <div style="font-weight: 600;">${exam.course_name}</div>
                <div style="font-size: 0.85rem; color: #64748b;">${exam.course_code}</div>
            </td>
            <td style="padding: 20px;">
                <span class="badge badge-${exam.exam_type.toLowerCase()}" style="padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: ${getExamTypeColor(exam.exam_type)}; color: white;">
                    ${exam.exam_type}
                </span>
            </td>
            <td style="padding: 20px; color: #475569;">
                <div style="font-weight: 600;">${new Date(exam.exam_date).toLocaleDateString()}</div>
                <div style="font-size: 0.85rem; color: #64748b;">${new Date(exam.exam_date).toLocaleTimeString()}</div>
            </td>
            <td style="padding: 20px; color: #475569;">${exam.duration} min</td>
            <td style="padding: 20px; color: #475569;">${exam.venue || 'Not specified'}</td>
            <td style="padding: 20px; color: #475569; font-weight: 600;">${exam.max_marks}</td>
            <td style="padding: 20px; text-align: center;">
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button onclick="editExam(${exam.id})" class="btn btn-sm" style="background: #3b82f6; color: white; border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 0.8rem;">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="manageMarks(${exam.id})" class="btn btn-sm" style="background: #10b981; color: white; border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 0.8rem;">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                    <button onclick="deleteExam(${exam.id})" class="btn btn-sm" style="background: #ef4444; color: white; border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-size: 0.8rem;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function getExamTypeColor(type) {
    const colors = {
        'Midterm': '#dc2626',
        'Final': '#7c3aed',
        'Quiz': '#0891b2',
        'Assignment': '#2563eb',
        'Practical': '#059669'
    };
    return colors[type] || '#6b7280';
}

function openModal(exam = null) {
    const modal = document.getElementById('examModal');
    const form = document.getElementById('examForm');
    const modalTitle = document.getElementById('modalTitle');
    
    if (exam) {
        modalTitle.textContent = 'Edit Exam';
        form.elements['id'].value = exam.id;
        form.elements['course_id'].value = exam.course_id;
        form.elements['title'].value = exam.title;
        form.elements['description'].value = exam.description || '';
        form.elements['exam_type'].value = exam.exam_type;
        form.elements['max_marks'].value = exam.max_marks;
        form.elements['exam_date'].value = new Date(exam.exam_date).toISOString().slice(0, 16);
        form.elements['duration'].value = exam.duration;
        form.elements['venue'].value = exam.venue || '';
    } else {
        modalTitle.textContent = 'Create New Exam';
        form.reset();
        form.elements['max_marks'].value = '100';
        form.elements['duration'].value = '60';
        form.elements['exam_date'].value = new Date().toISOString().slice(0, 16);
    }
    
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('examModal').style.display = 'none';
    document.getElementById('examForm').reset();
}

async function saveExam() {
    const form = document.getElementById('examForm');
    const formData = new FormData(form);
    const examId = formData.get('id');
    
    const data = {
        course_id: parseInt(formData.get('course_id')),
        title: formData.get('title'),
        description: formData.get('description'),
        exam_type: formData.get('exam_type'),
        max_marks: parseFloat(formData.get('max_marks')),
        exam_date: formData.get('exam_date'),
        duration: parseInt(formData.get('duration')),
        venue: formData.get('venue')
    };
    
    try {
        const url = examId ? `${API_BASE}/exams.php?id=${examId}` : `${API_BASE}/exams.php`;
        const method = examId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getCookie('jwt_token')}`
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            closeModal();
            loadExams();
            showSuccess(examId ? 'Exam updated successfully' : 'Exam created successfully');
        } else {
            showError(result.error || 'Failed to save exam');
        }
    } catch (error) {
        console.error('Error saving exam:', error);
        showError('Failed to save exam');
    }
}

async function deleteExam(examId) {
    if (!confirm('Are you sure you want to delete this exam? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/exams.php?id=${examId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${getCookie('jwt_token')}`
            }
        });
        
        if (response.ok) {
            loadExams();
            showSuccess('Exam deleted successfully');
        } else {
            const error = await response.json();
            showError(error.error || 'Failed to delete exam');
        }
    } catch (error) {
        console.error('Error deleting exam:', error);
        showError('Failed to delete exam');
    }
}

async function editExam(examId) {
    try {
        const response = await fetch(`${API_BASE}/exams.php?id=${examId}`);
        const exam = await response.json();
        
        if (exam) {
            openModal(exam);
        } else {
            showError('Exam not found');
        }
    } catch (error) {
        console.error('Error loading exam:', error);
        showError('Failed to load exam');
    }
}

function manageMarks(examId) {
    window.location.href = `manage_marks.php?exam_id=${examId}`;
}

function filterExams(searchTerm) {
    const rows = document.querySelectorAll('.exam-row');
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
