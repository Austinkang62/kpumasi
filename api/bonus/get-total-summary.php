<?php
/**
 * 전체 보너스 통계 조회 API (관리자용)
 * GET /api/bonus/get-total-summary.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GET 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    $db = Database::getInstance();

    // bonuses 테이블에서 보너스 집계 (일반 회원 매출로 인한 보너스만)
    // member-financial.html과 동일한 기준: from_user_id가 일반 회원(is_avatar=0)인 것만
    $bonusesQuery = "
        SELECT
            SUM(CASE WHEN b.bonus_type = 'referral' THEN b.amount ELSE 0 END) as total_referral,
            SUM(CASE WHEN b.bonus_type = 'edge' THEN b.amount ELSE 0 END) as total_edge,
            SUM(CASE WHEN b.bonus_type = 'matching' THEN b.amount ELSE 0 END) as total_matching,
            SUM(CASE WHEN b.bonus_type = 'rollup' THEN b.amount ELSE 0 END) as total_rollup,
            COUNT(CASE WHEN b.bonus_type = 'referral' THEN 1 END) as referral_count,
            COUNT(CASE WHEN b.bonus_type = 'edge' THEN 1 END) as edge_count,
            COUNT(CASE WHEN b.bonus_type = 'matching' THEN 1 END) as matching_count,
            COUNT(CASE WHEN b.bonus_type = 'rollup' THEN 1 END) as rollup_count
        FROM bonuses b
        JOIN users u ON b.from_user_id = u.id
        WHERE b.status = 'paid' AND u.is_avatar = 0
    ";

    $bonusesData = $db->selectOne($bonusesQuery);

    // 결과 통합
    $summary = [
        'total_referral' => (float)($bonusesData['total_referral'] ?? 0),
        'total_edge' => (float)($bonusesData['total_edge'] ?? 0),
        'total_matching' => (float)($bonusesData['total_matching'] ?? 0),
        'total_rollup' => (float)($bonusesData['total_rollup'] ?? 0),
        'referral_count' => (int)($bonusesData['referral_count'] ?? 0),
        'edge_count' => (int)($bonusesData['edge_count'] ?? 0),
        'matching_count' => (int)($bonusesData['matching_count'] ?? 0),
        'rollup_count' => (int)($bonusesData['rollup_count'] ?? 0),
        'total_bonus' =>
            (float)($bonusesData['total_referral'] ?? 0) +
            (float)($bonusesData['total_edge'] ?? 0) +
            (float)($bonusesData['total_matching'] ?? 0) +
            (float)($bonusesData['total_rollup'] ?? 0),
        'total_count' =>
            (int)($bonusesData['referral_count'] ?? 0) +
            (int)($bonusesData['edge_count'] ?? 0) +
            (int)($bonusesData['matching_count'] ?? 0) +
            (int)($bonusesData['rollup_count'] ?? 0)
    ];

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => $summary
    ]);

} catch (Exception $e) {
    error_log('Get total bonus summary error: ' . $e->getMessage());
    error_log('Get total bonus summary trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '보너스 통계 조회 중 오류가 발생했습니다.',
        'error_detail' => $e->getMessage()
    ]);
}
