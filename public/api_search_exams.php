<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Allow all logged-in users
checkAuth(['Admin', 'Teacher', 'Student']);

$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$search = $_GET['q'] ?? '';

try {
    $sql = "
        SELECT e.*, c.course_name, c.course_code 
        FROM exam e 
        JOIN courses c ON e.course_id = c.id 
    ";
    
    $params = [];
    $where = [];

    // Role-based filtering
    if ($role === 'Student') {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt->execute([$userId]);
        $studentId = $stmt->fetchColumn();
        
        $sql .= " JOIN enrollments en ON c.id = en.course_id ";
        $where[] = "en.student_id = :student_id";
        $params['student_id'] = $studentId;
    } elseif ($role === 'Teacher') {
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $teacherId = $stmt->fetchColumn();
        
        $where[] = "c.teacher_id = :teacher_id";
        $params['teacher_id'] = $teacherId;
    }

    // Search query
    if ($search) {
        $where[] = "(e.exam_name LIKE :q1 OR c.course_name LIKE :q2 OR c.course_code LIKE :q3)";
        $params['q1'] = "%$search%";
        $params['q2'] = "%$search%";
        $params['q3'] = "%$search%";
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " ORDER BY e.exam_date ASC, e.start_time ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($exams);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
