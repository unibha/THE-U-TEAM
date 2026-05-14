<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Global Academic Records - Admin Portal";
include_once '../includes/header.php';

$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM marks WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $message = "Academic record permanently erased.";
    } catch (PDOException $e) {
        $error = "Deletion error: " . $e->getMessage();
    }
}

// Fetch Summary Stats per Exam
try {
    $stats = $pdo->query("
        SELECT 
            e.exam_name, 
            c.course_name, 
            c.course_code,
            COUNT(m.id) as entries,
            AVG(m.marks_obtained) as avg_score,
            MAX(m.marks_obtained) as top_score,
            e.id as exam_id
        FROM exam e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN marks m ON e.id = m.exam_id
        GROUP BY e.id
        ORDER BY e.exam_date DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $stats = [];
}

// Fetch all marks for filtering or global view
$selected_exam = $_GET['exam_id'] ?? null;
$records = [];

try {
    if ($selected_exam) {
        $stmt = $pdo->prepare("
            SELECT m.*, u_s.first_name as s_fname, u_s.last_name as s_lname, 
                   u_e.first_name as e_fname, u_e.last_name as e_lname,
                   e.exam_name, e.total_marks, c.course_name
            FROM marks m
            JOIN students s ON m.student_id = s.id
            JOIN users u_s ON s.user_id = u_s.id
            JOIN users u_e ON m.entered_by = u_e.id
            JOIN exam e ON m.exam_id = e.id
            JOIN courses c ON m.course_id = c.id
            WHERE m.exam_id = ?
            ORDER BY u_s.first_name ASC
        ");
        $stmt->execute([$selected_exam]);
        $records = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $records = [];
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Global Results Master</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        
        <section class="admin-tools" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 50px;">
            <div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Student Search</h2>
                <div style="background: #fff; padding: 30px; border-radius: 25px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
                    <form action="view_marks.php" method="GET" style="display: flex; gap: 15px;">
                        <select name="student_id" required style="flex: 1; padding: 14px; border: 2px solid #f8fafc; border-radius: 14px; background: #f8fafc; font-weight: 600; outline: none;">
                            <option value="">-- Select Student to View Marksheet --</option>
                            <?php 
                                $allStudents = $pdo->query("SELECT s.id, u.first_name, u.last_name FROM students s JOIN users u ON s.user_id = u.id ORDER BY u.first_name ASC")->fetchAll();
                                foreach($allStudents as $st):
                            ?>
                                <option value="<?php echo $st['id']; ?>"><?php echo htmlspecialchars($st['first_name'] . ' ' . $st['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" style="background: #8b5cf6; color: #fff; border: none; padding: 14px 25px; border-radius: 14px; font-weight: 800; cursor: pointer;">View Profile</button>
                    </form>
                </div>
            </div>
            
            <div style="text-align: right; display: flex; flex-direction: column; justify-content: flex-end;">
                <p style="color: #64748b; font-weight: 600; margin-bottom: 10px;">Quick Access Actions</p>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="admin_manage_exam.php" style="background: #f1f5f9; color: #475569; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem;">Manage Exams</a>
                    <a href="admin_manage_courses.php" style="background: #f1f5f9; color: #475569; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem;">Manage Courses</a>
                </div>
            </div>
        </section>

        <section class="stats-overview" style="margin-bottom: 50px;">
            <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Examination Performance Summary</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">
                <?php foreach ($stats as $s): ?>
                    <div style="background: #fff; padding: 25px; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <span style="font-size: 0.7rem; color: #8b5cf6; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo htmlspecialchars($s['course_code']); ?></span>
                            <h4 style="font-size: 1.15rem; color: #1e293b; font-weight: 800; margin: 5px 0;"><?php echo htmlspecialchars($s['exam_name']); ?></h4>
                            <p style="color: #64748b; font-size: 0.85rem; font-weight: 600;"><?php echo $s['entries']; ?> Entries Processed</p>
                            
                            <div style="margin-top: 15px; display: flex; gap: 15px;">
                                <div>
                                    <span style="display: block; font-size: 0.65rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Average</span>
                                    <span style="font-size: 1.1rem; color: #1e293b; font-weight: 800;"><?php echo round($s['avg_score'], 1); ?></span>
                                </div>
                                <div>
                                    <span style="display: block; font-size: 0.65rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Highest</span>
                                    <span style="font-size: 1.1rem; color: #10b981; font-weight: 800;"><?php echo $s['top_score']; ?></span>
                                </div>
                            </div>
                        </div>
                        <a href="?exam_id=<?php echo $s['exam_id']; ?>" style="background: #f5f3ff; color: #8b5cf6; padding: 8px 15px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.8rem;">View Roster</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($selected_exam && !empty($records)): ?>
            <section class="detailed-roster">
                <h3 style="font-size: 1.5rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Detailed Roster: <?php echo htmlspecialchars($records[0]['exam_name']); ?></h3>
                
                <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;"><?php echo $message; ?></div> <?php endif; ?>

                <div class="table-container" style="border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); overflow: hidden; background: #fff; border: 1px solid #f1f5f9;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Student</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Score</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Grade</th>
                                <th style="padding: 20px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Entered By</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 20px; font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($r['s_fname'] . ' ' . $r['s_lname']); ?></td>
                                    <td style="padding: 20px; text-align: center; font-weight: 700; color: #1e293b;"><?php echo $r['marks_obtained']; ?> / <?php echo $r['total_marks']; ?></td>
                                    <td style="padding: 20px; text-align: center;">
                                        <span style="background: #f0fdf4; color: #10b981; padding: 5px 12px; border-radius: 8px; font-weight: 800; font-size: 0.85rem;"><?php echo $r['grade']; ?></span>
                                    </td>
                                    <td style="padding: 20px; font-size: 0.9rem; color: #64748b; font-weight: 600;">
                                        <i class="fas fa-user-edit" style="margin-right: 8px; color: #94a3b8;"></i><?php echo htmlspecialchars($r['e_fname'] . ' ' . $r['e_lname']); ?>
                                    </td>
                                    <td style="padding: 20px; text-align: center;">
                                        <div style="display: flex; justify-content: center; gap: 10px;">
                                            <a href="teacher_manage_marks.php?exam_id=<?php echo $selected_exam; ?>" style="color: #3b82f6;"><i class="fas fa-edit"></i></a>
                                            <a href="?delete_id=<?php echo $r['id']; ?>&exam_id=<?php echo $selected_exam; ?>" style="color: #f43f5e;" onclick="return confirm('Erase this record?')"><i class="fas fa-trash-alt"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
