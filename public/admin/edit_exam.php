<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Edit Examination Schedule";
include_once ROOT_DIR . '/includes/header.php';

$message = '';
$error = '';
$examId = $_GET['id'] ?? null;

if (!$examId) {
    header("Location: " . ROOT_URL . "/public/admin/manage_exam.php");
    exit();
}

// Fetch existing exam data
try {
    $stmt = $pdo->prepare("SELECT * FROM exam WHERE id = ?");
    $stmt->execute([$examId]);
    $exam = $stmt->fetch();

    if (!$exam) {
        header("Location: " . ROOT_URL . "/public/admin/manage_exam.php");
        exit();
    }

    // Fetch courses for the dropdown
    $courses = $pdo->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC")->fetchAll();
} catch (PDOException $e) {
    $error = "Data error: " . $e->getMessage();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_exam'])) {
    $exam_name = $_POST['exam_name'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $exam_date = $_POST['exam_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $total_marks = $_POST['total_marks'] ?? '';

    if (empty($exam_name) || empty($course_id) || empty($exam_date) || empty($start_time) || empty($end_time) || empty($total_marks)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE exam SET exam_name = ?, course_id = ?, exam_date = ?, start_time = ?, end_time = ?, total_marks = ? WHERE id = ?");
            $stmt->execute([$exam_name, $course_id, $exam_date, $start_time, $end_time, $total_marks, $examId]);
            
            $message = "Examination details updated successfully!";
            
            // Refresh local data
            $exam = array_merge($exam, $_POST);
        } catch (PDOException $e) {
            $error = "Update error: " . $e->getMessage();
        }
    }
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Edit Examination</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="<?php echo ROOT_URL; ?>/public/admin/manage_exam.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Exams</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div style="max-width: 600px; margin: 0 auto;">
            <section class="welcome-header" style="margin-bottom: 30px; text-align: center;">
                <h2 style="font-size: 2rem; color: #1e293b; font-weight: 800;">Update Exam Schedule</h2>
                <p style="color: #64748b; font-weight: 600;">Modify the parameters for the scheduled examination.</p>
            </section>

            <?php if ($message): ?>
                <div style="background: #dcfce7; color: #166534; padding: 20px; border-radius: 16px; margin-bottom: 25px; border: 1px solid #bbf7d0;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 16px; margin-bottom: 25px; border: 1px solid #fecaca;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div style="background: #fff; padding: 40px; border-radius: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
                <form action="" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                    <div>
                        <label style="font-size: 0.9rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">Examination Identity</label>
                        <input type="text" name="exam_name" value="<?php echo htmlspecialchars($exam['exam_name']); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; outline: none; font-weight: 600; background: #fcfdfe;">
                    </div>

                    <div>
                        <label style="font-size: 0.9rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">Associated Academic Module</label>
                        <select name="course_id" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; outline: none; font-weight: 600; background: #fcfdfe;">
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $exam['course_id'] == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="font-size: 0.9rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">Scheduled Date</label>
                        <input type="date" name="exam_date" value="<?php echo $exam['exam_date']; ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; outline: none; font-weight: 600; background: #fcfdfe;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="font-size: 0.9rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">Start Time</label>
                            <input type="time" name="start_time" value="<?php echo $exam['start_time']; ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; outline: none; font-weight: 600; background: #fcfdfe;">
                        </div>
                        <div>
                            <label style="font-size: 0.9rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">End Time</label>
                            <input type="time" name="end_time" value="<?php echo $exam['end_time']; ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; outline: none; font-weight: 600; background: #fcfdfe;">
                        </div>
                    </div>

                    <div>
                        <label style="font-size: 0.9rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">Total Marks Available</label>
                        <input type="number" name="total_marks" value="<?php echo $exam['total_marks']; ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; outline: none; font-weight: 600; background: #fcfdfe;">
                    </div>

                    <button type="submit" name="update_exam" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 16px; border: none; border-radius: 14px; font-weight: 800; cursor: pointer; transition: 0.3s ease; margin-top: 20px; box-shadow: 0 10px 15px -3px rgba(26, 54, 93, 0.3);">
                        Save Exam Modifications
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include_once ROOT_DIR . '/includes/footer.php'; ?>
