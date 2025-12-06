<?php
/**
 * Admin Authentication API
 * 별도 admins 테이블 사용
 */

// 출력 버퍼링 시작 (JSON 응답 전 불필요한 출력 방지)
ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();

    if ($method === 'POST' && $action === 'login') {
        // 관리자 로그인
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            ob_end_flush();
            exit;
        }

        // 관리자 계정 확인
        $admin = $db->selectOne(
            "SELECT admin_id, username, password, email, full_name, role, is_active
             FROM admins
             WHERE username = ? AND is_active = 1",
            [$username]
        );

        if (!$admin || !password_verify($password, $admin['password'])) {
            // 로그인 실패 로그 (실패해도 무시)
            if ($admin) {
                try {
                    $db->execute(
                        "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
                         VALUES (?, 'login_failed', ?, ?, ?)",
                        [
                            $admin['admin_id'],
                            json_encode(['reason' => 'invalid_password', 'username' => $username]),
                            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        ]
                    );
                } catch (Exception $e) {
                    error_log('Login failed log error: ' . $e->getMessage());
                }
            }

            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            ob_end_flush();
            exit;
        }

        // 세션 생성
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

        // 세션 저장
        $db->execute(
            "INSERT INTO admin_sessions (session_id, admin_id, ip_address, user_agent, expires_at)
             VALUES (?, ?, ?, ?, ?)",
            [
                $sessionId,
                $admin['admin_id'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $expiresAt
            ]
        );

        // 마지막 로그인 업데이트
        try {
            $db->execute(
                "UPDATE admins SET last_login = NOW() WHERE admin_id = ?",
                [$admin['admin_id']]
            );
        } catch (Exception $e) {
            error_log('Last login update error: ' . $e->getMessage());
        }

        // 로그인 성공 로그 (실패해도 무시)
        try {
            $db->execute(
                "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
                 VALUES (?, 'login_success', ?, ?, ?)",
                [
                    $admin['admin_id'],
                    json_encode(['username' => $username]),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]
            );
        } catch (Exception $e) {
            error_log('Login success log error: ' . $e->getMessage());
        }

        // 세션 설정
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_session_id'] = $sessionId;

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'admin_id' => $admin['admin_id'],
                'username' => $admin['username'],
                'email' => $admin['email'],
                'full_name' => $admin['full_name'],
                'role' => $admin['role']
            ]
        ]);
        ob_end_flush();

    } elseif ($method === 'POST' && $action === 'logout') {
        // 로그아웃
        if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_session_id'])) {
            // 세션 삭제
            try {
                $db->execute(
                    "DELETE FROM admin_sessions WHERE session_id = ?",
                    [$_SESSION['admin_session_id']]
                );
            } catch (Exception $e) {
                error_log('Session delete error: ' . $e->getMessage());
            }

            // 로그아웃 로그 (실패해도 무시)
            try {
                $db->execute(
                    "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
                     VALUES (?, 'logout', ?, ?, ?)",
                    [
                        $_SESSION['admin_id'],
                        json_encode(['username' => $_SESSION['admin_username']]),
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]
                );
            } catch (Exception $e) {
                error_log('Logout log error: ' . $e->getMessage());
            }
        }

        session_destroy();
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Logged out']);
        ob_end_flush();

    } elseif ($method === 'GET' && $action === 'check') {
        // 로그인 상태 확인
        $isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

        if ($isLoggedIn && isset($_SESSION['admin_session_id'])) {
            // 세션 유효성 확인
            $session = $db->selectOne(
                "SELECT expires_at FROM admin_sessions WHERE session_id = ?",
                [$_SESSION['admin_session_id']]
            );

            if (!$session || strtotime($session['expires_at']) < time()) {
                // 세션 만료
                session_destroy();
                $isLoggedIn = false;
            } else {
                // 세션 활동 시간 업데이트
                $db->execute(
                    "UPDATE admin_sessions SET last_activity = NOW() WHERE session_id = ?",
                    [$_SESSION['admin_session_id']]
                );
            }
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'logged_in' => $isLoggedIn,
            'user' => $isLoggedIn ? [
                'admin_id' => $_SESSION['admin_id'],
                'username' => $_SESSION['admin_username'],
                'email' => $_SESSION['admin_email'],
                'role' => $_SESSION['admin_role']
            ] : null
        ]);
        ob_end_flush();

    } elseif ($method === 'POST' && $action === 'change-password') {
        // 비밀번호 변경
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            ob_end_flush();
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'All fields required']);
            ob_end_flush();
            exit;
        }

        if (strlen($newPassword) < 8) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
            ob_end_flush();
            exit;
        }

        // 현재 비밀번호 확인
        $admin = $db->selectOne(
            "SELECT password FROM admins WHERE admin_id = ?",
            [$_SESSION['admin_id']]
        );

        if (!password_verify($currentPassword, $admin['password'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Current password incorrect']);
            ob_end_flush();
            exit;
        }

        // 새 비밀번호 해싱
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

        // 비밀번호 업데이트
        $db->execute(
            "UPDATE admins SET password = ?, updated_at = NOW() WHERE admin_id = ?",
            [$hashedPassword, $_SESSION['admin_id']]
        );

        // 로그 (실패해도 무시)
        try {
            $db->execute(
                "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
                 VALUES (?, 'password_changed', ?, ?, ?)",
                [
                    $_SESSION['admin_id'],
                    json_encode(['username' => $_SESSION['admin_username']]),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]
            );
        } catch (Exception $e) {
            error_log('Password change log error: ' . $e->getMessage());
        }

        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        ob_end_flush();

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        ob_end_flush();
    }

} catch (Exception $e) {
    error_log('Admin Auth Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    ob_clean();
    // 개발 환경에서는 상세 오류 표시
    if (defined('APP_ENV') && APP_ENV === 'development') {
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
    ob_end_flush();
}
