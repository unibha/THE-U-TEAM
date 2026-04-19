<?php
require_once 'includes/db.php';

$date = date('Y-m-d');
$search = '';
$course_id = '';
$role_filter = 'All';

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

    echo json_encode($results, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
