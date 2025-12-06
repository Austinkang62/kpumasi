<?php
/**
 * 보너스 타입별 요약 API
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
        "SELECT user_id FROM sessions WHERE session_token = ? AND expires_at > NOW()",
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

    $data = [
        'total_referral_bonus' => 0,
        'referral_count' => 0,
        'total_edge_bonus' => 0,
        'edge_count' => 0,
        'total_matching_bonus' => 0,
        'matching_count' => 0,
        'total_rollup_bonus' => 0,
        'rollup_count' => 0
    ];

    foreach ($bonusSummary as $bonus) {
        $data['total_' . $bonus['bonus_type'] . '_bonus'] = $bonus['total'];
        $data[$bonus['bonus_type'] . '_count'] = $bonus['count'];
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log('보너스 요약 조회 오류: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '보너스 요약을 불러오는 중 오류가 발생했습니다.',
        'error_detail' => $e->getMessage()
    ]);
}
