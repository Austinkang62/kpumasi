<?php
/**
 * 보너스 타입별 상세 API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

try {
    $db = Database::getInstance();

    // 세션 토큰 확인
    $sessionToken = $_GET['session_token'] ?? '';
    $bonusType = $_GET['bonus_type'] ?? '';

    if (empty($sessionToken)) {
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
        ]);
        exit;
    }

    if (empty($bonusType)) {
        echo json_encode([
            'success' => false,
            'message' => '보너스 타입이 필요합니다.'
        ]);
        exit;
    }

    // 유효한 보너스 타입 확인
    $validTypes = ['referral', 'edge', 'matching', 'rollup'];
    if (!in_array($bonusType, $validTypes)) {
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 보너스 타입입니다.'
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

    // 보너스 타입별 통계
    $stats = $db->selectOne(
        "SELECT
            COUNT(*) as total_count,
            COALESCE(SUM(amount), 0) as total_amount
        FROM bonuses
        WHERE user_id = ? AND bonus_type = ?",
        [$userId, $bonusType]
    );

    if (!$stats) {
        $stats = [
            'total_count' => 0,
            'total_amount' => 0
        ];
    }

    // 최근 발생 내역 (최근 50건)
    $bonuses = $db->select(
        "SELECT
            b.amount,
            b.level,
            b.payment_type,
            fu.user_id as from_user,
            DATE_FORMAT(b.created_at, '%Y-%m-%d %H:%i') as created_at
        FROM bonuses b
        LEFT JOIN users fu ON b.from_user_id = fu.id
        WHERE b.user_id = ? AND b.bonus_type = ?
        ORDER BY b.created_at DESC
        LIMIT 50",
        [$userId, $bonusType]
    );

    echo json_encode([
        'success' => true,
        'data' => [
            'total_count' => $stats['total_count'],
            'total_amount' => $stats['total_amount'],
            'bonuses' => $bonuses
        ]
    ]);

} catch (Exception $e) {
    error_log('보너스 타입 상세 조회 오류: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '보너스 상세 정보를 불러오는 중 오류가 발생했습니다.',
        'error_detail' => $e->getMessage()
    ]);
}
