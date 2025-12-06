<?php
/**
 * 회원가입 신청 API (관리자 승인 필요)
 * POST /api/auth/register-pending.php
 *
 * TXID 검증 없이 가입 신청을 받고, 관리자 승인을 거쳐 회원가입 완료
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    error_log('가입신청 받은 JSON: ' . $json);

    $data = json_decode($json, true);
    error_log('가입신청 파싱된 데이터: ' . print_r($data, true));

    if (!$data) {
        error_log('JSON 파싱 실패!');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '잘못된 요청 형식입니다.'
        ]);
        exit;
    }

    // 필수 필드 확인
    $required = ['password', 'email', 'txid'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            error_log("필수 필드 누락: {$field}");
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "{$field} 필드는 필수입니다."
            ]);
            exit;
        }
    }

    // 이메일 형식 검증
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '올바른 이메일 형식이 아닙니다.'
        ]);
        exit;
    }

    // 비밀번호 길이 검증
    if (strlen($data['password']) < 6) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '비밀번호는 최소 6자 이상이어야 합니다.'
        ]);
        exit;
    }

    // TXID 형식 검증 (기본적인 형식만 확인)
    $txid = trim($data['txid']);
    if (strlen($txid) < 30 || strlen($txid) > 100) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'TXID 형식이 올바르지 않습니다.'
        ]);
        exit;
    }

    $db = Database::getInstance()->getConnection();

    // ===== 이메일 중복 체크 비활성화 (다계정 지원) =====
    // 동일 이메일로 여러 계정 생성 가능하도록 설정 (2025-11-18)

    // // 이메일 중복 체크 (pending_registrations 테이블에서)
    // $stmt = $db->prepare("SELECT id FROM pending_registrations WHERE email = ? AND status = 'pending'");
    // $stmt->execute([trim($data['email'])]);
    // if ($stmt->fetch()) {
    //     http_response_code(400);
    //     echo json_encode([
    //         'success' => false,
    //         'message' => '이미 승인 대기 중인 가입 신청이 있습니다.'
    //     ]);
    //     exit;
    // }

    // // 기존 회원 이메일 중복 체크
    // $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    // $stmt->execute([trim($data['email'])]);
    // if ($stmt->fetch()) {
    //     http_response_code(400);
    //     echo json_encode([
    //         'success' => false,
    //         'message' => '이미 사용 중인 이메일입니다.'
    //     ]);
    //     exit;
    // }

    // ===== 이메일 중복 체크 비활성화 끝 =====

    // TXID 중복 체크
    $stmt = $db->prepare("SELECT id FROM pending_registrations WHERE txid = ?");
    $stmt->execute([$txid]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '이미 사용된 TXID입니다.'
        ]);
        exit;
    }

    // 비밀번호 해싱
    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    // 가입 신청 저장
    $stmt = $db->prepare("
        INSERT INTO pending_registrations (
            email, password, txid, network, payment_amount,
            referral_id, sponsor_id, sponsor_position,
            memo, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $result = $stmt->execute([
        trim($data['email']),
        $hashedPassword,
        $txid,
        $data['network'] ?? 'TRC20',
        $data['payment_amount'] ?? 100.00,
        !empty($data['referral_id']) ? trim($data['referral_id']) : null,
        !empty($data['sponsor_id']) ? trim($data['sponsor_id']) : null,
        !empty($data['sponsor_position']) ? intval($data['sponsor_position']) : null,
        !empty($data['memo']) ? trim($data['memo']) : null
    ]);

    if ($result) {
        $registrationId = $db->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => '회원가입 신청이 완료되었습니다. 관리자 승인 후 가입이 완료됩니다.',
            'data' => [
                'registration_id' => $registrationId,
                'email' => trim($data['email']),
                'status' => 'pending'
            ]
        ]);
    } else {
        throw new Exception('가입 신청 저장 실패');
    }

} catch (Exception $e) {
    error_log('Register Pending API error: ' . $e->getMessage());
    error_log('Register Pending API trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.',
        'error_detail' => $e->getMessage()
    ]);
}
