<?php
/**
 * 직접 TXID 테스트
 */

header('Content-Type: application/json; charset=utf-8');

// 설정
$SYSTEM_WALLET = 'TSMZdowTbRC7wMKKkEjSL3LXss9coZW16Q';
$txid = 'ab345eb5618a4887598e63fd2f5992bb2e895dae4b3597ec3953cd7ccf9326c1';
$expectedAmount = 100;

// API 호출
$apiUrl = "https://api.trongrid.io/v1/transactions/{$txid}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== HTTP 코드 ===\n";
echo "HTTP Code: {$httpCode}\n\n";

echo "=== 원본 응답 (처음 500자) ===\n";
echo substr($response, 0, 500) . "\n\n";

$data = json_decode($response, true);

echo "=== JSON 디코드 결과 ===\n";
echo "txID 존재: " . (isset($data['txID']) ? 'YES' : 'NO') . "\n";
echo "log 존재: " . (isset($data['log']) ? 'YES' : 'NO') . "\n";

if (isset($data['log'])) {
    echo "log 개수: " . count($data['log']) . "\n\n";

    echo "=== Transfer 이벤트 찾기 ===\n";
    foreach ($data['log'] as $index => $log) {
        $topic0 = $log['topics'][0] ?? '';
        echo "Log #{$index} - Topic[0]: {$topic0}\n";

        if ($topic0 === 'ddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef') {
            echo "  → Transfer 이벤트 발견!\n";

            $fromHex = substr($log['topics'][1], 24);
            $toHex = substr($log['topics'][2], 24);
            $amount = hexdec($log['data']) / 1000000;

            echo "  From (hex): {$fromHex}\n";
            echo "  To (hex): {$toHex}\n";
            echo "  Amount: {$amount} USDT\n";

            $systemWalletHex = strtolower(substr($SYSTEM_WALLET, 1));
            echo "  System Wallet (hex): {$systemWalletHex}\n";
            echo "  주소 일치: " . (strtolower($toHex) === $systemWalletHex ? 'YES' : 'NO') . "\n";
            echo "  금액 일치: " . (abs($amount - $expectedAmount) < 0.01 ? 'YES' : 'NO') . "\n";
        }
    }
}

echo "\n=== 전체 데이터 (JSON) ===\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
