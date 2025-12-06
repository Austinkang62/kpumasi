<?php
/**
 * Super Admin Authentication API
 * super_admin 권한만 허용
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();

    if ($method === 'POST' && $action === 'login') {
        // Super Admin 로그인
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            ob_end_flush();
            exit;
        }

        // Super Admin 계정만 조회 (role = 'super_admin')
        $admin = $db->selectOne(
            "SELECT admin_id, username, password, email, full_name, role, is_active
             FROM admins
             WHERE username = ? AND role = 'super_admin' AND is_active = 1",
            [$username]
        );

        if (!$admin) {
            // Super Admin이 아닌 경우
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Super Admin 권한이 필요합니다. 일반 관리자는 /admin을 이용하세요.'
            ]);
            ob_end_flush();
            exit;
        }

        if (!password_verify($password, $admin['password'])) {
            // 로그인 실패 로그
            try {
                $db->execute(
                    "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
                     VALUES (?, 'super_login_failed', ?, ?, ?)",
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

        // 로그인 성공 로그
        try {
            $db->execute(
                "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
                 VALUES (?, 'super_login_success', ?, ?, ?)",
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

        // 세션 설정 (super_admin 전용)
        $_SESSION['super_admin_logged_in'] = true;
        $_SESSION['super_admin_id'] = $admin['admin_id'];
        $_SESSION['super_admin_username'] = $admin['username'];
        $_SESSION['super_admin_email'] = $admin['email'];
        $_SESSION['super_admin_role'] = $admin['role'];
        $_SESSION['super_admin_session_id'] = $sessionId;

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
        if (isset($_SESSION['super_admin_id']) && isset($_SESSION['super_admin_session_id'])) {
            // 세션 삭제
            try {
                $db->execute(
                    "DELETE FROM admin_sessions WHERE session_id = ?",
                    [$_SESSION['super_admin_session_id']]
                );
            } catch (Exception $e) {
                error_log('Session delete error: ' . $e->getMessage());
            }

            // 로그아웃 로그
            try {
                $db->execute(
                    "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
                     VALUES (?, 'super_logout', ?, ?, ?)",
                    [
                        $_SESSION['super_admin_id'],
                        json_encode(['username' => $_SESSION['super_admin_username']]),
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
        // 로그인 상태 확인 (super_admin만)
        $isLoggedIn = isset($_SESSION['super_admin_logged_in']) && $_SESSION['super_admin_logged_in'];

        if ($isLoggedIn && isset($_SESSION['super_admin_session_id'])) {
            // 세션 유효성 확인
            $session = $db->selectOne(
                "SELECT expires_at FROM admin_sessions WHERE session_id = ?",
                [$_SESSION['super_admin_session_id']]
            );

            if (!$session || strtotime($session['expires_at']) < time()) {
                // 세션 만료
                session_destroy();
                $isLoggedIn = false;
            } else {
                // 세션 활동 시간 업데이트
                $db->execute(
                    "UPDATE admin_sessions SET last_activity = NOW() WHERE session_id = ?",
                    [$_SESSION['super_admin_session_id']]
                );
            }
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'logged_in' => $isLoggedIn,
            'user' => $isLoggedIn ? [
                'admin_id' => $_SESSION['super_admin_id'],
                'username' => $_SESSION['super_admin_username'],
                'email' => $_SESSION['super_admin_email'],
                'role' => $_SESSION['super_admin_role']
            ] : null
        ]);
        ob_end_flush();

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        ob_end_flush();
    }

} catch (Exception $e) {
    error_log('Super Admin Auth Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    ob_clean();
    if (defined('APP_ENV') && APP_ENV === 'development') {
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
    ob_end_flush();
}
?>
