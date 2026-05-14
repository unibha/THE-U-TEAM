<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/validation_helper.php';

// Only Teacher allowed
checkAuth(['Teacher']);

$teacherUserId = $_SESSION['user_id'];
$pageTitle = "Create Assignment - Teacher Portal";
include_once '../includes/header.php';

// Fetch teacher's courses
$stmt = $pdo->prepare("
    SELECT c.id, c.course_name, c.course_code 
    FROM courses c 
    JOIN teachers t ON c.teacher_id = t.id 
    WHERE t.user_id = ?
");
$stmt->execute([$teacherUserId]);
$courses = $stmt->fetchAll();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $courseId = $_POST['course_id'] ?? '';
    $totalMarks = intval($_POST['total_marks'] ?? 100);
    $dueDate = $_POST['due_date'] ?? '';
    
    if (empty($title) || empty($courseId) || empty($dueDate)) {
        $error = "Title, Course, and Due Date are mandatory.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO assignment (title, description, course_id, total_marks, due_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $courseId, $totalMarks, $dueDate, $teacherUserId]);
            
            $message = "Assignment created successfully!";
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
            <p>Teacher Portal > Assignment Creator</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="teacher_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
        </div>
    </header>

    <main class="main-content" style="padding: 60px; background: #f8fafc;">
        <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 50px; border-radius: 35px; box-shadow: 0 20px 50px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
            <div style="text-align: center; margin-bottom: 40px;">
                <div style="width: 70px; height: 70px; background: #fff7ed; color: #f97316; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem;">
                    <i class="fas fa-tasks"></i>
                </div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 900;">Create New Assignment</h2>
                <p style="color: #64748b; font-weight: 600;">Set tasks, deadlines, and marks for your students.</p>
            </div>

            <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; text-align: center; border: 1px solid #bbf7d0;"><i class="fas fa-check-circle" style="margin-right: 10px;"></i><?php echo $message; ?></div> <?php endif; ?>
            <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 30px; font-weight: 600; text-align: center; border: 1px solid #fecaca;"><i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i><?php echo $error; ?></div> <?php endif; ?>

            <form action="" method="POST">
                <?php echo csrf_field(); ?>
                
                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Assignment Title</label>
                    <input type="text" name="title" required placeholder="e.g. Midterm Project: Data Structures" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none;">
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Target Module</label>
                    <select name="course_id" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none; background: #fff;">
                        <option value="">-- Select Course --</option>
                        <?php foreach($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Total Marks</label>
                        <input type="number" name="total_marks" value="100" min="1" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none;">
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Due Date & Time</label>
                        <input type="datetime-local" name="due_date" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 35px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Description / Instructions</label>
                    <textarea name="description" placeholder="Detailed instructions for the assignment..." style="width: 100%; height: 120px; padding: 14px; border: 2px solid #f1f5f9; border-radius: 14px; font-weight: 600; outline: none; resize: none;"></textarea>
                </div>

                <button type="submit" style="width: 100%; background: #f97316; color: #fff; padding: 18px; border: none; border-radius: 16px; font-size: 1.1rem; font-weight: 800; cursor: pointer;">
                    Create Assignment
                </button>
            </form>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
