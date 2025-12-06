<?php
/**
 * 보너스 내역 조회 API
 * GET /api/bonus/list.php?session_token=xxx
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
    // 세션 토큰 확인
    $sessionToken = $_GET['session_token'] ?? '';

    if (empty($sessionToken)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
        ]);
        exit;
    }

    // 사용자 세션 검증
    $db = Database::getInstance();

    $sessionQuery = "SELECT u.id, u.user_id, u.name
                     FROM users u
                     INNER JOIN sessions s ON u.id = s.user_id
                     WHERE s.session_token = ? AND s.expires_at > NOW()";

    $user = $db->selectOne($sessionQuery, [$sessionToken]);

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 세션입니다.'
        ]);
        exit;
    }

    $userId = $user['id'];

    // 디버깅 로그
    error_log('[Bonus List API] User ID: ' . $userId . ', user_id: ' . $user['user_id']);

    // 보너스 내역 조회 (최근 50개)
    $bonusQuery = "
        SELECT
            rb.id,
            rb.bonus_amount as amount,
            rb.level,
            'referral' as commission_type,
            rb.created_at,
            rb.status,
            buyer.user_id as buyer_id,
            buyer.name as buyer_name
        FROM referral_bonuses rb
        LEFT JOIN users buyer ON rb.from_user_id = buyer.id
        WHERE rb.to_user_id = ?
        ORDER BY rb.created_at DESC
        LIMIT 50
    ";

    $bonuses = $db->select($bonusQuery, [$userId]);

    // 총 보너스 금액 조회
    $totalBonusQuery = "SELECT SUM(bonus_amount) as total FROM referral_bonuses WHERE to_user_id = ? AND status = 'paid'";
    $totalBonusResult = $db->selectOne($totalBonusQuery, [$userId]);
    $totalBonus = (float)($totalBonusResult['total'] ?? 0);

    // 이번 달 보너스 조회
    $monthlyBonusQuery = "
        SELECT SUM(bonus_amount) as total
        FROM referral_bonuses
        WHERE to_user_id = ?
        AND status = 'paid'
        AND YEAR(created_at) = YEAR(CURDATE())
        AND MONTH(created_at) = MONTH(CURDATE())
    ";
    $monthlyBonusResult = $db->selectOne($monthlyBonusQuery, [$userId]);
    $monthlyBonus = (float)($monthlyBonusResult['total'] ?? 0);

    // 보너스 건수 조회
    $bonusCountQuery = "SELECT COUNT(*) as count FROM referral_bonuses WHERE to_user_id = ? AND status = 'paid'";
    $bonusCountResult = $db->selectOne($bonusCountQuery, [$userId]);
    $bonusCount = (int)($bonusCountResult['count'] ?? 0);

    // 패키지 구매 정보 조회 (가장 최근 구매)
    $packageQuery = "
        SELECT s.product_id, s.total_amount
        FROM sales s
        WHERE s.user_id = ?
        AND s.payment_status = 'completed'
        ORDER BY s.payment_date DESC
        LIMIT 1
    ";
    $packageResult = $db->selectOne($packageQuery, [$userId]);

    // product_id 추출
    if ($packageResult) {
        $packageId = (int)$packageResult['product_id'];
        $packageAmount = (float)($packageResult['total_amount'] ?? 0);
    } else {
        $packageId = null;
        $packageAmount = 0;
    }

    // 보너스 합계 조회
    $bonusEarningsQuery = "
        SELECT COALESCE(SUM(bonus_amount), 0) as total
        FROM referral_bonuses
        WHERE to_user_id = ?
    ";
    $bonusEarningsResult = $db->selectOne($bonusEarningsQuery, [$userId]);
    $bonusEarnings = (float)($bonusEarningsResult['total'] ?? 0);

    // 총 수익 = 패키지 구매 금액 + 보너스 합계
    $totalEarnings = $packageAmount + $bonusEarnings;

    // 아바타 합계 계산 (보너스만으로 계산)
    $avatarTotal = 0;
    $completedCycles = 0;
    if ($packageId) {
        $cycleAmount = 0;
        if ($packageId == 1) { // PKG_50
            $cycleAmount = 50 * 3; // $150
        } elseif ($packageId == 2) { // PKG_100
            $cycleAmount = 100 * 3; // $300
        }

        if ($cycleAmount > 0) {
            // 보너스만으로 사이클 계산 (패키지 금액 제외)
            $completedCycles = floor($bonusEarnings / $cycleAmount);
            $avatarTotal = $completedCycles * ($cycleAmount / 3); // 100% per cycle
        }
    }

    // 보너스 합계 = 보너스 수익 - 아바타 합계
    $bonusTotal = $bonusEarnings - $avatarTotal;

    // 디버깅 로그
    error_log('[Bonus List API] package_amount: ' . $packageAmount . ', bonus_earnings: ' . $bonusEarnings . ', total_earnings: ' . $totalEarnings . ', avatar_total: ' . $avatarTotal . ', package_id: ' . ($packageId ?? 'NULL'));

    // 출금 합계 조회 (pending, approved, completed 모두 포함)
    $withdrawalQuery = "
        SELECT COALESCE(SUM(amount), 0) as total
        FROM withdrawals
        WHERE user_id = ?
        AND status IN ('pending', 'approved', 'completed')
    ";
    $withdrawalResult = $db->selectOne($withdrawalQuery, [$userId]);
    $withdrawalTotal = (float)($withdrawalResult['total'] ?? 0);

    // 현재 잔액 = 보너스 합계 - 출금 합계
    $currentBalance = $bonusTotal - $withdrawalTotal;

    // 보너스 데이터 포맷팅
    $formattedBonuses = array_map(function($bonus) {
        return [
            'id' => $bonus['id'],
            'date' => date('Y-m-d H:i', strtotime($bonus['created_at'])),
            'type' => '추천 보너스',
            'level' => (int)$bonus['level'],
            'buyer' => maskUserId($bonus['buyer_id'] ?? 'system'),
            'buyer_name' => $bonus['buyer_name'] ?? '-',
            'amount' => (float)$bonus['amount'],
            'status' => $bonus['status'] === 'paid' ? 'completed' : $bonus['status'],
            'commission_type' => $bonus['commission_type']
        ];
    }, $bonuses);

    // 응답 반환
    echo json_encode([
        'success' => true,
        'data' => [
            'total_bonus' => $totalBonus,
            'monthly_bonus' => $monthlyBonus,
            'bonus_count' => $bonusCount,
            'total_earnings' => $totalEarnings,
            'avatar_total' => $avatarTotal,
            'bonus_total' => $bonusTotal,
            'current_balance' => $currentBalance,
            'package_id' => $packageId,
            'withdrawal_total' => $withdrawalTotal,
            'bonuses' => $formattedBonuses
        ],
        'debug' => [
            'user_id' => $user['id'],
            'user_login_id' => $user['user_id'],
            'package_amount' => $packageAmount,
            'bonus_earnings' => $bonusEarnings,
            'total_earnings_calculated' => $totalEarnings,
            'package_raw' => $packageResult,
            'referral_bonuses_count' => $db->selectOne("SELECT COUNT(*) as cnt FROM referral_bonuses WHERE to_user_id = ?", [$userId])['cnt'] ?? 0,
            'sales_count' => $db->selectOne("SELECT COUNT(*) as cnt FROM sales WHERE user_id = ? AND payment_status = 'completed'", [$userId])['cnt'] ?? 0
        ]
    ]);

} catch (Exception $e) {
    error_log('Bonus list error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '보너스 내역 조회 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}

/**
 * 사용자 ID 마스킹 처리
 */
function maskUserId($userId) {
    if (empty($userId) || $userId === 'system') {
        return 'system';
    }

    $len = strlen($userId);
    if ($len <= 4) {
        return 'user_***';
    }

    // 앞 5자 표시, 나머지 ***로 마스킹
    return substr($userId, 0, min(5, $len - 2)) . '***' . substr($userId, -2);
}
