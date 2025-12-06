<?php
/**
 * TXID 검증 API (회원가입용)
 * BSC/TRC20 네트워크에서 USDT 입금 트랜잭션 확인
 */
ob_start();

require_once __DIR__ . '/../../config/database.php';

/**
 * TRON 주소 변환: Hex -> Base58Check
 */
function hexToBase58($hexAddress) {
    // Base58 알파벳
    $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    // Hex를 바이너리로 변환
    $addressBin = hex2bin($hexAddress);

    // SHA256 두 번 해싱하여 체크섬 생성
    $hash1 = hash('sha256', $addressBin, true);
    $hash2 = hash('sha256', $hash1, true);
    $checksum = substr($hash2, 0, 4);

    // 주소 + 체크섬
    $addressWithChecksum = $addressBin . $checksum;

    // Base58 인코딩
    $num = gmp_import($addressWithChecksum);
    $base58 = '';

    while (gmp_cmp($num, 0) > 0) {
        list($num, $rem) = gmp_div_qr($num, 58);
        $base58 = $alphabet[gmp_intval($rem)] . $base58;
    }

    // 선행 0 바이트 처리
    for ($i = 0; $i < strlen($addressWithChecksum) && $addressWithChecksum[$i] === "\x00"; $i++) {
        $base58 = '1' . $base58;
    }

    return $base58;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
    ob_end_flush();
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$txid = $input['txid'] ?? '';
$network = $input['network'] ?? '';
$requiredAmount = $input['required_amount'] ?? 100;

if (empty($txid)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'TXID를 입력해주세요.']);
    ob_end_flush();
    exit;
}

if (empty($network) || !in_array($network, ['BSC', 'TRC20'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => '네트워크를 선택해주세요. (BSC 또는 TRC20)']);
    ob_end_flush();
    exit;
}

