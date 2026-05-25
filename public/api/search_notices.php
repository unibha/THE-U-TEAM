<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

// Allow all roles to search notices
checkAuth(['Admin', 'Teacher', 'Student']);

$role = $_SESSION['role'];
$search = $_GET['q'] ?? '';
$date = $_GET['date'] ?? '';

try {
    // Match the logic in view_notice.php
    $sql = "SELECT * FROM notice WHERE publish_date <= CURRENT_DATE";
    $params = [];
    
    // Filter by role
    if ($role === 'Student') {
        $sql .= " AND (target_audience = 'All' OR target_audience = 'Student')";
    } elseif ($role === 'Teacher') {
        $sql .= " AND (target_audience = 'All' OR target_audience = 'Teacher')";
    }
    
    if ($search) {
        $sql .= " AND (title LIKE :q1 OR content LIKE :q2)";
        $params['q1'] = "%$search%";
        $params['q2'] = "%$search%";
    }

    if ($date) {
        $sql .= " AND publish_date = :d1";
        $params['d1'] = $date;
    }
    
    $sql .= " ORDER BY CASE WHEN priority = 'Urgent' THEN 1 ELSE 2 END, publish_date DESC, created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($notices);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
