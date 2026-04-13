<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed for the Master Hub
checkAuth(['Admin']);

$pageTitle = "Attendance Master Hub - Admin Control";
include_once '../includes/header.php';

$message = '';
$error = '';

/**
 * Self-healing: Ensure Teacher Attendance table exists
 */
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS teacher_attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            status ENUM('Present', 'Absent', 'Late', 'On Leave') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY teacher_date_idx (teacher_id, attendance_date),
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ");
} catch (PDOException $e) { /* Table exists or other non-critical error */ }

// Handle Deletions (Student or Teacher)
if (isset($_GET['delete_record'])) {
    $type = $_GET['type'];
    $id = $_GET['id']; // Attendance ID
    try {
        $table = ($type == 'Student') ? 'attendance' : 'teacher_attendance';
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Presence record removed from system data.";
    } catch (PDOException $e) { $error = "Deletion error: " . $e->getMessage(); }
}

// Handle Status Updates (Upsert)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $type = $_POST['type'];
    $record_id = $_POST['record_id'] ?? null;
    $target_id = $_POST['target_id']; // student_id or teacher_id
    $date = $_POST['date'];
    $new_status = $_POST['new_status'];
    
    try {
        if ($type == 'Student') {
            $stmt = $pdo->prepare("
                INSERT INTO attendance (student_id, course_id, attendance_date, status) 
                VALUES (?, (SELECT course_id FROM enrollments WHERE student_id = ? LIMIT 1), ?, ?) 
                ON DUPLICATE KEY UPDATE status = VALUES(status)
            ");
            $stmt->execute([$target_id, $target_id, $date, $new_status]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO teacher_attendance (teacher_id, attendance_date, status) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE status = VALUES(status)
            ");
            $stmt->execute([$target_id, $date, $new_status]);
        }
        $message = "Presence record for $type refined successfully!";
    } catch (PDOException $e) { $error = "Process failed: " . $e->getMessage(); }
}

$search = $_GET['search'] ?? '';
$course_id = $_GET['course_filter'] ?? '';
$role_filter = $_GET['role_filter'] ?? 'All';
$date = $_GET['date_filter'] ?? date('Y-m-d');

$results = [];

try {
    // 1. Fetch Students Attendance
    if ($role_filter == 'All' || $role_filter == 'Student') {
        $student_query = "
            SELECT 
                a.id as record_id, 
                s.id as target_id, 
                u.first_name, 
                u.last_name, 
                'Student' as role, 
                COALESCE(a.status, 'Not Marked') as status, 
                COALESCE(a.attendance_date, :date1) as attendance_date, 
                c.course_name, 
                c.course_code 
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN enrollments e ON s.id = e.student_id
            JOIN courses c ON e.course_id = c.id
            LEFT JOIN attendance a ON (s.id = a.student_id AND c.id = a.course_id AND a.attendance_date = :date2)
            WHERE 1=1
        ";
        if ($search) $student_query .= " AND (u.first_name LIKE :search1 OR u.last_name LIKE :search2)";
        if ($course_id) $student_query .= " AND c.id = :course_id";
        
        $stmt = $pdo->prepare($student_query);
        $params = [ 'date1' => $date, 'date2' => $date ];
        if ($search) {
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
        }
        if ($course_id) $params['course_id'] = $course_id;
        $stmt->execute($params);
        $results = array_merge($results, $stmt->fetchAll());
    }

    // 2. Fetch Teachers Attendance (Master List)
    if ($role_filter == 'All' || $role_filter == 'Teacher') {
        $teacher_query = "
            SELECT 
                ta.id as record_id, 
                t.id as target_id,
                u.first_name, 
                u.last_name, 
                'Teacher' as role, 
                COALESCE(ta.status, 'Not Marked') as status, 
                COALESCE(ta.attendance_date, :date1) as attendance_date,
                COALESCE(c.course_name, 'Staff Presence') as course_name, 
                COALESCE(c.course_code, 'N/A') as course_code 
            FROM teachers t
            JOIN users u ON t.user_id = u.id 
            LEFT JOIN courses c ON t.id = c.teacher_id
            LEFT JOIN teacher_attendance ta ON (t.id = ta.teacher_id AND ta.attendance_date = :date2)
            WHERE 1=1
        ";
        if ($search) $teacher_query .= " AND (u.first_name LIKE :search1 OR u.last_name LIKE :search2)";
        if ($course_id) $teacher_query .= " AND c.id = :course_id";

        $stmt = $pdo->prepare($teacher_query);
        $teacher_params = ['date1' => $date, 'date2' => $date];
        if ($search) {
            $teacher_params['search1'] = "%$search%";
            $teacher_params['search2'] = "%$search%";
        }
        if ($course_id) $teacher_params['course_id'] = $course_id;
        $stmt->execute($teacher_params);
        $results = array_merge($results, $stmt->fetchAll());
    }

    // Final sorting by name
    usort($results, function($a, $b) { return strcmp($a['first_name'], $b['first_name']); });

} catch (PDOException $e) { $error = "Search failed: " . $e->getMessage(); }

// Fetch courses for filter
$courses = $pdo->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name")->fetchAll();
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Control > Attendance Master Hub</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
            <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
        </div>
    </header>

    <main class="main-content">
        <!-- Advanced Selection Bar -->
        <section class="selection-bar" style="background: #f8fafc; padding: 30px; border-radius: 24px; border: 1px solid #f1f5f9; margin-bottom: 40px;">
            <form action="" method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr 200px 150px; gap: 20px; align-items: flex-end;">
                <div class="input-group">
                    <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 8px; display: block;">Search by Name</label>
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 15px; color: #94a3b8;"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter first or last name..." style="width: 100%; padding: 12px 12px 12px 40px; border: 2px solid #fff; border-radius: 12px; outline: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    </div>
                </div>
                <div class="input-group">
                    <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 8px; display: block;">Role</label>
                    <select name="role_filter" style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <option value="All" <?php echo ($role_filter == 'All') ? 'selected' : ''; ?>>All Members</option>
                        <option value="Student" <?php echo ($role_filter == 'Student') ? 'selected' : ''; ?>>Only Students</option>
                        <option value="Teacher" <?php echo ($role_filter == 'Teacher') ? 'selected' : ''; ?>>Only Teachers</option>
                    </select>
                </div>
                <div class="input-group">
                    <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 8px; display: block;">Course Context</label>
                    <select name="course_filter" style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <option value="">-- All Modules --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($course_id == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['course_name'] . ' (' . $c['course_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 8px; display: block;">Academic Date</label>
                    <input type="date" name="date_filter" value="<?php echo $date; ?>" style="width: 100%; padding: 12px; border: 2px solid #fff; border-radius: 12px; outline: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                </div>
                <button type="submit" style="background: var(--brand-gradient); color: #fff; padding: 14px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer;">Perform Search</button>
            </form>
        </section>

        <!-- Dynamic Results Hub -->
        <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $message; ?></div> <?php endif; ?>
        <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 25px;"><?php echo $error; ?></div> <?php endif; ?>

        <div class="table-container" style="border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9;">User Identity</th>
                        <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9;">Module / Context</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9;">Presence Status</th>
                        <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9;">Control Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr><td colspan="4" style="padding: 40px; text-align: center; color: #94a3b8;">No members matching your search parameters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($results as $r): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 20px;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></span>
                                    <span style="font-size: 0.8rem; font-weight: 700; color: <?php echo $r['role'] == 'Teacher' ? '#8b5cf6' : '#2563eb'; ?>; text-transform: uppercase;"><?php echo $r['role']; ?></span>
                                </div>
                            </td>
                            <td style="padding: 20px;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 0.9rem; color: #475569; font-weight: 600;"><?php echo htmlspecialchars($r['course_name']); ?></span>
                                    <span style="font-size: 0.8rem; color: #94a3b8;"><?php echo htmlspecialchars($r['course_code']); ?></span>
                                </div>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <form action="" method="POST" style="display: flex; justify-content: center;">
                                    <input type="hidden" name="type" value="<?php echo $r['role']; ?>">
                                    <input type="hidden" name="record_id" value="<?php echo $r['record_id']; ?>">
                                    <input type="hidden" name="target_id" value="<?php echo $r['target_id']; ?>">
                                    <input type="hidden" name="date" value="<?php echo $r['attendance_date']; ?>">
                                    
                                    <select name="new_status" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 10px; border: 1px solid <?php echo $r['record_id'] ? '#e2e8f0' : '#fecaca'; ?>; font-size: 0.85rem; font-weight: 700; background: <?php echo $r['record_id'] ? '#fff' : '#fff5f5'; ?>; cursor: pointer;">
                                        <option value="" <?php echo !$r['record_id'] ? 'selected' : ''; ?> disabled>-- Mark Standing --</option>
                                        <option value="Present" <?php echo $r['status'] == 'Present' ? 'selected' : ''; ?>>Present</option>
                                        <option value="Absent" <?php echo $r['status'] == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                                        <option value="Late" <?php echo $r['status'] == 'Late' ? 'selected' : ''; ?>>Late</option>
                                        <?php if ($r['role'] == 'Teacher'): ?>
                                            <option value="On Leave" <?php echo $r['status'] == 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                                        <?php endif; ?>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td style="padding: 20px; text-align: center;">
                                <?php if ($r['record_id']): ?>
                                <a href="?search=<?php echo urlencode($search); ?>&course_filter=<?php echo $course_id; ?>&date_filter=<?php echo $date; ?>&id=<?php echo $r['record_id']; ?>&type=<?php echo $r['role']; ?>&delete_record=1" 
                                   style="color: #f43f5e; font-size: 1.1rem; text-decoration: none;" 
                                   onclick="return confirm('Permanently remove this presence record?')" title="Delete Record">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <?php else: ?>
                                <span style="color: #94a3b8; font-size: 0.8rem; font-style: italic;">Standby</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
