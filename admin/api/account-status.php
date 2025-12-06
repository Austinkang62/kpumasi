<?php
/**
 * 계정 상태 관리 API
 * POST /admin/api/account-status.php
 * - 외상매출 지정/해제
 * - 출금홀딩 지정/해제
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $action = $data['action'] ?? null;
    $userId = $data['user_id'] ?? null;

    if (!$action || !$userId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'action과 user_id가 필요합니다.'
        ]);
        exit;
    }

    $db = Database::getInstance();

    // 사용자 존재 확인
    $user = $db->selectOne("SELECT id, user_id FROM users WHERE user_id = ?", [$userId]);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '사용자를 찾을 수 없습니다.'
        ]);
        exit;
    }

    // 액션 처리
    if ($action === 'set_credit_sale') {
        // 외상매출 지정/해제
        $amount = floatval($data['amount'] ?? 0);

        $db->execute("
            UPDATE users
            SET credit_sale_amount = ?
            WHERE user_id = ?
        ", [$amount, $userId]);

        echo json_encode([
            'success' => true,
            'message' => $amount > 0 ? '외상매출이 지정되었습니다.' : '외상매출이 해제되었습니다.',
            'data' => [
                'user_id' => $userId,
                'credit_sale_amount' => $amount
            ]
        ]);

    } elseif ($action === 'set_withdrawal_hold') {
        // 출금홀딩 지정/해제
        $hold = intval($data['hold'] ?? 0);

        $db->execute("
            UPDATE users
            SET withdrawal_hold = ?
            WHERE user_id = ?
        ", [$hold, $userId]);

        echo json_encode([
            'success' => true,
            'message' => $hold === 1 ? '출금홀딩이 지정되었습니다.' : '출금홀딩이 해제되었습니다.',
            'data' => [
                'user_id' => $userId,
                'withdrawal_hold' => $hold
            ]
        ]);

    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '알 수 없는 액션입니다.'
        ]);
    }

} catch (Exception $e) {
    error_log('Account status API error: ' . $e->getMessage());
    error_log('Account status API trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
