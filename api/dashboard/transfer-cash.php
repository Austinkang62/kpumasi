<?php
/**
 * 캐시 이전 API (추천인에게 캐시 전송)
 * POST /api/dashboard/transfer-cash.php
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
    // JSON 입력 파싱
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 JSON 형식입니다.'
        ]);
        exit;
    }

    // 필수 파라미터 검증
    $sessionToken = $input['session_token'] ?? null;
    $toUserId = $input['to_user_id'] ?? null;
    $amount = $input['amount'] ?? null;
    $note = $input['note'] ?? '';

    if (empty($sessionToken)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
        ]);
        exit;
    }

    if (empty($toUserId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '수신자 ID가 필요합니다.'
        ]);
        exit;
    }

    if (!is_numeric($amount) || $amount <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '유효한 금액을 입력해주세요.'
        ]);
        exit;
    }

    $amount = floatval($amount);

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

    $db = Database::getInstance();

    // 트랜잭션 시작
    $db->beginTransaction();

    try {
        // 발신자 정보 조회
        $fromUserQuery = "SELECT id, user_id, available_bonus FROM users WHERE user_id = ? FOR UPDATE";
        $fromUser = $db->selectOne($fromUserQuery, [$currentUser['user_id']]);

        if (!$fromUser) {
            throw new Exception('발신자 정보를 찾을 수 없습니다.');
        }

        // 잔액 확인
        if ($fromUser['available_bonus'] < $amount) {
            throw new Exception('잔액이 부족합니다. (현재 잔액: $' . number_format($fromUser['available_bonus'], 2) . ')');
        }

        // 수신자 정보 조회
        $toUserQuery = "SELECT id, user_id, available_bonus FROM users WHERE user_id = ? FOR UPDATE";
        $toUser = $db->selectOne($toUserQuery, [$toUserId]);

        if (!$toUser) {
            throw new Exception('수신자 정보를 찾을 수 없습니다.');
        }

        // 자기 자신에게 전송 방지
        if ($fromUser['user_id'] === $toUser['user_id']) {
            throw new Exception('자기 자신에게는 전송할 수 없습니다.');
        }

        // 발신자 잔액 차감
        $updateFromQuery = "UPDATE users
                            SET available_bonus = available_bonus - ?
                            WHERE user_id = ?";
        $db->execute($updateFromQuery, [$amount, $fromUser['user_id']]);

        // 수신자 잔액 증가
        $updateToQuery = "UPDATE users
                          SET available_bonus = available_bonus + ?,
                              total_bonus = total_bonus + ?
                          WHERE user_id = ?";
        $db->execute($updateToQuery, [$amount, $amount, $toUser['user_id']]);

        // 거래 기록 생성 (발신자)
        $insertFromTxQuery = "INSERT INTO transactions
                              (user_id, type, amount, balance_after, description, created_at)
                              VALUES (?, 'transfer_out', ?, ?, ?, NOW())";
        $balanceAfterFrom = $fromUser['available_bonus'] - $amount;
        $descriptionFrom = "캐시 전송 to {$toUser['user_id']}" . ($note ? " ({$note})" : "");
        $db->execute($insertFromTxQuery, [
            $fromUser['user_id'],
            $amount,
            $balanceAfterFrom,
            $descriptionFrom
        ]);

        // 거래 기록 생성 (수신자)
        $insertToTxQuery = "INSERT INTO transactions
                            (user_id, type, amount, balance_after, description, created_at)
                            VALUES (?, 'transfer_in', ?, ?, ?, NOW())";
        $balanceAfterTo = $toUser['available_bonus'] + $amount;
        $descriptionTo = "캐시 수신 from {$fromUser['user_id']}" . ($note ? " ({$note})" : "");
        $db->execute($insertToTxQuery, [
            $toUser['user_id'],
            $amount,
            $balanceAfterTo,
            $descriptionTo
        ]);

        // 커밋
        $db->commit();

        // 성공 응답
        echo json_encode([
            'success' => true,
            'message' => '캐시 전송이 완료되었습니다.',
            'data' => [
                'from_user_id' => $fromUser['user_id'],
                'to_user_id' => $toUser['user_id'],
                'amount' => $amount,
                'balance_after' => $balanceAfterFrom,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (Exception $e) {
        // 롤백
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Transfer cash error: ' . $e->getMessage());
    error_log('Transfer cash trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_detail' => APP_ENV === 'development' ? $e->getTraceAsString() : null
    ]);
}
