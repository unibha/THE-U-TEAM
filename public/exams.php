<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../includes/db.php';
require_once '../includes/auth_middleware.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
            break;
        case 'PUT':
            handlePutRequest();
            break;
        case 'DELETE':
            handleDeleteRequest();
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest() {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    $course_id = $_GET['course_id'] ?? null;
    $student_id = $_GET['student_id'] ?? null;
    $check_conflicts = $_GET['check_conflicts'] ?? null;
    
    if ($id) {
        // Get specific exam
        $stmt = $pdo->prepare("
            SELECT e.*, c.course_name, c.course_code, u.first_name, u.last_name 
            FROM exams e 
            JOIN courses c ON e.course_id = c.id 
            JOIN users u ON e.created_by = u.id 
            WHERE e.id = ? AND e.is_active = 1
        ");
        $stmt->execute([$id]);
        $exam = $stmt->fetch();
        
        if (!$exam) {
            http_response_code(404);
            echo json_encode(['error' => 'Exam not found']);
            return;
        }
        
        echo json_encode($exam);
    } elseif ($student_id) {
        // Get exams for a specific student
        $stmt = $pdo->prepare("
            SELECT e.*, c.course_name, c.course_code, 
                   m.marks_obtained, m.grade, m.remarks
            FROM exams e 
            JOIN courses c ON e.course_id = c.id 
            JOIN enrollments en ON c.id = en.course_id 
            LEFT JOIN marks m ON (e.id = m.exam_id AND m.student_id = ?)
            WHERE en.student_id = ? AND e.is_active = 1
            ORDER BY e.exam_date ASC
        ");
        $stmt->execute([$student_id, $student_id]);
        $exams = $stmt->fetchAll();
        
        echo json_encode($exams);
    } else {
        // Get all exams with filters
        $query = "
            SELECT e.*, c.course_name, c.course_code, u.first_name, u.last_name 
            FROM exams e 
            JOIN courses c ON e.course_id = c.id 
            JOIN users u ON e.created_by = u.id 
            WHERE e.is_active = 1
        ";
        $params = [];
        
        if ($course_id) {
            $query .= " AND e.course_id = ?";
            $params[] = $course_id;
        }
        
        $query .= " ORDER BY e.exam_date ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $exams = $stmt->fetchAll();
        
        // Check for conflicts if requested
        if ($check_conflicts) {
            $conflicts = detectExamConflicts($exams);
            echo json_encode(['exams' => $exams, 'conflicts' => $conflicts]);
        } else {
            echo json_encode($exams);
        }
    }
}

function handlePostRequest() {
    global $pdo;
    
    // Validate JWT token
    $user = validateJWT();
    if (!$user || ($user['role'] !== 'Admin' && $user['role'] !== 'Teacher')) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['course_id', 'title', 'exam_date'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "$field is required"]);
            return;
        }
    }
    
    // Check if user has permission for this course (if teacher)
    if ($user['role'] === 'Teacher') {
        $stmt = $pdo->prepare("
            SELECT t.id FROM teachers t 
            JOIN courses c ON t.id = c.teacher_id 
            WHERE t.user_id = ? AND c.id = ?
        ");
        $stmt->execute([$user['user_id'], $data['course_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'You can only create exams for your courses']);
            return;
        }
    }
    
    // Check for scheduling conflicts
    $conflictCheck = checkSchedulingConflict($data['course_id'], $data['exam_date'], $data['venue'] ?? null);
    if ($conflictCheck) {
        http_response_code(409);
        echo json_encode(['error' => 'Scheduling conflict detected', 'conflict' => $conflictCheck]);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO exams (course_id, title, description, exam_type, max_marks, exam_date, duration, venue, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['course_id'],
        $data['title'],
        $data['description'] ?? null,
        $data['exam_type'] ?? 'Midterm',
        $data['max_marks'] ?? 100.00,
        $data['exam_date'],
        $data['duration'] ?? 60,
        $data['venue'] ?? null,
        $user['user_id']
    ]);
    
    $examId = $pdo->lastInsertId();
    
    http_response_code(201);
    echo json_encode([
        'message' => 'Exam created successfully',
        'exam_id' => $examId
    ]);
}

function handlePutRequest() {
    global $pdo;
    
    // Validate JWT token
    $user = validateJWT();
    if (!$user || ($user['role'] !== 'Admin' && $user['role'] !== 'Teacher')) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Exam ID is required']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check if exam exists and user has permission
    $stmt = $pdo->prepare("
        SELECT e.*, c.teacher_id 
        FROM exams e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $exam = $stmt->fetch();
    
    if (!$exam) {
        http_response_code(404);
        echo json_encode(['error' => 'Exam not found']);
        return;
    }
    
    // Check permissions
    if ($user['role'] === 'Teacher') {
        $teacherStmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $teacherStmt->execute([$user['user_id']]);
        $teacher = $teacherStmt->fetch();
        
        if (!$teacher || $exam['teacher_id'] != $teacher['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'You can only update your own exams']);
            return;
        }
    }
    
    $updateFields = [];
    $params = [];
    
    $updatableFields = ['title', 'description', 'exam_type', 'max_marks', 'exam_date', 'duration', 'venue'];
    foreach ($updatableFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    // Check for scheduling conflicts if date/venue is being updated
    if (isset($data['exam_date']) || isset($data['venue'])) {
        $newDate = $data['exam_date'] ?? $exam['exam_date'];
        $newVenue = $data['venue'] ?? $exam['venue'];
        
        $conflictCheck = checkSchedulingConflict($exam['course_id'], $newDate, $newVenue, $id);
        if ($conflictCheck) {
            http_response_code(409);
            echo json_encode(['error' => 'Scheduling conflict detected', 'conflict' => $conflictCheck]);
            return;
        }
    }
    
    $params[] = $id;
    
    $stmt = $pdo->prepare("
        UPDATE exams 
        SET " . implode(', ', $updateFields) . " 
        WHERE id = ?
    ");
    
    $stmt->execute($params);
    
    echo json_encode(['message' => 'Exam updated successfully']);
}

function handleDeleteRequest() {
    global $pdo;
    
    // Validate JWT token
    $user = validateJWT();
    if (!$user || ($user['role'] !== 'Admin' && $user['role'] !== 'Teacher')) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Exam ID is required']);
        return;
    }
    
    // Check if exam exists and user has permission
    $stmt = $pdo->prepare("
        SELECT e.*, c.teacher_id 
        FROM exams e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $exam = $stmt->fetch();
    
    if (!$exam) {
        http_response_code(404);
        echo json_encode(['error' => 'Exam not found']);
        return;
    }
    
    // Check permissions
    if ($user['role'] === 'Teacher') {
        $teacherStmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $teacherStmt->execute([$user['user_id']]);
        $teacher = $teacherStmt->fetch();
        
        if (!$teacher || $exam['teacher_id'] != $teacher['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'You can only delete your own exams']);
            return;
        }
    }
    
    // Soft delete (set is_active = 0)
    $stmt = $pdo->prepare("UPDATE exams SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['message' => 'Exam deleted successfully']);
}

function checkSchedulingConflict($course_id, $exam_date, $venue, $exclude_exam_id = null) {
    global $pdo;
    
    $query = "
        SELECT e.*, c.course_name 
        FROM exams e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.is_active = 1 
        AND e.exam_date = ? 
        AND e.id != ?
    ";
    $params = [$exam_date, $exclude_exam_id ?? 0];
    
    if ($venue) {
        $query .= " AND e.venue = ?";
        $params[] = $venue;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $conflicts = $stmt->fetchAll();
    
    if (!empty($conflicts)) {
        return $conflicts[0];
    }
    
    return null;
}

function detectExamConflicts($exams) {
    $conflicts = [];
    
    for ($i = 0; $i < count($exams); $i++) {
        for ($j = $i + 1; $j < count($exams); $j++) {
            $exam1 = $exams[$i];
            $exam2 = $exams[$j];
            
            // Check if exams are at the same time
            if ($exam1['exam_date'] === $exam2['exam_date']) {
                $conflictType = 'Same Time';
                
                // Check if same venue
                if ($exam1['venue'] && $exam2['venue'] && $exam1['venue'] === $exam2['venue']) {
                    $conflictType = 'Same Venue';
                }
                
                $conflicts[] = [
                    'exam1' => ['id' => $exam1['id'], 'title' => $exam1['title']],
                    'exam2' => ['id' => $exam2['id'], 'title' => $exam2['title']],
                    'conflict_type' => $conflictType,
                    'exam_date' => $exam1['exam_date']
                ];
            }
        }
    }
    
    return $conflicts;
}
?>
