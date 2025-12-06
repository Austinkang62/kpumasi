<?php
/**
 * 아바타 족보 통계 API
 * 총 아바타 수, 수익, APT 적립 등 통계 정보 반환
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';

try {
    $db = Database::getInstance();
    $userObj = new User();

    // 세션 토큰 확인
    $sessionToken = $_GET['session_token'] ?? '';

    if (empty($sessionToken)) {
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
        ]);
        exit;
    }

    // 세션 검증
    $currentUser = $userObj->validateSession($sessionToken);

    if (!$currentUser) {
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 세션입니다.'
        ]);
        exit;
    }

    // 사용자 ID 가져오기
    $userId = $currentUser['id'];

    // 재귀적으로 모든 아바타 ID 수집
    function getAllAvatarIds($db, $parentAccountId, &$avatarIds = []) {
        $avatars = $db->select(
            "SELECT id FROM users
             WHERE parent_account_id = ?
               AND is_avatar = 1
               AND (deleted_at IS NULL OR deleted_at = '')",
            [$parentAccountId]
        );

        foreach ($avatars as $avatar) {
            $avatarIds[] = $avatar['id'];
            // 재귀적으로 하위 아바타 조회
            getAllAvatarIds($db, $avatar['id'], $avatarIds);
        }

        return $avatarIds;
    }

    $avatarIds = getAllAvatarIds($db, $userId);

    // 통계 계산
    $stats = [
        'total_avatars' => count($avatarIds),
        'active_avatars' => count($avatarIds), // 현재는 모두 활성
        'total_revenue' => 0,
        'owner_revenue' => 0,
        'apt_accumulated' => 0
    ];

    if (!empty($avatarIds)) {
        $placeholders = implode(',', array_fill(0, count($avatarIds), '?'));

        // 총 발생 수익 계산
        $revenueQuery = "SELECT COALESCE(SUM(total_bonus), 0) as total_revenue
                         FROM users
                         WHERE id IN ($placeholders)";
        $revenueResult = $db->selectOne($revenueQuery, $avatarIds);

        $totalRevenue = floatval($revenueResult['total_revenue'] ?? 0);

        $stats['total_revenue'] = $totalRevenue;
        $stats['owner_revenue'] = $totalRevenue * 0.65; // 소유자 65%
        $stats['apt_accumulated'] = $totalRevenue * 0.35; // APT 35%
    }

    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);

} catch (Exception $e) {
    error_log('아바타 족보 통계 조회 오류: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '통계 데이터를 불러오는 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
