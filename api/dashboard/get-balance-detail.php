<?php
/**
 * 잔액 상세 정보 API
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

    // 사용자 정보 조회
    $user = $db->selectOne(
        "SELECT
            total_bonus,
            available_bonus,
            avatar_points
        FROM users
        WHERE id = ?",
        [$userId]
    );

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => '사용자 정보를 찾을 수 없습니다.'
        ]);
        exit;
    }

    // 총 출금액 조회
    $withdrawal = $db->selectOne(
        "SELECT COALESCE(SUM(amount), 0) as total_withdrawn
        FROM withdrawals
        WHERE user_id = ? AND status IN ('approved', 'processing', 'completed')",
        [$userId]
    );

    if (!$withdrawal) {
        $withdrawal = ['total_withdrawn' => 0];
    }

    // 보너스 타입별 통계
    $bonusSummary = $db->select(
        "SELECT
            bonus_type,
            COUNT(*) as count,
            COALESCE(SUM(amount), 0) as total
        FROM bonuses
        WHERE user_id = ?
        GROUP BY bonus_type",
        [$userId]
    );

    $bonusStats = [
        'referral_count' => 0,
        'referral_total' => 0,
        'edge_count' => 0,
        'edge_total' => 0,
        'matching_count' => 0,
        'matching_total' => 0,
        'rollup_count' => 0,
        'rollup_total' => 0
    ];

    foreach ($bonusSummary as $bonus) {
        $bonusStats[$bonus['bonus_type'] . '_count'] = $bonus['count'];
        $bonusStats[$bonus['bonus_type'] . '_total'] = $bonus['total'];
    }

    $data = array_merge([
        'total_bonus' => $user['total_bonus'],
        'available_bonus' => $user['available_bonus'],
        'avatar_points' => $user['avatar_points'],
        'total_withdrawn' => $withdrawal['total_withdrawn']
    ], $bonusStats);

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log('잔액 상세 조회 오류: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '잔액 정보를 불러오는 중 오류가 발생했습니다.',
        'error_detail' => $e->getMessage()
    ]);
}
