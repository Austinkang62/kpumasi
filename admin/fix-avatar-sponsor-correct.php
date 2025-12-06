<?php
/**
 * 아바타의 sponsor_id를 올바르게 수정 (user_id 문자열로)
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance();

echo "<html><head><title>Fix Avatar Sponsor - Correct</title>";
echo "<style>
body{font-family:monospace;padding:20px;background:#0f172a;color:#e2e8f0;}
h1{color:#fbbf24;}
.success{color:#10b981;font-weight:bold;}
.error{color:#ef4444;font-weight:bold;}
.info{background:#1e293b;padding:15px;border-radius:8px;margin:15px 0;border-left:4px solid #3b82f6;}
pre{background:#000;padding:15px;border-radius:5px;color:#10b981;}
</style></head><body>";

echo "<h1>🔧 아바타 sponsor_id 올바르게 수정</h1>";

// 모든 아바타 찾기
$avatars = $db->select("SELECT id, user_id, sponsor_id, referral_id, sponsor_position FROM users WHERE is_avatar = 1");

echo "<h2>발견된 아바타: " . count($avatars) . "개</h2>";

foreach ($avatars as $avatar) {
    echo "<div class='info'>";
    echo "<h3>🤖 {$avatar['user_id']}</h3>";

    echo "<h4>수정 전:</h4>";
    echo "<pre>";
    echo "sponsor_id: " . ($avatar['sponsor_id'] ?: 'NULL/빈값') . "\n";
    echo "sponsor_position: " . ($avatar['sponsor_position'] ?: 'NULL') . "\n";
    echo "referral_id: " . ($avatar['referral_id'] ?: 'NULL') . "\n";
    echo "</pre>";

    // referral_id로 부모 user_id 찾기
    if ($avatar['referral_id']) {
        $parent = $db->selectOne("SELECT id, user_id FROM users WHERE id = ?", [$avatar['referral_id']]);

        if ($parent) {
            echo "<h4>부모 정보:</h4>";
            echo "<pre>";
            echo "부모 ID: {$parent['id']}\n";
            echo "부모 user_id: {$parent['user_id']}\n";
            echo "</pre>";

            // sponsor_id를 부모의 user_id(문자열)로 수정
            try {
                $db->execute("
                    UPDATE users
                    SET sponsor_id = ?,
                        sponsor_position = 1
                    WHERE id = ?
                ", [$parent['user_id'], $avatar['id']]);

                echo "<p class='success'>✅ 수정 완료!</p>";

                // 수정 후 확인
                $after = $db->selectOne("SELECT sponsor_id, sponsor_position FROM users WHERE id = ?", [$avatar['id']]);
                echo "<h4>수정 후:</h4>";
                echo "<pre>";
                echo "sponsor_id: {$after['sponsor_id']}\n";
                echo "sponsor_position: {$after['sponsor_position']}\n";
                echo "</pre>";

            } catch (Exception $e) {
                echo "<p class='error'>❌ 오류: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p class='error'>❌ 부모를 찾을 수 없습니다 (referral_id: {$avatar['referral_id']})</p>";
        }
    } else {
        echo "<p class='error'>❌ referral_id가 NULL입니다!</p>";
    }

    echo "</div>";
}

echo "<div class='info'>";
echo "<strong>✅ 모든 아바타 처리 완료!</strong><br><br>";
echo "이제 조직도를 새로고침하세요:<br>";
echo "• <a href='/html/organization.html' style='color:#3b82f6;'>조직도 보기</a><br>";
echo "</div>";

echo "</body></html>";
