<?php
/**
 * 관리자용 아바타 족보 통계 조회 API
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => '관리자 권한이 필요합니다.'
    ]);
    exit;
}

try {
    $db = Database::getInstance();

    // 전체 아바타 통계
    $stats = $db->selectOne(
        "SELECT
            COUNT(*) as total_avatars,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_avatars,
            SUM(total_bonus) as total_revenue
        FROM users
        WHERE is_avatar = 1
          AND (deleted_at IS NULL OR deleted_at = '')"
    );

    // 수익 분배 계산
    $totalRevenue = floatval($stats['total_revenue'] ?? 0);
    $ownerRevenue = $totalRevenue * 0.65;
    $aptAccumulated = $totalRevenue * 0.35;

    // 1세대 아바타 통계
    $gen1Stats = $db->selectOne(
        "SELECT
            COUNT(*) as gen1_avatars,
            SUM(total_bonus) as gen1_revenue
        FROM users
        WHERE is_avatar = 1
          AND parent_account_id IS NOT NULL
          AND (deleted_at IS NULL OR deleted_at = '')"
    );

    echo json_encode([
        'success' => true,
        'data' => [
            'total_avatars' => intval($stats['total_avatars'] ?? 0),
            'active_avatars' => intval($stats['active_avatars'] ?? 0),
            'total_revenue' => $totalRevenue,
            'owner_revenue' => $ownerRevenue,
            'apt_accumulated' => $aptAccumulated,
            'gen1_avatars' => intval($gen1Stats['gen1_avatars'] ?? 0),
            'gen1_revenue' => floatval($gen1Stats['gen1_revenue'] ?? 0)
        ]
    ]);

} catch (Exception $e) {
    error_log('아바타 족보 통계 조회 오류: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '통계 데이터를 불러오는 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
