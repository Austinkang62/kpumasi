<?php
/**
 * 제품 목록 조회 API (디버깅용)
 * GET /api/sales/get-products.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // 데이터베이스 연결
    $db = Database::getInstance();

    // 제품 목록 조회
    $query = "SELECT * FROM products ORDER BY price ASC";
    $products = $db->select($query);

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => $products,
        'count' => count($products)
    ]);

} catch (Exception $e) {
    error_log('Get products error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '제품 목록 조회 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
