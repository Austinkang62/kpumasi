<?php
/**
 * 대기중인 아바타 수 조회 API
 * 아바타포인트 >= $100인 회원 수 반환
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'count' => 0, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance();

    // 대기중인 아바타 수 및 총 포인트 조회
    $result = $db->selectOne("
        SELECT
            COUNT(*) as count,
            SUM(FLOOR(avatar_points / 100)) as total_avatars,
            SUM(avatar_points) as total_points
        FROM users
        WHERE avatar_points >= 100.00
          AND deleted_at IS NULL
    ");

    $count = intval($result['count'] ?? 0);
    $totalAvatars = intval($result['total_avatars'] ?? 0);
    $totalPoints = floatval($result['total_points'] ?? 0);

    echo json_encode([
        'success' => true,
        'count' => $count,                    // 대기중인 회원 수
        'total_avatars' => $totalAvatars,     // 생성 가능한 총 아바타 수
        'total_points' => $totalPoints,       // 총 아바타포인트
        'total_value' => $totalAvatars * 100  // 총 매출 가치
    ]);

} catch (Exception $e) {
    error_log('Avatar pending count error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Server error'
    ]);
}
?>
