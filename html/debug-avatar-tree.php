<?php
/**
 * 아바타가 조직도에 나타나지 않는 문제 디버깅
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance();

echo "<html><head><title>Avatar Tree Debug</title>";
echo "<style>
body{font-family:monospace;padding:20px;background:#0f172a;color:#e2e8f0;}
h2{color:#fbbf24;margin-top:30px;}
pre{background:#1e293b;padding:15px;border-radius:8px;overflow:auto;color:#10b981;}
.error{color:#ef4444;}
.success{color:#10b981;}
</style></head><body>";

echo "<h1>🔍 아바타 조직도 디버깅</h1>";

// SHCN5583_avatar_1 확인
echo "<h2>1. SHCN5583_avatar_1 정보</h2>";
$avatar = $db->selectOne('SELECT id, user_id, sponsor_id, sponsor_position, referral_id, is_avatar FROM users WHERE user_id = ?', ['SHCN5583_avatar_1']);
echo "<pre>";
print_r($avatar);
echo "</pre>";

// SHCN5583 확인
echo "<h2>2. SHCN5583 (부모) 정보</h2>";
$parent = $db->selectOne('SELECT id, user_id FROM users WHERE user_id = ?', ['SHCN5583']);
echo "<pre>";
print_r($parent);
echo "</pre>";

if ($parent) {
    // sponsor_id로 자식 조회 (숫자 ID 기준)
    echo "<h2>3. sponsor_id = {$parent['id']} (숫자) 로 조회</h2>";
    $childrenById = $db->select('SELECT id, user_id, sponsor_id, sponsor_position, is_avatar FROM users WHERE sponsor_id = ?', [$parent['id']]);
    echo "<pre>";
    echo "결과 개수: " . count($childrenById) . "\n";
    print_r($childrenById);
    echo "</pre>";

    // sponsor_id로 자식 조회 (user_id 문자열 기준)
    echo "<h2>4. sponsor_id = '{$parent['user_id']}' (문자열) 로 조회</h2>";
    $childrenByUserId = $db->select('SELECT id, user_id, sponsor_id, sponsor_position, is_avatar FROM users WHERE sponsor_id = ?', [$parent['user_id']]);
    echo "<pre>";
    echo "결과 개수: " . count($childrenByUserId) . "\n";
    print_r($childrenByUserId);
    echo "</pre>";
}

// API가 어떻게 조회하는지 시뮬레이션
echo "<h2>5. 조직도 API 시뮬레이션</h2>";
echo "<h3>5-1. 관리자 API (/api/admin/organization/get-tree.php)</h3>";
if ($parent) {
    echo "<p>현재 코드: <code>WHERE sponsor_id = ? (user_id 문자열 전달)</code></p>";
    $apiChildren = $db->select("SELECT id, user_id FROM users WHERE sponsor_id = ? ORDER BY created_at ASC", [$parent['user_id']]);
    echo "<pre>";
    echo "결과 개수: " . count($apiChildren) . "\n";
    print_r($apiChildren);
    echo "</pre>";

    echo "<p>올바른 코드: <code>WHERE sponsor_id = ? (숫자 ID 전달)</code></p>";
    $correctChildren = $db->select("SELECT id, user_id FROM users WHERE sponsor_id = ? ORDER BY created_at ASC", [$parent['id']]);
    echo "<pre>";
    echo "결과 개수: " . count($correctChildren) . "\n";
    print_r($correctChildren);
    echo "</pre>";
}

// sponsor_id 필드 타입 확인
echo "<h2>6. users 테이블 sponsor_id 필드 타입</h2>";
$tableInfo = $db->select("DESCRIBE users");
echo "<pre>";
foreach ($tableInfo as $col) {
    if ($col['Field'] === 'sponsor_id' || $col['Field'] === 'referral_id' || $col['Field'] === 'id') {
        print_r($col);
    }
}
echo "</pre>";

echo "</body></html>";
