<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// All logged in users can view exam schedules
checkAuth(['Admin', 'Teacher', 'Student']);

$pageTitle = "Examination Schedule - Academic Management System";
include_once '../includes/header.php';

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// Fetch exams
try {
    if ($role === 'Student') {
        // Only show exams for courses student is enrolled in
        $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt->execute([$userId]);
        $studentId = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT e.*, c.course_name, c.course_code 
            FROM exam e 
            JOIN courses c ON e.course_id = c.id 
            JOIN enrollments en ON c.id = en.course_id
            WHERE en.student_id = ?
            ORDER BY e.exam_date ASC, e.start_time ASC
        ");
        $stmt->execute([$studentId]);
        $exams = $stmt->fetchAll();
    } elseif ($role === 'Teacher') {
        // Only show exams for courses teacher leads
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $teacherId = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT e.*, c.course_name, c.course_code 
            FROM exam e 
            JOIN courses c ON e.course_id = c.id 
            WHERE c.teacher_id = ?
            ORDER BY e.exam_date ASC, e.start_time ASC
        ");
        $stmt->execute([$teacherId]);
        $exams = $stmt->fetchAll();
    } else {
        // Admin sees everything
        $exams = $pdo->query("
            SELECT e.*, c.course_name, c.course_code 
            FROM exam e 
            JOIN courses c ON e.course_id = c.id 
            ORDER BY e.exam_date ASC, e.start_time ASC
        ")->fetchAll();
    }
} catch (PDOException $e) {
    $exams = [];
}
?>

<div class="dashboard-container" style="flex-direction: column;">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>User Portal > Examination Schedule</p>
        </div>
        <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
            <a href="<?php 
                if($role == 'Admin') echo 'admin_dashboard.php';
                elseif($role == 'Teacher') echo 'teacher_dashboard.php';
                else echo 'student_dashboard.php';
            ?>" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <div style="max-width: 1000px; margin: 0 auto;">
            <section class="welcome-header" style="margin-bottom: 50px; text-align: center;">
                <h2 style="font-size: 2.2rem; color: #1e293b; font-weight: 800;">Academic Examinations</h2>
                <p style="color: #64748b; font-weight: 600;">Official schedule for upcoming assessments and finals.</p>
                
                <div class="search-wrapper" style="margin: 30px auto 0; width: 400px; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                    <input type="text" id="examSearch" placeholder="Search exams or courses..." style="width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; font-weight: 600; outline: none; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                </div>
            </section>

            <div id="examGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px;">
                <?php if (empty($exams)): ?>
                    <div style="grid-column: 1/-1; background: #fff; padding: 80px; border-radius: 30px; text-align: center; border: 2px dashed #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                        <i class="fas fa-file-invoice" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 25px;"></i>
                        <p style="color: #64748b; font-weight: 700; font-size: 1.2rem;">No examinations currently scheduled for your modules.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($exams as $e): 
                        $examDate = strtotime($e['exam_date']);
                        $isUpcoming = $examDate >= strtotime('today');
                    ?>
                        <div class="exam-card" style="background: #fff; padding: 30px; border-radius: 28px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; position: relative; overflow: hidden; transition: transform 0.2s ease;">
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: <?php echo $isUpcoming ? '#8b5cf6' : '#cbd5e1'; ?>;"></div>
                            
                            <div style="margin-bottom: 20px;">
                                <span style="font-size: 0.75rem; color: #8b5cf6; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;"><?php echo htmlspecialchars($e['course_code']); ?></span>
                                <h3 style="font-size: 1.4rem; color: #1e293b; font-weight: 800; margin: 0;"><?php echo htmlspecialchars($e['exam_name']); ?></h3>
                                <p style="color: #64748b; font-weight: 600; font-size: 0.9rem; margin-top: 5px;"><?php echo htmlspecialchars($e['course_name']); ?></p>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 36px; height: 36px; background: #f5f3ff; color: #8b5cf6; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                        <i class="far fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Date</span>
                                        <span style="font-size: 0.95rem; color: #1e293b; font-weight: 800;"><?php echo date('l, M d, Y', $examDate); ?></span>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 36px; height: 36px; background: #f0fdf4; color: #10b981; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                        <i class="far fa-clock"></i>
                                    </div>
                                    <div>
                                        <span style="display: block; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Timing</span>
                                        <span style="font-size: 0.95rem; color: #1e293b; font-weight: 800;"><?php echo date('h:i A', strtotime($e['start_time'])) . ' - ' . date('h:i A', strtotime($e['end_time'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="padding-top: 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-award" style="color: #fbbf24;"></i>
                                    <span style="font-size: 0.85rem; color: #475569; font-weight: 800;"><?php echo $e['total_marks']; ?> Marks</span>
                                </div>
                                <span style="font-size: 0.75rem; font-weight: 800; color: <?php echo $isUpcoming ? '#10b981' : '#94a3b8'; ?>;">
                                    <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 5px;"></i> <?php echo $isUpcoming ? 'UPCOMING' : 'CONCLUDED'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
document.getElementById('examSearch').addEventListener('input', function(e) {
    const query = e.target.value;
    
    fetch(`api_search_exams.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const grid = document.getElementById('examGrid');
            grid.innerHTML = '';
            
            if (data.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; background: #fff; padding: 80px; border-radius: 30px; text-align: center; border: 2px dashed #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                        <i class="fas fa-file-invoice" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 25px;"></i>
                        <p style="color: #64748b; font-weight: 700; font-size: 1.2rem;">No exams matching your search.</p>
                    </div>
                `;
                return;
            }
            
            data.forEach(e => {
                const card = document.createElement('div');
                card.className = 'exam-card';
                
                const examDate = new Date(e.exam_date);
                const isUpcoming = examDate >= new Date().setHours(0,0,0,0);
                const dateStr = examDate.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: '2-digit', year: 'numeric' });
                
                const formatTime = (timeStr) => {
                    const [h, m] = timeStr.split(':');
                    const hh = parseInt(h);
                    const suffix = hh >= 12 ? 'PM' : 'AM';
                    const h12 = hh % 12 || 12;
                    return `${h12}:${m} ${suffix}`;
                };

                card.style.cssText = `background: #fff; padding: 30px; border-radius: 28px; box-shadow: 0 10px 40px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; position: relative; overflow: hidden; transition: transform 0.2s ease;`;
                card.innerHTML = `
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: ${isUpcoming ? '#8b5cf6' : '#cbd5e1'};"></div>
                    
                    <div style="margin-bottom: 20px;">
                        <span style="font-size: 0.75rem; color: #8b5cf6; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">${e.course_code}</span>
                        <h3 style="font-size: 1.4rem; color: #1e293b; font-weight: 800; margin: 0;">${e.exam_name}</h3>
                        <p style="color: #64748b; font-weight: 600; font-size: 0.9rem; margin-top: 5px;">${e.course_name}</p>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 36px; height: 36px; background: #f5f3ff; color: #8b5cf6; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                <i class="far fa-calendar-alt"></i>
                            </div>
                            <div>
                                <span style="display: block; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Date</span>
                                <span style="font-size: 0.95rem; color: #1e293b; font-weight: 800;">${dateStr}</span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 36px; height: 36px; background: #f0fdf4; color: #10b981; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                <i class="far fa-clock"></i>
                            </div>
                            <div>
                                <span style="display: block; font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Timing</span>
                                <span style="font-size: 0.95rem; color: #1e293b; font-weight: 800;">${formatTime(e.start_time)} - ${formatTime(e.end_time)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding-top: 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-award" style="color: #fbbf24;"></i>
                            <span style="font-size: 0.85rem; color: #475569; font-weight: 800;">${e.total_marks} Marks</span>
                        </div>
                        <span style="font-size: 0.75rem; font-weight: 800; color: ${isUpcoming ? '#10b981' : '#94a3b8'};">
                            <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 5px;"></i> ${isUpcoming ? 'UPCOMING' : 'CONCLUDED'}
                        </span>
                    </div>
                `;
                grid.appendChild(card);
            });
        })
        .catch(error => console.error('Error:', error));
});
</script>

<?php include_once '../includes/footer.php'; ?>

