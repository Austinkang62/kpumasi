<?php
/**
 * 공지사항 상세 조회 API
 * GET /api/notices/get-detail.php?id=1
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $noticeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($noticeId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '공지사항 ID가 필요합니다.']);
        exit;
    }

    $db = Database::getInstance();

    $query = "
        SELECT id, title, content, is_important, created_at, updated_at
        FROM notices
        WHERE id = ? AND status = 'active'
    ";

    $notice = $db->selectOne($query, [$noticeId]);

    if (!$notice) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => '공지사항을 찾을 수 없습니다.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $notice
    ]);

} catch (Exception $e) {
    error_log('Notice detail error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '공지사항을 불러오는 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
