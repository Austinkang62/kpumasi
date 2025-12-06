<?php
/**
 * 회원가입 API
 * POST /api/auth/register.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/EmailVerification.php';

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

    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '잘못된 요청 형식입니다.'
        ]);
        exit;
    }

    // 필수 필드 확인
    $required = ['user_id', 'password', 'name', 'email', 'phone', 'usdt_address'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "{$field} 필드는 필수입니다."
            ]);
            exit;
        }
    }

    // 이메일 인증 확인 (실제 운영시 활성화)
    // $emailVerification = new EmailVerification();
    // if (!$emailVerification->isEmailVerified($data['email'])) {
    //     http_response_code(400);
    //     echo json_encode([
    //         'success' => false,
    //         'message' => '이메일 인증이 완료되지 않았습니다.'
    //     ]);
    //     exit;
    // }

    // 회원가입 처리
    $user = new User();
    $result = $user->register([
        'user_id' => trim($data['user_id']),
        'password' => $data['password'],
        'name' => trim($data['name']),
        'email' => trim($data['email']),
        'phone' => trim($data['phone']),
        'usdt_address' => trim($data['usdt_address']),
        'referral_id' => isset($data['referral_id']) ? trim($data['referral_id']) : null
    ]);

    if ($result['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log('Register API error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.'
    ]);
}
