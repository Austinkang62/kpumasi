<?php
/**
 * 패키지 누락 건 수동 처리 API
 * POST /admin/api/fix-missing-packages.php
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? null;

    if (!$userId) {
        throw new Exception('user_id is required');
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // 사용자 확인
    $user = $pdo->query("SELECT id, user_id, package_id FROM users WHERE id = " . intval($userId))->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // 이미 패키지가 있는지 확인
    if ($user['package_id'] !== null) {
        throw new Exception('User already has a package (package_id: ' . $user['package_id'] . ')');
    }

    // package_id를 2로 업데이트
    $stmt = $pdo->prepare("UPDATE users SET package_id = 2, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    echo json_encode([
        'success' => true,
        'message' => '패키지 ID 업데이트 완료',
        'data' => [
            'user_id' => $user['user_id'],
            'old_package_id' => null,
            'new_package_id' => 2
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
