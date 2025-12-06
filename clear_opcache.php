<?php
/**
 * OPcache 클리어
 */

echo "<h1>OPcache 클리어</h1>";
echo "<pre>";

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✅ OPcache가 성공적으로 클리어되었습니다.\n";
    } else {
        echo "❌ OPcache 클리어 실패\n";
    }
} else {
    echo "⚠️ OPcache가 활성화되어 있지 않습니다.\n";
}

echo "\nOPcache 상태:\n";
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "Enabled: " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "\n";
    if (isset($status['opcache_statistics'])) {
        echo "Cached scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
    }
} else {
    echo "OPcache 상태를 확인할 수 없습니다.\n";
}

echo "\n</pre>";

echo "<h2>Stats API 직접 테스트</h2>";
echo "<pre>";

// 세션 시작
session_start();
$_SESSION['admin_logged_in'] = true;

// Database 직접 연결
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

echo "Database 직접 쿼리:\n";
$result = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE package_id > 0");
$userCount = $result['count'] ?? 0;
$calculatedSales = $userCount * 100;

echo "package_id > 0 사용자 수: {$userCount}\n";
echo "계산된 총 매출: \${$calculatedSales}\n";

echo "</pre>";

echo "<p><a href='admin/index.html'>Admin 페이지로 이동</a></p>";
?>
