<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Cron Test</h1>";
echo "<pre>";

try {
    echo "1. Config 로드 시도...\n";
    require_once __DIR__ . '/../config/database.php';
    echo "   ✓ Config 로드 성공\n\n";

    echo "2. Database 연결 시도...\n";
    $db = Database::getInstance();
    echo "   ✓ Database 연결 성공\n\n";

    echo "3. Avatar Points >= 100 회원 조회...\n";
    $users = $db->select("
        SELECT id, user_id, name, avatar_points
        FROM users
        WHERE avatar_points >= 100.00
          AND deleted_at IS NULL
          AND is_avatar = 0
        ORDER BY avatar_points DESC
        LIMIT 5
    ");
    echo "   ✓ 조회 성공: " . count($users) . "명\n\n";

    if (empty($users)) {
        echo "생성 대기 중인 아바타가 없습니다.\n";
    } else {
        echo "생성 대기 중인 회원:\n";
        foreach ($users as $user) {
            $avatarPoints = floatval($user['avatar_points']);
            $avatarCount = floor($avatarPoints / 100);
            echo "  - {$user['user_id']}: \${$avatarPoints} ({$avatarCount}개 생성 가능)\n";
        }
    }

    echo "\n✅ 모든 테스트 통과!\n";

} catch (Exception $e) {
    echo "\n❌ 오류 발생:\n";
    echo "메시지: " . $e->getMessage() . "\n";
    echo "파일: " . $e->getFile() . "\n";
    echo "라인: " . $e->getLine() . "\n";
    echo "\n스택 트레이스:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
