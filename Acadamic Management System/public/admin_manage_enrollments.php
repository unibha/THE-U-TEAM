<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Manage Enrollments - Admin Portal";
include_once '../includes/header.php';

$message = '';
$error = '';

// Handle Enrollment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enroll_student'])) {
    $course_id = $_POST['course_id'];
    $student_id = $_POST['student_id'];

    if (empty($course_id) || empty($student_id)) {
        $error = "Select both course and student.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
            $stmt->execute([$student_id, $course_id]);
            $message = "Student successfully enrolled in course catalog!";
        } catch (PDOException $e) {
            $error = ($e->getCode() == 23000) ? "Student is already enrolled in this course." : "Error: " . $e->getMessage();
        }
    }
}

// Handle Removal
if (isset($_GET['remove_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM enrollments WHERE id = ?");
        $stmt->execute([$_GET['remove_id']]);
        $message = "Enrollment record removed.";
    } catch (PDOException $e) { $error = "Removal failed: " . $e->getMessage(); }
}

// Fetch all courses
$courses = $pdo->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC")->fetchAll();

// Fetch all students (for the enrollment dropdown)
$students_all = $pdo->query("
    SELECT s.id, u.first_name, u.last_name 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY u.first_name ASC
")->fetchAll();

// Fetch currently enrolled students for the selected course
// Priority: GET (from search/selection) or POST (from enrollment submit)
$selected_course = $_GET['course_id'] ?? ($_POST['course_id'] ?? null);
$enrolled_students = [];
if ($selected_course) {
    try {
        $stmt = $pdo->prepare("
            SELECT e.id as enrollment_id, u.first_name, u.last_name, u.email
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE e.course_id = ?
            ORDER BY u.first_name ASC
        ");
        $stmt->execute([$selected_course]);
        $enrolled_students = $stmt->fetchAll();
    } catch (PDOException $e) { $enrolled_students = []; }
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Manage Enrollments</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
            <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
        </div>
    </header>

    <main class="main-content">
        <section style="display: grid; grid-template-columns: 1fr 350px; gap: 40px;">
            <!-- Current Enrollment Roster -->
            <div>
                <section class="selection-bar" style="background: #f8fafc; padding: 25px; border-radius: 20px; border: 1px solid #f1f5f9; margin-bottom: 35px;">
                    <form action="" method="GET" style="display: flex; gap: 20px; align-items: flex-end;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Select Academic Module</label>
                            <select name="course_id" required style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                                <option value="">-- Choose Module --</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($selected_course == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" style="background: var(--brand-gradient); color: #fff; padding: 12px 30px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer;">Load Member Roster</button>
                    </form>
                </section>

                <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $message; ?></div> <?php endif; ?>
                <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $error; ?></div> <?php endif; ?>

                <?php if ($selected_course): ?>
                    <h2 style="font-size: 1.6rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Classroom Roster</h2>
                    <div class="table-container" style="border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Student Identity</th>
                                    <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Academic Email</th>
                                    <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($enrolled_students)): ?>
                                    <tr><td colspan="3" style="padding: 40px; text-align: center; color: #94a3b8;">No members currently enrolled for this module.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($enrolled_students as $s): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 20px; font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                                        <td style="padding: 20px; color: #64748b; font-size: 0.9rem;"><?php echo htmlspecialchars($s['email']); ?></td>
                                        <td style="padding: 20px; text-align: center;">
                                            <a href="?course_id=<?php echo $selected_course; ?>&remove_id=<?php echo $s['enrollment_id']; ?>" style="color: #f43f5e; font-size: 1.1rem;" onclick="return confirm('Remove student from course?')"><i class="fas fa-user-minus"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Enrollment Sidebar -->
            <aside style="background: #f8fafc; padding: 30px; border-radius: 24px; border: 1px solid #f1f5f9; height: fit-content; position: sticky; top: 40px;">
                <h3 style="font-size: 1.25rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Assign New Member</h3>
                <form action="" method="POST">
                    <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
                    <div style="margin-bottom: 30px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Authorized Student</label>
                        <select name="student_id" required style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                            <option value="">-- Select Member --</option>
                            <?php foreach ($students_all as $s_all): ?>
                                <option value="<?php echo $s_all['id']; ?>"><?php echo htmlspecialchars($s_all['first_name'] . ' ' . $s_all['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($selected_course): ?>
                        <button type="submit" name="enroll_student" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 15px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer;">Finalize Enrollment</button>
                    <?php else: ?>
                        <p style="color: #94a3b8; font-size: 0.85rem; text-align: center;">Choose a course from the roster view to enable enrollment.</p>
                    <?php endif; ?>
                </form>
            </aside>
        </section>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
