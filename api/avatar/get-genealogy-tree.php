<?php
/**
 * 아바타 족보 트리 조회 API
 * 아바타의 계층 구조와 수익 분배 정보를 반환
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

    // 아바타 족보 트리 구축 (재귀 함수)
    function buildAvatarTree($db, $parentAccountId, $level = 0) {
        // 최대 깊이 제한 (무한 루프 방지)
        if ($level > 10) {
            return [];
        }

        // 직속 아바타 조회
        $avatars = $db->select(
            "SELECT
                u.id,
                u.user_id,
                u.name,
                u.created_at,
                u.total_bonus,
                u.package_id,
                p.name as package_name,
                (SELECT COUNT(*) FROM users WHERE referral_id = u.id) as direct_referrals
            FROM users u
            LEFT JOIN packages p ON u.package_id = p.id
            WHERE u.parent_account_id = ?
              AND u.is_avatar = 1
              AND (u.deleted_at IS NULL OR u.deleted_at = '')
            ORDER BY u.created_at ASC",
            [$parentAccountId]
        );

        $result = [];
        foreach ($avatars as $avatar) {
            $totalBonus = floatval($avatar['total_bonus']);

            // 수익 분배 계산 (65% 소유자, 35% APT)
            $ownerCash = $totalBonus * 0.65;
            $aptPoints = $totalBonus * 0.35;

            $avatarData = [
                'id' => $avatar['id'],
                'user_id' => $avatar['user_id'],
                'name' => $avatar['name'],
                'created_date' => date('Y-m-d', strtotime($avatar['created_at'])),
                'total_bonus' => $totalBonus,
                'owner_cash' => $ownerCash,
                'apt_points' => $aptPoints,
                'package_name' => $avatar['package_name'],
                'direct_referrals' => $avatar['direct_referrals'],
                'level' => $level
            ];

            // 하위 아바타 재귀 조회
            $children = buildAvatarTree($db, $avatar['id'], $level + 1);
            if (!empty($children)) {
                $avatarData['children'] = $children;
            }

            $result[] = $avatarData;
        }

        return $result;
    }

    // 사용자의 최상위 아바타부터 트리 구축
    $tree = buildAvatarTree($db, $currentUser['id']);

    echo json_encode([
        'success' => true,
        'data' => $tree
    ]);

} catch (Exception $e) {
    error_log('아바타 족보 트리 조회 오류: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '아바타 족보 데이터를 불러오는 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
