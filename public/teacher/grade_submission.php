<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';
require_once ROOT_DIR . '/includes/security/csrf_helper.php';
require_once ROOT_DIR . '/includes/helpers/validation_helper.php';

// Only Teacher allowed
checkAuth(['Teacher']);

$teacherUserId = $_SESSION['user_id'];
$submissionId = $_GET['sid'] ?? '';

if (!$submissionId) {
    header("Location: " . ROOT_URL . "/public/teacher/dashboard.php");
    exit();
}

// Fetch submission and assignment details
$stmt = $pdo->prepare("
    SELECT s.*, a.title, a.total_marks, a.assignment_id, u.first_name, u.last_name
    FROM submissions s
    JOIN assignment a ON s.assignment_id = a.assignment_id
    JOIN users u ON s.student_id = u.id
    WHERE s.submission_id = ? AND a.created_by = ?
");
$stmt->execute([$submissionId, $teacherUserId]);
$data = $stmt->fetch();

if (!$data) {
    die("Access denied or submission not found.");
}

$pageTitle = "Grade Submission - Teacher Portal";
include_once ROOT_DIR . '/includes/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $marks = intval($_POST['marks'] ?? 0);
    $feedback = sanitize($_POST['feedback'] ?? '');
    
    if ($marks < 0 || $marks > $data['total_marks']) {
        $error = "Marks must be between 0 and " . $data['total_marks'];
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE submissions SET marks = ?, feedback = ? WHERE submission_id = ?");
            $stmt->execute([$marks, $feedback, $submissionId]);
            
            header("Location: view_submissions.php?assignment_id=" . $data['assignment_id'] . "&msg=Grading saved successfully!");
            exit();
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Teacher Portal > Evaluation</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="view_submissions.php?assignment_id=<?php echo $data['assignment_id']; ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Return to List</a>
        </div>
    </header>

    <main class="main-content" style="padding: 60px; background: #f8fafc;">
        <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 50px; border-radius: 35px; box-shadow: 0 20px 50px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
            <div style="text-align: center; margin-bottom: 40px;">
                <div style="width: 70px; height: 70px; background: #fdf4ff; color: #a855f7; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem;">
                    <i class="fas fa-marker"></i>
                </div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 900;">Evaluation Form</h2>
                <p style="color: #64748b; font-weight: 600;">Student: <?php echo htmlspecialchars($data['first_name'] . ' ' . $data['last_name']); ?></p>
            </div>

            <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; text-align: center; border: 1px solid #fecaca;"><i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i><?php echo $error; ?></div> <?php endif; ?>

            <form action="" method="POST">
                <?php echo csrf_field(); ?>
                
                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Awarded Marks (Out of <?php echo $data['total_marks']; ?>)</label>
                    <input type="number" name="marks" value="<?php echo $data['marks'] ?? 0; ?>" min="0" max="<?php echo $data['total_marks']; ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none;">
                </div>

                <div style="margin-bottom: 35px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Feedback to Student</label>
                    <textarea name="feedback" placeholder="Provide constructive comments..." style="width: 100%; height: 150px; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none; resize: none;"><?php echo htmlspecialchars($data['feedback'] ?? ''); ?></textarea>
                </div>

                <button type="submit" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 18px; border: none; border-radius: 16px; font-size: 1.1rem; font-weight: 800; cursor: pointer;">
                    Finalize Grade
                </button>
            </form>
        </div>
    </main>
</div>

<?php include_once ROOT_DIR . '/includes/footer.php'; ?>
