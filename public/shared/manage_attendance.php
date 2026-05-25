<?php
require_once '../../includes/security/auth_middleware.php';
require_once '../../includes/db.php';

// Teacher and Admin allowed
checkAuth(['Admin', 'Teacher']);

$pageTitle = "Manage Attendance - Academic Management System";
include_once '../../includes/header.php';

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
} catch (PDOException $e) {
    $courses = [];
}

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {

    $course_id = $_POST['course_id'];
    $date = $_POST['attendance_date'];
    $statuses = $_POST['status'] ?? [];

    if (empty($course_id) || empty($date)) {

        $error = "Select course and date.";

    } else {

        try {

            $pdo->beginTransaction();

            foreach ($statuses as $student_id => $status) {

                $stmt = $pdo->prepare("
                    INSERT INTO attendance 
                    (student_id, course_id, attendance_date, status) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = VALUES(status)
                ");

                $stmt->execute([$student_id, $course_id, $date, $status]);

                // Notification
                require_once '../../includes/helpers/notification_helper.php';

                $studentUser = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
                $studentUser->execute([$student_id]);
                $sUid = $studentUser->fetchColumn();

                $courseName = $pdo->prepare("SELECT course_name FROM courses WHERE id = ?");
                $courseName->execute([$course_id]);
                $cName = $courseName->fetchColumn();

                if ($sUid) {
                    sendNotification(
                        $sUid,
                        "Attendance Marked: $cName",
                        "Your attendance for $cName on $date has been marked as: $status.",
                        'Attendance'
                    );
                }
            }

            $pdo->commit();
            $message = "Attendance processed successfully!";

        } catch (PDOException $e) {

            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch students for selected course
$selected_course = $_GET['course_id'] ?? null;
$view_date = $_GET['view_date'] ?? date('Y-m-d');

$students = [];
$existing = [];

if ($selected_course) {

    try {

        $stmt = $pdo->prepare("
            SELECT s.id as student_id, u.first_name, u.last_name
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN enrollments e ON s.id = e.student_id
            WHERE e.course_id = ?
            ORDER BY u.first_name ASC
        ");

        $stmt->execute([$selected_course]);
        $students = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT student_id, status
            FROM attendance
            WHERE course_id = ? AND attendance_date = ?
        ");

        $stmt->execute([$selected_course, $view_date]);

        $existing = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    } catch (PDOException $e) {
        $students = [];
    }
}
?>

<div class="dashboard-container">

    <header class="dashboard-header">

        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Portal > Manage Attendance</p>
        </div>

        <div class="header-tools">
            <div class="header-icons" style="margin-left:20px; display:flex; gap:15px; align-items:center;">

                <a href="<?php echo $role == 'Admin' 
                    ? '../admin/dashboard.php' 
                    : '../teacher/dashboard.php'; ?>"

                   style="color:#fff; text-decoration:none; font-weight:700; font-size:0.9rem; padding:8px 16px; border:1px solid rgba(255,255,255,0.3); border-radius:12px;">

                    Dashboard

                </a>

            </div>
        </div>

    </header>

    <main class="main-content">

        <section class="attendance-selection"
                 style="background:#f8fafc; padding:30px; border-radius:20px; border:1px solid #f1f5f9; margin-bottom:40px;">

            <form action="" method="GET"
                  style="display:flex; gap:30px; align-items:flex-end;">

                <div style="flex:1;">

                    <label style="font-size:0.85rem; font-weight:700; color:#475569; margin-bottom:8px; display:block;">
                        Select Classroom Course
                    </label>

                    <select name="course_id" required
                            style="width:100%; padding:12px; border:2px solid #fff; border-radius:12px;">

                        <option value="">-- Select --</option>

                        <?php foreach ($courses as $c): ?>

                            <option value="<?php echo $c['id']; ?>"
                                <?php echo ($selected_course == $c['id']) ? 'selected' : ''; ?>>

                                <?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')'); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div style="width:250px;">

                    <label style="font-size:0.85rem; font-weight:700; color:#475569; margin-bottom:8px; display:block;">
                        Academic Date
                    </label>

                    <input type="date"
                           name="view_date"
                           value="<?php echo $view_date; ?>"
                           style="width:100%; padding:12px; border:2px solid #fff; border-radius:12px;">

                </div>

                <button type="submit"
                        style="background:#1e3a5f; color:#fff; padding:12px 30px; border:none; border-radius:12px; font-weight:800; cursor:pointer;">

                    Load Student Roster

                </button>

            </form>
        </section>

<?php
    // If a course is selected, display the roster for attendance marking
    if ($selected_course && !empty($students)):
?>
    <section class="attendance-roster" style="background:#ffffff; padding:30px; border-radius:20px; border:1px solid #e5e7eb; margin-top:20px;">
        <form action="" method="POST" style="display:flex; flex-direction:column; gap:20px;">
            <!-- Preserve selected course and date -->
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($selected_course); ?>" />
            <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($view_date); ?>" />
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th style="padding:12px; text-align:left;">Student Name</th>
                        <th style="padding:12px; text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:12px;">
                            <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?>
                        </td>
                        <td style="padding:12px; text-align:center;">
                            <select name="status[<?php echo $s['student_id']; ?>]" style="padding:6px 10px; border:1px solid #d1d5db; border-radius:6px;">
                                <option value="Present" <?php echo (isset($existing[$s['student_id']]) && $existing[$s['student_id']] == 'Present') ? 'selected' : ''; ?>>Present</option>
                                <option value="Absent" <?php echo (isset($existing[$s['student_id']]) && $existing[$s['student_id']] == 'Absent') ? 'selected' : ''; ?>>Absent</option>
                                <option value="Late" <?php echo (isset($existing[$s['student_id']]) && $existing[$s['student_id']] == 'Late') ? 'selected' : ''; ?>>Late</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="mark_attendance" style="align-self:flex-end; background:#1e3a5f; color:#fff; padding:10px 20px; border:none; border-radius:8px; font-weight:600; cursor:pointer;">
                Save Attendance
            </button>
        </form>
    </section>
<?php endif; ?>
</main>
</div>
<?php include_once '../../includes/footer.php'; ?>