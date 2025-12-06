<?php
/**
 * 공개 API: 지갑 주소 조회
 * 인증 불필요 (사용자 구매 페이지에서 사용)
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $db = Database::getInstance();

    // 지갑 주소 조회
    $results = $db->select("
        SELECT setting_key, setting_value
        FROM settings
        WHERE setting_key IN ('bsc_usdt_address', 'trc20_usdt_address')
    ");

    $walletAddresses = [
        'bsc_usdt_address' => '',
        'trc20_usdt_address' => ''
    ];

    foreach ($results as $row) {
        $walletAddresses[$row['setting_key']] = $row['setting_value'];
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => $walletAddresses
    ]);
    ob_end_flush();

} catch (Exception $e) {
    error_log('Public Wallet API Error: ' . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '지갑 주소를 불러올 수 없습니다.'
    ]);
    ob_end_flush();
}
