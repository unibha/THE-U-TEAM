<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Students allowed for their own, Admin/Teacher for others
checkAuth(['Student', 'Admin', 'Teacher']);

$role = $_SESSION['role'];
$myUserId = $_SESSION['user_id'];

// Determine which student's results to view
$targetStudentId = $_GET['student_id'] ?? null;

if ($role === 'Student') {
    // Students can only view themselves
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$myUserId]);
    $studentInternalId = $stmt->fetchColumn();
} else {
    // Admin/Teacher specifies student_id
    if (!$targetStudentId) {
        header("Location: " . ($role === 'Admin' ? 'admin_manage_marks.php' : 'teacher_manage_marks.php'));
        exit();
    }
    
    // Security Check for Teachers: Can only view students in their own courses
    if ($role === 'Teacher') {
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $stmt->execute([$myUserId]);
        $teacherId = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            WHERE e.student_id = ? AND c.teacher_id = ?
        ");
        $stmt->execute([$targetStudentId, $teacherId]);
        if ($stmt->fetchColumn() == 0) {
            header("Location: teacher_manage_marks.php?error=Access Denied: Student not in your module.");
            exit();
        }
    }
    
    $studentInternalId = $targetStudentId;
}

$pageTitle = "Academic Marksheet - Academic Management System";
include_once '../includes/header.php';

try {
    // 1. Fetch Student Details
    $stmt = $pdo->prepare("
        SELECT u.first_name, u.last_name, s.id as sid, u.email
        FROM students s
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$studentInternalId]);
    $studentInfo = $stmt->fetch();

    if (!$studentInfo) {
        die("Student record not found.");
    }

    // 2. Fetch Results categorized by Exam (Filtered by teacher if not Admin)
    if ($role === 'Teacher') {
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $stmt->execute([$myUserId]);
        $teacherId = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT m.*, e.exam_name, e.exam_date, e.total_marks, c.course_name, c.course_code
            FROM marks m
            JOIN exam e ON m.exam_id = e.id
            JOIN courses c ON m.course_id = c.id
            WHERE m.student_id = ? AND c.teacher_id = ?
            ORDER BY e.exam_date DESC, c.course_name ASC
        ");
        $stmt->execute([$studentInternalId, $teacherId]);
    } else {
        // Students see all their own, Admins see all
        $stmt = $pdo->prepare("
            SELECT m.*, e.exam_name, e.exam_date, e.total_marks, c.course_name, c.course_code
            FROM marks m
            JOIN exam e ON m.exam_id = e.id
            JOIN courses c ON m.course_id = c.id
            WHERE m.student_id = ?
            ORDER BY e.exam_date DESC, c.course_name ASC
        ");
        $stmt->execute([$studentInternalId]);
    }
    $allResults = $stmt->fetchAll();

    // 3. Stats Calculation
    $totalObtained = 0;
    $totalPossible = 0;
    $totalPoints = 0;
    $count = count($allResults);

    foreach ($allResults as $r) {
        $totalObtained += $r['marks_obtained'];
        $totalPossible += $r['total_marks'];
        
        // GPA Scale (4.0)
        switch ($r['grade']) {
            case 'A': $totalPoints += 4.0; break;
            case 'B': $totalPoints += 3.0; break;
            case 'C': $totalPoints += 2.0; break;
            case 'D': $totalPoints += 1.0; break;
            default: $totalPoints += 0.0;
        }
    }

    $overallPercentage = $totalPossible > 0 ? round(($totalObtained / $totalPossible) * 100, 2) : 0;
    $overallGPA = $count > 0 ? round($totalPoints / $count, 2) : 0.00;

} catch (PDOException $e) {
    die("Data error: " . $e->getMessage());
}
?>

