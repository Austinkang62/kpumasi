<?php
/**
 * 관리자 비밀번호 변경 스크립트
 * 사용법: 브라우저에서 직접 접속하여 실행
 *
 * ⚠️ 보안 주의: 비밀번호 변경 후 이 파일을 즉시 삭제하세요!
 */

require_once __DIR__ . '/../config/database.php';

// ============================================
// 여기를 수정하세요
// ============================================
$targetUsername = 'admin1';           // 변경할 관리자 username
$newPassword = 'new_password_123';     // 새 비밀번호
// ============================================

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 비밀번호 변경</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .result {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #60a5fa;
            margin-bottom: 30px;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fbbf24;
        }
        pre {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 관리자 비밀번호 변경</h1>

        <div class="info">
            <strong>⚠️ 보안 경고</strong><br>
            이 파일은 비밀번호 변경 후 즉시 삭제해야 합니다!
        </div>

<?php
try {
    $db = Database::getInstance();

    // 1. 관리자 존재 확인
    $admin = $db->selectOne(
        "SELECT admin_id, username, email FROM admins WHERE username = ?",
        [$targetUsername]
    );

    if (!$admin) {
        echo '<div class="result error">';
        echo '<strong>❌ 오류:</strong> 관리자를 찾을 수 없습니다.<br>';
        echo "Username: <strong>{$targetUsername}</strong>";
        echo '</div>';

        // 전체 관리자 목록 표시
        $allAdmins = $db->select("SELECT admin_id, username, email, role FROM admins ORDER BY admin_id");
        echo '<h3>현재 등록된 관리자 목록:</h3>';
        echo '<pre>';
        foreach ($allAdmins as $a) {
            echo "ID: {$a['admin_id']}, Username: {$a['username']}, Role: {$a['role']}\n";
        }
        echo '</pre>';
        exit;
    }

    // 2. 비밀번호 해싱
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

    // 3. 비밀번호 업데이트
    $db->execute(
        "UPDATE admins SET password = ?, updated_at = NOW() WHERE admin_id = ?",
        [$hashedPassword, $admin['admin_id']]
    );

    // 4. 성공 메시지
    echo '<div class="result success">';
    echo '<strong>✅ 비밀번호가 성공적으로 변경되었습니다!</strong><br><br>';
    echo '<strong>관리자 정보:</strong><br>';
    echo "Username: <strong>{$admin['username']}</strong><br>";
    echo "Email: {$admin['email']}<br>";
    echo "새 비밀번호: <strong>{$newPassword}</strong><br><br>";
    echo '<strong>bcrypt 해시:</strong><br>';
    echo '<pre>' . htmlspecialchars($hashedPassword) . '</pre>';
    echo '</div>';

    echo '<div class="result warning">';
    echo '<strong>⚠️ 다음 단계:</strong><br>';
    echo '1. 새 비밀번호로 로그인 테스트<br>';
    echo '2. 로그인 성공 확인 후 이 파일 삭제<br>';
    echo '3. 보안을 위해 비밀번호를 다시 변경하는 것을 권장합니다';
    echo '</div>';

    echo '<a href="#" class="btn" onclick="if(confirm(\'정말 이 파일을 삭제하시겠습니까?\')) { deleteFile(); } return false;">이 파일 삭제하기</a>';

    // 로그 기록 (선택적)
    try {
        $db->execute(
            "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
             VALUES (?, 'password_changed_via_script', ?, ?, ?)",
            [
                $admin['admin_id'],
                json_encode(['username' => $targetUsername, 'method' => 'direct_script']),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        );
    } catch (Exception $e) {
        // 로그 실패는 무시
    }

} catch (Exception $e) {
    echo '<div class="result error">';
    echo '<strong>❌ 오류 발생:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
}
?>

    </div>

    <script>
    function deleteFile() {
        fetch('<?php echo basename(__FILE__); ?>?delete=1', {
            method: 'POST'
        }).then(() => {
            alert('파일이 삭제되었습니다.');
            window.location.href = '../admins/login.php';
        }).catch(err => {
            alert('파일 삭제 실패. 수동으로 삭제하세요: <?php echo __FILE__; ?>');
        });
    }
    </script>
</body>
</html>

<?php
// 파일 자동 삭제 기능
if (isset($_GET['delete']) && $_GET['delete'] == '1') {
    @unlink(__FILE__);
    echo json_encode(['success' => true]);
    exit;
}
?>
