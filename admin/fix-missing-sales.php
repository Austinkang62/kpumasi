<?php
/**
 * package_id는 있는데 sales 기록이 없는 회원들에게
 * 자동으로 sales 기록을 생성하는 스크립트
 */

require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "누락된 매출 기록 자동 생성 (Missing Sales Auto-Fix)\n";
echo "=================================================================\n\n";

try {
    $db = Database::getInstance();

    // package_id가 있는데 sales가 없는 회원 조회
    echo "1. 문제 회원 조회 중...\n";
    $users = $db->select("
        SELECT
            u.id,
            u.user_id,
            u.name,
            u.package_id,
            u.package_date,
            u.created_at
        FROM users u
        LEFT JOIN sales s ON u.id = s.user_id
        WHERE u.package_id > 0
        AND u.is_avatar = 0
        AND s.id IS NULL
        ORDER BY u.created_at ASC
    ");

    if (empty($users)) {
        echo "   ✅ 문제가 있는 회원이 없습니다.\n";
        exit(0);
    }

    echo "   ⚠️  발견된 문제 회원: " . count($users) . "명\n\n";

    // 패키지 정보 조회
    $packages = $db->select("SELECT * FROM packages");
    $packageMap = [];
    foreach ($packages as $pkg) {
        $packageMap[$pkg['id']] = $pkg;
    }

    echo "2. 문제 회원 목록:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($users as $idx => $user) {
        $num = $idx + 1;
        $packagePrice = $packageMap[$user['package_id']]['price'] ?? '???';
        echo "   {$num}. {$user['user_id']} ({$user['name']}) - Package {$user['package_id']} (\${$packagePrice}) - 가입일: {$user['created_at']}\n";
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // 사용자 확인
    echo "=================================================\n";
    echo "⚠️  주의사항:\n";
    echo "=================================================\n";
    echo "- 위 " . count($users) . "명의 회원에게 매출 기록을 생성합니다.\n";
    echo "- 각 회원의 package_id에 해당하는 금액으로 sales를 생성합니다.\n";
    echo "- 생성일은 회원의 package_date 또는 created_at을 사용합니다.\n";
    echo "- 상태는 'confirmed'로 설정됩니다.\n";
    echo "- 이 작업은 되돌릴 수 없습니다!\n";
    echo "=================================================\n\n";

    if (php_sapi_name() === 'cli') {
        echo "계속하시겠습니까? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);

        if (strtolower($confirmation) !== 'yes') {
            echo "\n❌ 작업이 취소되었습니다.\n";
            exit;
        }
    } else {
        if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
            echo "<h2>변경 확인</h2>";
            echo "<p>위 내용을 확인하고 <a href='?confirm=yes' style='color: red; font-weight: bold;'>여기를 클릭</a>하여 변경을 진행하세요.</p>";
            exit;
        }
    }

    echo "\n3. 매출 기록 생성 시작...\n\n";

    $db->beginTransaction();

    try {
        $successCount = 0;
        $errorCount = 0;

        foreach ($users as $user) {
            $userId = $user['id'];
            $packageId = $user['package_id'];
            $packagePrice = $packageMap[$packageId]['price'];
            $saleDate = $user['package_date'] ?? $user['created_at'];

            try {
                // sales 레코드 생성
                $db->execute("
                    INSERT INTO sales (
                        user_id,
                        package_id,
                        amount,
                        payment_method,
                        status,
                        confirmed_at,
                        created_at
                    ) VALUES (?, ?, ?, 'USDT_TRC20', 'confirmed', ?, ?)
                ", [
                    $userId,
                    $packageId,
                    $packagePrice,
                    $saleDate,
                    $saleDate
                ]);

                $successCount++;
                echo "   ✅ {$user['user_id']} - \${$packagePrice} 매출 생성 완료\n";

            } catch (Exception $e) {
                $errorCount++;
                echo "   ❌ {$user['user_id']} - 오류: " . $e->getMessage() . "\n";
            }
        }

        $db->commit();

        echo "\n=================================================\n";
        echo "처리 결과\n";
        echo "=================================================\n";
        echo "총 처리: " . count($users) . "명\n";
        echo "성공: {$successCount}명\n";
        echo "실패: {$errorCount}명\n";
        echo "=================================================\n\n";

        if ($successCount > 0) {
            echo "⚠️  중요: 보너스 재계산이 필요합니다!\n";
            echo "→ 생성된 매출에 대한 보너스를 지급하려면 별도의 보너스 재계산 스크립트를 실행해야 합니다.\n\n";
        }

        echo "=================================================\n";
        echo "✅ 매출 기록 생성 완료!\n";
        echo "=================================================\n";

    } catch (Exception $e) {
        $db->rollback();
        echo "\n❌ 오류 발생! 모든 변경사항이 롤백되었습니다.\n";
        echo "오류 메시지: " . $e->getMessage() . "\n";
        throw $e;
    }

} catch (Exception $e) {
    echo "\n❌ 스크립트 실행 오류:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
