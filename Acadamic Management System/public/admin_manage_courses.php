<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Manage Courses - Admin Portal";
include_once '../includes/header.php';

$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $message = "Course deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting course: " . $e->getMessage();
    }
}

// Handle Add Course
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_course'])) {
    $course_name = $_POST['course_name'] ?? '';
    $course_code = $_POST['course_code'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? null;

    if (empty($course_name) || empty($course_code)) {
        $error = "Name and Code are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (course_name, course_code, teacher_id) VALUES (?, ?, ?)");
            $stmt->execute([$course_name, $course_code, $teacher_id]);
            $message = "New course added successfully!";
        } catch (PDOException $e) {
            $error = ($e->getCode() == 23000) ? "Course code must be unique." : "Error: " . $e->getMessage();
        }
    }
}

// Fetch all courses with teacher names
try {
    $courses = $pdo->query("
        SELECT c.*, u.first_name, u.last_name 
        FROM courses c 
        LEFT JOIN teachers t ON c.teacher_id = t.id 
        LEFT JOIN users u ON t.user_id = u.id 
        ORDER BY c.course_name ASC
    ")->fetchAll();

    // Fetch teachers for the assignment dropdown
    $teachers = $pdo->query("
        SELECT t.id, u.first_name, u.last_name 
        FROM teachers t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY u.first_name ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $courses = $teachers = [];
    $error = "Data fetch error: " . $e->getMessage();
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Manage Courses</p>
        </div>
        <div class="header-tools">
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
                <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section style="display: grid; grid-template-columns: 1fr 350px; gap: 40px;">
            <!-- Courses List -->
            <div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Available Curriculum</h2>
                
                <?php if ($message): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                        <i class="fas fa-check-circle" style="margin-right: 10px;"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="table-container" style="border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Course Name/Code</th>
                                <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Lead Instructor</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 20px;">
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($course['course_name']); ?></span>
                                        <span style="font-size: 0.85rem; color: #8b5cf6; font-weight: 700;"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                    </div>
                                </td>
                                <td style="padding: 20px;">
                                    <?php if ($course['teacher_id']): ?>
                                        <span style="color: #475569; font-weight: 600;">Prof. <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-style: italic;">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 20px; text-align: center;">
                                    <div style="display: flex; justify-content: center; gap: 15px;">
                                        <a href="admin_edit_course.php?id=<?php echo $course['id']; ?>" style="color: #6366f1; font-size: 1.1rem;"><i class="fas fa-edit"></i></a>
                                        <a href="?delete_id=<?php echo $course['id']; ?>" style="color: #f43f5e; font-size: 1.1rem;" onclick="return confirm('Delete this course?')"><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Course Sidebar -->
            <aside style="background: #f8fafc; padding: 30px; border-radius: 24px; border: 1px solid #f1f5f9; height: fit-content;">
                <h3 style="font-size: 1.2rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Establish New Course</h3>
                <form action="" method="POST">
                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Course Identity Name</label>
                        <input type="text" name="course_name" required style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Unique Course Code</label>
                        <input type="text" name="course_code" required placeholder="e.g. CS101" style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none;">
                    </div>
                    <div style="margin-bottom: 30px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Assign Instructor</label>
                        <select name="teacher_id" style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none; background: #fff;">
                            <option value="">-- Select Faculty --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">Prof. <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="add_course" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 14px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; transition: all 0.3s ease;">
                        Publish Course
                    </button>
                </form>
            </aside>
        </section>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
