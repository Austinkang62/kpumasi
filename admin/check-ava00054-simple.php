<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain; charset=utf-8');

$db = Database::getInstance();

$user = $db->selectOne("
    SELECT user_id, sponsor_id, sponsor_position, referral_id, created_at
    FROM users
    WHERE user_id = 'AVA00054'
");

if (!$user) {
    echo "AVA00054를 찾을 수 없습니다.";
    exit;
}

echo "AVA00054 정보:\n";
echo "후원인(sponsor_id): {$user['sponsor_id']}\n";
echo "후원위치(sponsor_position): {$user['sponsor_position']}\n";
echo "추천인ID(referral_id): {$user['referral_id']}\n";
echo "가입일: {$user['created_at']}\n\n";

// 발생한 보너스 확인
$bonuses = $db->select("
    SELECT bonus_type, SUM(amount) as total
    FROM bonuses
    WHERE from_user_id = (SELECT id FROM users WHERE user_id = 'AVA00054')
    GROUP BY bonus_type
");

echo "발생한 보너스:\n";
foreach ($bonuses as $b) {
    echo "  {$b['bonus_type']}: \${$b['total']}\n";
}
