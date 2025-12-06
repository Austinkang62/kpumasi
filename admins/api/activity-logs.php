<?php
/**
 * Super Admin - Activity Logs API
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// Super Admin 권한 확인
if (!isset($_SESSION['super_admin_logged_in']) || !$_SESSION['super_admin_logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

try {
    $db = Database::getInstance();

    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);

    // 최근 활동 로그 조회
    $logs = $db->select(
        "SELECT al.*, a.username
         FROM admin_logs al
         LEFT JOIN admins a ON al.admin_id = a.admin_id
         ORDER BY al.created_at DESC
         LIMIT ? OFFSET ?",
        [$limit, $offset]
    );

    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => $logs
    ]);
    ob_end_flush();

} catch (Exception $e) {
    error_log('Activity Logs Error: ' . $e->getMessage());

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
    ob_end_flush();
}
?>