<style>
@media print {
    .dashboard-header, .header-icons, .print-btn, footer { display: none !important; }
    .main-content { padding: 0 !important; background: #fff !important; }
    .marksheet-card { box-shadow: none !important; border: 2px solid #000 !important; }
    .dashboard-container { display: block !important; }
}
</style>

<div class="dashboard-container" style="flex-direction: column;">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p><?php echo $role; ?> Portal > Academic Performance Profile</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <button onclick="window.print()" class="print-btn" style="background: #10b981; color: #fff; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-file-pdf"></i> Export Marksheet
            </button>
            <a href="<?php 
                if($role == 'Admin') echo 'admin_dashboard.php';
                elseif($role == 'Teacher') echo 'teacher_dashboard.php';
                else echo 'student_dashboard.php';
            ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div style="max-width: 1000px; margin: 0 auto;">
            
            <!-- Summary Header -->
            <section style="display: grid; grid-template-columns: 1fr 400px; gap: 30px; margin-bottom: 50px;">
                <div style="background: #fff; padding: 40px; border-radius: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
                    <span style="font-size: 0.8rem; color: #8b5cf6; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Student Profile</span>
                    <h2 style="font-size: 2.4rem; color: #1e293b; font-weight: 900; margin: 10px 0;"><?php echo htmlspecialchars($studentInfo['first_name'] . ' ' . $studentInfo['last_name']); ?></h2>
                    <p style="color: #64748b; font-weight: 600; font-size: 1.1rem;"><?php echo htmlspecialchars($studentInfo['email']); ?> | Student ID: #<?php echo str_pad($studentInfo['sid'], 5, '0', STR_PAD_LEFT); ?></p>
                </div>

                <div style="background: var(--brand-gradient); padding: 40px; border-radius: 30px; color: #fff; display: flex; justify-content: space-around; align-items: center; box-shadow: 0 15px 35px rgba(139, 92, 246, 0.25);">
                    <div style="text-align: center;">
                        <span style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; opacity: 0.9;">Aggregate GPA</span>
                        <span style="font-size: 2.8rem; font-weight: 900;"><?php echo number_format($overallGPA, 2); ?></span>
                    </div>
                    <div style="width: 2px; height: 60px; background: rgba(255,255,255,0.2);"></div>
                    <div style="text-align: center;">
                        <span style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; opacity: 0.9;">Total Percent</span>
                        <span style="font-size: 2.8rem; font-weight: 900;"><?php echo $overallPercentage; ?>%</span>
                    </div>
                </div>
            </section>

            <!-- Results Tables by Exam -->
            <?php
            $currentExam = null;
            if (empty($allResults)): ?>
                <div style="background: #fff; padding: 80px; border-radius: 35px; text-align: center; border: 2px dashed #e2e8f0;">
                    <i class="fas fa-search" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 20px;"></i>
                    <p style="color: #64748b; font-weight: 700;">No academic records found for this profile.</p>
                </div>
            <?php else: 
                foreach ($allResults as $r): 
                    if ($currentExam !== $r['exam_id']):
                        if ($currentExam !== null) echo '</tbody></table></div>';
                        $currentExam = $r['exam_id'];
            ?>
                <div class="marksheet-card" style="background: #fff; border-radius: 30px; border: 1px solid #f1f5f9; box-shadow: 0 10px 40px rgba(0,0,0,0.02); margin-bottom: 40px; overflow: hidden;">
                    <div style="background: #f8fafc; padding: 25px 35px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; color: #1e293b; font-weight: 800; font-size: 1.3rem;">
                            <i class="fas fa-file-invoice" style="margin-right: 12px; color: #8b5cf6;"></i>
                            <?php echo htmlspecialchars($r['exam_name']); ?>
                        </h3>
                        <span style="color: #64748b; font-weight: 700; font-size: 0.9rem;"><?php echo date('M Y', strtotime($r['exam_date'])); ?></span>
                    </div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #fcfdfe;">
                                <th style="padding: 18px 35px; text-align: left; color: #94a3b8; font-weight: 700; text-transform: uppercase; font-size: 0.7rem; border-bottom: 1px solid #f1f5f9;">Module Code</th>
                                <th style="padding: 18px 35px; text-align: left; color: #94a3b8; font-weight: 700; text-transform: uppercase; font-size: 0.7rem; border-bottom: 1px solid #f1f5f9;">Course Name</th>
                                <th style="padding: 18px 35px; text-align: center; color: #94a3b8; font-weight: 700; text-transform: uppercase; font-size: 0.7rem; border-bottom: 1px solid #f1f5f9;">Score</th>
                                <th style="padding: 18px 35px; text-align: center; color: #94a3b8; font-weight: 700; text-transform: uppercase; font-size: 0.7rem; border-bottom: 1px solid #f1f5f9;">Percentage</th>
                                <th style="padding: 18px 35px; text-align: center; color: #94a3b8; font-weight: 700; text-transform: uppercase; font-size: 0.7rem; border-bottom: 1px solid #f1f5f9;">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php endif; ?>
                            <tr style="border-bottom: 1px solid #f8fafc;">
                                <td style="padding: 18px 35px; font-weight: 700; color: #8b5cf6;"><?php echo htmlspecialchars($r['course_code']); ?></td>
                                <td style="padding: 18px 35px; font-weight: 800; color: #1e293b;"><?php echo htmlspecialchars($r['course_name']); ?></td>
                                <td style="padding: 18px 35px; text-align: center; font-weight: 700; color: #1e293b;"><?php echo $r['marks_obtained']; ?> / <?php echo $r['total_marks']; ?></td>
                                <td style="padding: 18px 35px; text-align: center; font-weight: 800; color: #475569;"><?php echo round(($r['marks_obtained'] / $r['total_marks']) * 100, 1); ?>%</td>
                                <td style="padding: 18px 35px; text-align: center;">
                                    <span style="background: <?php echo $r['grade'] == 'F' ? '#fff1f2' : '#f0fdf4'; ?>; color: <?php echo $r['grade'] == 'F' ? '#f43f5e' : '#10b981'; ?>; padding: 6px 15px; border-radius: 10px; font-weight: 900; font-size: 0.9rem;"><?php echo $r['grade']; ?></span>
                                </td>
                            </tr>
                <?php endforeach; 
                echo '</tbody></table></div>';
            endif; ?>

            <div style="text-align: center; margin-top: 60px; padding-top: 40px; border-top: 1px solid #e2e8f0;">
                <p style="color: #94a3b8; font-weight: 600; font-style: italic;">This is a computer-generated marksheet. Official verification may be required.</p>
                <p style="color: #cbd5e1; font-size: 0.8rem; margin-top: 10px;">Academic Management System | Generated on <?php echo date('M d, Y'); ?></p>
            </div>
        </div>
    </main>
</div>

<?php include_once '../includes/footer.php'; ?>
