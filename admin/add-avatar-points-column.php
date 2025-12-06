<?php
/**
 * users 테이블에 avatar_points 컬럼 추가
 * 아바타 포인트 별도 관리를 위한 마이그레이션
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "<h1>Avatar Points 컬럼 추가</h1>\n";
echo "<pre>\n";

try {
    $db = Database::getInstance();

    // 1. 컬럼 존재 확인
    $columns = $db->select("SHOW COLUMNS FROM users LIKE 'avatar_points'");

    if (!empty($columns)) {
        echo "✅ avatar_points 컬럼이 이미 존재합니다.\n\n";

        // 현재 데이터 확인
        $stats = $db->selectOne("
            SELECT
                COUNT(*) as total_users,
                COUNT(CASE WHEN avatar_points > 0 THEN 1 END) as users_with_points,
                SUM(avatar_points) as total_points
            FROM users
        ");

        echo "현재 상태:\n";
        echo "- 전체 회원: " . $stats['total_users'] . "명\n";
        echo "- 포인트 보유: " . $stats['users_with_points'] . "명\n";
        echo "- 총 포인트: ℙ" . number_format($stats['total_points'], 2) . "\n";
        exit;
    }

    echo "avatar_points 컬럼을 추가합니다...\n\n";

    // 확인
    if (php_sapi_name() !== 'cli') {
        $confirm = $_GET['confirm'] ?? '';
        if ($confirm !== 'yes') {
            echo "<a href='?confirm=yes' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>";
            echo "예, 추가하겠습니다 (클릭)";
            echo "</a>\n";
            echo "</pre>";
            exit;
        }
    }

    // 2. 컬럼 추가
    $db->execute("
        ALTER TABLE users
        ADD COLUMN avatar_points DECIMAL(15,2) DEFAULT 0.00 COMMENT '아바타 포인트 (아바타 생성으로 획득한 포인트)'
        AFTER available_bonus
    ");

    echo "✅ avatar_points 컬럼이 추가되었습니다.\n\n";

    // 3. 인덱스 추가 (옵션)
    $db->execute("
        ALTER TABLE users
        ADD INDEX idx_avatar_points (avatar_points)
    ");

    echo "✅ 인덱스가 추가되었습니다.\n\n";

    // 4. 최종 확인
    $finalColumns = $db->select("SHOW COLUMNS FROM users LIKE 'avatar_points'");

    echo "====================================\n";
    echo "완료!\n";
    echo "====================================\n";
    echo "컬럼 정보:\n";
    foreach ($finalColumns as $col) {
        echo "- Field: " . $col['Field'] . "\n";
        echo "- Type: " . $col['Type'] . "\n";
        echo "- Default: " . ($col['Default'] ?? 'NULL') . "\n";
        echo "- Comment: " . ($col['Comment'] ?? '') . "\n";
    }

} catch (Exception $e) {
    echo "\n\n치명적 에러:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n</pre>";
?>
