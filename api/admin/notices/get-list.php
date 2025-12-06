<?php
/**
 * 관리자용 공지사항 목록 조회 API
 */

session_start();

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../classes/Database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
    exit;
}

try {
    $db = Database::getInstance();

    $query = "
        SELECT id, title, content, is_important, status, created_at, updated_at
        FROM notices
        ORDER BY is_important DESC, created_at DESC
    ";

    $notices = $db->select($query);

    echo json_encode([
        'success' => true,
        'data' => $notices
    ]);

} catch (Exception $e) {
    error_log('Admin notices list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '공지사항을 불러오는 중 오류가 발생했습니다.'
    ]);
}
