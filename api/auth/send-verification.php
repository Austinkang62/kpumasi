<?php
/**
 * 이메일 인증코드 전송 API
 * POST /api/auth/send-verification.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../classes/MailSender.php';

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['email']) || empty($data['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '이메일이 필요합니다']);
        exit;
    }

    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '올바른 이메일 형식이 아닙니다']);
        exit;
    }

    // 6자리 인증 코드 생성
    $verificationCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // 세션에 인증 코드 저장 (10분 유효)
    session_start();
    $_SESSION['verification_code'] = $verificationCode;
    $_SESSION['verification_email'] = $email;
    $_SESSION['verification_expires'] = time() + 600; // 10분

    // 이메일 전송
    $mailer = new MailSender();
    $mailSent = $mailer->sendVerificationCode($email, $verificationCode);

    if ($mailSent) {
        error_log("Verification code sent to {$email}: {$verificationCode}");

        echo json_encode([
            'success' => true,
            'message' => '인증 코드가 이메일로 전송되었습니다',
            'debug' => [
                'code' => $verificationCode, // 개발용 - 프로덕션에서는 제거
                'email' => $email
            ]
        ]);
    } else {
        error_log("Failed to send email to {$email}");

        // 이메일 전송 실패해도 세션에 저장된 코드는 유효 (개발용)
        echo json_encode([
            'success' => true,
            'message' => '인증 코드가 생성되었습니다 (이메일 전송 실패)',
            'debug' => [
                'code' => $verificationCode, // 개발용
                'email' => $email,
                'note' => '이메일 서버 미설정 - 콘솔에서 코드 확인 가능'
            ]
        ]);
    }

} catch (Exception $e) {
    error_log('Send verification error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '인증 코드 전송 중 오류가 발생했습니다'
    ]);
}
