<?php
/**
 * 관리자용 아바타 족보 트리 조회 API
 * 전체 아바타의 계층 구조와 수익 분배 정보를 반환
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

    // 1세대 아바타 조회 (parent_account_id가 실제 사용자인 경우)
    $firstGenAvatars = $db->select(
        "SELECT DISTINCT u.parent_account_id
        FROM users u
        WHERE u.is_avatar = 1
          AND (u.deleted_at IS NULL OR u.deleted_at = '')
        ORDER BY u.created_at ASC
        LIMIT 1"
    );

    $tree = [];

    if (!empty($firstGenAvatars)) {
        // 각 1세대 소유자의 아바타 트리 구축
        $owners = $db->select(
            "SELECT DISTINCT u.parent_account_id
            FROM users u
            WHERE u.is_avatar = 1
              AND (u.deleted_at IS NULL OR u.deleted_at = '')
            ORDER BY u.parent_account_id ASC"
        );

        foreach ($owners as $owner) {
            $ownerTree = buildAvatarTree($db, $owner['parent_account_id']);
            $tree = array_merge($tree, $ownerTree);
        }
    }

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
