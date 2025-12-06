<?php
/**
 * 세션 확인 API
 * 일반 회원의 로그인 세션 확인
 */

session_start();

header('Content-Type: application/json; charset=utf-8');

// CORS 헤더 (credentials 사용 시 * 불가)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // 세션 확인
    if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
        // 로그인된 상태
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user_id' => $_SESSION['user_id'],
            'token' => $_SESSION['session_token'] ?? null
        ]);
    } else {
        // 로그인되지 않은 상태
        echo json_encode([
            'success' => true,
            'logged_in' => false
        ]);
    }

} catch (Exception $e) {
    error_log('Check session error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'logged_in' => false,
        'message' => 'Session check failed'
    ]);
}
