<?php
/**
 * 사용자 프로필 수정 API
 * POST /api/user/update-profile.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
    $data = json_decode($json, true);

    // 필수 필드 검증
    if (empty($data['session_token'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
        ]);
        exit;
    }

    // 세션 검증
    $user = new User();
    $currentUser = $user->validateSession($data['session_token']);

    if (!$currentUser) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 세션입니다.'
        ]);
        exit;
    }

    $db = Database::getInstance();

    // 데이터 검증
    $email = trim($data['email'] ?? '');
    $bnbAddress = trim($data['bnb_address'] ?? '');
    $currentPassword = $data['current_password'] ?? '';

    // 필수 필드 체크
    if (empty($email) || empty($bnbAddress) || empty($currentPassword)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '모든 필드를 입력해주세요.'
        ]);
        exit;
    }

    // 이메일 형식 검증
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 이메일 형식입니다.'
        ]);
        exit;
    }

    // BSC 지갑주소 형식 검증 (0x + 40자의 16진수)
    if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $bnbAddress)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'BSC 지갑주소 형식이 올바르지 않습니다. (0x로 시작하는 42자)'
        ]);
        exit;
    }

    // 현재 비밀번호 확인 (정보 변경 시 보안을 위해 필수)
    $userQuery = "SELECT password FROM users WHERE id = ?";
    $userInfo = $db->selectOne($userQuery, [$currentUser['id']]);

    if (!$userInfo || !password_verify($currentPassword, $userInfo['password'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '현재 비밀번호가 올바르지 않습니다.'
        ]);
        exit;
    }

    // 이메일 중복 체크 (현재 사용자 제외)
    $emailCheckQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
    $emailExists = $db->selectOne($emailCheckQuery, [$email, $currentUser['id']]);

    if ($emailExists) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '이미 사용 중인 이메일입니다.'
        ]);
        exit;
    }

    // 트랜잭션 시작
    $db->beginTransaction();

    // 기본 정보 업데이트
    $updateQuery = "UPDATE users
                    SET email = ?,
                        bnb_address = ?,
                        updated_at = NOW()
                    WHERE id = ?";

    $updated = $db->update($updateQuery, [
        $email,
        $bnbAddress,
        $currentUser['id']
    ]);

    if (!$updated) {
        $db->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '프로필 업데이트에 실패했습니다.'
        ]);
        exit;
    }

    // 비밀번호 변경 (선택사항)
    if (!empty($data['new_password'])) {
        $newPassword = $data['new_password'];

        // 새 비밀번호 길이 검증
        if (strlen($newPassword) < 8) {
            $db->rollback();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => '새 비밀번호는 최소 8자 이상이어야 합니다.'
            ]);
            exit;
        }

        // 새 비밀번호 해싱 및 업데이트
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

        $passwordUpdateQuery = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        $passwordUpdated = $db->update($passwordUpdateQuery, [$hashedPassword, $currentUser['id']]);

        if (!$passwordUpdated) {
            $db->rollback();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => '비밀번호 변경에 실패했습니다.'
            ]);
            exit;
        }
    }

    // 활동 로그 기록
    try {
        $logQuery = "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, details)
                     VALUES (?, ?, ?, ?, ?)";
        $db->insert($logQuery, [
            $currentUser['id'],
            'profile_update',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            json_encode([
                'updated_fields' => ['email', 'bnb_address'],
                'password_changed' => !empty($data['new_password'])
            ])
        ]);
    } catch (Exception $logError) {
        error_log('Activity log error: ' . $logError->getMessage());
        // 로그 실패해도 프로필 업데이트는 진행
    }

    // 트랜잭션 커밋
    $db->commit();

    // 성공 응답
    echo json_encode([
        'success' => true,
        'message' => '프로필이 성공적으로 업데이트되었습니다.'
    ]);

} catch (Exception $e) {
    // 트랜잭션 롤백
    if (isset($db) && $db) {
        try {
            $db->rollback();
        } catch (Exception $rollbackError) {
            error_log('Rollback error: ' . $rollbackError->getMessage());
        }
    }

    error_log('Update profile error: ' . $e->getMessage());
    error_log('Update profile trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '프로필 업데이트 중 오류가 발생했습니다.',
        'error_detail' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}
