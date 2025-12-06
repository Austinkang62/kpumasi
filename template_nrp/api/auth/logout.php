<?php
/**
 * 로그아웃 API
 * POST /api/auth/logout.php
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

    if (!$data || empty($data['token'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '잘못된 요청입니다.'
        ]);
        exit;
    }

    // 로그아웃 처리
    $user = new User();
    $result = $user->logout($data['token']);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '로그아웃되었습니다.'
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '로그아웃 처리 중 오류가 발생했습니다.'
        ]);
    }

} catch (Exception $e) {
    error_log('Logout API error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.'
    ]);
}
