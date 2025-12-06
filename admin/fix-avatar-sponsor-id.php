<?php
/**
 * sponsor_id 즉시 수정
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance();

echo "<html><head><title>Fix Avatar Sponsor ID</title>";
echo "<style>
body{font-family:monospace;padding:20px;background:#0f172a;color:#e2e8f0;}
h1{color:#fbbf24;}
.success{color:#10b981;font-weight:bold;}
.error{color:#ef4444;font-weight:bold;}
.info{background:#1e293b;padding:15px;border-radius:8px;margin:15px 0;border-left:4px solid #3b82f6;}
pre{background:#000;padding:15px;border-radius:5px;color:#10b981;}
</style></head><body>";

echo "<h1>🔧 SHCN5583_avatar_1 sponsor_id 수정</h1>";

// 현재 상태 확인
$before = $db->selectOne("
    SELECT sponsor_id, sponsor_position, referral_id
    FROM users
    WHERE user_id = 'SHCN5583_avatar_1'
");

echo "<h2>수정 전 상태:</h2>";
echo "<pre>";
echo "sponsor_id: " . ($before['sponsor_id'] ?? 'NULL') . "\n";
echo "sponsor_position: " . ($before['sponsor_position'] ?? 'NULL') . "\n";
echo "referral_id: " . ($before['referral_id'] ?? 'NULL') . "\n";
echo "</pre>";

// 수정 실행
try {
    echo "<h2>🔧 수정 실행 중...</h2>";

    $db->execute("
        UPDATE users
        SET sponsor_id = 38,
            sponsor_position = 1
        WHERE user_id = 'SHCN5583_avatar_1'
    ");

    echo "<p class='success'>✅ SQL 실행 완료!</p>";

    // 수정 후 상태 확인
    $after = $db->selectOne("
        SELECT sponsor_id, sponsor_position, referral_id
        FROM users
        WHERE user_id = 'SHCN5583_avatar_1'
    ");

    echo "<h2>수정 후 상태:</h2>";
    echo "<pre>";
    echo "sponsor_id: " . ($after['sponsor_id'] ?? 'NULL');
    if ($after['sponsor_id'] == 38) {
        echo " ✅ (SHCN5583)\n";
    } else {
        echo " ❌\n";
    }

    echo "sponsor_position: " . ($after['sponsor_position'] ?? 'NULL');
    if ($after['sponsor_position'] == 1) {
        echo " ✅ (LEFT)\n";
    } else {
        echo " ❌\n";
    }

    echo "referral_id: " . ($after['referral_id'] ?? 'NULL');
    if ($after['referral_id'] == 38) {
        echo " ✅ (SHCN5583)\n";
    } else {
        echo " ❌\n";
    }
    echo "</pre>";

    echo "<div class='info'>";
    echo "<strong>✅ 수정 완료!</strong><br><br>";
    echo "이제 다음 페이지에서 확인하세요:<br>";
    echo "• <a href='test-spillover-check.php?user_id=SHCN5583' style='color:#3b82f6;'>스필오버 위치 확인</a><br>";
    echo "• <a href='check-avatar-sponsor.php' style='color:#3b82f6;'>아바타 데이터 확인</a><br>";
    echo "</div>";

} catch (Exception $e) {
    echo "<p class='error'>❌ 오류 발생: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
