<?php
/**
 * TXID 자동 검증 (가장 간단한 버전)
 * 회원이 TXID 입력 → 자동 검증 → 즉시 승인/거부
 */

// 에러 핸들링 강화
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// 버전 확인용 (캐시 체크)
if (isset($_GET['version'])) {
    echo json_encode(['version' => '2024-10-27-v7-duplicate-error']);
    exit;
}

// 전역 try-catch로 모든 에러 캐치
try {

// ============================================================
// 설정 (여기만 수정하세요)
// ============================================================
$SYSTEM_WALLET = 'TSMZdowTbRC7wMKKkEjSL3LXss9coZW16Q'; // 우리 입금 주소
$DB_HOST = 'localhost';
$DB_NAME = 'ai77';
$DB_USER = 'ai77';
$DB_PASS = 'Ai0505**ftd';

// ============================================================
// 1. 입력 받기
// ============================================================
$input = json_decode(file_get_contents('php://input'), true);
$saleId = $input['sale_id'] ?? 0;
$txid = $input['txid'] ?? '';
$expectedAmount = $input['expected_amount'] ?? 0; // 예상 금액

// ============================================================
// 데모 모드 (테스트용 - TXID에 "demo" 또는 "test" 입력)
// ============================================================
if (strtolower($txid) === 'demo' || strtolower($txid) === 'test') {
    echo json_encode([
        'success' => true,
        'message' => '✅ 데모 모드 - 자동 승인되었습니다!',
        'data' => [
            'payment_id' => 999,
            'txid' => 'DEMO_' . time(),
            'status' => 'confirmed',
            'from_address' => 'DEMO_FROM_ADDRESS',
            'to_address' => $SYSTEM_WALLET,
            'amount' => $expectedAmount,
            'expected_amount' => $expectedAmount,
            'tronscan_url' => 'https://tronscan.org'
        ]
    ]);
    exit;
}

// 필수 입력 확인
if (empty($txid) || empty($expectedAmount)) {
    echo json_encode([
        'success' => false,
        'message' => 'TXID와 금액을 입력하세요'
    ]);
    exit;
}

// ============================================================
// 2. TXID 형식 확인 (64자 hex, 단 demo/test는 제외)
// ============================================================
if (strtolower($txid) !== 'demo' && strtolower($txid) !== 'test') {
    if (!preg_match('/^[a-fA-F0-9]{64}$/', $txid)) {
        echo json_encode([
            'success' => false,
            'message' => 'TXID 형식이 올바르지 않습니다 (64자 hex 필요)'
        ]);
        exit;
    }
}

// ============================================================
// 3. DB 연결
// ============================================================
try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8",
        $DB_USER,
        $DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'DB 연결 실패: ' . $e->getMessage()
    ]);
    exit;
}

// ============================================================
// 4. 중복 확인 (이미 있으면 기존 데이터 반환)
// ============================================================
$stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE txid = ?");
$stmt->execute([$txid]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // 중복된 TXID - 에러 처리
    echo json_encode([
        'success' => false,
        'message' => '❌ 중복된 TXID입니다. 이미 등록된 거래입니다.',
        'data' => [
            'payment_id' => $existing['id'],
            'txid' => $existing['txid'],
            'status' => $existing['status'],
            'from_address' => $existing['from_address'],
            'to_address' => $existing['to_address'],
            'amount' => floatval($existing['amount']),
            'expected_amount' => $expectedAmount,
            'registered_at' => $existing['created_at'],
            'tronscan_url' => "https://tronscan.org/#/transaction/{$txid}",
            'duplicate' => true
        ]
    ]);
    exit;
}

