<?php
/**
 * 보너스 누락 회원에 대한 보너스 재계산 스크립트
 * 매출은 있는데 보너스가 없는 회원들에게 보너스 지급
 */

set_time_limit(300); // 5분
ini_set('max_execution_time', 300);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/BonusDistributor.php';

echo "=================================================================\n";
echo "보너스 재계산 (Bonus Recalculation)\n";
echo "=================================================================\n\n";

try {
    $db = Database::getInstance();

    // 1. 보너스가 누락된 회원 찾기
    echo "1. 보너스 누락 회원 조회 중...\n";

    // sales는 있는데 bonuses가 없는 회원들
    $usersWithoutBonuses = $db->select("
        SELECT DISTINCT
            u.id,
            u.user_id,
            u.name,
            u.referral_id,
            u.sponsor_id,
            s.id as sale_id,
            s.amount,
            s.created_at as sale_date
        FROM sales s
        INNER JOIN users u ON s.user_id = u.id
        LEFT JOIN bonuses b ON b.from_user_id = u.id
        WHERE b.id IS NULL
        AND u.is_avatar = 0
        ORDER BY s.created_at ASC
    ");

    if (empty($usersWithoutBonuses)) {
        echo "   ✅ 보너스 누락 회원이 없습니다.\n";
        exit(0);
    }

    echo "   ⚠️  발견된 누락 회원: " . count($usersWithoutBonuses) . "명\n\n";

    // 2. 누락 회원 목록 출력
    echo "2. 보너스 누락 회원 목록:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($usersWithoutBonuses as $idx => $user) {
        $num = $idx + 1;
        $name = $user['name'] ? "({$user['name']})" : "";
        echo "   {$num}. {$user['user_id']} {$name} - \${$user['amount']} - {$user['sale_date']}\n";
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // 3. 사용자 확인
    echo "=================================================\n";
    echo "⚠️  주의사항:\n";
    echo "=================================================\n";
    echo "- 위 " . count($usersWithoutBonuses) . "명의 회원에게 보너스를 계산합니다.\n";
    echo "- 4가지 보너스를 모두 계산합니다:\n";
    echo "  1) Referral 보너스 (25%)\n";
    echo "  2) Edge 보너스 (25%)\n";
    echo "  3) Matching 보너스 (25%)\n";
    echo "  4) Rollup 보너스 (25단계 × 1%)\n";
    echo "- 각 보너스는 65% 캐시 + 35% 아바타 포인트로 지급됩니다.\n";
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

    echo "\n3. 보너스 재계산 시작...\n\n";

    $db->beginTransaction();

    try {
        $distributor = new BonusDistributor();
        $successCount = 0;
        $errorCount = 0;
        $totalBonusDistributed = 0;

        foreach ($usersWithoutBonuses as $user) {
            $userId = $user['id'];
            $userCode = $user['user_id'];
            $packageAmount = floatval($user['amount']);

            try {
                echo "   처리 중: {$userCode}";

                // 보너스 배포
                $result = $distributor->distributeAllBonuses($userId, $packageAmount);

                if ($result['success']) {
                    $successCount++;
                    $totalBonusDistributed += $result['total_distributed'];

                    echo " ✅ 완료 (총 \$" . number_format($result['total_distributed'], 2) . " 지급)\n";

                    // 상세 내역 출력 (옵션)
                    if (isset($result['bonuses']['referral'])) {
                        echo "      - Referral: \$" . number_format($result['bonuses']['referral']['amount'], 2) . "\n";
                    }
                    if (isset($result['bonuses']['edge'])) {
                        echo "      - Edge: \$" . number_format($result['bonuses']['edge']['amount'], 2) . "\n";
                    }
                    if (isset($result['bonuses']['matching'])) {
                        echo "      - Matching: \$" . number_format($result['bonuses']['matching']['amount'], 2) . "\n";
                    }
                    if (isset($result['bonuses']['rollup'])) {
                        $rollupTotal = array_sum(array_column($result['bonuses']['rollup'], 'amount'));
                        echo "      - Rollup: \$" . number_format($rollupTotal, 2) . " (" . count($result['bonuses']['rollup']) . "단계)\n";
                    }
                } else {
                    $errorCount++;
                    echo " ❌ 실패: " . ($result['error'] ?? 'Unknown error') . "\n";
                }

            } catch (Exception $e) {
                $errorCount++;
                echo " ❌ 오류: " . $e->getMessage() . "\n";
            }
        }

        $db->commit();

        echo "\n=================================================\n";
        echo "처리 결과\n";
        echo "=================================================\n";
        echo "총 처리: " . count($usersWithoutBonuses) . "명\n";
        echo "성공: {$successCount}명\n";
        echo "실패: {$errorCount}명\n";
        echo "총 보너스 지급액: \$" . number_format($totalBonusDistributed, 2) . "\n";
        echo "=================================================\n\n";

        echo "=================================================\n";
        echo "✅ 보너스 재계산 완료!\n";
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
