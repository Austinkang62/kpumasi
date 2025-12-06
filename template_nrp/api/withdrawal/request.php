<?php
/**
 * 출금 신청 API
 * POST /api/withdrawal/request.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // JSON 데이터 파싱
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // 필수 필드 확인
    if (empty($data['session_token']) || empty($data['amount']) || empty($data['usdt_address'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '필수 정보가 누락되었습니다.'
        ]);
        exit;
    }

    // 세션 검증
    $user = new User();
    $currentUser = $user->validateSession($data['session_token']);

    if (!$currentUser) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 세션입니다.'
        ]);
        exit;
    }

    $userId = $currentUser['id'];
    $amount = (float)$data['amount'];
    $usdtAddress = trim($data['usdt_address']);

    $db = Database::getInstance();

    // 1. 패키지 구매 정보 조회
    $packageQuery = "
        SELECT s.product_id, s.total_amount
        FROM sales s
        WHERE s.user_id = ?
        AND s.payment_status = 'completed'
        ORDER BY s.payment_date DESC
        LIMIT 1
    ";
    $packageResult = $db->selectOne($packageQuery, [$userId]);

    if ($packageResult) {
        $packageId = (int)$packageResult['product_id'];
        $packageAmount = (float)($packageResult['total_amount'] ?? 0);
    } else {
        $packageId = null;
        $packageAmount = 0;
    }

    // 2. 보너스 합계 조회
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

    // 8. 마지막 출금일 조회
    $lastWithdrawalQuery = "SELECT created_at
                            FROM withdrawals
                            WHERE user_id = ? AND status IN ('pending', 'completed', 'approved')
                            ORDER BY created_at DESC
                            LIMIT 1";
    $lastWithdrawalResult = $db->selectOne($lastWithdrawalQuery, [$userId]);

    // 4. 출금 가능 여부 검증

    // 4-1. 최소 금액 체크
    if ($amount < 10) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '최소 출금 금액은 $10입니다.'
        ]);
        exit;
    }

    // 4-2. 잔액 확인
    if ($amount > $currentBalance) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '출금 가능 잔액을 초과했습니다.'
        ]);
        exit;
    }

    // 4-3. 주간 제한 체크 (7일)
    if ($lastWithdrawalResult) {
        $lastWithdrawal = new DateTime($lastWithdrawalResult['created_at']);
        $today = new DateTime();
        $daysSince = $today->diff($lastWithdrawal)->days;

        if ($daysSince < 7) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '주 1회 출금 제한이 있습니다. ' . (7 - $daysSince) . '일 후 신청 가능합니다.'
            ]);
            exit;
        }
    }

    // 4-4. 패키지별 출금 가능 금액 계산 (새로운 정책)
    // 아바타 구간에 있어도 이전 사이클에서 번 금액은 출금 가능
    $maxWithdrawableAmount = $currentBalance;

    if ($packageId === 1) { // PKG_50
        $cycleAmount = 150; // $150 사이클
        $safeZoneEnd = 100; // $100까지 안전 구간

        // 현재 사이클 시작점 계산
        $currentCycle = floor($currentBalance / $cycleAmount);
        $cycleStart = $currentCycle * $cycleAmount;

        // 아바타 구간에 있는지 체크 ($100-$150, $250-$300, ...)
        $balanceInCycle = $currentBalance - $cycleStart;
        if ($balanceInCycle >= $safeZoneEnd && $balanceInCycle < $cycleAmount) {
            // 아바타 구간에 있음: 이전 사이클까지만 출금 가능
            $maxWithdrawableAmount = $cycleStart + $safeZoneEnd;
        }

    } else if ($packageId === 2) { // PKG_100
        $cycleAmount = 300; // $300 사이클
        $safeZoneEnd = 200; // $200까지 안전 구간

        // 현재 사이클 시작점 계산
        $currentCycle = floor($currentBalance / $cycleAmount);
        $cycleStart = $currentCycle * $cycleAmount;

        // 아바타 구간에 있는지 체크 ($200-$300, $500-$600, ...)
        $balanceInCycle = $currentBalance - $cycleStart;
        if ($balanceInCycle >= $safeZoneEnd && $balanceInCycle < $cycleAmount) {
            // 아바타 구간에 있음: 이전 사이클까지만 출금 가능
            $maxWithdrawableAmount = $cycleStart + $safeZoneEnd;
        }
    }

    // 최대 출금 가능 금액 체크
    if ($amount > $maxWithdrawableAmount) {
        $cycleInfo = '';
        if ($packageId === 1) {
            $cycleStart = floor($currentBalance / 150) * 150;
            $cycleInfo = "현재 잔액이 아바타 구간($" . ($cycleStart + 100) . "-$" . ($cycleStart + 150) . ")에 있습니다. ";
        } else if ($packageId === 2) {
            $cycleStart = floor($currentBalance / 300) * 300;
            $cycleInfo = "현재 잔액이 아바타 구간($" . ($cycleStart + 200) . "-$" . ($cycleStart + 300) . ")에 있습니다. ";
        }

        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $cycleInfo . "최대 출금 가능 금액은 $" . number_format($maxWithdrawableAmount, 2) . "입니다. 더 출금하려면 아바타를 구매하여 다음 사이클로 진행하세요."
        ]);
        exit;
    }

    // 5. USDT 주소 검증 (BNB Smart Chain 형식: 0x + 40자)
    if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $usdtAddress)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 USDT 주소입니다.'
        ]);
        exit;
    }

    // 6. 출금 신청 등록
    $insertQuery = "INSERT INTO withdrawals (user_id, amount, usdt_address, status, requested_at)
                    VALUES (?, ?, ?, 'pending', NOW())";

    $withdrawalId = $db->insert($insertQuery, [$userId, $amount, $usdtAddress]);

    if (!$withdrawalId) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '출금 신청 처리 중 오류가 발생했습니다.'
        ]);
        exit;
    }

    // 7. 성공 응답
    echo json_encode([
        'success' => true,
        'message' => '출금 신청이 완료되었습니다.',
        'data' => [
            'withdrawal_id' => $withdrawalId,
            'amount' => $amount,
            'usdt_address' => $usdtAddress,
            'status' => 'pending'
        ]
    ]);

} catch (Exception $e) {
    error_log('Withdrawal request error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '출금 신청 중 오류가 발생했습니다.',
        'error' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}
