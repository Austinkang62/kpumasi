<?php
/**
 * packages 테이블 기본 데이터 삽입 스크립트
 */

require_once __DIR__ . '/../config/database.php';

echo "=================================================\n";
echo "Packages 테이블 데이터 수정\n";
echo "=================================================\n\n";

try {
    $db = Database::getInstance();

    // 현재 패키지 확인
    echo "1. 현재 패키지 데이터 확인...\n";
    $packages = $db->select("SELECT * FROM packages");

    if (empty($packages)) {
        echo "   ⚠️  packages 테이블이 비어있습니다.\n\n";
    } else {
        echo "   현재 패키지:\n";
        foreach ($packages as $pkg) {
            echo "   - ID: {$pkg['id']}, Name: {$pkg['name']}, Price: \${$pkg['price']}\n";
        }
        echo "\n";
    }

    // 기본 패키지 데이터 삽입/업데이트
    echo "2. 기본 패키지 데이터 삽입/업데이트...\n";

    $db->beginTransaction();

    try {
        // Package 1: $50
        $db->execute("
            INSERT INTO packages (id, name, price, commission_level_1_5, commission_level_6_15, avatar_trigger, avatar_multiplier, max_earning, status)
            VALUES (1, 'Package \$50', 50.00, 4.00, 2.00, 150.00, 1, 131192.00, 'active')
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                price = VALUES(price),
                commission_level_1_5 = VALUES(commission_level_1_5),
                commission_level_6_15 = VALUES(commission_level_6_15),
                avatar_trigger = VALUES(avatar_trigger),
                avatar_multiplier = VALUES(avatar_multiplier),
                max_earning = VALUES(max_earning),
                status = VALUES(status)
        ");

        // Package 2: $100
        $db->execute("
            INSERT INTO packages (id, name, price, commission_level_1_5, commission_level_6_15, avatar_trigger, avatar_multiplier, max_earning, status)
            VALUES (2, 'Package \$100', 100.00, 9.00, 4.50, 300.00, 2, 295182.00, 'active')
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                price = VALUES(price),
                commission_level_1_5 = VALUES(commission_level_1_5),
                commission_level_6_15 = VALUES(commission_level_6_15),
                avatar_trigger = VALUES(avatar_trigger),
                avatar_multiplier = VALUES(avatar_multiplier),
                max_earning = VALUES(max_earning),
                status = VALUES(status)
        ");

        $db->commit();
        echo "   ✅ 패키지 데이터 삽입/업데이트 완료\n\n";

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }

    // 결과 확인
    echo "3. 결과 확인...\n";
    $packages = $db->select("SELECT * FROM packages ORDER BY id");

    echo "   업데이트된 패키지:\n";
    foreach ($packages as $pkg) {
        echo "   - ID: {$pkg['id']}\n";
        echo "     Name: {$pkg['name']}\n";
        echo "     Price: \${$pkg['price']}\n";
        echo "     Level 1-5 Commission: \${$pkg['commission_level_1_5']}\n";
        echo "     Level 6-15 Commission: \${$pkg['commission_level_6_15']}\n";
        echo "     Avatar Trigger: \${$pkg['avatar_trigger']}\n";
        echo "     Avatar Multiplier: {$pkg['avatar_multiplier']}x\n";
        echo "     Max Earning: \${$pkg['max_earning']}\n";
        echo "     Status: {$pkg['status']}\n\n";
    }

    echo "=================================================\n";
    echo "✅ 완료! 이제 audit 스크립트를 다시 실행하세요.\n";
    echo "=================================================\n";

} catch (Exception $e) {
    echo "\n❌ 오류 발생:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
