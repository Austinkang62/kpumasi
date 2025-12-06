<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

try {
    // POST 데이터 받기
    $input = json_decode(file_get_contents('php://input'), true);

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'JSON 응답 테스트 성공',
        'received_data' => $input
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();
?>
