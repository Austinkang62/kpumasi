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
    // 세션 토큰 확인 (Authorization 헤더 또는 GET 파라미터)
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
        $sessionToken = $_GET['session_token'] ?? '';
    }

    if (empty($sessionToken)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.',
            'debug' => [
                'auth_header' => $authHeader,
                'server_keys' => array_keys($_SERVER)
            ]
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

    // 1. 사용자 정보 조회 (available_bonus, avatar_points, bnb_address, total_bonus)
    $userInfo = $db->selectOne("
        SELECT
            available_bonus,
            avatar_points,
            total_bonus,
            bnb_address
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
    $avatarPoints = (float)($userInfo['avatar_points'] ?? 0);
    $totalBonus = (float)($userInfo['total_bonus'] ?? 0);
    $usdtAddress = $userInfo['bnb_address'] ?? '';

    // 2. 출금 합계 조회 (pending, approved, completed 모두 포함)
    $withdrawalQuery = "
        SELECT COALESCE(SUM(amount), 0) as total
        FROM withdrawals
        WHERE user_id = ?
        AND status IN ('pending', 'approved', 'completed')
    ";
    $withdrawalResult = $db->selectOne($withdrawalQuery, [$userId]);
    $withdrawalTotal = (float)($withdrawalResult['total'] ?? 0);

    // 3. 현재 출금 가능 잔액 = available_bonus - 출금 합계
    $currentBalance = $availableBonus - $withdrawalTotal;

    // 4. 마지막 출금일 조회
    $lastWithdrawalQuery = "
        SELECT requested_at
        FROM withdrawals
        WHERE user_id = ? AND status IN ('pending', 'completed', 'approved')
        ORDER BY requested_at DESC
        LIMIT 1
    ";
    $lastWithdrawalResult = $db->selectOne($lastWithdrawalQuery, [$userId]);
    $lastWithdrawal = $lastWithdrawalResult ? $lastWithdrawalResult['requested_at'] : null;

    // 응답 데이터
    echo json_encode([
        'success' => true,
        'data' => [
            'total_earned' => $totalBonus,
            'current_balance' => max(0, $currentBalance),
            'avatar_points' => $avatarPoints,
            'usdt_address' => $usdtAddress,
            'last_withdrawal' => $lastWithdrawal
        ],
        'debug' => [
            'available_bonus' => $availableBonus,
            'avatar_points' => $avatarPoints,
            'total_bonus' => $totalBonus,
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
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
