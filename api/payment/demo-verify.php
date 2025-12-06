<?php
/**
 * 데모 전용 검증 API (간단 버전)
 */

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$txid = $input['txid'] ?? '';
$expectedAmount = $input['expected_amount'] ?? 0;

// 데모 모드만 처리
if (strtolower($txid) === 'demo' || strtolower($txid) === 'test') {
    echo json_encode([
        'success' => true,
        'message' => '✅ 데모 승인 완료!',
        'data' => [
            'payment_id' => 999,
            'txid' => 'DEMO_' . time(),
            'status' => 'confirmed',
            'from_address' => 'DEMO_FROM',
            'to_address' => 'TSMZdowTbRC7wMKKkEjSL3LXss9coZW16Q',
            'amount' => $expectedAmount,
            'expected_amount' => $expectedAmount,
            'tronscan_url' => 'https://tronscan.org'
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '이 API는 데모 전용입니다. TXID에 "demo" 또는 "test"를 입력하세요.'
    ]);
}
