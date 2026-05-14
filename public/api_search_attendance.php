<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

checkAuth(['Admin']);

$search = $_GET['q'] ?? '';
$roleFilter = $_GET['role'] ?? 'All';
$courseId = $_GET['course'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

$results = [];

try {
    // 1. Students
    if ($roleFilter == 'All' || $roleFilter == 'Student') {
        $sql = "
            SELECT 
                a.id as record_id, 
                s.id as target_id, 
                u.first_name, 
                u.last_name, 
                'Student' as role, 
                COALESCE(a.status, 'Not Marked') as status, 
                COALESCE(a.attendance_date, :d1) as attendance_date, 
                c.course_name, 
                c.course_code 
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN enrollments e ON s.id = e.student_id
            JOIN courses c ON e.course_id = c.id
            LEFT JOIN attendance a ON (s.id = a.student_id AND c.id = a.course_id AND a.attendance_date = :d2)
            WHERE 1=1
        ";
        $params = ['d1' => $date, 'd2' => $date];
        if ($search) {
            $sql .= " AND (u.first_name LIKE :s1 OR u.last_name LIKE :s2)";
            $params['s1'] = "%$search%";
            $params['s2'] = "%$search%";
        }
        if ($courseId) {
            $sql .= " AND c.id = :c1";
            $params['c1'] = $courseId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = array_merge($results, $stmt->fetchAll());
    }

    // 2. Teachers
    if ($roleFilter == 'All' || $roleFilter == 'Teacher') {
        $sql = "
            SELECT 
                ta.id as record_id, 
                t.id as target_id,
                u.first_name, 
                u.last_name, 
                'Teacher' as role, 
                COALESCE(ta.status, 'Not Marked') as status, 
                COALESCE(ta.attendance_date, :d1) as attendance_date,
                COALESCE(c.course_name, 'Staff Presence') as course_name, 
                COALESCE(c.course_code, 'N/A') as course_code 
            FROM teachers t
            JOIN users u ON t.user_id = u.id 
            LEFT JOIN courses c ON t.id = c.teacher_id
            LEFT JOIN teacher_attendance ta ON (t.id = ta.teacher_id AND ta.attendance_date = :d2)
            WHERE 1=1
        ";
        $params = ['d1' => $date, 'd2' => $date];
        if ($search) {
            $sql .= " AND (u.first_name LIKE :s1 OR u.last_name LIKE :s2)";
            $params['s1'] = "%$search%";
            $params['s2'] = "%$search%";
        }
        if ($courseId) {
            $sql .= " AND c.id = :c1";
            $params['c1'] = $courseId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = array_merge($results, $stmt->fetchAll());
    }

    usort($results, function($a, $b) { return strcmp($a['first_name'], $b['first_name']); });

    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
