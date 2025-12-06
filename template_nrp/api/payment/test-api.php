<?php
/**
 * TronGrid API 연결 테스트
 */

header('Content-Type: application/json; charset=utf-8');

$txid = '6477f48542529d1ce9ccaa6d50da0015bd82b7836475e497ce75c5367f331aca';
$apiUrl = "https://api.trongrid.io/v1/transactions/{$txid}";

echo json_encode([
    'test' => 'API 연결 테스트',
    'php_version' => phpversion(),
    'curl_enabled' => function_exists('curl_init') ? 'YES' : 'NO',
    'allow_url_fopen' => ini_get('allow_url_fopen') ? 'YES' : 'NO',
    'api_url' => $apiUrl
]);

echo "\n\n=== cURL 테스트 ===\n\n";

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo json_encode([
        'curl_test' => 'RESULT',
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'response_length' => strlen($response),
        'response_preview' => substr($response, 0, 200)
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode(['error' => 'cURL not available']);
}
