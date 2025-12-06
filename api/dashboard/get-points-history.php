<?php
/**
 * 아바타 포인트 내역 API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

try {
    $db = Database::getInstance();

    // 세션 토큰 확인
    $sessionToken = $_GET['session_token'] ?? '';

    if (empty($sessionToken)) {
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
        ]);
        exit;
    }

    // 세션에서 사용자 조회
    $session = $db->selectOne(
        "SELECT user_id FROM sessions WHERE token = ? AND expires_at > NOW()",
        [$sessionToken]
    );

    if (!$session) {
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 세션입니다.'
        ]);
        exit;
    }

    $userId = $session['user_id'];

    // 현재 포인트
    $user = $db->selectOne(
        "SELECT avatar_points FROM users WHERE id = ?",
        [$userId]
    );

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => '사용자 정보를 찾을 수 없습니다.'
        ]);
        exit;
    }

    // 누적 포인트 획득 (보너스의 35%)
    $totalPointsEarned = $db->selectOne(
        "SELECT COALESCE(SUM(amount), 0) * 0.35 as total_points FROM bonuses WHERE user_id = ?",
        [$userId]
    );

    if (!$totalPointsEarned) {
        $totalPointsEarned = ['total_points' => 0];
    }

    // 아바타 생성에 사용된 포인트 (생성된 아바타 수 * 100)
    $avatarCount = $db->selectOne(
        "SELECT COUNT(*) as count FROM avatars WHERE parent_user_id = ?",
        [$userId]
    );

    if (!$avatarCount) {
        $avatarCount = ['count' => 0];
    }

    $usedForAvatars = $avatarCount['count'] * 100;

    // 최근 생성된 아바타 (최근 10개)
    $recentAvatars = $db->select(
        "SELECT
            au.user_id as avatar_code,
            DATE_FORMAT(a.created_at, '%Y-%m-%d') as created_at,
            COALESCE(au.total_bonus, 0) as total_bonus
        FROM avatars a
        JOIN users au ON a.avatar_user_id = au.id
        WHERE a.parent_user_id = ?
        ORDER BY a.created_at DESC
        LIMIT 10",
        [$userId]
    );

    echo json_encode([
        'success' => true,
        'data' => [
            'current_points' => $user['avatar_points'],
            'total_points_earned' => $totalPointsEarned['total_points'],
            'used_for_avatars' => $usedForAvatars,
            'total_avatars' => $avatarCount['count'],
            'recent_avatars' => $recentAvatars
        ]
    ]);

} catch (Exception $e) {
    error_log('포인트 내역 조회 오류: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '포인트 내역을 불러오는 중 오류가 발생했습니다.',
        'error_detail' => $e->getMessage()
    ]);
}
