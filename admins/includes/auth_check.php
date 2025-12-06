<?php
/**
 * Super Admin Authentication Middleware
 * 모든 Super Admin 페이지에서 include하여 권한 확인
 */

session_start();

// Super Admin 로그인 확인
if (!isset($_SESSION['super_admin_logged_in']) || !$_SESSION['super_admin_logged_in']) {
    // 로그인되지 않음 - 로그인 페이지로 리다이렉트
    header('Location: login.php');
    exit;
}

// Super Admin 권한 확인
if (!isset($_SESSION['super_admin_role']) || $_SESSION['super_admin_role'] !== 'super_admin') {
    // Super Admin이 아님 - 에러 페이지
    http_response_code(403);
    echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f3f4f6;
            margin: 0;
        }
        .error-box {
            background: white;
            padding: 60px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        .error-box h1 {
            color: #dc2626;
            font-size: 3em;
            margin-bottom: 20px;
        }
        .error-box p {
            color: #6b7280;
            font-size: 1.1em;
            margin-bottom: 30px;
        }
        .error-box a {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>🚫 Access Denied</h1>
        <p>Super Admin 권한이 필요합니다.<br>일반 관리자는 /admin 페이지를 이용하세요.</p>
        <a href="login.php">로그인 페이지로 이동</a>
    </div>
</body>
</html>';
    exit;
}

// 세션 유효성 확인 (선택적)
require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getInstance();

    if (isset($_SESSION['super_admin_session_id'])) {
        $session = $db->selectOne(
            "SELECT expires_at FROM admin_sessions WHERE session_id = ?",
            [$_SESSION['super_admin_session_id']]
        );

        if (!$session || strtotime($session['expires_at']) < time()) {
            // 세션 만료
            session_destroy();
            header('Location: login.php?expired=1');
            exit;
        }

        // 세션 활동 시간 업데이트
        $db->execute(
            "UPDATE admin_sessions SET last_activity = NOW() WHERE session_id = ?",
            [$_SESSION['super_admin_session_id']]
        );
    }
} catch (Exception $e) {
    error_log('Session check error: ' . $e->getMessage());
}

// 현재 로그인한 Super Admin 정보
$currentSuperAdmin = [
    'admin_id' => $_SESSION['super_admin_id'],
    'username' => $_SESSION['super_admin_username'],
    'email' => $_SESSION['super_admin_email'],
    'role' => $_SESSION['super_admin_role']
];
?>
