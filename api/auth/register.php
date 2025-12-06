<?php
/**
 * 회원가입 API
 * POST /api/auth/register.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/User.php';

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
    // 회원가입 허용 여부 확인
    $db = Database::getInstance();
    $registrationSetting = $db->selectOne(
        "SELECT setting_value FROM settings WHERE setting_key = 'registration_enabled'"
    );

    if ($registrationSetting && $registrationSetting['setting_value'] !== '1') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => '현재 회원가입이 일시 중단되었습니다. 나중에 다시 시도해주세요.'
        ]);
        exit;
    }

    // JSON 데이터 파싱
    $json = file_get_contents('php://input');
    error_log('받은 JSON: ' . $json);

    $data = json_decode($json, true);
    error_log('파싱된 데이터: ' . print_r($data, true));

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
    $required = ['password', 'email', 'bnb_address', 'verification_code'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            error_log("필수 필드 누락: {$field}");
            error_log("값: " . var_export($data[$field] ?? 'NOT_SET', true));
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "{$field} 필드는 필수입니다."
            ]);
            exit;
        }
    }
    error_log('필수 필드 검증 통과');

    // TXID 검증 필수 (구버전 signup.html 차단)
    if (empty($data['verified_txid']) || empty($data['verified_network']) || empty($data['verified_amount'])) {
        error_log('TXID 검증 정보 누락 - 구버전 signup.html 차단');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'TXID 검증이 완료되지 않았습니다. 최신 버전의 가입 페이지를 사용해주세요.'
        ]);
        exit;
    }

    // TXID 검증 금액 확인 ($100 이상)
    if (floatval($data['verified_amount']) < 100) {
        error_log('TXID 검증 금액 부족: ' . $data['verified_amount']);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '최소 입금 금액은 $100 입니다.'
        ]);
        exit;
    }

    error_log('TXID 검증 통과: ' . $data['verified_txid']);

    // 테스트 모드: 이메일 인증 코드 검증 생략
    // 더미 코드 '000000'은 무조건 통과
    session_start();
    if (trim($data['verification_code']) !== '000000') {
        // 실제 인증 코드 확인 (테스트 모드 아닌 경우)
        $sessionCode = $_SESSION['verification_code'] ?? null;
        $sessionEmail = $_SESSION['verification_email'] ?? null;
        $sessionExpires = $_SESSION['verification_expires'] ?? 0;

        if (!$sessionCode || !$sessionEmail || time() > $sessionExpires) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '인증 코드가 만료되었습니다. 다시 전송해주세요.'
            ]);
            exit;
        }

        if (trim($data['verification_code']) !== $sessionCode) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '인증 코드가 일치하지 않습니다.'
            ]);
            exit;
        }

        if (strtolower(trim($data['email'])) !== strtolower($sessionEmail)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '인증된 이메일과 다릅니다.'
            ]);
            exit;
        }
    }

    // 인증 완료 - 세션 정리
    unset($_SESSION['verification_code']);
    unset($_SESSION['verification_email']);
    unset($_SESSION['verification_expires']);

    // 회원가입 처리 (user_id는 자동 생성됨)
    $user = new User();
    $result = $user->register([
        'password' => $data['password'],
        'email' => trim($data['email']),
        'bnb_address' => trim($data['bnb_address']),
        'referral_id' => !empty($data['referral_id']) ? trim($data['referral_id']) : null,
        'sponsor_id' => !empty($data['sponsor_id']) ? trim($data['sponsor_id']) : null,
        'sponsor_position' => !empty($data['sponsor_position']) ? intval($data['sponsor_position']) : null,
        'account_count' => !empty($data['account_count']) ? intval($data['account_count']) : 1
    ]);

    if ($result['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);

} catch (Exception $e) {
    error_log('Register API error: ' . $e->getMessage());
    error_log('Register API trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.',
        'error_detail' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine()
    ]);
}
