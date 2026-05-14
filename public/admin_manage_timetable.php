<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$pageTitle = "Manage Timetable - Admin Portal";
include_once '../includes/header.php';

$message = '';
$error = '';

// Institutional Periods (3 Fixed Slots)
$periods = [
    1 => ['start' => '07:00:00', 'end' => '09:00:00'],
    2 => ['start' => '10:00:00', 'end' => '12:00:00'],
    3 => ['start' => '13:00:00', 'end' => '15:00:00'],
];

// Handle Delete
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM timetable WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        $message = "Schedule entry removed.";
    } catch (PDOException $e) {
        $error = "Deletion error: " . $e->getMessage();
    }
}

// Handle Add Entry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_schedule'])) {
    $course_id = $_POST['course_id'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? '';
    $classroom = $_POST['classroom'] ?? '';
    $day = $_POST['day_of_week'] ?? '';
    $period_num = $_POST['period_number'] ?? '';
    $admin_id = $_SESSION['user_id'];

    if (empty($course_id) || empty($teacher_id) || empty($classroom) || empty($day) || empty($period_num)) {
        $error = "All fields are required.";
    } else {
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

            // 1. Clash Detection: Teacher Conflict
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM timetable WHERE teacher_id = ? AND day_of_week = ? AND period_number = ?");
            $stmt->execute([$teacher_id, $day, $period_num]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("CLASH DETECTED: This teacher is already assigned to another class during Period $period_num.");
            }

            // 2. Clash Detection: Classroom Conflict
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM timetable WHERE classroom = ? AND day_of_week = ? AND period_number = ?");
            $stmt->execute([$classroom, $day, $period_num]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("CLASH DETECTED: Classroom '$classroom' is already booked for Period $period_num.");
            }

            // 3. Insert
            $stmt = $pdo->prepare("INSERT INTO timetable (course_id, teacher_id, classroom, day_of_week, period_number, start_time, end_time, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$course_id, $teacher_id, $classroom, $day, $period_num, $start, $end, $admin_id]);
            
            // Trigger Notifications
            require_once '../includes/notification_helper.php';
            $courseData = $pdo->prepare("SELECT course_name FROM courses WHERE id = ?");
            $courseData->execute([$course_id]);
            $cName = $courseData->fetchColumn();

            $msg = "A new session has been scheduled for $cName on $day (Period $period_num) in $classroom.";
            notifyTeacher($course_id, "Timetable Update: $cName", $msg, 'Academic');
            notifyEnrolledStudents($course_id, "Timetable Update: $cName", $msg, 'Academic');

            $message = "Timetable entry for Period $period_num established successfully!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Fetch Data for Lists
try {
    $courses = $pdo->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC")->fetchAll();
    $teachers = $pdo->query("SELECT t.id, u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY u.first_name ASC")->fetchAll();
    
    $timetable = $pdo->query("
        SELECT tt.*, c.course_name, c.course_code, u.first_name, u.last_name 
        FROM timetable tt
        JOIN courses c ON tt.course_id = c.id
        JOIN teachers t ON tt.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        ORDER BY FIELD(tt.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), tt.period_number ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $courses = $teachers = $timetable = [];
    $error = "Data fetch error: " . $e->getMessage();
}
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-brand">
            <h1>Academic Management System</h1>
            <p>Admin Portal > Timetable Master (Fixed Periods)</p>
        </div>
        <div class="header-tools">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="timetableSearch" placeholder="Search sessions, days or halls...">
            </div>
            <div class="header-icons" style="margin-left: 20px; display: flex; gap: 15px; align-items: center;">
                <a href="admin_dashboard.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;"><i class="fas fa-th-large" style="margin-right: 8px;"></i>Dashboard</a>
                <a href="logout.php" style="color: #fff; text-decoration: none; font-weight: 700; font-size: 0.9rem; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 12px;">Logout</a>
            </div>
        </div>
    </header>

    <main class="main-content" style="padding: 40px 60px; background: #f8fafc;">
        <section style="display: grid; grid-template-columns: 1fr 380px; gap: 40px;">
            <!-- Timetable List -->
            <div>
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 800; margin-bottom: 25px;">Institutional Schedule</h2>
                
                <?php if ($message): ?> <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #bbf7d0;"><i class="fas fa-check-circle" style="margin-right: 10px;"></i> <?php echo $message; ?></div> <?php endif; ?>
                <?php if ($error): ?> <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; border: 1px solid #fecaca;"><i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i> <?php echo $error; ?></div> <?php endif; ?>

                <div class="table-container" style="background: #fff; border-radius: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Day & Period</th>
                                <th style="padding: 20px; text-align: left; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Module & Teacher</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Classroom</th>
                                <th style="padding: 20px; text-align: center; color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($timetable)): ?>
                                <tr><td colspan="4" style="padding: 50px; text-align: center; color: #94a3b8; font-weight: 600;">No schedule entries established yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($timetable as $tt): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: 0.2s ease;">
                                    <td style="padding: 20px;">
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <span style="font-weight: 800; color: #1e293b;"><?php echo $tt['day_of_week']; ?></span>
                                            <span style="font-size: 0.8rem; color: #8b5cf6; font-weight: 700;">Period <?php echo $tt['period_number']; ?> (<?php echo date('h:i A', strtotime($tt['start_time'])); ?> - <?php echo date('h:i A', strtotime($tt['end_time'])); ?>)</span>
                                        </div>
                                    </td>
                                    <td style="padding: 20px;">
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <span style="font-weight: 700; color: #475569;"><?php echo htmlspecialchars($tt['course_name']); ?></span>
                                            <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;"><i class="fas fa-user-tie" style="margin-right: 8px;"></i><?php echo htmlspecialchars($tt['first_name'] . ' ' . $tt['last_name']); ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 20px; text-align: center;">
                                        <span style="background: #f1f5f9; color: #475569; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.9rem;"><?php echo htmlspecialchars($tt['classroom']); ?></span>
                                    </td>
                                    <td style="padding: 20px; text-align: center;">
                                        <div style="display: flex; justify-content: center; gap: 15px;">
                                            <a href="admin_edit_timetable.php?id=<?php echo $tt['id']; ?>" style="color: #3b82f6;"><i class="fas fa-edit"></i></a>
                                            <a href="?delete_id=<?php echo $tt['id']; ?>" style="color: #f43f5e;" onclick="return confirm('Remove this schedule entry?')"><i class="fas fa-trash-alt"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Entry Sidebar -->
            <aside style="background: #fff; padding: 30px; border-radius: 30px; border: 1px solid #f1f5f9; box-shadow: 0 10px 40px rgba(0,0,0,0.03); height: fit-content; position: sticky; top: 40px;">
                <h3 style="font-size: 1.3rem; color: #1e293b; font-weight: 800; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-calendar-plus" style="color: #8b5cf6;"></i> Add Session
                </h3>
                <form action="" method="POST">
                    <div style="margin-bottom: 15px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Academic Module</label>
                        <select name="course_id" id="course_select" required style="width: 100%; padding: 12px; border: 2px solid #f8fafc; border-radius: 12px; background: #f8fafc; font-weight: 600; outline: none;" onchange="syncTeacher()">
                            <option value="">-- Choose Module --</option>
                            <?php foreach ($courses as $c): 
                                // Fetch the official teacher ID for this course to use in JS
                                $stmt = $pdo->prepare("SELECT teacher_id FROM courses WHERE id = ?");
                                $stmt->execute([$c['id']]);
                                $officialTeacher = $stmt->fetchColumn();
                            ?>
                                <option value="<?php echo $c['id']; ?>" data-teacher="<?php echo $officialTeacher; ?>">
                                    <?php echo htmlspecialchars($c['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Assigned Faculty</label>
                        <select name="teacher_id" id="teacher_select" required style="width: 100%; padding: 12px; border: 2px solid #f8fafc; border-radius: 12px; background: #f8fafc; font-weight: 600; outline: none;">
                            <option value="">-- Choose Teacher --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Day of Week</label>
                        <select name="day_of_week" required style="width: 100%; padding: 12px; border: 2px solid #f8fafc; border-radius: 12px; background: #f8fafc; font-weight: 600; outline: none;">
                            <option value="Sunday">Sunday</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Institutional Period</label>
                        <select name="period_number" required style="width: 100%; padding: 12px; border: 2px solid #f8fafc; border-radius: 12px; background: #f8fafc; font-weight: 600; outline: none;">
                            <option value="1">Period 1 (07:00 AM - 09:00 AM)</option>
                            <option value="2">Period 2 (10:00 AM - 12:00 PM)</option>
                            <option value="3">Period 3 (01:00 PM - 03:00 PM)</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: block;">Classroom / Hall</label>
                        <input type="text" name="classroom" required placeholder="e.g. Hall A-202" style="width: 100%; padding: 12px; border: 2px solid #f8fafc; border-radius: 12px; background: #f8fafc; font-weight: 600; outline: none;">
                    </div>

                    <button type="submit" name="add_schedule" style="width: 100%; background: var(--brand-gradient); color: #fff; padding: 16px; border: none; border-radius: 14px; font-weight: 800; cursor: pointer; transition: 0.3s ease; box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);">
                        Establish Session
                    </button>
                </form>
            </aside>
        </section>
    </main>
</div>

<script>
function syncTeacher() {
    const select = document.getElementById('course_select');
    const teacherId = select.options[select.selectedIndex].getAttribute('data-teacher');
    const teacherSelect = document.getElementById('teacher_select');
    
    if (teacherId) {
        teacherSelect.value = teacherId;
    }
}

document.getElementById('timetableSearch').addEventListener('input', function(e) {
    const query = e.target.value;
    
    fetch(`api_search_timetable.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.querySelector('table tbody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="padding: 50px; text-align: center; color: #94a3b8; font-weight: 600;">No schedule entries found matching your search.</td></tr>';
                return;
            }
            
            data.forEach(tt => {
                const row = document.createElement('tr');
                row.style.borderBottom = '1px solid #f1f5f9';
                
                // Format time helper (simple version for JS)
                const formatTime = (timeStr) => {
                    const [h, m] = timeStr.split(':');
                    const hh = parseInt(h);
                    const suffix = hh >= 12 ? 'PM' : 'AM';
                    const h12 = hh % 12 || 12;
                    return `${h12}:${m} ${suffix}`;
                };

                row.innerHTML = `
                    <td style="padding: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <span style="font-weight: 800; color: #1e293b;">${tt.day_of_week}</span>
                            <span style="font-size: 0.8rem; color: #8b5cf6; font-weight: 700;">Period ${tt.period_number} (${formatTime(tt.start_time)} - ${formatTime(tt.end_time)})</span>
                        </div>
                    </td>
                    <td style="padding: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <span style="font-weight: 700; color: #475569;">${tt.course_name}</span>
                            <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;"><i class="fas fa-user-tie" style="margin-right: 8px;"></i>${tt.first_name} ${tt.last_name}</span>
                        </div>
                    </td>
                    <td style="padding: 20px; text-align: center;">
                        <span style="background: #f1f5f9; color: #475569; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 0.9rem;">${tt.classroom}</span>
                    </td>
                    <td style="padding: 20px; text-align: center;">
                        <div style="display: flex; justify-content: center; gap: 15px;">
                            <a href="admin_edit_timetable.php?id=${tt.id}" style="color: #3b82f6;"><i class="fas fa-edit"></i></a>
                            <a href="?delete_id=${tt.id}" style="color: #f43f5e;" onclick="return confirm('Remove this schedule entry?')"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => console.error('Error fetching timetable:', error));
});
</script>

<?php include_once '../includes/footer.php'; ?>

