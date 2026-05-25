<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Edit Schedule Entry - Admin Portal";
include_once ROOT_DIR . '/includes/header.php';

$id = $_GET['id'] ?? null;
$message = '';
$error = '';

if (!$id) {
    header("Location: " . ROOT_URL . "/public/admin/manage_timetable.php");
    exit();
}

// Institutional Periods
$periods = [
    1 => ['start' => '07:00:00', 'end' => '09:00:00'],
    2 => ['start' => '10:00:00', 'end' => '12:00:00'],
    3 => ['start' => '13:00:00', 'end' => '15:00:00'],
];

// Fetch current entry
try {
    $stmt = $pdo->prepare("SELECT * FROM timetable WHERE id = ?");
    $stmt->execute([$id]);
    $entry = $stmt->fetch();

    if (!$entry) {
        die("Schedule entry not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_schedule'])) {
    $course_id = $_POST['course_id'];
    $teacher_id = $_POST['teacher_id'];
    $classroom = $_POST['classroom'];
    $day = $_POST['day_of_week'];
    $period_num = $_POST['period_number'];

    $start = $periods[$period_num]['start'];
    $end = $periods[$period_num]['end'];

    try {
        // 0. Strict Validation: Is this teacher actually assigned to this course?
        $stmt = $pdo->prepare("SELECT teacher_id FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $officialTeacherId = $stmt->fetchColumn();

        if ($officialTeacherId != $teacher_id) {
            throw new Exception("UNAUTHORIZED FACULTY: The selected teacher is not the official leader for this academic module.");
        }

        // Clash Detection (excluding current record)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM timetable WHERE teacher_id = ? AND day_of_week = ? AND period_number = ? AND id != ?");
        $stmt->execute([$teacher_id, $day, $period_num, $id]);
        if ($stmt->fetchColumn() > 0) throw new Exception("CLASH: Teacher already assigned elsewhere.");

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM timetable WHERE classroom = ? AND day_of_week = ? AND period_number = ? AND id != ?");
        $stmt->execute([$classroom, $day, $period_num, $id]);
        if ($stmt->fetchColumn() > 0) throw new Exception("CLASH: Classroom already booked.");

        // Update
        $stmt = $pdo->prepare("UPDATE timetable SET course_id = ?, teacher_id = ?, classroom = ?, day_of_week = ?, period_number = ?, start_time = ?, end_time = ? WHERE id = ?");
        $stmt->execute([$course_id, $teacher_id, $classroom, $day, $period_num, $start, $end, $id]);
        
        header("Location: " . ROOT_URL . "/public/admin/manage_timetable.php?msg=Updated");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch lists for dropdowns
$courses = $pdo->query("SELECT id, course_name FROM courses")->fetchAll();
$teachers = $pdo->query("SELECT t.id, u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id")->fetchAll();
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Edit Session Schedule</p>
        </div>
        <div class="header-icons" style="margin-left: 20px;">
            <a href="<?php echo ROOT_URL; ?>/public/admin/manage_timetable.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Back to Master List</a>
        </div>
    </header>

    <main class="main-content" style="padding: 60px; background: #f8fafc; display: flex; justify-content: center;">
        <div style="background: #fff; padding: 50px; border-radius: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.05); width: 100%; max-width: 600px; border: 1px solid #f1f5f9;">
            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 30px;">Update Academic Session</h2>
            
            <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; border: 1px solid #fecaca;"><?php echo $error; ?></div> <?php endif; ?>

            <form action="" method="POST">
                <div style="margin-bottom: 20px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">Academic Module</label>
                    <select name="course_id" id="course_select" required style="width: 100%; padding: 14px; border: 2px solid #f8fafc; border-radius: 15px; background: #f8fafc; font-weight: 600;" onchange="syncTeacher()">
                        <?php foreach ($courses as $c): 
                            $stmt = $pdo->prepare("SELECT teacher_id FROM courses WHERE id = ?");
                            $stmt->execute([$c['id']]);
                            $officialTeacher = $stmt->fetchColumn();
                        ?>
                            <option value="<?php echo $c['id']; ?>" data-teacher="<?php echo $officialTeacher; ?>" <?php echo $entry['course_id'] == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['course_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">Assigned Faculty</label>
                    <select name="teacher_id" id="teacher_select" required style="width: 100%; padding: 14px; border: 2px solid #f8fafc; border-radius: 15px; background: #f8fafc; font-weight: 600;">
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo $entry['teacher_id'] == $t['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">Day of Week</label>
                        <select name="day_of_week" required style="width: 100%; padding: 14px; border: 2px solid #f8fafc; border-radius: 15px; background: #f8fafc; font-weight: 600;">
                            <?php foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'] as $d): ?>
                                <option value="<?php echo $d; ?>" <?php echo $entry['day_of_week'] == $d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">Period Slot</label>
                        <select name="period_number" required style="width: 100%; padding: 14px; border: 2px solid #f8fafc; border-radius: 15px; background: #f8fafc; font-weight: 600;">
                            <option value="1" <?php echo $entry['period_number'] == 1 ? 'selected' : ''; ?>>Period 1 (07-09 AM)</option>
                            <option value="2" <?php echo $entry['period_number'] == 2 ? 'selected' : ''; ?>>Period 2 (10-12 PM)</option>
                            <option value="3" <?php echo $entry['period_number'] == 3 ? 'selected' : ''; ?>>Period 3 (01-03 PM)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 40px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">Classroom / Hall</label>
                    <input type="text" name="classroom" value="<?php echo htmlspecialchars($entry['classroom']); ?>" required style="width: 100%; padding: 14px; border: 2px solid #f8fafc; border-radius: 15px; background: #f8fafc; font-weight: 600;">
                </div>

                <button type="submit" name="update_schedule" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 18px; border: none; border-radius: 18px; font-weight: 800; cursor: pointer; font-size: 1rem; box-shadow: 0 10px 20px rgba(26, 54, 93, 0.2);">
                    Update Session Details
                </button>
            </form>
        </div>
    </main>
</div>

<?php include_once ROOT_DIR . '/includes/footer.php'; ?>