try {
    $db = Database::getInstance();

    // 지갑 주소 조회
    $walletKey = ($network === 'BSC') ? 'bsc_usdt_address' : 'trc20_usdt_address';
    $walletResult = $db->selectOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$walletKey]);
    $depositAddress = $walletResult ? $walletResult['setting_value'] : '';

    if (empty($depositAddress)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => '시스템 지갑 주소가 설정되지 않았습니다.']);
        ob_end_flush();
        exit;
    }

    // 이미 사용된 TXID인지 확인
    $usedTxid = $db->selectOne("SELECT id FROM payments WHERE txid = ?", [$txid]);
    if ($usedTxid) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => '이미 사용된 TXID입니다.']);
        ob_end_flush();
        exit;
    }

    // 실제 블록체인 검증
    $isValid = false;
    $amount = 0;
    $verificationError = '';

    if ($network === 'BSC') {
        // BSC TXID 형식 검증: 0x로 시작하는 66자
        if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $txid)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'BSC TXID 형식이 올바르지 않습니다. (0x + 64자 hex)']);
            ob_end_flush();
            exit;
        }

        // BSC Public RPC를 사용하여 트랜잭션 영수증 조회
        // USDT Contract: 0x55d398326f99059fF775485246999027B3197955 (BSC)
        $bscUsdtContract = '0x55d398326f99059fF775485246999027B3197955';

        // BSC Public RPC 노드
        $rpcUrl = "https://bsc-dataseed.binance.org/";

        // eth_getTransactionReceipt JSON-RPC 호출
        $rpcData = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'eth_getTransactionReceipt',
            'params' => [$txid],
            'id' => 1
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rpcUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rpcData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0'
        ]);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            $verificationError = 'BSC RPC 연결 실패: ' . $curlError;
        } else if ($httpCode !== 200 || !$response) {
            $verificationError = "BSC RPC 연결 실패 (HTTP {$httpCode})";
        } else {
            $data = json_decode($response, true);

            if (isset($data['result']) && $data['result'] !== null) {
                $receipt = $data['result'];

                // 트랜잭션 성공 여부 확인 (0x1 = 성공)
                $txStatus = $receipt['status'] ?? '';
                if ($txStatus === '0x1') {
                    // logs에서 Transfer 이벤트 찾기
                    if (isset($receipt['logs']) && is_array($receipt['logs'])) {
                        foreach ($receipt['logs'] as $log) {
                            // USDT 컨트랙트 주소 확인 (대소문자 무시)
                            if (strtolower($log['address']) === strtolower($bscUsdtContract)) {
                                // Transfer 이벤트 topic: 0xddf252ad...
                                if (isset($log['topics'][0]) && $log['topics'][0] === '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef') {
                                    // 받는 주소 확인 (topics[2])
                                    if (isset($log['topics'][2])) {
                                        $toAddress = '0x' . substr($log['topics'][2], 26); // 앞의 0 패딩 제거

                                        // 설정된 지갑 주소와 비교 (대소문자 무시)
                                        if (strtolower($toAddress) === strtolower($depositAddress)) {
                                            // 금액 파싱 (data 필드, 18 decimals)
                                            $amountHex = $log['data'];
                                            $amountWei = hexdec($amountHex);
                                            $amount = $amountWei / 1e18;
                                            $isValid = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        if (!$isValid) {
                            $verificationError = '해당 트랜잭션에서 설정된 지갑으로의 USDT 전송을 찾을 수 없습니다.';
                        }
                    } else {
                        $verificationError = '트랜잭션 로그를 찾을 수 없습니다.';
                    }
                } else {
                    $verificationError = '트랜잭션이 실패했습니다.';
                }
            } else {
                $verificationError = '트랜잭션을 찾을 수 없습니다. TXID를 확인해주세요.';
            }
        }

    } else if ($network === 'TRC20') {
        // TRC20 TXID 형식 검증: 64자 hex
        if (!preg_match('/^[a-fA-F0-9]{64}$/', $txid)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'TRC20 TXID 형식이 올바르지 않습니다. (64자 hex)']);
            ob_end_flush();
            exit;
        }

        // TronScan API 호출 (TRC20 토큰 전송 조회)
        // USDT Contract: TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t (TRC20)
        $apiUrl = "https://apilist.tronscanapi.com/api/transaction-info?hash={$txid}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: Mozilla/5.0'
        ]);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            $verificationError = 'TronScan API 연결 실패: ' . $curlError;
        } else if ($httpCode !== 200 || !$response) {
            $verificationError = "TronScan API 연결 실패 (HTTP {$httpCode})";
        } else {
            $data = json_decode($response, true);

            // 트랜잭션 존재 및 성공 확인
            if (isset($data['hash']) && isset($data['contractRet']) && $data['contractRet'] === 'SUCCESS') {
                // TRC20 토큰 전송 정보 확인
                if (isset($data['trc20TransferInfo']) && is_array($data['trc20TransferInfo']) && count($data['trc20TransferInfo']) > 0) {
                    $transferFound = false;

                    foreach ($data['trc20TransferInfo'] as $transfer) {
                        // USDT 토큰 확인 (symbol 또는 contract_address)
                        $isUsdt = (isset($transfer['symbol']) && $transfer['symbol'] === 'USDT') ||
                                  (isset($transfer['contract_address']) && $transfer['contract_address'] === 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');

                        if ($isUsdt) {
                            $toAddress = $transfer['to_address'] ?? '';
                            $transferAmount = $transfer['amount_str'] ?? '0';

                            // 금액 변환 (이미 실제 값이거나 decimals 적용 필요)
                            if (isset($transfer['decimals'])) {
                                $amount = floatval($transferAmount) / pow(10, $transfer['decimals']);
                            } else {
                                // amount_str이 이미 실제 값인 경우
                                $amount = floatval($transferAmount);
                                // 너무 큰 값이면 decimals 적용
                                if ($amount > 1000000) {
                                    $amount = $amount / 1e6;
                                }
                            }

                            // 지갑 주소 비교
                            if ($toAddress === $depositAddress) {
                                $isValid = true;
                                $transferFound = true;
                                break;
                            }
                        }
                    }

                    if (!$transferFound) {
                        $verificationError = '해당 트랜잭션에서 설정된 지갑으로의 USDT 전송을 찾을 수 없습니다.';
                    }
                } else {
                    $verificationError = 'TRC20 토큰 전송 정보를 찾을 수 없습니다.';
                }
            } else if (isset($data['hash'])) {
                $verificationError = '트랜잭션이 실패했습니다.';
            } else {
                $verificationError = '트랜잭션을 찾을 수 없습니다. TXID를 확인해주세요.';
            }
        }
    }

    if (!$isValid) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => $verificationError ?: 'TXID 검증에 실패했습니다.'
        ]);
        ob_end_flush();
        exit;
    }

    if ($amount < $requiredAmount) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => "입금액이 부족합니다. (필요: \${$requiredAmount}, 확인: \$" . number_format($amount, 2) . ")"
        ]);
        ob_end_flush();
        exit;
    }

    // 검증 성공
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => '입금이 확인되었습니다.',
        'data' => [
            'txid' => $txid,
            'network' => $network,
            'amount' => $amount,
            'to_address' => $depositAddress
        ]
    ]);
    ob_end_flush();

} catch (Exception $e) {
    error_log('TXID Verify Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ob_end_flush();
}
