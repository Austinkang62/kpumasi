<?php
/**
 * USDT 주소 중복 체크 API
 * POST /api/auth/check-usdt.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/User.php';

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

    if (!$data || empty($data['usdt_address'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'USDT 주소를 입력해주세요.'
        ]);
        exit;
    }

    $usdtAddress = trim($data['usdt_address']);

    // USDT 주소 형식 체크 (BNB Smart Chain)
    if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $usdtAddress)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 USDT 주소 형식입니다. (0x + 40자리 16진수)'
        ]);
        exit;
    }

    // USDT 주소 중복 체크
    $user = new User();
    $available = $user->checkUsdtAddressAvailable($usdtAddress);

    echo json_encode([
        'success' => true,
        'available' => $available,
        'message' => $available ? 'This USDT address is available' : 'This USDT address is already in use'
    ]);

} catch (Exception $e) {
    error_log('Check USDT API error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.'
    ]);
}
