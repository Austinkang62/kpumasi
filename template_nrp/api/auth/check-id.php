<?php
/**
 * ID 중복 체크 API
 * POST /api/auth/check-id.php
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

    if (!$data || empty($data['user_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID를 입력해주세요.'
        ]);
        exit;
    }

    $userId = trim($data['user_id']);

    // ID 길이 체크
    if (strlen($userId) < 4 || strlen($userId) > 50) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID는 4-50자 사이여야 합니다.'
        ]);
        exit;
    }

    // ID 중복 체크
    $user = new User();
    $available = $user->checkUserIdAvailable($userId);

    echo json_encode([
        'success' => true,
        'available' => $available,
        'message' => $available ? 'This ID is available' : 'This ID is already taken'
    ]);

} catch (Exception $e) {
    error_log('Check ID API error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.'
    ]);
}
