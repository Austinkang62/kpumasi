<?php
/**
 * 공지사항 목록 조회 API
 * GET /api/notices/get-list.php
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
    $db = Database::getInstance();

    // 공지사항 테이블이 없으면 생성
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            is_important TINYINT(1) DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $db->execute($createTableQuery);

    // 활성 공지사항 조회 (최신순)
    $query = "
        SELECT id, title, content, is_important, created_at, updated_at
        FROM notices
        WHERE status = 'active'
        ORDER BY is_important DESC, created_at DESC
        LIMIT 50
    ";

    $notices = $db->select($query);

    echo json_encode([
        'success' => true,
        'data' => $notices,
        'count' => count($notices)
    ]);

} catch (Exception $e) {
    error_log('Notices list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '공지사항을 불러오는 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
