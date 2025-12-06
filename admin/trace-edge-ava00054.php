<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain; charset=utf-8');

$db = Database::getInstance();

echo "=== AVA00054 엣지 조건 추적 ===\n\n";

// AVA00054 정보
$user = $db->selectOne("
    SELECT id, user_id, sponsor_id, sponsor_position
    FROM users
    WHERE user_id = 'AVA00054'
");

echo "AVA00054: 후원인={$user['sponsor_id']}, 위치=" . ($user['sponsor_position'] == 1 ? '좌측(1)' : '우측(2)') . "\n\n";

// 상위로 올라가면서 방향 전환 찾기
$currentUserCode = $user['sponsor_id']; // AVA00039부터 시작
$lastPosition = intval($user['sponsor_position']); // 1 (좌측)
$level = 1;

echo "상위 트리 추적:\n";
echo "현재 위치: " . ($lastPosition == 1 ? '좌측(1)' : '우측(2)') . "\n\n";

while ($currentUserCode && $level <= 20) {
    $current = $db->selectOne("
        SELECT user_id, sponsor_id, sponsor_position
        FROM users
        WHERE user_id = ?
    ", [$currentUserCode]);

    if (!$current) {
        echo "레벨 {$level}: {$currentUserCode} - 사용자를 찾을 수 없음\n";
        break;
    }

    $posText = $current['sponsor_position'] == 1 ? '좌측(1)' : ($current['sponsor_position'] == 2 ? '우측(2)' : 'NULL');

    echo "레벨 {$level}: {$current['user_id']} → 후원인: " . ($current['sponsor_id'] ?? 'NULL') . ", 위치: {$posText}";

    if (!$current['sponsor_id']) {
        echo " [최상위]\n";
        echo "\n❌ 최상위까지 방향 전환 없음 - 엣지 미발생\n";
        break;
    }

    if (!$current['sponsor_position']) {
        echo " [sponsor_position NULL]\n";
        echo "\n❌ sponsor_position이 NULL - 엣지 조건 체크 불가\n";
        break;
    }

    $currentPosition = intval($current['sponsor_position']);

    // 방향 전환 확인
    if ($currentPosition !== $lastPosition) {
        echo " ⭐ 방향 전환 발생!\n";
        echo "\n✅ 엣지 수령자: {$current['sponsor_id']}\n";
        echo "   ({$current['user_id']}의 부모)\n";

        // 실제 엣지 보너스 발생 여부 확인
        $edgeBonus = $db->selectOne("
            SELECT b.id, b.amount, u.user_id as receiver
            FROM bonuses b
            JOIN users u ON b.user_id = u.id
            WHERE b.from_user_id = ? AND b.bonus_type = 'edge'
        ", [$user['id']]);

        if ($edgeBonus) {
            echo "\n✅ 엣지 보너스 발생 확인: {$edgeBonus['receiver']} - \${$edgeBonus['amount']}\n";
        } else {
            echo "\n❌ 엣지 보너스 미발생! (조건 충족했으나 실제 미지급)\n";
        }
        break;
    }

    echo "\n";
    $currentUserCode = $current['sponsor_id'];
    $lastPosition = $currentPosition;
    $level++;
}

if ($level > 20) {
    echo "\n⚠️ 20레벨까지 추적했으나 방향 전환 없음\n";
}
