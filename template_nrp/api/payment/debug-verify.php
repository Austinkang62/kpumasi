<?php
/**
 * 디버그용 검증 API
 */

// 에러 로깅 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    // 입력 받기
    $input = json_decode(file_get_contents('php://input'), true);
    $txid = $input['txid'] ?? '';
    $expectedAmount = $input['expected_amount'] ?? 0;

    // 데모 모드
    if (strtolower($txid) === 'demo' || strtolower($txid) === 'test') {
        echo json_encode([
            'success' => true,
            'message' => 'DEMO 모드',
            'data' => [
                'txid' => 'DEMO_' . time(),
                'amount' => $expectedAmount,
                'status' => 'confirmed'
            ]
        ]);
        exit;
    }

    // TXID 검증
    if (empty($txid)) {
        echo json_encode([
            'success' => false,
            'message' => 'TXID를 입력하세요'
        ]);
        exit;
    }

    // API 호출 1단계
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

    // 404면 2단계 API 시도
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

    if (!$response) {
        echo json_encode([
            'success' => false,
            'message' => 'API 호출 실패',
            'debug' => [
                'curl_error' => $curlError,
                'http_code' => $httpCode
            ]
        ]);
        exit;
    }

    if ($httpCode !== 200) {
        echo json_encode([
            'success' => false,
            'message' => "TXID를 찾을 수 없습니다 (HTTP {$httpCode})",
            'debug' => [
                'http_code' => $httpCode,
                'response' => substr($response, 0, 200)
            ]
        ]);
        exit;
    }

    $data = json_decode($response, true);

    // Transfer 이벤트 찾기
    $transferFound = false;
    $fromAddress = '';
    $toAddress = '';
    $amount = 0;

    if (isset($data['log'])) {
        foreach ($data['log'] as $log) {
            $transferSignature = 'ddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';

            if (($log['topics'][0] ?? '') === $transferSignature) {
                $fromAddress = substr($log['topics'][1], 24);
                $toAddress = substr($log['topics'][2], 24);
                $amount = hexdec($log['data']) / 1000000;
                $transferFound = true;
                break;
            }
        }
    }

    if (!$transferFound) {
        echo json_encode([
            'success' => false,
            'message' => 'TRC20 USDT 전송을 찾을 수 없습니다',
            'debug' => [
                'has_log' => isset($data['log']),
                'log_count' => isset($data['log']) ? count($data['log']) : 0
            ]
        ]);
        exit;
    }

    // 주소 검증
    $systemWallet = 'TSMZdowTbRC7wMKKkEjSL3LXss9coZW16Q';
    $systemWalletHex = 'b3bc63468737d43c916cd7dd08bc68def4c364a5'; // hex 버전 (41 제거)

    $errors = [];

    // 디버그: 주소 비교
    $toAddressLower = strtolower($toAddress);
    $systemWalletHexLower = strtolower($systemWalletHex);

    if ($toAddressLower !== $systemWalletHexLower) {
        $errors[] = "입금 주소 불일치";
        $errors[] = "받은: [{$toAddressLower}]";
        $errors[] = "예상: [{$systemWalletHexLower}]";
        $errors[] = "같음: " . ($toAddressLower === $systemWalletHexLower ? 'YES' : 'NO');
    }

    if (abs($amount - $expectedAmount) > 0.01) {
        $errors[] = "금액 불일치 (예상: {$expectedAmount}, 실제: {$amount})";
    }

    if (empty($errors)) {
        echo json_encode([
            'success' => true,
            'message' => '✅ 검증 성공',
            'data' => [
                'txid' => $txid,
                'from_address' => $fromAddress,
                'to_address' => $toAddress,
                'amount' => $amount,
                'expected_amount' => $expectedAmount,
                'status' => 'confirmed',
                'tronscan_url' => "https://tronscan.org/#/transaction/{$txid}"
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '검증 실패: ' . implode(', ', $errors),
            'data' => [
                'txid' => $txid,
                'from_address' => $fromAddress,
                'to_address' => $toAddress,
                'amount' => $amount,
                'expected_amount' => $expectedAmount,
                'tronscan_url' => "https://tronscan.org/#/transaction/{$txid}"
            ]
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '오류 발생',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
