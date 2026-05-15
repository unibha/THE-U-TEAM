<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Global Assignment Control - Academic Management System";
include_once '../includes/header.php';

$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $message = "Assignment forcefully removed from curriculum records.";
    } catch (PDOException $e) { $error = "Force removal failed: " . $e->getMessage(); }
}

// Handle Add Assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_assignment'])) {
    $course_id = $_POST['course_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';

    if (empty($course_id) || empty($title) || empty($due_date)) {
        $error = "Module, Title, and Deadline are mandatory.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO assignments (course_id, title, description, due_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$course_id, $title, $description, $due_date]);
            $message = "New academic task established successfully!";
        } catch (PDOException $e) { $error = "Establishment failed: " . $e->getMessage(); }
    }
}

// Fetch all assignments with full metadata
try {
    $assignments = $pdo->query("
        SELECT a.*, c.course_name, c.course_code, u.first_name, u.last_name 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        LEFT JOIN teachers t ON c.teacher_id = t.id 
        LEFT JOIN users u ON t.user_id = u.id 
        ORDER BY a.due_date DESC
    ")->fetchAll();

    // Fetch courses for the creation dropdown
    $courses = $pdo->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC")->fetchAll();
} catch (PDOException $e) {
    $assignments = [];
    $error = "Record fetch failure: " . $e->getMessage();
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Global Assignment Control</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
            <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
        </div>
    </header>

    <main class="main-content">
        <section style="display: grid; grid-template-columns: 1fr 350px; gap: 40px;">
            <div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 30px;">Academic Tasks Master List</h2>

        <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $message; ?></div> <?php endif; ?>
        <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $error; ?></div> <?php endif; ?>

        <div class="table-container" style="border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Course & Instructor</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Assignment Title</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Deadline</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #f1f5f9;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignments)): ?>
                        <tr><td colspan="4" style="padding: 40px; text-align: center; color: #94a3b8;">No assignment records found in the system catalog.</td></tr>
                    <?php else: ?>
                        <?php foreach ($assignments as $a): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 20px;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($a['course_name']); ?></span>
                                    <span style="font-size: 0.8rem; color: #8b5cf6; font-weight: 700;">Prof. <?php echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name'] ?: 'Unassigned'); ?></span>
                                </div>
                            </td>
                            <td style="padding: 20px;">
                                <span style="font-weight: 700; color: #475569;"><?php echo htmlspecialchars($a['title']); ?></span>
                            </td>
                            <td style="padding: 20px;">
                                <span style="font-size: 0.9rem; color: #f43f5e; font-weight: 700;">
                                    <?php echo date('M d, Y', strtotime($a['due_date'])); ?>
                                </span>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <div style="display: flex; justify-content: center; gap: 15px;">
                                    <a href="admin_edit_assignment.php?id=<?php echo $a['id']; ?>" style="color: #6366f1; font-size: 1.1rem;" title="Edit Assignment"><i class="fas fa-edit"></i></a>
                                    <a href="?delete_id=<?php echo $a['id']; ?>" style="color: #f43f5e; font-size: 1.1rem;" onclick="return confirm('Forcefully delete this assignment record?')" title="Delete Assignment"><i class="fas fa-trash-alt"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Assignment Sidebar -->
    <aside style="background: #f8fafc; padding: 30px; border-radius: 24px; border: 1px solid #f1f5f9; height: fit-content; position: sticky; top: 40px;">
        <h3 style="font-size: 1.25rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Establish New Task</h3>
        <form action="" method="POST">
            <div style="margin-bottom: 20px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Target Module*</label>
                <select name="course_id" required style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Task Working Title*</label>
                <input type="text" name="title" required placeholder="e.g. Mid-term Research" style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Submission Deadline*</label>
                <input type="date" name="due_date" required style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            </div>
            <div style="margin-bottom: 30px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Brief Instructions</label>
                <textarea name="description" style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; height: 100px; resize: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);"></textarea>
            </div>
            <button type="submit" name="add_assignment" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 15px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.3s ease;">
                Publish Task
            </button>
        </form>
    </aside>
</section>
</main>
</div>


<?php include_once '../includes/footer.php'; ?>
