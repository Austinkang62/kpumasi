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

    // 세션 토큰 확인 (Authorization 헤더 또는 POST body)
    $authHeader = '';

    // Authorization 헤더 가져오기 (여러 방법 시도)
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }
    }

    $sessionToken = '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $sessionToken = $matches[1];
    } else {
        $sessionToken = $data['session_token'] ?? '';
    }

    // 필수 필드 확인
    if (empty($sessionToken) || empty($data['amount']) || empty($data['usdt_address'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '필수 정보가 누락되었습니다.'
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
    $amount = (float)$data['amount'];
    $usdtAddress = trim($data['usdt_address']);

    $db = Database::getInstance();

    // 1. 사용자 정보 조회
    $userInfo = $db->selectOne("
        SELECT available_bonus, credit_sale_amount, withdrawal_hold
        FROM users
        WHERE id = ?
    ", [$userId]);

    if (!$userInfo) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '사용자 정보를 찾을 수 없습니다.'
        ]);
        exit;
    }

    $availableBonus = (float)($userInfo['available_bonus'] ?? 0);
    $creditSaleAmount = (float)($userInfo['credit_sale_amount'] ?? 0);
    $withdrawalHold = (int)($userInfo['withdrawal_hold'] ?? 0);

    // 1-1. 출금홀딩 체크
    if ($withdrawalHold === 1) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => '출금이 홀딩된 상태입니다. 관리자에게 문의하세요.',
            'error_code' => 'WITHDRAWAL_HOLD'
        ]);
        exit;
    }

    // 2. 출금 합계 조회 (pending, approved, completed 모두 포함)
    $withdrawalQuery = "
        SELECT COALESCE(SUM(amount), 0) as total
        FROM withdrawals
        WHERE user_id = ?
        AND status IN ('pending', 'approved', 'completed')
    ";
    $withdrawalResult = $db->selectOne($withdrawalQuery, [$userId]);
    $withdrawalTotal = (float)($withdrawalResult['total'] ?? 0);

    // 3. 현재 출금 가능 잔액 = available_bonus - 출금 합계 - 외상매출
    $currentBalance = $availableBonus - $withdrawalTotal - $creditSaleAmount;

    // 외상매출이 있는 경우 로그
    if ($creditSaleAmount > 0) {
        error_log("Withdrawal request - User {$userId} has credit sale amount: \${$creditSaleAmount}");
        error_log("Withdrawal calculation: available(\${$availableBonus}) - withdrawn(\${$withdrawalTotal}) - credit(\${$creditSaleAmount}) = \${$currentBalance}");
    }

    // 4. 마지막 출금일 조회
    $lastWithdrawalQuery = "
        SELECT requested_at
        FROM withdrawals
        WHERE user_id = ? AND status IN ('pending', 'completed', 'approved')
        ORDER BY requested_at DESC
        LIMIT 1
    ";
    $lastWithdrawalResult = $db->selectOne($lastWithdrawalQuery, [$userId]);

    // 5. 출금 가능 여부 검증

    // 5-1. 최소 금액 체크
    if ($amount < 100) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '최소 출금 금액은 $100입니다.'
        ]);
        exit;
    }

    // 5-2. $10 단위 체크
    if (fmod($amount, 10) != 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '출금 금액은 $10 단위로만 가능합니다.'
        ]);
        exit;
    }

    // 5-3. 잔액 확인
    if ($amount > $currentBalance) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '출금 가능 잔액($' . number_format($currentBalance, 2) . ')을 초과했습니다.'
        ]);
        exit;
    }

    // 5-4. 주간 제한 체크 (7일)
    if ($lastWithdrawalResult) {
        $lastWithdrawal = new DateTime($lastWithdrawalResult['requested_at']);
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

    // 7. USDT 주소 검증 (BNB Smart Chain 형식: 0x + 40자)
    if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $usdtAddress)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 USDT 주소입니다.'
        ]);
        exit;
    }

    // 8. 출금 신청 등록
    // 수수료: 5%
    $feeRate = 0.05;
    $fee = $amount * $feeRate;
    $netAmount = $amount - $fee; // 실제 수령 금액 = 출금 금액 - 수수료

    $insertQuery = "INSERT INTO withdrawals (user_id, amount, fee, net_amount, withdrawal_address, status)
                    VALUES (?, ?, ?, ?, ?, 'pending')";

    $withdrawalId = $db->insert($insertQuery, [$userId, $amount, $fee, $netAmount, $usdtAddress]);

    if (!$withdrawalId) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '출금 신청 처리 중 오류가 발생했습니다.'
        ]);
        exit;
    }

    // 9. 성공 응답
    echo json_encode([
        'success' => true,
        'message' => '출금 신청이 완료되었습니다.',
        'data' => [
            'withdrawal_id' => $withdrawalId,
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => $netAmount,
            'usdt_address' => $usdtAddress,
            'status' => 'pending',
            'remaining_balance' => max(0, $currentBalance - $amount)
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
