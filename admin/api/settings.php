<?php
ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '관리자 인증이 필요합니다.'
    ]);
    ob_end_flush();
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();

    switch ($action) {
        case 'get_all':
            // 모든 설정 조회
            $settingsRows = $db->select("SELECT setting_key, setting_value FROM settings");

            $settings = [];
            foreach ($settingsRows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'settings' => $settings
            ]);
            ob_end_flush();
            break;

        case 'get':
            // 특정 설정 조회
            $key = $_GET['key'] ?? '';

            if (empty($key)) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Setting key required']);
                ob_end_flush();
                exit;
            }

            $setting = $db->selectOne(
                "SELECT setting_key, setting_value, setting_type, description FROM settings WHERE setting_key = ?",
                [$key]
            );

            if (!$setting) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Setting not found']);
                ob_end_flush();
                exit;
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'setting' => $setting
            ]);
            ob_end_flush();
            break;

        case 'update':
            // 설정 업데이트
            $data = json_decode(file_get_contents('php://input'), true);

            $key = $data['setting_key'] ?? '';
            $value = $data['setting_value'] ?? '';

            if (empty($key)) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Setting key required']);
                ob_end_flush();
                exit;
            }

            // 설정이 존재하는지 확인
            $existing = $db->selectOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);

            if (!$existing) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Setting not found']);
                ob_end_flush();
                exit;
            }

            // 업데이트 실행
            $db->execute(
                "UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?",
                [$value, $key]
            );

            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Setting updated successfully',
                'setting_key' => $key,
                'setting_value' => $value
            ]);
            ob_end_flush();
            break;

        case 'get_wallet':
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
            break;

        case 'save_wallet':
            // 지갑 설정 저장
            $input = json_decode(file_get_contents('php://input'), true);

            $bscAddress = $input['bsc_usdt_address'] ?? '';
            $trc20Address = $input['trc20_usdt_address'] ?? '';

            // BSC 주소 유효성 검사 (0x로 시작, 42자)
            if ($bscAddress && (!preg_match('/^0x[a-fA-F0-9]{40}$/', $bscAddress))) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'BSC 주소 형식이 올바르지 않습니다.'
                ]);
                ob_end_flush();
                exit;
            }

            // TRC20 주소 유효성 검사 (T로 시작, 34자)
            if ($trc20Address && (!preg_match('/^T[a-zA-Z0-9]{33}$/', $trc20Address))) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'TRC20 주소 형식이 올바르지 않습니다.'
                ]);
                ob_end_flush();
                exit;
            }

            // BSC 주소 저장 (UPSERT)
            $db->execute("
                INSERT INTO settings (setting_key, setting_value, setting_type, description, updated_at)
                VALUES ('bsc_usdt_address', ?, 'string', 'BSC USDT 지갑 주소', NOW())
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ", [$bscAddress, $bscAddress]);

            // TRC20 주소 저장 (UPSERT)
            $db->execute("
                INSERT INTO settings (setting_key, setting_value, setting_type, description, updated_at)
                VALUES ('trc20_usdt_address', ?, 'string', 'TRC20 USDT 지갑 주소', NOW())
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ", [$trc20Address, $trc20Address]);

            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => '지갑 설정이 저장되었습니다.'
            ]);
            ob_end_flush();
            break;

        default:
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => '잘못된 요청입니다.'
            ]);
            ob_end_flush();
    }

} catch (Exception $e) {
    error_log('Settings API Error: ' . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
    ob_end_flush();
}
