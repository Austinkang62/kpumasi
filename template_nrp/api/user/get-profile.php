<?php
/**
 * 사용자 프로필 조회 API
 * GET /api/user/get-profile.php
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
    // 세션 토큰 검증
    $sessionToken = $_GET['session_token'] ?? null;

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

    // 사용자 정보 조회 (비밀번호 제외)
    $db = Database::getInstance();
    $query = "SELECT user_id, name, email, phone, usdt_address, referral_id,
                     email_verified, status, created_at, last_login
              FROM users
              WHERE id = ?";

    $profile = $db->selectOne($query, [$currentUser['id']]);

    if (!$profile) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '사용자 정보를 찾을 수 없습니다.'
        ]);
        exit;
    }

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => $profile
    ]);

} catch (Exception $e) {
    error_log('Get profile error: ' . $e->getMessage());
    error_log('Get profile trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '프로필 조회 중 오류가 발생했습니다.',
        'error_detail' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}
