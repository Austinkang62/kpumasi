<?php
/**
 * pumasi의 자식 노드가 왜 안보이는지 확인
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance();

echo "<html><head><title>Pumasi Children Debug</title>";
echo "<style>
body{font-family:monospace;padding:20px;background:#0f172a;color:#e2e8f0;}
h2{color:#fbbf24;margin-top:30px;}
pre{background:#1e293b;padding:15px;border-radius:8px;overflow:auto;color:#10b981;}
table{border-collapse:collapse;margin:20px 0;width:100%;}
th,td{border:1px solid #334155;padding:10px;text-align:left;background:#1e293b;}
th{background:#0f172a;color:#fbbf24;}
.error{color:#ef4444;}
.success{color:#10b981;}
</style></head><body>";

echo "<h1>🔍 Pumasi 자식 노드 디버깅</h1>";

// pumasi 정보
echo "<h2>1. pumasi 정보</h2>";
$pumasi = $db->selectOne('SELECT id, user_id, sponsor_id, referral_id FROM users WHERE user_id = ?', ['pumasi']);
echo "<pre>";
print_r($pumasi);
echo "</pre>";

// pumasi의 모든 잠재적 자식 찾기
echo "<h2>2. referral_id = {$pumasi['id']} (pumasi가 추천한 사람들)</h2>";
$referrals = $db->select('SELECT id, user_id, sponsor_id, referral_id, is_avatar, sponsor_position FROM users WHERE referral_id = ?', [$pumasi['id']]);
echo "<table>";
echo "<tr><th>ID</th><th>User ID</th><th>sponsor_id</th><th>referral_id</th><th>is_avatar</th><th>sponsor_position</th></tr>";
foreach ($referrals as $r) {
    echo "<tr>";
    echo "<td>{$r['id']}</td>";
    echo "<td>{$r['user_id']}</td>";
    echo "<td>{$r['sponsor_id']}</td>";
    echo "<td>{$r['referral_id']}</td>";
    echo "<td>{$r['is_avatar']}</td>";
    echo "<td>{$r['sponsor_position']}</td>";
    echo "</tr>";
}
echo "</table>";

// sponsor_id로 자식 찾기 (숫자 ID)
echo "<h2>3. sponsor_id = {$pumasi['id']} (pumasi의 바이너리 트리 자식)</h2>";
$children = $db->select('SELECT id, user_id, sponsor_id, referral_id, is_avatar, sponsor_position FROM users WHERE sponsor_id = ? ORDER BY sponsor_position ASC', [$pumasi['id']]);
echo "<table>";
echo "<tr><th>ID</th><th>User ID</th><th>sponsor_id</th><th>referral_id</th><th>is_avatar</th><th>sponsor_position</th></tr>";
foreach ($children as $c) {
    echo "<tr>";
    echo "<td>{$c['id']}</td>";
    echo "<td>{$c['user_id']}</td>";
    echo "<td>{$c['sponsor_id']}</td>";
    echo "<td>{$c['referral_id']}</td>";
    echo "<td>{$c['is_avatar']}</td>";
    echo "<td>{$c['sponsor_position']}</td>";
    echo "</tr>";
}
echo "</table>";

// 모든 회원 출력 (처음 20명)
echo "<h2>4. 전체 회원 (처음 20명)</h2>";
$allUsers = $db->select('SELECT id, user_id, sponsor_id, referral_id, is_avatar, sponsor_position FROM users ORDER BY id ASC LIMIT 20');
echo "<table>";
echo "<tr><th>ID</th><th>User ID</th><th>sponsor_id</th><th>referral_id</th><th>is_avatar</th><th>sponsor_position</th></tr>";
foreach ($allUsers as $u) {
    $highlight = '';
    if ($u['user_id'] == 'pumasi') $highlight = 'style="background:#1e40af;"';
    if ($u['is_avatar'] == 1) $highlight = 'style="background:#7c3aed;"';
    echo "<tr $highlight>";
    echo "<td>{$u['id']}</td>";
    echo "<td>{$u['user_id']}</td>";
    echo "<td>" . ($u['sponsor_id'] ?: '<span class="error">NULL/빈값</span>') . "</td>";
    echo "<td>" . ($u['referral_id'] ?: '<span class="error">NULL</span>') . "</td>";
    echo "<td>{$u['is_avatar']}</td>";
    echo "<td>" . ($u['sponsor_position'] ?: '<span class="error">NULL/0</span>') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "</body></html>";
