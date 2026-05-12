<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// All roles allowed to view marks
checkAuth(['Admin', 'Teacher', 'Student']);

$pageTitle = "View Marks";
include_once '../includes/header.php';

$user = $_SESSION;
$studentId = null;

// Get student ID based on user role
if ($user['role'] === 'Student') {
    // Get student's internal ID
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    $studentData = $stmt->fetch();
    $studentId = $studentData['id'] ?? null;
}

// Get students for dropdown (for teachers/admin)
$students = [];
if ($user['role'] !== 'Student') {
    try {
        $stmt = $pdo->query("
            SELECT s.id, u.first_name, u.last_name, u.email 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            ORDER BY u.first_name, u.last_name
        ");
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        $students = [];
    }
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
                <input type="text" id="marksSearch" placeholder="Search marks...">
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
                    <li><a href="<?php echo $user['role'] == 'Teacher' ? 'teacher_dashboard.php' : ($user['role'] == 'Student' ? 'student_dashboard.php' : 'admin_dashboard.php'); ?>"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                    <?php if ($user['role'] == 'Admin'): ?>
                        <li><a href="admin_manage_users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
                        <li><a href="admin_manage_courses.php"><i class="fas fa-book"></i> <span>Courses</span></a></li>
                    <?php endif; ?>
                    <?php if ($user['role'] == 'Teacher'): ?>
                        <li><a href="manage_assignments.php"><i class="fas fa-file-alt"></i> <span>Assignments</span></a></li>
                        <li><a href="manage_attendance.php"><i class="fas fa-calendar-check"></i> <span>Attendance</span></a></li>
                        <li><a href="manage_exams.php"><i class="fas fa-clipboard-list"></i> <span>Exams</span></a></li>
                    <?php endif; ?>
                    <li><a href="view_marks.php" class="active"><i class="fas fa-chart-bar"></i> <span>Marks</span></a></li>
                    <li><a href="view_gpa.php"><i class="fas fa-graduation-cap"></i> <span>GPA</span></a></li>
                    <li><a href="logout.php" class="logout-link" style="margin-top: 50px; color: #f43f5e;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section class="page-header" style="margin-bottom: 30px;">
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">View Marks</h2>
                
                <?php if ($user['role'] !== 'Student'): ?>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <select id="studentSelect" style="padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; background-color: white;">
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button id="calculateGPABtn" class="btn btn-primary" style="background: #8b5cf6; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-calculator"></i> Calculate GPA
                        </button>
                    </div>
                <?php endif; ?>
            </section>

            <!-- GPA Summary Card (for students) -->
            <?php if ($user['role'] === 'Student' && $studentId): ?>
                <div id="gpaSummary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; padding: 30px; margin-bottom: 30px; color: white;">
                    <h3 style="margin-bottom: 20px; font-size: 1.3rem;">Academic Performance</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div style="text-align: center;">
                            <div style="font-size: 2.5rem; font-weight: 800;" id="currentGPA">-</div>
                            <div style="font-size: 0.9rem; opacity: 0.9;">Current GPA</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2.5rem; font-weight: 800;" id="cgpa">-</div>
                            <div style="font-size: 0.9rem; opacity: 0.9;">CGPA</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 2.5rem; font-weight: 800;" id="totalCredits">-</div>
                            <div style="font-size: 0.9rem; opacity: 0.9;">Total Credits</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Marks Table -->
            <div class="table-container" style="background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid #f1f5f9;">
                <table id="marksTable">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Course</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Exam</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Type</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Date</th>
                            <th style="padding: 20px; text-align: center; font-weight: 700; color: #475569;">Marks Obtained</th>
                            <th style="padding: 20px; text-align: center; font-weight: 700; color: #475569;">Max Marks</th>
                            <th style="padding: 20px; text-align: center; font-weight: 700; color: #475569;">Percentage</th>
                            <th style="padding: 20px; text-align: center; font-weight: 700; color: #475569;">Grade</th>
                            <th style="padding: 20px; text-align: left; font-weight: 700; color: #475569;">Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="marksTableBody">
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 50px; color: #94a3b8;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 20px;"></i>
                                <p>Loading marks...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script>
// API Base URL
const API_BASE = '.';
let currentStudentId = <?php echo $studentId ?: 'null'; ?>;

// Load marks on page load
document.addEventListener('DOMContentLoaded', function() {
    if (currentStudentId) {
        loadMarks(currentStudentId);
        if (<?php echo $user['role'] === 'Student' ? 'true' : 'false'; ?>) {
            loadGPASummary(currentStudentId);
        }
    }
    setupEventListeners();
});

function setupEventListeners() {
    // Student selection change
    const studentSelect = document.getElementById('studentSelect');
    if (studentSelect) {
        studentSelect.addEventListener('change', function() {
            currentStudentId = this.value ? parseInt(this.value) : null;
            if (currentStudentId) {
                loadMarks(currentStudentId);
            } else {
                clearMarksTable();
            }
        });
    }

    // Calculate GPA button
    const calculateGPABtn = document.getElementById('calculateGPABtn');
    if (calculateGPABtn) {
        calculateGPABtn.addEventListener('click', function() {
            if (currentStudentId) {
                calculateAndDisplayGPA(currentStudentId);
            } else {
                showError('Please select a student first');
            }
        });
    }

    // Search functionality
    document.getElementById('marksSearch').addEventListener('input', function() {
        filterMarks(this.value);
    });
}

async function loadMarks(studentId) {
    try {
        const response = await fetch(`${API_BASE}/marks.php?student_id=${studentId}`);
        const marks = await response.json();
        
        displayMarks(marks);
    } catch (error) {
        console.error('Error loading marks:', error);
        showError('Failed to load marks');
    }
}

async function loadGPASummary(studentId) {
    try {
        const response = await fetch(`${API_BASE}/marks.php?student_id=${studentId}&calculate_gpa=1`);
        const gpaData = await response.json();
        
        displayGPASummary(gpaData);
    } catch (error) {
        console.error('Error loading GPA summary:', error);
    }
}

async function calculateAndDisplayGPA(studentId) {
    try {
        const response = await fetch(`${API_BASE}/marks.php?student_id=${studentId}&calculate_gpa=1`);
        const gpaData = await response.json();
        
        if (gpaData.cgpa !== undefined) {
            showSuccess(`GPA calculated successfully. CGPA: ${gpaData.cgpa}`);
            
            // Show GPA modal
            showGPAModal(gpaData);
        } else {
            showError('Failed to calculate GPA');
        }
    } catch (error) {
        console.error('Error calculating GPA:', error);
        showError('Failed to calculate GPA');
    }
}

function displayMarks(marks) {
    const tbody = document.getElementById('marksTableBody');
    
    if (marks.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 50px; color: #94a3b8;">
                    <i class="fas fa-chart-bar" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                    <p style="font-weight: 600;">No marks found</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = marks.map(mark => {
        const percentage = mark.max_marks > 0 ? ((mark.marks_obtained / mark.max_marks) * 100).toFixed(1) : 0;
        const gradeColor = getGradeColor(mark.grade);
        
        return `
            <tr class="mark-row searchable-item">
                <td style="padding: 20px;">
                    <div style="font-weight: 600; color: #1e293b;">${mark.course_name}</div>
                    <div style="font-size: 0.85rem; color: #64748b;">${mark.course_code}</div>
                    ${mark.credit_hours ? `<div style="font-size: 0.8rem; color: #8b5cf6;">${mark.credit_hours} credits</div>` : ''}
                </td>
                <td style="padding: 20px; color: #475569; font-weight: 600;">${mark.exam_title}</td>
                <td style="padding: 20px;">
                    <span class="badge" style="padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: ${getExamTypeColor(mark.exam_type)}; color: white;">
                        ${mark.exam_type}
                    </span>
                </td>
                <td style="padding: 20px; color: #475569;">
                    <div>${new Date(mark.exam_date).toLocaleDateString()}</div>
                    <div style="font-size: 0.85rem; color: #64748b;">${new Date(mark.exam_date).toLocaleTimeString()}</div>
                </td>
                <td style="padding: 20px; text-align: center; font-weight: 700; color: ${mark.marks_obtained >= (mark.max_marks * 0.6) ? '#059669' : '#dc2626'};">
                    ${mark.marks_obtained !== null ? mark.marks_obtained : '-'}
                </td>
                <td style="padding: 20px; text-align: center; color: #475569; font-weight: 600;">${mark.max_marks}</td>
                <td style="padding: 20px; text-align: center;">
                    <div style="font-weight: 700; color: ${percentage >= 60 ? '#059669' : percentage >= 40 ? '#d97706' : '#dc2626'};">
                        ${percentage}%
                    </div>
                </td>
                <td style="padding: 20px; text-align: center;">
                    <span class="grade-badge" style="padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 1rem; background: ${gradeColor}; color: white;">
                        ${mark.grade || '-'}
                    </span>
                </td>
                <td style="padding: 20px; color: #64748b; font-size: 0.9rem;">${mark.remarks || '-'}</td>
            </tr>
        `;
    }).join('');
}

function displayGPASummary(gpaData) {
    if (gpaData.semesters && gpaData.semesters.length > 0) {
        const latestSemester = gpaData.semesters[0];
        document.getElementById('currentGPA').textContent = latestSemester.gpa.toFixed(2);
        document.getElementById('cgpa').textContent = gpaData.cgpa.toFixed(2);
        document.getElementById('totalCredits').textContent = gpaData.total_credit_hours;
    }
}

function showGPAModal(gpaData) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.cssText = `
        position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; 
        background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;
    `;
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 20px; padding: 0; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 20px 20px 0 0;">
                <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">GPA Calculation Results</h3>
            </div>
            <div style="padding: 30px;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
                    <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 15px;">
                        <div style="font-size: 2rem; font-weight: 800; color: #8b5cf6;">${gpaData.cgpa.toFixed(2)}</div>
                        <div style="color: #64748b; margin-top: 5px;">CGPA</div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 15px;">
                        <div style="font-size: 2rem; font-weight: 800; color: #10b981;">${gpaData.total_credit_hours}</div>
                        <div style="color: #64748b; margin-top: 5px;">Total Credits</div>
                    </div>
                </div>
                
                <h4 style="margin-bottom: 15px; color: #1e293b; font-weight: 700;">Semester Breakdown</h4>
                <div style="max-height: 300px; overflow-y: auto;">
                    ${gpaData.semesters.map(semester => `
                        <div style="padding: 15px; margin-bottom: 10px; background: #f1f5f9; border-radius: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <strong style="color: #1e293b;">${semester.semester} ${semester.year}</strong>
                                <span style="font-size: 1.2rem; font-weight: 700; color: #8b5cf6;">GPA: ${semester.gpa.toFixed(2)}</span>
                            </div>
                            <div style="font-size: 0.9rem; color: #64748b;">
                                Credits: ${semester.total_credit_hours} | Grade Points: ${semester.total_grade_points.toFixed(2)}
                            </div>
                        </div>
                    `).join('')}
                </div>
                
                <div style="text-align: right; margin-top: 30px;">
                    <button onclick="this.closest('.modal').remove()" style="background: #8b5cf6; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer;">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

function clearMarksTable() {
    const tbody = document.getElementById('marksTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="9" style="text-align: center; padding: 50px; color: #94a3b8;">
                <p style="font-weight: 600;">Please select a student to view marks</p>
            </td>
        </tr>
    `;
}

function getGradeColor(grade) {
    const colors = {
        'A+': '#10b981',
        'A': '#10b981',
        'A-': '#059669',
        'B+': '#0891b2',
        'B': '#0284c7',
        'B-': '#0369a1',
        'C+': '#6366f1',
        'C': '#6366f1',
        'C-': '#4f46e5',
        'D': '#f59e0b',
        'F': '#ef4444'
    };
    return colors[grade] || '#6b7280';
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

function filterMarks(searchTerm) {
    const rows = document.querySelectorAll('.mark-row');
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
