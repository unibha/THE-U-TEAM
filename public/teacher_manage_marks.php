<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';
require_once '../includes/notification_helper.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/validation_helper.php';

// Only Teacher (and Admin) allowed
checkAuth(['Teacher', 'Admin']);

$pageTitle = "Academic Marks Entry - Teacher Portal";
include_once '../includes/header.php';

$teacherUserId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$error = '';

// Get internal Teacher ID if role is Teacher
$teacherInternalId = null;
if ($role == 'Teacher') {
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$teacherUserId]);
    $teacherInternalId = $stmt->fetchColumn();
}

// 1. Fetch available exams for the teacher's courses
try {
    if ($role == 'Admin') {
        $exams = $pdo->query("
            SELECT e.*, c.course_name, c.course_code 
            FROM exam e 
            JOIN courses c ON e.course_id = c.id 
            ORDER BY e.exam_date DESC
        ")->fetchAll();
    } else {
        $stmt = $pdo->prepare("
            SELECT e.*, c.course_name, c.course_code 
            FROM exam e 
            JOIN courses c ON e.course_id = c.id 
            WHERE c.teacher_id = ? 
            ORDER BY e.exam_date DESC
        ");
        $stmt->execute([$teacherInternalId]);
        $exams = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $exams = [];
}

$selected_exam_id = $_GET['exam_id'] ?? null;
$exam_details = null;
$students = [];

// 2. If an exam is selected, fetch students and exam info
if ($selected_exam_id) {
    try {
        // Fetch exam info for validation (ensure teacher owns the course linked to this exam)
        if ($role === 'Admin') {
            $stmt = $pdo->prepare("SELECT e.*, c.id as course_id FROM exam e JOIN courses c ON e.course_id = c.id WHERE e.id = ?");
            $stmt->execute([$selected_exam_id]);
        } else {
            $stmt = $pdo->prepare("SELECT e.*, c.id as course_id FROM exam e JOIN courses c ON e.course_id = c.id WHERE e.id = ? AND c.teacher_id = ?");
            $stmt->execute([$selected_exam_id, $teacherInternalId]);
        }
        $exam_details = $stmt->fetch();

        if (!$exam_details && $role !== 'Admin') {
            $error = "Access Denied: You are not authorized to manage results for this examination.";
            $selected_exam_id = null;
        } elseif ($exam_details) {
            // Fetch enrolled students and their existing marks (if any)
            $stmt = $pdo->prepare("
                SELECT s.id as student_id, u.first_name, u.last_name, m.marks_obtained, m.grade, m.remarks, m.id as mark_record_id
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN enrollments en ON s.id = en.student_id
                LEFT JOIN marks m ON (s.id = m.student_id AND m.exam_id = ?)
                WHERE en.course_id = ?
                ORDER BY u.first_name ASC
            ");
            $stmt->execute([$selected_exam_id, $exam_details['course_id']]);
            $students = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $error = "Data fetch error: " . $e->getMessage();
    }
}

// 3. Handle Marks Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_marks'])) {
    // 1. Validate CSRF
    validate_csrf();

    $marks_data = $_POST['marks'] ?? []; 
    $max_marks = (float)$exam_details['total_marks'];

    try {
        $pdo->beginTransaction();
        foreach ($marks_data as $sid => $data) {
            $obtained = $data['obtained'] === '' ? null : (float)$data['obtained'];
            $remarks = sanitize($data['remarks'] ?? '');
            
            if ($obtained !== null) {
                // Validate range
                if ($obtained > $max_marks || $obtained < 0) {
                    throw new Exception("Marks for student ID $sid cannot exceed $max_marks or be negative.");
                }

                // Calculate Grade (A: 90+, B: 80+, C: 70+, D: 60+, F: <60)
                $percent = ($obtained / $max_marks) * 100;
                if ($percent >= 90) $grade = 'A';
                elseif ($percent >= 80) $grade = 'B';
                elseif ($percent >= 70) $grade = 'C';
                elseif ($percent >= 60) $grade = 'D';
                else $grade = 'F';

                $stmt = $pdo->prepare("
                    INSERT INTO marks (exam_id, student_id, course_id, marks_obtained, grade, remarks, entered_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        marks_obtained = VALUES(marks_obtained),
                        grade = VALUES(grade),
                        remarks = VALUES(remarks),
                        entered_by = VALUES(entered_by)
                ");
                $stmt->execute([$selected_exam_id, $sid, $exam_details['course_id'], $obtained, $grade, $remarks, $teacherUserId]);
                
                // Optional: Notify student about the result
                $studentUser = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
                $studentUser->execute([$sid]);
                $sUid = $studentUser->fetchColumn();
                if ($sUid) {
                    sendNotification($sUid, "Exam Result Published", "Your results for '{$exam_details['exam_name']}' have been published. Grade: $grade", 'Academic');
                }
            }
        }
        $pdo->commit();
        $message = "Academic results processed and saved successfully!";
        
        // Refresh student data
        header("Location: teacher_manage_marks.php?exam_id=$selected_exam_id&msg=Success");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Processing error: " . $e->getMessage();
    }
}

if (isset($_GET['msg'])) $message = "Results updated and students notified!";
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Teacher Portal > Performance Master</p>
        </div>
        <div class="header-tools">
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="<?php echo $role == 'Admin' ? 'admin_dashboard.php' : 'teacher_dashboard.php'; ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px; transition: 0.3s ease; background: rgba(255,255,255,0.1);"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Exam Selection Section -->
        <section class="selection-bar" style="background: #f8fafc; padding: 30px; border-radius: 25px; border: 1px solid #f1f5f9; margin-bottom: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
            <form action="" method="GET" style="display: flex; gap: 30px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 10px; display: block;">Choose Examination Event</label>
                    <select name="exam_id" required style="width: 100%; padding: 14px; border: 2px solid #fff; border-radius: 14px; outline: none; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); font-weight: 600; cursor: pointer;">
                        <option value="">-- Select Exam --</option>
                        <?php foreach ($exams as $e): ?>
                            <option value="<?php echo $e['id']; ?>" <?php echo $selected_exam_id == $e['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['exam_name'] . ' [' . $e['course_code'] . ']'); ?> - <?php echo date('M d, Y', strtotime($e['exam_date'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" style="background: var(--brand-gradient); color: #fff; padding: 14px 40px; border: none; border-radius: 14px; font-weight: 800; cursor: pointer; transition: 0.3s ease; box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);">Load Performance Roster</button>
            </form>
        </section>

        <section style="margin-bottom: 40px;">
            <div style="background: #fff; padding: 25px; border-radius: 20px; border: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-search" style="color: #94a3b8; font-size: 1.2rem;"></i>
                    <h4 style="margin: 0; color: #1e293b; font-weight: 800;">Quick Marksheet Lookup</h4>
                </div>
                <form action="view_marks.php" method="GET" style="display: flex; gap: 10px;">
                    <select name="student_id" required style="padding: 10px 20px; border: 1px solid #e2e8f0; border-radius: 10px; font-weight: 600;">
                        <option value="">-- Select Student --</option>
                        <?php 
                            $myStudents = $pdo->prepare("
                                SELECT DISTINCT s.id, u.first_name, u.last_name 
                                FROM students s 
                                JOIN users u ON s.user_id = u.id 
                                JOIN enrollments e ON s.id = e.student_id
                                JOIN courses c ON e.course_id = c.id
                                WHERE c.teacher_id = ?
                                ORDER BY u.first_name ASC
                            ");
                            $myStudents->execute([$teacherInternalId]);
                            foreach($myStudents->fetchAll() as $mst):
                        ?>
                            <option value="<?php echo $mst['id']; ?>"><?php echo htmlspecialchars($mst['first_name'] . ' ' . $mst['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" style="background: #f1f5f9; color: #475569; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer;">View Results</button>
                </form>
            </div>
        </section>

        <?php if ($selected_exam_id && $exam_details): ?>
            <section class="marks-entry-view">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px;">
                    <div>
                        <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800;">Grade Submission: <?php echo htmlspecialchars($exam_details['exam_name']); ?></h2>
                        <p style="color: #64748b; font-weight: 600;">Maximum Possible Marks: <span style="color: #8b5cf6; font-weight: 800;"><?php echo $exam_details['total_marks']; ?></span></p>
                    </div>
                </div>

                <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 18px; border-radius: 15px; margin-bottom: 25px; font-weight: 600;"><i class="fas fa-check-circle" style="margin-right: 10px;"></i> <?php echo $message; ?></div> <?php endif; ?>
                <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 18px; border-radius: 15px; margin-bottom: 25px; font-weight: 600;"><i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i> <?php echo $error; ?></div> <?php endif; ?>

                <form action="" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="table-container" style="border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); overflow: hidden; background: #fff; border: 1px solid #f1f5f9;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th style="padding: 22px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9;">Student Identity</th>
                                    <th style="padding: 22px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9; width: 180px;">Marks Obtained</th>
                                    <th style="padding: 22px; text-align: center; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9; width: 100px;">Grade</th>
                                    <th style="padding: 22px; text-align: left; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9;">Teacher Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="4" style="padding: 50px; text-align: center; color: #94a3b8; font-weight: 600;">No members enrolled in this academic module.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($students as $s): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s ease;">
                                        <td style="padding: 22px;">
                                            <span style="font-weight: 800; color: #1e293b; font-size: 1rem;"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></span>
                                        </td>
                                        <td style="padding: 22px; text-align: center;">
                                            <input type="number" step="0.01" name="marks[<?php echo $s['student_id']; ?>][obtained]" value="<?php echo $s['marks_obtained']; ?>" placeholder="0.00" style="width: 100px; padding: 10px; border: 2px solid #f1f5f9; border-radius: 10px; text-align: center; outline: none; font-weight: 800; color: #1e293b; background: #fcfdfe;">
                                        </td>
                                        <td style="padding: 22px; text-align: center;">
                                            <?php if ($s['grade']): ?>
                                                <span style="background: <?php echo $s['grade'] == 'F' ? '#fff1f2' : '#f0fdf4'; ?>; color: <?php echo $s['grade'] == 'F' ? '#f43f5e' : '#10b981'; ?>; padding: 6px 14px; border-radius: 10px; font-weight: 900; font-size: 0.9rem;"><?php echo $s['grade']; ?></span>
                                            <?php else: ?>
                                                <span style="color: #cbd5e1; font-weight: 600;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 22px;">
                                            <input type="text" name="marks[<?php echo $s['student_id']; ?>][remarks]" value="<?php echo htmlspecialchars($s['remarks']); ?>" placeholder="Optional feedback..." style="width: 100%; padding: 10px; border: 2px solid #f1f5f9; border-radius: 10px; outline: none; background: #fcfdfe;">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="margin-top: 40px; display: flex; justify-content: flex-end; gap: 20px; align-items: center;">
                        <p style="color: #64748b; font-size: 0.9rem; font-weight: 600;">Submission will automatically notify all students of their results.</p>
                        <button type="submit" name="save_marks" style="background: #10b981; color: #fff; padding: 16px 45px; border: none; border-radius: 15px; font-weight: 800; cursor: pointer; transition: 0.3s ease; font-size: 1rem; box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);">
                            Finalize and Publish Grades
                        </button>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <div style="background: #fff; padding: 100px; border-radius: 30px; text-align: center; border: 2px dashed #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <i class="fas fa-edit" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 25px;"></i>
                <h3 style="color: #64748b; font-weight: 800; font-size: 1.4rem;">Performance Ready</h3>
                <p style="color: #94a3b8; font-weight: 600;">Select an examination event from the dropdown to start entering academic records.</p>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
