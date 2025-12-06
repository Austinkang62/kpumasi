<?php
/**
 * 간단한 Admin Authentication API
 */

// 출력 버퍼링 시작 (JSON 응답 전 불필요한 출력 방지)
ob_start();

// 에러는 로그로만 기록 (JSON 응답 깨짐 방지)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Config 로드
    require_once __DIR__ . '/../../config/database.php';

    // 2. 세션 시작
    session_start();

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    // 3. Database 연결
    $db = Database::getInstance();

    if ($method === 'POST' && $action === 'login') {
        // 로그인 처리
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            ob_end_flush();
            exit;
        }

        // 관리자 조회
        $admin = $db->selectOne(
            "SELECT admin_id, username, password, email, full_name, role, is_active
             FROM admins
             WHERE username = ? AND is_active = 1",
            [$username]
        );

        if (!$admin) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User not found']);
            ob_end_flush();
            exit;
        }

        // 비밀번호 확인
        if (!password_verify($password, $admin['password'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            ob_end_flush();
            exit;
        }

        // 세션 설정
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];

        // 세션 ID 생성
        $sessionId = bin2hex(random_bytes(32));
        $_SESSION['admin_session_id'] = $sessionId;

        // DB에 세션 저장
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + 7200);
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
        } catch (Exception $e) {
            // 세션 저장 실패는 무시 (로그인은 성공)
            error_log('Session save failed: ' . $e->getMessage());
        }

        // 마지막 로그인 업데이트
        try {
            $db->execute("UPDATE admins SET last_login = NOW() WHERE admin_id = ?", [$admin['admin_id']]);
        } catch (Exception $e) {
            error_log('Last login update failed: ' . $e->getMessage());
        }

        // 성공 응답
        ob_clean(); // 버퍼 클리어
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

    } elseif ($method === 'GET' && $action === 'check') {
        // 로그인 상태 확인
        $isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'];

        ob_clean();
        echo json_encode([
            'success' => true,
            'logged_in' => $isLoggedIn,
            'user' => $isLoggedIn ? [
                'admin_id' => $_SESSION['admin_id'],
                'username' => $_SESSION['admin_username']
            ] : null
        ]);
        ob_end_flush();

    } elseif ($method === 'POST' && $action === 'logout') {
        // 로그아웃
        session_destroy();
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Logged out']);
        ob_end_flush();

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        ob_end_flush();
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ob_end_flush();
}
