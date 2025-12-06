<?php
/**
 * 단순 TXID 검증 API
 * 회원이 TXID 입력 → 기본 정보만 가져와서 저장 → 관리자 승인 대기
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/SimpleTronVerifier.php';

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

$sessionToken = $input['session_token'] ?? '';
$saleId = $input['sale_id'] ?? 0;
$txid = $input['txid'] ?? '';

// 간단 검증
if (empty($sessionToken) || empty($txid)) {
    echo json_encode([
        'success' => false,
        'message' => '필수 정보가 누락되었습니다'
    ]);
    exit;
}

try {
    $db = new Database();
    $verifier = new SimpleTronVerifier($db);

    // TXID로 트랜잭션 정보 가져오기
    $result = $verifier->getTransactionInfo($txid);

    if (!$result['success']) {
        echo json_encode([
            'success' => false,
            'message' => $result['error']
        ]);
        exit;
    }

    // 관리자 승인 대기로 저장
    $paymentId = $verifier->savePendingTransaction($saleId, $result);

    echo json_encode([
        'success' => true,
        'message' => 'TXID가 등록되었습니다. 관리자 승인을 기다려주세요.',
        'data' => [
            'payment_id' => $paymentId,
            'txid' => $result['txid'],
            'from' => $result['from'],
            'to' => $result['to'],
            'amount' => $result['amount'],
            'status' => 'pending',
            'tronscan_url' => $result['tronscan_url']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
