<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Teacher and Admin allowed
checkAuth(['Admin', 'Teacher']);

$pageTitle = "Manage Assignments - Academic Management System";
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

// Handle Delete
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $message = "Assignment removed successfully!";
    } catch (PDOException $e) {
        $error = "Task deletion failed: " . $e->getMessage();
    }
}

// Handle Add Assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_assignment'])) {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';

    if (empty($course_id) || empty($title)) {
        $error = "Module and Title are mandatory.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO assignments (course_id, title, description, due_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$course_id, $title, $description, $due_date]);
            $message = "New assignment published! <a href='manage_assignments.php?course_id=$course_id' style='color: #166534; font-weight: 700;'>Reload View</a>";
        } catch (PDOException $e) {
            $error = "Publishing error: " . $e->getMessage();
        }
    }
}

// Fetch relevant courses for selection
try {
    if ($role == 'Admin') {
        $courses = $pdo->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name")->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT id, course_name, course_code FROM courses WHERE teacher_id = ? ORDER BY course_name");
        $stmt->execute([$teacherId]);
        $courses = $stmt->fetchAll();
    }
} catch (PDOException $e) { $courses = []; }

// Fetch assignments for selected course
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

$assignments = [];
if ($selected_course) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date ASC");
        $stmt->execute([$selected_course]);
        $assignments = $stmt->fetchAll();
    } catch (PDOException $e) { $assignments = []; }
}

$default_date = date('Y-m-d\TH:i', strtotime('+7 days 23:59:00'));
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Portal > Manage Assignments</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="<?php echo $role == 'Admin' ? 'admin_dashboard.php' : 'teacher_dashboard.php'; ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Dashboard</a>
        </div>
    </header>

    <main class="main-content">
        <section style="display: grid; grid-template-columns: 1fr 350px; gap: 50px;">
            <!-- Assignment Roster -->
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
                        <button type="submit" style="background: var(--brand-gradient); color: #fff; padding: 12px 30px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer;">Load Tasks</button>
                    </form>
                </section>

                <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $message; ?></div> <?php endif; ?>
                <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $error; ?></div> <?php endif; ?>

                <?php if ($selected_course): ?>
                    <h2 style="font-size: 1.6rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Published Coursework</h2>
                    <div class="table-container" style="border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Task Identity</th>
                                    <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Deadline</th>
                                    <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($assignments)): ?>
                                    <tr><td colspan="3" style="padding: 40px; text-align: center; color: #94a3b8;">No tasks currently assigned for this module.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($assignments as $a): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 20px;">
                                            <div style="display: flex; flex-direction: column;">
                                                <span style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($a['title']); ?></span>
                                                <span style="font-size: 0.8rem; color: #64748b; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; max-width: 300px;"><?php echo htmlspecialchars($a['description']); ?></span>
                                            </div>
                                        </td>
                                        <td style="padding: 20px;">
                                            <span style="font-size: 0.9rem; color: #475569; font-weight: 600;">
                                                <?php echo date('M d, Y | h:i A', strtotime($a['due_date'])); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 20px; text-align: center;">
                                            <div style="display: flex; justify-content: center; gap: 15px;">
                                                <a href="edit_assignment.php?id=<?php echo $a['id']; ?>" style="color: #6366f1; font-size: 1.1rem;"><i class="fas fa-edit"></i></a>
                                                <a href="?course_id=<?php echo $selected_course; ?>&delete_id=<?php echo $a['id']; ?>" style="color: #f43f5e; font-size: 1.1rem;" onclick="return confirm('Delete this assignment permanently?')"><i class="fas fa-trash-alt"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Publish Assignment Sidebar -->
            <aside style="background: #f8fafc; padding: 30px; border-radius: 24px; border: 1px solid #f1f5f9; height: fit-content; position: sticky; top: 40px;">
                <h3 style="font-size: 1.25rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Publish New Task</h3>
                <form action="" method="POST">
                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Academic Module*</label>
                        <select name="course_id" required style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                            <option value="">-- Choose Module --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($selected_course == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Task Identity Title*</label>
                        <input type="text" name="title" required placeholder="e.g. Midterm Lab" style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Deadline (Date & Time)*</label>
                        <input type="datetime-local" name="due_date" value="<?php echo $default_date; ?>" required style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none;">
                    </div>
                    <div style="margin-bottom: 30px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Detailed Briefing</label>
                        <textarea name="description" style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); outline: none; height: 120px; resize: none;" placeholder="Provide task requirements..."></textarea>
                    </div>
                    <button type="submit" name="add_assignment" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 15px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer;">Publish Requirements</button>
                </form>
            </aside>
        </section>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