// ============================================================
// 5. TronGrid API 호출 (하이브리드 방식)
// ============================================================
// 방법 1: /v1/transactions/{txid} 시도 (빠른 조회)
$apiUrl = "https://api.trongrid.io/v1/transactions/{$txid}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 404인 경우 방법 2 시도 (구형 트랜잭션 지원)
if ($httpCode === 404) {
    $walletApiUrl = "https://api.trongrid.io/wallet/gettransactioninfobyid";
    $postData = json_encode(['value' => $txid]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $walletApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
}

// 응답 확인
if (!$response) {
    echo json_encode([
        'success' => false,
        'message' => '트론 네트워크 조회 실패',
        'debug' => [
            'curl_error' => $curlError
        ]
    ]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode([
        'success' => false,
        'message' => '해당 TXID를 찾을 수 없습니다. TXID를 다시 확인해주세요.',
        'debug' => [
            'http_code' => $httpCode,
            'tronscan_url' => "https://tronscan.org/#/transaction/{$txid}"
        ]
    ]);
    exit;
}

$data = json_decode($response, true);

// txID 확인 (v1 API는 txID, wallet API는 id 사용)
$txID = $data['txID'] ?? $data['id'] ?? null;

if (!$txID) {
    echo json_encode([
        'success' => false,
        'message' => '트랜잭션을 찾을 수 없습니다'
    ]);
    exit;
}

// ============================================================
// 6. Transfer 이벤트 찾기 (TRC20 USDT)
// ============================================================
$transferFound = false;
$fromAddress = '';
$toAddress = '';
$amount = 0;

if (isset($data['log'])) {
    foreach ($data['log'] as $log) {
        // Transfer 이벤트 시그니처
        $transferSignature = 'ddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';

        if (($log['topics'][0] ?? '') === $transferSignature) {
            // 주소 추출 (간단 버전: hex만 사용)
            $fromAddress = substr($log['topics'][1], 24);
            $toAddress = substr($log['topics'][2], 24);
            $amount = hexdec($log['data']) / 1000000; // USDT 6 decimals
            $transferFound = true;
            break;
        }
    }
}

if (!$transferFound) {
    echo json_encode([
        'success' => false,
        'message' => 'TRC20 USDT 전송 내역을 찾을 수 없습니다'
    ]);
    exit;
}

// ============================================================
// 7. 자동 검증
// ============================================================
$errors = [];

// 7-1. 수신 주소 확인 (우리 지갑으로 왔는지)
// Tron 주소 hex 형식: 41b3bc63468737d43c916cd7dd08bc68def4c364a5 (41 제거)
$systemWalletHex = 'b3bc63468737d43c916cd7dd08bc68def4c364a5';
if (strtolower($toAddress) !== $systemWalletHex) {
    $errors[] = '입금 주소가 일치하지 않습니다';
}

// 7-2. 금액 확인 (±0.01 USDT 오차 허용)
if (abs($amount - $expectedAmount) > 0.01) {
    $errors[] = "금액이 일치하지 않습니다 (예상: {$expectedAmount} USDT, 실제: {$amount} USDT)";
}

// ============================================================
// 8. 결과 처리
// ============================================================
if (empty($errors)) {
    // 검증 성공 → 자동 승인
    $status = 'confirmed';
    $verifiedAt = date('Y-m-d H:i:s');
    $verificationError = null;
    $message = '✅ 결제가 자동 승인되었습니다!';
} else {
    // 검증 실패 → 자동 거부
    $status = 'rejected';
    $verifiedAt = null;
    $verificationError = implode(', ', $errors);
    $message = '❌ 검증 실패: ' . $verificationError;
}

// ============================================================
// 9. DB 저장
// ============================================================
$stmt = $pdo->prepare("
    INSERT INTO payment_transactions
    (sale_id, txid, from_address, to_address, amount, status,
     block_number, block_timestamp, verification_error, verified_at, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->execute([
    $saleId,
    $txid,
    $fromAddress,
    $toAddress,
    $amount,
    $status,
    $data['blockNumber'] ?? null,
    $data['block_timestamp'] ?? $data['blockTimeStamp'] ?? null,
    $verificationError,
    $verifiedAt
]);

$paymentId = $pdo->lastInsertId();

// ============================================================
// 10. 매출 상태 업데이트 (승인된 경우만)
// ============================================================
if ($status === 'confirmed' && $saleId > 0) {
    $stmt = $pdo->prepare("
        UPDATE sales
        SET payment_status = 'completed',
            transaction_id = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$txid, $saleId]);
}

// ============================================================
// 11. 결과 반환
// ============================================================
echo json_encode([
    'success' => ($status === 'confirmed'),
    'message' => $message,
    'data' => [
        'payment_id' => $paymentId,
        'txid' => $txid,
        'status' => $status,
        'from_address' => $fromAddress,
        'to_address' => $toAddress,
        'amount' => $amount,
        'expected_amount' => $expectedAmount,
        'tronscan_url' => "https://tronscan.org/#/transaction/{$txid}"
    ]
]);

} catch (Exception $e) {
    // 전역 에러 핸들링
    echo json_encode([
        'success' => false,
        'message' => '시스템 오류가 발생했습니다',
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
}
