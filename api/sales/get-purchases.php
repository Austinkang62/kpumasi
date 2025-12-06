<?php
/**
 * 구매 내역 조회 API
 * POST /api/sales/get-purchases.php
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

    if (empty($data['session_token'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
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

    // 데이터베이스 연결
    $db = Database::getInstance();

    // 구매 내역 조회
    $query = "SELECT
                s.id,
                s.user_id,
                s.product_id,
                p.product_name,
                p.product_code,
                s.quantity,
                s.unit_price,
                s.total_amount,
                s.payment_method,
                s.payment_status,
                s.transaction_id,
                s.payment_date,
                s.note,
                s.created_at
              FROM sales s
              INNER JOIN products p ON s.product_id = p.id
              WHERE s.user_id = ?
              ORDER BY s.created_at DESC";

    $purchases = $db->select($query, [$currentUser['id']]);

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => $purchases,
        'total_count' => count($purchases)
    ]);

} catch (Exception $e) {
    error_log('Get purchases error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '구매 내역 조회 중 오류가 발생했습니다.'
    ]);
}
