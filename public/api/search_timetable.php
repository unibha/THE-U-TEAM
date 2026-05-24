<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

checkAuth(['Admin']);

$search = $_GET['q'] ?? '';

try {
    $sql = "
        SELECT tt.*, c.course_name, c.course_code, u.first_name, u.last_name 
        FROM timetable tt
        JOIN courses c ON tt.course_id = c.id
        JOIN teachers t ON tt.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    if ($search) {
        $sql .= " AND (c.course_name LIKE :s1 OR c.course_code LIKE :s2 OR u.first_name LIKE :s3 OR u.last_name LIKE :s4 OR tt.classroom LIKE :s5 OR tt.day_of_week LIKE :s6)";
        $params['s1'] = "%$search%";
        $params['s2'] = "%$search%";
        $params['s3'] = "%$search%";
        $params['s4'] = "%$search%";
        $params['s5'] = "%$search%";
        $params['s6'] = "%$search%";
    }
    
    $sql .= " ORDER BY FIELD(tt.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), tt.period_number ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
