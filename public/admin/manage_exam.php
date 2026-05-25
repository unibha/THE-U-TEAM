<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';
require_once ROOT_DIR . '/includes/helpers/notification_helper.php';
require_once ROOT_DIR . '/includes/security/csrf_helper.php';
require_once ROOT_DIR . '/includes/helpers/validation_helper.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Manage Examinations - Admin Portal";
include_once ROOT_DIR . '/includes/header.php';

$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM exam WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $message = "Examination schedule removed successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting exam: " . $e->getMessage();
    }
}

// Handle Add Exam
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_exam'])) {
    // 1. Validate CSRF
    validate_csrf();

    $exam_name = sanitize($_POST['exam_name'] ?? '');
    $course_id = $_POST['course_id'] ?? '';
    $exam_date = $_POST['exam_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $total_marks = $_POST['total_marks'] ?? '';

    $errors = [];
    if (empty($exam_name)) $errors[] = "Examination title is required.";
    if (!validate_date($exam_date)) $errors[] = "Please provide a valid date.";
    if (!is_numeric($total_marks) || $total_marks <= 0) $errors[] = "Total marks must be a positive number.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO exam (exam_name, course_id, exam_date, start_time, end_time, total_marks) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$exam_name, $course_id, $exam_date, $start_time, $end_time, $total_marks]);
            
            // Notify students enrolled in this course
            $courseData = $pdo->prepare("SELECT course_name FROM courses WHERE id = ?");
            $courseData->execute([$course_id]);
            $cName = $courseData->fetchColumn();
            
            notifyEnrolledStudents($course_id, "New Exam Scheduled: $exam_name", "A new exam has been scheduled for $cName on $exam_date from $start_time to $end_time.", 'Academic');
            notifyTeacher($course_id, "New Exam Scheduled: $exam_name", "An examination ($exam_name) has been scheduled for your module $cName.", 'Academic');
            
            $message = "Examination established and notifications sent!";
        } catch (PDOException $e) {
            $error = "System error: " . $e->getMessage();
        }
    } else {
        $error = format_errors($errors);
    }
}

