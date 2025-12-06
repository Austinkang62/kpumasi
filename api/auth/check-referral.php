<?php
/**
 * 추천코드 검증 API
 * GET /api/auth/check-referral.php?code=ABCD1234
 *
 * 추천인 코드가 유효한지 확인
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
    // code 파라미터 확인
    if (empty($_GET['code'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '추천인 코드를 입력해주세요.'
        ]);
        exit;
    }

    $referralCode = strtoupper(trim($_GET['code']));

    // 코드 형식 검증 (8자리)
    if (strlen($referralCode) !== 8) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '추천인 코드는 8자리입니다 (예: ABCD1234)'
        ]);
        exit;
    }

    // 데이터베이스 연결
    $db = Database::getInstance();

    // 추천인 정보 조회
    $query = "SELECT id, user_id, name, email, created_at, package_id
              FROM users
              WHERE user_id = ?";
    $user = $db->selectOne($query, [$referralCode]);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '존재하지 않는 회원코드입니다.'
        ]);
        exit;
    }

    // 성공 응답
    $response = [
        'success' => true,
        'message' => '✅ 올바른 추천인 코드입니다.',
        'data' => [
            'user_id' => $user['user_id'],
            'name' => $user['name'] ?? '',
            'email' => $user['email'],
            'package' => $user['package_id'] ? "Package $" . ($user['package_id'] == 1 ? '50' : '100') : 'No Package',
            'joined_date' => $user['created_at']
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Check referral error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '조회 중 오류가 발생했습니다. 네트워크를 확인해주세요.',
        'error' => $e->getMessage()
    ]);
}
