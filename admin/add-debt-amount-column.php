<?php
/**
 * users 테이블에 debt_amount 컬럼 추가
 * 외상금액 관리를 위한 마이그레이션
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "<h1>Debt Amount 컬럼 추가</h1>\n";
echo "<pre>\n";

try {
    $db = Database::getInstance();

    // 1. 컬럼 존재 확인
    $columns = $db->select("SHOW COLUMNS FROM users LIKE 'debt_amount'");

    if (!empty($columns)) {
        echo "✅ debt_amount 컬럼이 이미 존재합니다.\n\n";

        // 현재 데이터 확인
        $stats = $db->selectOne("
            SELECT
                COUNT(*) as total_users,
                COUNT(CASE WHEN debt_amount > 0 THEN 1 END) as users_with_debt,
                SUM(debt_amount) as total_debt
            FROM users
        ");

        echo "현재 상태:\n";
        echo "- 전체 회원: " . $stats['total_users'] . "명\n";
        echo "- 외상 보유: " . $stats['users_with_debt'] . "명\n";
        echo "- 총 외상: $" . number_format($stats['total_debt'], 2) . "\n";
        exit;
    }

    echo "debt_amount 컬럼을 추가합니다...\n\n";

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
        ADD COLUMN debt_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT '외상 금액 (출금 시 차감)'
        AFTER total_withdrawn
    ");

    echo "✅ debt_amount 컬럼이 추가되었습니다.\n\n";

    // 3. 인덱스 추가 (옵션)
    $db->execute("
        ALTER TABLE users
        ADD INDEX idx_debt_amount (debt_amount)
    ");

    echo "✅ 인덱스가 추가되었습니다.\n\n";

    // 4. 최종 확인
    $finalColumns = $db->select("SHOW COLUMNS FROM users LIKE 'debt_amount'");

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

    echo "\n사용법:\n";
    echo "- 외상금액은 출금 시 실수령액에서 차감됩니다.\n";
    echo "- 잔액 = 실수령액 - 외상금액\n";
    echo "- 회원별로 외상금액을 설정하려면 users 테이블의 debt_amount 컬럼을 수정하세요.\n";

} catch (Exception $e) {
    echo "\n\n치명적 에러:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n</pre>";
?>
