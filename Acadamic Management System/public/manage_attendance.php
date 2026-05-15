<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Teacher and Admin allowed
checkAuth(['Admin', 'Teacher']);

$pageTitle = "Manage Attendance - Academic Management System";
include_once '../includes/header.php';

$message = '';
$error = '';
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// Get internal Teacher ID if role is Teacher
$teacherId = null;
if ($role == 'Teacher') {
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $teacherId = $stmt->fetchColumn();
}

// Fetch relevant courses
try {
    if ($role == 'Admin') {
        $courses = $pdo->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name")->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT id, course_name, course_code FROM courses WHERE teacher_id = ? ORDER BY course_name");
        $stmt->execute([$teacherId]);
        $courses = $stmt->fetchAll();
    }
} catch (PDOException $e) { $courses = []; }

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $course_id = $_POST['course_id'];
    $date = $_POST['attendance_date'];
    $statuses = $_POST['status'] ?? []; // student_id => status

    if (empty($course_id) || empty($date)) {
        $error = "Select course and date.";
    } else {
        try {
            $pdo->beginTransaction();
            foreach ($statuses as $student_id => $status) {
                // Upsert logic (Check if exists for this date/student/course)
                $stmt = $pdo->prepare("
                    INSERT INTO attendance (student_id, course_id, attendance_date, status) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE status = VALUES(status)
                ");
                $stmt->execute([$student_id, $course_id, $date, $status]);
            }
            $pdo->commit();
            $message = "Attendance processed for $date!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Deletion (Admin Only)
if ($role == 'Admin' && isset($_GET['delete_student_id']) && isset($_GET['course_id']) && isset($_GET['view_date'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE student_id = ? AND course_id = ? AND attendance_date = ?");
        $stmt->execute([$_GET['delete_student_id'], $_GET['course_id'], $_GET['view_date']]);
        $message = "Attendance record deleted successfully!";
    } catch (PDOException $e) {
        $error = "Deletion failed: " . $e->getMessage();
    }
}

// Fetch students for selected course
$selected_course = $_GET['course_id'] ?? null;

// Security check for Teacher access
if ($selected_course && $role == 'Teacher') {
    $checkStmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
    $checkStmt->execute([$selected_course, $teacherId]);
    if (!$checkStmt->fetch()) {
        $selected_course = null;
        $error = "Academic mismatch: Security access denied for this module.";
    }
}

$students = [];
if ($selected_course) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.id as student_id, u.first_name, u.last_name, e.enrolled_at
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN enrollments e ON s.id = e.student_id
            WHERE e.course_id = ?
            ORDER BY u.first_name ASC
        ");
        $stmt->execute([$selected_course]);
        $students = $stmt->fetchAll();

        // Fetch existing attendance for this date if set
        $view_date = $_GET['view_date'] ?? date('Y-m-d');
        $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE course_id = ? AND attendance_date = ?");
        $stmt->execute([$selected_course, $view_date]);
        $existing = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) { $students = []; }
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Portal > Manage Attendance</p>
        </div>
        <div class="header-tools">
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="<?php echo $role == 'Admin' ? 'admin_dashboard.php' : 'teacher_dashboard.php'; ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Dashboard</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="attendance-selection" style="background: #f8fafc; padding: 30px; border-radius: 20px; border: 1px solid #f1f5f9; margin-bottom: 40px;">
            <form action="" method="GET" style="display: flex; gap: 30px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Select Classroom Course</label>
                    <select name="course_id" required style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <option value="">-- Select --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($selected_course == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="width: 250px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Academic Date</label>
                    <input type="date" name="view_date" value="<?php echo $view_date ?? date('Y-m-d'); ?>" style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                </div>
                <button type="submit" style="background: var(--brand-gradient); color: #fff; padding: 12px 30px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer;">Load Student Roster</button>
            </form>
        </section>

        <?php if ($selected_course): ?>
            <section class="roster-view">
                <form action="" method="POST">
                    <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
                    <input type="hidden" name="attendance_date" value="<?php echo $view_date; ?>">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h2 style="font-size: 1.5rem; color: #1e293b; font-weight: 800;">Member Presence Records</h2>
                        <button type="submit" name="mark_attendance" style="background: #10b981; color: #fff; padding: 10px 25px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer;">Confirm All Attendance</button>
                    </div>

                    <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px;"><?php echo $message; ?></div> <?php endif; ?>
                    <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 20px;"><?php echo $error; ?></div> <?php endif; ?>

                    <div class="table-container" style="border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Attendance Status</th>
                                    <?php if ($role == 'Admin'): ?>
                                    <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="2" style="padding: 40px; text-align: center; color: #94a3b8;">No members enrolled in this course catalog.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($students as $s): ?>
                                    <?php $current_status = $existing[$s['student_id']] ?? 'Present'; ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 20px;">
                                            <span style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></span>
                                        </td>
                                        <td style="padding: 20px; text-align: center;">
                                            <div style="display: flex; justify-content: center; gap: 15px;">
                                                <label style="cursor: pointer;">
                                                    <input type="radio" name="status[<?php echo $s['student_id']; ?>]" value="Present" <?php echo $current_status == 'Present' ? 'checked' : ''; ?> style="display: none;">
                                                    <span class="status-chip present">Present</span>
                                                </label>
                                                <label style="cursor: pointer;">
                                                    <input type="radio" name="status[<?php echo $s['student_id']; ?>]" value="Absent" <?php echo $current_status == 'Absent' ? 'checked' : ''; ?> style="display: none;">
                                                    <span class="status-chip absent">Absent</span>
                                                </label>
                                                <label style="cursor: pointer;">
                                                    <input type="radio" name="status[<?php echo $s['student_id']; ?>]" value="Late" <?php echo $current_status == 'Late' ? 'checked' : ''; ?> style="display: none;">
                                                    <span class="status-chip late">Late</span>
                                                </label>
                                            </div>
                                        </td>
                                        <?php if ($role == 'Admin'): ?>
                                        <td style="padding: 20px; text-align: center;">
                                            <?php if (isset($existing[$s['student_id']])): ?>
                                            <a href="?course_id=<?php echo $selected_course; ?>&view_date=<?php echo $view_date; ?>&delete_student_id=<?php echo $s['student_id']; ?>" 
                                               style="color: #f43f5e; font-size: 1.1rem;" 
                                               onclick="return confirm('Erase this attendance record?')" 
                                               title="Delete Record"><i class="fas fa-trash-alt"></i></a>
                                            <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.8rem; font-style: italic;">No Record</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </section>
        <?php endif; ?>
    </main>
</div>

<style>
.status-chip {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 800;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}
input[type="radio"]:checked + .status-chip.present { background: #dcfce7; color: #166534; border-color: #166534; }
input[type="radio"]:checked + .status-chip.absent { background: #fee2e2; color: #991b1b; border-color: #991b1b; }
input[type="radio"]:checked + .status-chip.late { background: #fef3c7; color: #b45309; border-color: #b45309; }

/* Inactive styles */
.status-chip.present { background: #f8fafc; color: #94a3b8; }
.status-chip.absent { background: #f8fafc; color: #94a3b8; }
.status-chip.late { background: #f8fafc; color: #94a3b8; }
</style>

<?php include_once '../includes/footer.php'; ?>
