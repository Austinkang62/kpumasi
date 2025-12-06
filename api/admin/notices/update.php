<?php
/**
 * 공지사항 수정 API
 */

session_start();

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../classes/Database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
    exit;
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (empty($data['id']) || empty($data['title']) || empty($data['content'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID, 제목, 내용을 입력해주세요.']);
        exit;
    }

    $db = Database::getInstance();

    $query = "UPDATE notices SET title = ?, content = ?, is_important = ? WHERE id = ?";
    $isImportant = isset($data['is_important']) ? intval($data['is_important']) : 0;

    $db->execute($query, [
        $data['title'],
        $data['content'],
        $isImportant,
        $data['id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => '공지사항이 수정되었습니다.'
    ]);

} catch (Exception $e) {
    error_log('Notice update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '공지사항 수정 중 오류가 발생했습니다.'
    ]);
}
