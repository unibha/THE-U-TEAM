<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Edit Course - Admin Portal";
include_once '../includes/header.php';

$message = '';
$error = '';
$courseId = $_GET['id'] ?? 0;

// Fetch current course data
try {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();

    if (!$course) {
        header("Location: admin_manage_courses.php");
        exit();
    }

    // Fetch teachers for the dropdown
    $teachers = $pdo->query("
        SELECT t.id, u.first_name, u.last_name 
        FROM teachers t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY u.first_name ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $error = "Data fetch error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = $_POST['course_name'] ?? '';
    $course_code = $_POST['course_code'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? null;

    if (empty($course_name) || empty($course_code)) {
        $error = "Name and Code are required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, course_code = ?, teacher_id = ? WHERE id = ?");
            $stmt->execute([$course_name, $course_code, $teacher_id, $courseId]);
            $message = "Course updated successfully! <a href='admin_manage_courses.php' style='color: #166534; font-weight: 700;'>Return to list</a>";
            $course['course_name'] = $course_name;
            $course['course_code'] = $course_code;
            $course['teacher_id'] = $teacher_id;
        } catch (PDOException $e) {
            $error = ($e->getCode() == 23000) ? "Course code must be unique." : "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Edit Course</p>
        </div>
        <div class="header-tools">
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
                <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="form-container" style="max-width: 600px; margin: 0 auto; background: #fff; padding: 50px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 30px; text-align: center;">Course Update Hub</h2>

            <?php if ($message): ?>
                <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 30px; text-align: center;">
                    <i class="fas fa-check-circle" style="margin-right: 10px;"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 30px; text-align: center;">
                    <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Course Identity Name</label>
                    <input type="text" name="course_name" value="<?php echo htmlspecialchars($course['course_name']); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Unique Course Code</label>
                    <input type="text" name="course_code" value="<?php echo htmlspecialchars($course['course_code']); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none;">
                </div>
                <div style="margin-bottom: 40px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Lead Instructor Assignment</label>
                    <select name="teacher_id" style="width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; outline: none; background: #fff;">
                        <option value="">-- Select Faculty --</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>" <?php echo ($course['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                Prof. <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 18px; border: none; border-radius: 15px; font-size: 1.1rem; font-weight: 800; cursor: pointer; transition: all 0.3s ease;">
                    Authorize Changes
                </button>
            </form>
        </section>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