// Fetch all exams with course names
try {
    $exams = $pdo->query("
        SELECT e.*, c.course_name, c.course_code 
        FROM exam e 
        JOIN courses c ON e.course_id = c.id 
        ORDER BY e.exam_date ASC, e.start_time ASC
    ")->fetchAll();

    // Fetch courses for the dropdown
    $courses = $pdo->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC")->fetchAll();
} catch (PDOException $e) {
    $exams = $courses = [];
    $error = "Data fetch error: " . $e->getMessage();
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Examination Master</p>
        </div>
        <div class="header-tools">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="examSearch" placeholder="Search exams or modules...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="<?php echo ROOT_URL; ?>/public/admin/dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
                
            </div>
        </div>
    </header>

    <main class="main-content">
        <section style="display: grid; grid-template-columns: 1fr 380px; gap: 40px;">
            <!-- Exams List -->
            <div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Scheduled Examinations</h2>
                
                <?php if ($message): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;">
                        <i class="fas fa-check-circle" style="margin-right: 10px;"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;">
                        <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="table-container" style="border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; background: #fff;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9;">Exam Detail</th>
                                <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9;">Schedule</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9;">Marks</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($exams)): ?>
                                <tr>
                                    <td colspan="4" style="padding: 40px; text-align: center; color: #94a3b8; font-weight: 600;">No examinations currently scheduled.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($exams as $exam): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s ease;">
                                    <td style="padding: 20px;">
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <span style="font-weight: 800; color: #1e293b; font-size: 1rem;"><?php echo htmlspecialchars($exam['exam_name']); ?></span>
                                            <span style="font-size: 0.85rem; color: #8b5cf6; font-weight: 700;"><?php echo htmlspecialchars($exam['course_name'] . ' (' . $exam['course_code'] . ')'); ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 20px;">
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <span style="font-weight: 700; color: #475569; font-size: 0.9rem;"><i class="far fa-calendar-alt" style="margin-right: 8px; color: #64748b;"></i><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></span>
                                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;"><i class="far fa-clock" style="margin-right: 8px; color: #64748b;"></i><?php echo date('h:i A', strtotime($exam['start_time'])) . ' - ' . date('h:i A', strtotime($exam['end_time'])); ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 20px; text-align: center;">
                                        <span style="background: #eef2ff; color: #4338ca; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.9rem;"><?php echo $exam['total_marks']; ?></span>
                                    </td>
                                    <td style="padding: 20px; text-align: center;">
                                        <div style="display: flex; justify-content: center; gap: 12px;">
                                            <a href="edit_exam.php?id=<?php echo $exam['id']; ?>" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #eff6ff; color: #3b82f6; border-radius: 10px; transition: 0.3s ease;"><i class="fas fa-edit"></i></a>
                                            <a href="?delete_id=<?php echo $exam['id']; ?>" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #fff1f2; color: #f43f5e; border-radius: 10px; transition: 0.3s ease;" onclick="return confirm('Permanently delete this examination schedule?')"><i class="fas fa-trash-alt"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Exam Sidebar -->
            <aside style="background: #f8fafc; padding: 35px; border-radius: 30px; border: 1px solid #f1f5f9; height: fit-content; position: sticky; top: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <h3 style="font-size: 1.3rem; color: #1e293b; font-weight: 800; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-plus-circle" style="color: #8b5cf6;"></i>
                    Schedule New Exam
                </h3>
                <form action="" method="POST">
                    <?php echo csrf_field(); ?>
                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Exam Title / Phase</label>
                        <input type="text" name="exam_name" required placeholder="e.g. Final Semester Examination" style="width: 100%; padding: 14px; border: 2px solid #fff; border-radius: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none; background: #fff; font-weight: 600;">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Select Course</label>
                        <select name="course_id" required style="width: 100%; padding: 14px; border: 2px solid #fff; border-radius: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none; background: #fff; font-weight: 600; cursor: pointer;">
                            <option value="">-- Choose Module --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Examination Date</label>
                        <input type="date" name="exam_date" required style="width: 100%; padding: 14px; border: 2px solid #fff; border-radius: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none; background: #fff; font-weight: 600;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Start Time</label>
                            <input type="time" name="start_time" required style="width: 100%; padding: 14px; border: 2px solid #fff; border-radius: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none; background: #fff; font-weight: 600;">
                        </div>
                        <div>
                            <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">End Time</label>
                            <input type="time" name="end_time" required style="width: 100%; padding: 14px; border: 2px solid #fff; border-radius: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none; background: #fff; font-weight: 600;">
                        </div>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Total Possible Marks</label>
                        <input type="number" name="total_marks" required placeholder="e.g. 100" style="width: 100%; padding: 14px; border: 2px solid #fff; border-radius: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none; background: #fff; font-weight: 600;">
                    </div>

                    <button type="submit" name="add_exam" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 16px; border: none; border-radius: 14px; font-weight: 800; cursor: pointer; transition: all 0.3s ease; font-size: 1rem; box-shadow: 0 10px 15px -3px rgba(26, 54, 93, 0.3);">
                        Establish Exam Schedule
                    </button>
                </form>
            </aside>
        </section>
    </main>
</div>

<script>
document.getElementById('examSearch').addEventListener('input', function(e) {
    const query = e.target.value;
    
    fetch(`<?php echo ROOT_URL; ?>/public/api/search_exams.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('table tbody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="padding: 40px; text-align: center; color: #94a3b8; font-weight: 600;">No examinations found.</td></tr>';
                return;
            }
            
            data.forEach(exam => {
                const row = document.createElement('tr');
                row.style.borderBottom = '1px solid #f1f5f9';
                
                // Helper to format date/time like PHP side
                const examDate = new Date(exam.exam_date).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
                
                row.innerHTML = `
                    <td style="padding: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <span style="font-weight: 800; color: #1e293b; font-size: 1rem;">${exam.exam_name}</span>
                            <span style="font-size: 0.85rem; color: #8b5cf6; font-weight: 700;">${exam.course_name} (${exam.course_code})</span>
                        </div>
                    </td>
                    <td style="padding: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <span style="font-weight: 700; color: #475569; font-size: 0.9rem;"><i class="far fa-calendar-alt" style="margin-right: 8px; color: #64748b;"></i>${examDate}</span>
                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;"><i class="far fa-clock" style="margin-right: 8px; color: #64748b;"></i>${exam.start_time} - ${exam.end_time}</span>
                        </div>
                    </td>
                    <td style="padding: 20px; text-align: center;">
                        <span style="background: #eef2ff; color: #4338ca; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.9rem;">${exam.total_marks}</span>
                    </td>
                    <td style="padding: 20px; text-align: center;">
                        <div style="display: flex; justify-content: center; gap: 12px;">
                            <a href="<?php echo ROOT_URL; ?>/public/admin/edit_exam.php?id=${exam.id}" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #eff6ff; color: #3b82f6; border-radius: 10px;"><i class="fas fa-edit"></i></a>
                            <a href="?delete_id=${exam.id}" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #fff1f2; color: #f43f5e; border-radius: 10px;" onclick="return confirm('Permanently delete this examination schedule?')"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => console.error('Error fetching exams:', error));
});
</script>

<?php include_once ROOT_DIR . '/includes/footer.php'; ?>

