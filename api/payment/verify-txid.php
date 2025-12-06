<?php
/**
 * TXID 검증 API
 * POST /api/payment/verify-txid.php
 *
 * TRC20 USDT 트랜잭션 ID를 검증하고 결제를 확인합니다.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/TronPaymentVerifier.php';

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

    // 필수 필드 검증
    if (empty($data['session_token']) || empty($data['txid']) || empty($data['sale_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '필수 정보가 누락되었습니다. (session_token, txid, sale_id 필요)'
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

    // 매출 정보 조회
    $saleQuery = "SELECT * FROM sales WHERE id = ? AND user_id = ?";
    $sale = $db->selectOne($saleQuery, [$data['sale_id'], $currentUser['id']]);

    if (!$sale) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '매출 정보를 찾을 수 없습니다.'
        ]);
        exit;
    }

    // 이미 완료된 결제인지 확인
    if ($sale['payment_status'] === 'completed') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '이미 결제가 완료된 주문입니다.'
        ]);
        exit;
    }

    // TXID 검증
    $verifier = new TronPaymentVerifier();
    $result = $verifier->verifyTransaction(
        $data['txid'],
        (float)$sale['total_amount'],
        $sale['id']
    );

    if (!$result['success']) {
        // 검증 실패 로그
        error_log('TXID verification failed: ' . json_encode([
            'user_id' => $currentUser['id'],
            'sale_id' => $sale['id'],
            'txid' => $data['txid'],
            'error' => $result['message']
        ]));

        http_response_code(400);
        echo json_encode($result);
        exit;
    }

    // 트랜잭션 시작
    $db->beginTransaction();

    try {
        // 매출 상태 업데이트
        $updateSaleQuery = "UPDATE sales
                           SET payment_status = 'completed',
                               transaction_id = ?,
                               payment_date = NOW(),
                               note = CONCAT(IFNULL(note, ''), '\n[자동검증] TXID 검증 완료')
                           WHERE id = ?";

        $db->update($updateSaleQuery, [$data['txid'], $sale['id']]);

        // payment_transactions 테이블의 sale_id 업데이트
        $updatePaymentQuery = "UPDATE payment_transactions
                              SET sale_id = ?
                              WHERE txid = ?";

        $db->update($updatePaymentQuery, [$sale['id'], $data['txid']]);

        // user_statistics 업데이트
        $statsCheckQuery = "SELECT id FROM user_statistics WHERE user_id = ?";
        $stats = $db->selectOne($statsCheckQuery, [$currentUser['id']]);

        if ($stats) {
            $updateStatsQuery = "UPDATE user_statistics
                                SET total_earnings = total_earnings + ?
                                WHERE user_id = ?";
            $db->update($updateStatsQuery, [$sale['total_amount'], $currentUser['id']]);
        } else {
            $createStatsQuery = "INSERT INTO user_statistics (user_id, total_earnings)
                                VALUES (?, ?)";
            $db->insert($createStatsQuery, [$currentUser['id'], $sale['total_amount']]);
        }

        // 활동 로그 기록
        $logQuery = "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, details)
                    VALUES (?, ?, ?, ?, ?)";
        $db->insert($logQuery, [
            $currentUser['id'],
            'payment_verified',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            json_encode([
                'sale_id' => $sale['id'],
                'txid' => $data['txid'],
                'amount' => $sale['total_amount'],
                'verification_method' => 'auto_txid'
            ])
        ]);

        // 보너스 계산 (BonusSystem 사용 가능한 경우)
        // TODO: 보너스 시스템 연동
        // if (file_exists(__DIR__ . '/../../classes/BonusSystem.php')) {
        //     require_once __DIR__ . '/../../classes/BonusSystem.php';
        //     $bonusSystem = new BonusSystem();
        //     // 보너스 계산 로직 호출
        // }

        $db->commit();

        // 성공 응답
        echo json_encode([
            'success' => true,
            'message' => '결제가 확인되었습니다.',
            'data' => [
                'sale_id' => $sale['id'],
                'txid' => $data['txid'],
                'amount' => $sale['total_amount'],
                'payment_status' => 'completed',
                'verification_details' => $result['data'],
                'tronscan_url' => $verifier->getTronscanUrl($data['txid'])
            ]
        ]);

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Payment verification error: ' . $e->getMessage());
    error_log('Payment verification trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '결제 확인 중 오류가 발생했습니다.',
        'error_detail' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}
