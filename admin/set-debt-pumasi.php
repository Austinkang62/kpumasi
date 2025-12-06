<?php
/**
 * pumasi 회원에게 외상 $105 설정
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>pumasi 외상 설정</h1>\n";
echo "<pre>\n";

session_start();

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo "❌ 관리자 로그인이 필요합니다.\n";
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();

    // 확인 메시지
    $confirm = $_GET['confirm'] ?? '';
    if ($confirm !== 'yes') {
        echo "pumasi 회원에게 외상 \$105.00을 설정하시겠습니까?\n\n";
        echo "<a href='?confirm=yes' style='background: #ef4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>";
        echo "예, 설정하겠습니다";
        echo "</a>\n";
        echo "</pre>";
        exit;
    }

    // pumasi 회원 확인
    $user = $db->selectOne("SELECT id, user_id, debt_amount FROM users WHERE user_id = 'pumasi'");

    if (!$user) {
        echo "❌ pumasi 회원을 찾을 수 없습니다.\n";
        exit;
    }

    echo "현재 pumasi 회원 정보:\n";
    echo "- ID: {$user['id']}\n";
    echo "- user_id: {$user['user_id']}\n";
    echo "- 현재 외상: \${$user['debt_amount']}\n\n";

    // 외상 설정
    $db->execute("UPDATE users SET debt_amount = 105.00 WHERE user_id = 'pumasi'");

    echo "✅ 외상 설정 완료!\n\n";

    // 업데이트된 정보 확인
    $updatedUser = $db->selectOne("SELECT id, user_id, debt_amount FROM users WHERE user_id = 'pumasi'");

    echo "업데이트된 정보:\n";
    echo "- ID: {$updatedUser['id']}\n";
    echo "- user_id: {$updatedUser['user_id']}\n";
    echo "- 외상: \${$updatedUser['debt_amount']}\n\n";

    echo "====================================\n";
    echo "이제 회원 관리 페이지를 확인하세요:\n";
    echo "/admin/pages/users-manage.html\n";
    echo "====================================\n";

} catch (Exception $e) {
    echo "❌ 오류 발생\n";
    echo "오류: " . $e->getMessage() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
