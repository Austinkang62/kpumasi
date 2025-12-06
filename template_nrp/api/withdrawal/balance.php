<?php
/**
 * 출금 잔액 및 가능 여부 조회 API
 * GET /api/withdrawal/balance.php?session_token=xxx
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';

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

    // 세션 검증
    $user = new User();
    $currentUser = $user->validateSession($sessionToken);

    if (!$currentUser) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 세션입니다.'
        ]);
        exit;
    }

    $userId = $currentUser['id'];
    $db = Database::getInstance();

    // 1. 패키지 구매 정보 조회 (가장 최근 구매)
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

    // 2. 보너스 합계 조회 (referral_bonuses)
    $bonusEarningsQuery = "
        SELECT COALESCE(SUM(bonus_amount), 0) as total
        FROM referral_bonuses
        WHERE to_user_id = ?
    ";
    $bonusEarningsResult = $db->selectOne($bonusEarningsQuery, [$userId]);
    $bonusEarnings = (float)($bonusEarningsResult['total'] ?? 0);

    // 3. 총 수익 = 패키지 구매 금액 + 보너스 합계
    $totalEarnings = $packageAmount + $bonusEarnings;

    // 4. 아바타 합계 계산 (보너스만으로 계산)
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

    // 디버깅 로그
    error_log('[Withdrawal Balance] bonus_earnings: ' . $bonusEarnings . ', completed_cycles: ' . $completedCycles . ', avatar_total: ' . $avatarTotal);

    // 5. 보너스 합계 = 보너스 수익 - 아바타 합계
    $bonusTotal = $bonusEarnings - $avatarTotal;

    // 6. 출금 합계 조회 (pending, approved, completed 모두 포함)
    $withdrawalQuery = "
        SELECT COALESCE(SUM(amount), 0) as total
        FROM withdrawals
        WHERE user_id = ?
        AND status IN ('pending', 'approved', 'completed')
    ";
    $withdrawalResult = $db->selectOne($withdrawalQuery, [$userId]);
    $withdrawalTotal = (float)($withdrawalResult['total'] ?? 0);

    // 7. 현재 잔액 = 보너스 합계 - 출금 합계
    $currentBalance = $bonusTotal - $withdrawalTotal;

    // 8. USDT 주소 조회
    $userInfoQuery = "SELECT usdt_address FROM users WHERE id = ?";
    $userInfo = $db->selectOne($userInfoQuery, [$userId]);
    $usdtAddress = $userInfo ? $userInfo['usdt_address'] : '';

    // 9. 마지막 출금일 조회
    $lastWithdrawalQuery = "SELECT created_at
                            FROM withdrawals
                            WHERE user_id = ? AND status IN ('completed', 'approved')
                            ORDER BY created_at DESC
                            LIMIT 1";
    $lastWithdrawalResult = $db->selectOne($lastWithdrawalQuery, [$userId]);
    $lastWithdrawal = $lastWithdrawalResult ? $lastWithdrawalResult['created_at'] : null;

    // 응답 데이터
    echo json_encode([
        'success' => true,
        'data' => [
            'total_earned' => $totalEarnings,
            'current_balance' => $currentBalance,
            'package_id' => $packageId,
            'usdt_address' => $usdtAddress,
            'last_withdrawal' => $lastWithdrawal
        ],
        'debug' => [
            'package_amount' => $packageAmount,
            'bonus_earnings' => $bonusEarnings,
            'total_earnings' => $totalEarnings,
            'completed_cycles' => $completedCycles,
            'avatar_total' => $avatarTotal,
            'bonus_total' => $bonusTotal,
            'withdrawal_total' => $withdrawalTotal,
            'current_balance' => $currentBalance
        ]
    ]);

} catch (Exception $e) {
    error_log('Withdrawal balance error: ' . $e->getMessage());
    error_log('Withdrawal balance trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '잔액 조회 중 오류가 발생했습니다.',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
