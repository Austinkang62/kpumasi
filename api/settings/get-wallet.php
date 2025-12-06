<?php
/**
 * 공개 지갑 주소 조회 API
 * 회원가입 페이지에서 BSC/TRC20 주소를 표시하기 위해 사용
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

    // 지갑 설정 조회
    $results = $db->select("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('bsc_usdt_address', 'trc20_usdt_address')");

    $walletSettings = [
        'bsc_usdt_address' => '',
        'trc20_usdt_address' => ''
    ];

    foreach ($results as $row) {
        $walletSettings[$row['setting_key']] = $row['setting_value'];
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => $walletSettings
    ]);
    ob_end_flush();

} catch (Exception $e) {
    error_log('Public Wallet Settings API Error: ' . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.'
    ]);
    ob_end_flush();
}
