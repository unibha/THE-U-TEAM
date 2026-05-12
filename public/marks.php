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
    
    $exam_id = $_GET['exam_id'] ?? null;
    $student_id = $_GET['student_id'] ?? null;
    $course_id = $_GET['course_id'] ?? null;
    $calculate_gpa = $_GET['calculate_gpa'] ?? null;
    
    if ($calculate_gpa && $student_id) {
        // Calculate GPA for a student
        $gpaData = calculateStudentGPA($student_id);
        echo json_encode($gpaData);
        return;
    }
    
    if ($exam_id && $student_id) {
        // Get specific mark
        $stmt = $pdo->prepare("
            SELECT m.*, e.title as exam_title, e.max_marks, e.exam_type,
                   c.course_name, c.course_code,
                   u.first_name, u.last_name
            FROM marks m
            JOIN exams e ON m.exam_id = e.id
            JOIN courses c ON e.course_id = c.id
            JOIN students s ON m.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE m.exam_id = ? AND m.student_id = ?
        ");
        $stmt->execute([$exam_id, $student_id]);
        $mark = $stmt->fetch();
        
        if (!$mark) {
            http_response_code(404);
            echo json_encode(['error' => 'Mark not found']);
            return;
        }
        
        echo json_encode($mark);
    } elseif ($exam_id) {
        // Get all marks for an exam
        $stmt = $pdo->prepare("
            SELECT m.*, e.title as exam_title, e.max_marks,
                   c.course_name, c.course_code,
                   u.first_name, u.last_name
            FROM marks m
            JOIN exams e ON m.exam_id = e.id
            JOIN courses c ON e.course_id = c.id
            JOIN students s ON m.student_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE m.exam_id = ?
            ORDER BY u.first_name, u.last_name
        ");
        $stmt->execute([$exam_id]);
        $marks = $stmt->fetchAll();
        
        echo json_encode($marks);
    } elseif ($student_id) {
        // Get all marks for a student
        $query = "
            SELECT m.*, e.title as exam_title, e.max_marks, e.exam_type, e.exam_date,
                   c.course_name, c.course_code,
                   cc.credit_hours
            FROM marks m
            JOIN exams e ON m.exam_id = e.id
            JOIN courses c ON e.course_id = c.id
            JOIN course_credits cc ON (c.id = cc.course_id AND cc.academic_year = YEAR(e.exam_date) AND cc.semester = 
                CASE 
                    WHEN MONTH(e.exam_date) BETWEEN 1 AND 4 THEN 'Spring'
                    WHEN MONTH(e.exam_date) BETWEEN 5 AND 8 THEN 'Summer'
                    ELSE 'Fall'
                END)
            WHERE m.student_id = ?
        ";
        $params = [$student_id];
        
        if ($course_id) {
            $query .= " AND c.id = ?";
            $params[] = $course_id;
        }
        
        $query .= " ORDER BY e.exam_date DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $marks = $stmt->fetchAll();
        
        // Calculate grades if not already set
        foreach ($marks as &$mark) {
            if (!$mark['grade'] && $mark['marks_obtained'] !== null) {
                $mark['grade'] = calculateGrade($mark['marks_obtained'], $mark['max_marks']);
            }
        }
        
        echo json_encode($marks);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Either exam_id or student_id is required']);
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
    $required = ['exam_id', 'student_id', 'marks_obtained'];
    foreach ($required as $field) {
        if (!isset($data[$field]) && $data[$field] !== '0') {
            http_response_code(400);
            echo json_encode(['error' => "$field is required"]);
            return;
        }
    }
    
    // Check if exam exists and user has permission
    $stmt = $pdo->prepare("
        SELECT e.*, c.teacher_id 
        FROM exams e 
        JOIN courses c ON e.course_id = c.id 
        WHERE e.id = ? AND e.is_active = 1
    ");
    $stmt->execute([$data['exam_id']]);
    $exam = $stmt->fetch();
    
    if (!$exam) {
        http_response_code(404);
        echo json_encode(['error' => 'Exam not found']);
        return;
    }
    
    // Check permissions (teacher can only mark their own course exams)
    if ($user['role'] === 'Teacher') {
        $teacherStmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $teacherStmt->execute([$user['user_id']]);
        $teacher = $teacherStmt->fetch();
        
        if (!$teacher || $exam['teacher_id'] != $teacher['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'You can only mark exams for your courses']);
            return;
        }
    }
    
    // Validate marks
    if ($data['marks_obtained'] < 0 || $data['marks_obtained'] > $exam['max_marks']) {
        http_response_code(400);
        echo json_encode(['error' => 'Marks must be between 0 and ' . $exam['max_marks']]);
        return;
    }
    
    // Calculate grade
    $grade = calculateGrade($data['marks_obtained'], $exam['max_marks']);
    
    // Check if mark already exists
    $stmt = $pdo->prepare("SELECT id FROM marks WHERE exam_id = ? AND student_id = ?");
    $stmt->execute([$data['exam_id'], $data['student_id']]);
    $existingMark = $stmt->fetch();
    
    if ($existingMark) {
        http_response_code(409);
        echo json_encode(['error' => 'Mark already exists for this exam and student']);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO marks (exam_id, student_id, marks_obtained, grade, remarks, graded_by, graded_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $data['exam_id'],
        $data['student_id'],
        $data['marks_obtained'],
        $grade,
        $data['remarks'] ?? null,
        $user['user_id']
    ]);
    
    $markId = $pdo->lastInsertId();
    
    // Update GPA for the student
    updateStudentGPA($data['student_id']);
    
    http_response_code(201);
    echo json_encode([
        'message' => 'Mark recorded successfully',
        'mark_id' => $markId,
        'grade' => $grade
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
    
    $exam_id = $_GET['exam_id'] ?? null;
    $student_id = $_GET['student_id'] ?? null;
    
    if (!$exam_id || !$student_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Both exam_id and student_id are required']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check if mark exists
    $stmt = $pdo->prepare("
        SELECT m.*, e.max_marks, c.teacher_id 
        FROM marks m
        JOIN exams e ON m.exam_id = e.id
        JOIN courses c ON e.course_id = c.id
        WHERE m.exam_id = ? AND m.student_id = ?
    ");
    $stmt->execute([$exam_id, $student_id]);
    $mark = $stmt->fetch();
    
    if (!$mark) {
        http_response_code(404);
        echo json_encode(['error' => 'Mark not found']);
        return;
    }
    
    // Check permissions
    if ($user['role'] === 'Teacher') {
        $teacherStmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $teacherStmt->execute([$user['user_id']]);
        $teacher = $teacherStmt->fetch();
        
        if (!$teacher || $mark['teacher_id'] != $teacher['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'You can only update marks for your courses']);
            return;
        }
    }
    
    $updateFields = [];
    $params = [];
    
    if (isset($data['marks_obtained'])) {
        if ($data['marks_obtained'] < 0 || $data['marks_obtained'] > $mark['max_marks']) {
            http_response_code(400);
            echo json_encode(['error' => 'Marks must be between 0 and ' . $mark['max_marks']]);
            return;
        }
        
        $updateFields[] = "marks_obtained = ?";
        $params[] = $data['marks_obtained'];
        
        // Recalculate grade
        $grade = calculateGrade($data['marks_obtained'], $mark['max_marks']);
        $updateFields[] = "grade = ?";
        $params[] = $grade;
    }
    
    if (isset($data['remarks'])) {
        $updateFields[] = "remarks = ?";
        $params[] = $data['remarks'];
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    $updateFields[] = "graded_by = ?";
    $updateFields[] = "graded_at = NOW()";
    $params[] = $user['user_id'];
    $params[] = $exam_id;
    $params[] = $student_id;
    
    $stmt = $pdo->prepare("
        UPDATE marks 
        SET " . implode(', ', $updateFields) . " 
        WHERE exam_id = ? AND student_id = ?
    ");
    
    $stmt->execute($params);
    
    // Update GPA for the student
    updateStudentGPA($student_id);
    
    echo json_encode(['message' => 'Mark updated successfully']);
}

function handleDeleteRequest() {
    global $pdo;
    
    // Validate JWT token
    $user = validateJWT();
    if (!$user || $user['role'] !== 'Admin') {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    $exam_id = $_GET['exam_id'] ?? null;
    $student_id = $_GET['student_id'] ?? null;
    
    if (!$exam_id || !$student_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Both exam_id and student_id are required']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM marks WHERE exam_id = ? AND student_id = ?");
    $stmt->execute([$exam_id, $student_id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Mark not found']);
        return;
    }
    
    // Update GPA for the student
    updateStudentGPA($student_id);
    
    echo json_encode(['message' => 'Mark deleted successfully']);
}

function calculateGrade($marks_obtained, $max_marks) {
    $percentage = ($marks_obtained / $max_marks) * 100;
    
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 85) return 'A';
    if ($percentage >= 80) return 'A-';
    if ($percentage >= 75) return 'B+';
    if ($percentage >= 70) return 'B';
    if ($percentage >= 65) return 'B-';
    if ($percentage >= 60) return 'C+';
    if ($percentage >= 55) return 'C';
    if ($percentage >= 50) return 'C-';
    if ($percentage >= 45) return 'D';
    return 'F';
}

function calculateStudentGPA($student_id) {
    global $pdo;
    
    // Get current academic year and semester
    $currentYear = date('Y');
    $currentMonth = date('n');
    $semester = $currentMonth >= 1 && $currentMonth <= 4 ? 'Spring' : 
                ($currentMonth >= 5 && $currentMonth <= 8 ? 'Summer' : 'Fall');
    
    // Get all marks for current semester with course credits
    $stmt = $pdo->prepare("
        SELECT m.*, e.exam_date, e.max_marks,
               c.course_name, cc.credit_hours,
               CASE 
                   WHEN MONTH(e.exam_date) BETWEEN 1 AND 4 THEN 'Spring'
                   WHEN MONTH(e.exam_date) BETWEEN 5 AND 8 THEN 'Summer'
                   ELSE 'Fall'
               END as semester
        FROM marks m
        JOIN exams e ON m.exam_id = e.id
        JOIN courses c ON e.course_id = c.id
        JOIN course_credits cc ON (c.id = cc.course_id AND cc.academic_year = YEAR(e.exam_date))
        WHERE m.student_id = ? AND m.marks_obtained IS NOT NULL
        ORDER BY YEAR(e.exam_date) DESC, 
                 CASE 
                     WHEN MONTH(e.exam_date) BETWEEN 1 AND 4 THEN 1
                     WHEN MONTH(e.exam_date) BETWEEN 5 AND 8 THEN 2
                     ELSE 3
                 END DESC
    ");
    $stmt->execute([$student_id]);
    $marks = $stmt->fetchAll();
    
    // Group by semester
    $semesters = [];
    foreach ($marks as $mark) {
        $year = date('Y', strtotime($mark['exam_date']));
        $semesterKey = $year . '_' . $mark['semester'];
        
        if (!isset($semesters[$semesterKey])) {
            $semesters[$semesterKey] = [
                'year' => $year,
                'semester' => $mark['semester'],
                'total_credit_hours' => 0,
                'total_grade_points' => 0,
                'courses' => []
            ];
        }
        
        $gradePoints = getGradePoints($mark['grade']);
        $semesters[$semesterKey]['total_credit_hours'] += $mark['credit_hours'];
        $semesters[$semesterKey]['total_grade_points'] += $gradePoints * $mark['credit_hours'];
        $semesters[$semesterKey]['courses'][] = [
            'course_name' => $mark['course_name'],
            'credit_hours' => $mark['credit_hours'],
            'marks_obtained' => $mark['marks_obtained'],
            'max_marks' => $mark['max_marks'],
            'grade' => $mark['grade'],
            'grade_points' => $gradePoints
        ];
    }
    
    // Calculate GPA for each semester
    $gpaData = [];
    $cumulativeGradePoints = 0;
    $cumulativeCredits = 0;
    
    foreach ($semesters as $semesterKey => $semester) {
        $gpa = $semester['total_credit_hours'] > 0 ? 
                round($semester['total_grade_points'] / $semester['total_credit_hours'], 2) : 0;
        
        $semesters[$semesterKey]['gpa'] = $gpa;
        
        $cumulativeGradePoints += $semester['total_grade_points'];
        $cumulativeCredits += $semester['total_credit_hours'];
        
        // Store in database
        storeGPA($student_id, $semester['year'], $semester['semester'], 
                $semester['total_credit_hours'], $semester['total_grade_points'], $gpa);
    }
    
    // Calculate CGPA
    $cgpa = $cumulativeCredits > 0 ? round($cumulativeGradePoints / $cumulativeCredits, 2) : 0;
    
    return [
        'semesters' => array_values($semesters),
        'cgpa' => $cgpa,
        'total_credit_hours' => $cumulativeCredits
    ];
}

function getGradePoints($grade) {
    $gradePoints = [
        'A+' => 4.0,
        'A' => 4.0,
        'A-' => 3.7,
        'B+' => 3.3,
        'B' => 3.0,
        'B-' => 2.7,
        'C+' => 2.3,
        'C' => 2.0,
        'C-' => 1.7,
        'D' => 1.0,
        'F' => 0.0
    ];
    
    return $gradePoints[$grade] ?? 0.0;
}

function storeGPA($student_id, $academic_year, $semester, $total_credit_hours, $total_grade_points, $gpa) {
    global $pdo;
    
    // Calculate CGPA
    $stmt = $pdo->prepare("
        SELECT SUM(total_grade_points) as total_points, SUM(total_credit_hours) as total_credits
        FROM gpa 
        WHERE student_id = ? AND (academic_year < ? OR (academic_year = ? AND semester < ?))
    ");
    $stmt->execute([$student_id, $academic_year, $academic_year, $semester]);
    $previousData = $stmt->fetch();
    
    $cumulativePoints = ($previousData['total_points'] ?? 0) + $total_grade_points;
    $cumulativeCredits = ($previousData['total_credits'] ?? 0) + $total_credit_hours;
    $cgpa = $cumulativeCredits > 0 ? round($cumulativePoints / $cumulativeCredits, 2) : 0;
    
    // Insert or update GPA record
    $stmt = $pdo->prepare("
        INSERT INTO gpa (student_id, academic_year, semester, total_credit_hours, total_grade_points, gpa, cgpa)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        total_credit_hours = VALUES(total_credit_hours),
        total_grade_points = VALUES(total_grade_points),
        gpa = VALUES(gpa),
        cgpa = VALUES(cgpa),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$student_id, $academic_year, $semester, $total_credit_hours, $total_grade_points, $gpa, $cgpa]);
}

function updateStudentGPA($student_id) {
    calculateStudentGPA($student_id);
}
?>
