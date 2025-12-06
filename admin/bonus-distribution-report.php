<?php
/**
 * 전체 매출별 보너스 지급 내역 리포트
 * 모든 매출에 대해 지급된 보너스를 상세하게 보여줌
 */

require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "매출별 보너스 지급 내역 리포트 (Sales Bonus Distribution Report)\n";
echo "=================================================================\n\n";

try {
    $db = Database::getInstance();

    // 1. 전체 통계
    echo "📊 전체 통계\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $totalSales = $db->selectOne("SELECT COUNT(*) as cnt, SUM(amount) as total FROM sales");
    $totalBonuses = $db->selectOne("
        SELECT
            COUNT(*) as cnt,
            SUM(amount) as total,
            SUM(CASE WHEN payment_type = 'cash' THEN amount ELSE 0 END) as total_cash,
            SUM(CASE WHEN payment_type = 'avatar_point' THEN amount ELSE 0 END) as total_avatar
        FROM bonuses
    ");

    $bonusByType = $db->select("
        SELECT
            bonus_type,
            COUNT(*) as count,
            SUM(amount) as total,
            SUM(CASE WHEN payment_type = 'cash' THEN amount ELSE 0 END) as cash,
            SUM(CASE WHEN payment_type = 'avatar_point' THEN amount ELSE 0 END) as avatar
        FROM bonuses
        GROUP BY bonus_type
    ");

    echo "총 매출: {$totalSales['cnt']}건, \$" . number_format($totalSales['total'], 2) . "\n";
    echo "총 보너스: {$totalBonuses['cnt']}건, \$" . number_format($totalBonuses['total'], 2) . "\n";
    echo "  - 캐시 (65%): \$" . number_format($totalBonuses['total_cash'], 2) . "\n";
    echo "  - 아바타 포인트 (35%): \$" . number_format($totalBonuses['total_avatar'], 2) . "\n\n";

    echo "보너스 타입별 통계:\n";
    foreach ($bonusByType as $type) {
        echo "  [{$type['bonus_type']}] {$type['count']}건, \$" . number_format($type['total'], 2);
        echo " (캐시: \$" . number_format($type['cash'], 2);
        echo ", 아바타: \$" . number_format($type['avatar'], 2) . ")\n";
    }
    echo "\n";

    // 2. 매출별 상세 내역
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📋 매출별 보너스 지급 상세 내역\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // 모든 매출 조회
    $sales = $db->select("
        SELECT
            s.id,
            s.user_id as buyer_id,
            u.user_id as buyer_code,
            u.name as buyer_name,
            s.amount,
            s.created_at
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.id
        ORDER BY s.created_at ASC
    ");

    $saleNum = 0;
    foreach ($sales as $sale) {
        $saleNum++;
        $saleId = $sale['id'];
        $buyerCode = $sale['buyer_code'];
        $buyerName = $sale['buyer_name'] ? "({$sale['buyer_name']})" : "";
        $amount = number_format($sale['amount'], 2);
        $date = $sale['created_at'];

        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "매출 #{$saleNum} [Sale ID: {$saleId}]\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "구매자: {$buyerCode} {$buyerName}\n";
        echo "금액: \${$amount}\n";
        echo "날짜: {$date}\n";
        echo "───────────────────────────────────────────────────────────────\n";

        // 이 매출로 발생한 보너스 조회
        $bonuses = $db->select("
            SELECT
                b.id as bonus_id,
                b.bonus_type,
                b.payment_type,
                b.amount,
                b.level,
                receiver.user_id as receiver_code,
                receiver.name as receiver_name,
                receiver.is_avatar,
                b.description
            FROM bonuses b
            LEFT JOIN users receiver ON b.user_id = receiver.id
            WHERE b.from_user_id = ?
            ORDER BY
                FIELD(b.bonus_type, 'referral', 'edge', 'matching', 'rollup'),
                b.level ASC,
                FIELD(b.payment_type, 'cash', 'avatar_point')
        ", [$sale['buyer_id']]);

        if (empty($bonuses)) {
            echo "⚠️  보너스 지급 내역 없음\n";
            continue;
        }

        // 보너스를 타입별로 그룹화
        $bonusByType = [
            'referral' => [],
            'edge' => [],
            'matching' => [],
            'rollup' => []
        ];

        foreach ($bonuses as $bonus) {
            $bonusByType[$bonus['bonus_type']][] = $bonus;
        }

        $totalBonusAmount = 0;

        // 1. Referral 보너스
        if (!empty($bonusByType['referral'])) {
            echo "\n✓ Referral 보너스 (직접 추천 25%):\n";
            $cashBonus = null;
            $avatarBonus = null;
            foreach ($bonusByType['referral'] as $b) {
                if ($b['payment_type'] === 'cash') $cashBonus = $b;
                else $avatarBonus = $b;
            }

            if ($cashBonus) {
                $receiver = $cashBonus['receiver_code'] . ($cashBonus['receiver_name'] ? " ({$cashBonus['receiver_name']})" : "");
                $cashAmt = number_format($cashBonus['amount'], 2);
                $avatarAmt = $avatarBonus ? number_format($avatarBonus['amount'], 2) : "0.00";
                $total = number_format($cashBonus['amount'] + ($avatarBonus ? $avatarBonus['amount'] : 0), 2);

                echo "  → {$receiver}\n";
                echo "     캐시: \${$cashAmt} | 아바타: \${$avatarAmt} | 합계: \${$total}\n";

                $totalBonusAmount += floatval($total);
            }
        }

        // 2. Edge 보너스
        if (!empty($bonusByType['edge'])) {
            echo "\n✓ Edge 보너스 (바이너리 꺾임 25%):\n";
            $cashBonus = null;
            $avatarBonus = null;
            foreach ($bonusByType['edge'] as $b) {
                if ($b['payment_type'] === 'cash') $cashBonus = $b;
                else $avatarBonus = $b;
            }

            if ($cashBonus) {
                $receiver = $cashBonus['receiver_code'] . ($cashBonus['receiver_name'] ? " ({$cashBonus['receiver_name']})" : "");
                $cashAmt = number_format($cashBonus['amount'], 2);
                $avatarAmt = $avatarBonus ? number_format($avatarBonus['amount'], 2) : "0.00";
                $total = number_format($cashBonus['amount'] + ($avatarBonus ? $avatarBonus['amount'] : 0), 2);

                echo "  → {$receiver}\n";
                echo "     캐시: \${$cashAmt} | 아바타: \${$avatarAmt} | 합계: \${$total}\n";

                $totalBonusAmount += floatval($total);
            }
        }

        // 3. Matching 보너스
        if (!empty($bonusByType['matching'])) {
            echo "\n✓ Matching 보너스 (엣지 수령자의 추천인 25%):\n";
            $cashBonus = null;
            $avatarBonus = null;
            foreach ($bonusByType['matching'] as $b) {
                if ($b['payment_type'] === 'cash') $cashBonus = $b;
                else $avatarBonus = $b;
            }

            if ($cashBonus) {
                $receiver = $cashBonus['receiver_code'] . ($cashBonus['receiver_name'] ? " ({$cashBonus['receiver_name']})" : "");
                $cashAmt = number_format($cashBonus['amount'], 2);
                $avatarAmt = $avatarBonus ? number_format($avatarBonus['amount'], 2) : "0.00";
                $total = number_format($cashBonus['amount'] + ($avatarBonus ? $avatarBonus['amount'] : 0), 2);

                echo "  → {$receiver}\n";
                echo "     캐시: \${$cashAmt} | 아바타: \${$avatarAmt} | 합계: \${$total}\n";

                $totalBonusAmount += floatval($total);
            }
        }

        // 4. Rollup 보너스
        if (!empty($bonusByType['rollup'])) {
            echo "\n✓ Rollup 보너스 (상위 25단계 × 1%):\n";

            // 레벨별로 그룹화
            $rollupByLevel = [];
            foreach ($bonusByType['rollup'] as $b) {
                $level = $b['level'];
                if (!isset($rollupByLevel[$level])) {
                    $rollupByLevel[$level] = ['cash' => null, 'avatar' => null];
                }
                if ($b['payment_type'] === 'cash') {
                    $rollupByLevel[$level]['cash'] = $b;
                } else {
                    $rollupByLevel[$level]['avatar'] = $b;
                }
            }

            ksort($rollupByLevel);

            foreach ($rollupByLevel as $level => $bonuses) {
                $cashBonus = $bonuses['cash'];
                $avatarBonus = $bonuses['avatar'];

                if ($cashBonus) {
                    $receiver = $cashBonus['receiver_code'] . ($cashBonus['receiver_name'] ? " ({$cashBonus['receiver_name']})" : "");
                    $cashAmt = number_format($cashBonus['amount'], 2);
                    $avatarAmt = $avatarBonus ? number_format($avatarBonus['amount'], 2) : "0.00";
                    $total = number_format($cashBonus['amount'] + ($avatarBonus ? $avatarBonus['amount'] : 0), 2);

                    echo "  L{$level} → {$receiver}\n";
                    echo "       캐시: \${$cashAmt} | 아바타: \${$avatarAmt} | 합계: \${$total}\n";

                    $totalBonusAmount += floatval($total);
                }
            }
        }

        echo "\n───────────────────────────────────────────────────────────────\n";
        echo "이 매출로 발생한 총 보너스: \$" . number_format($totalBonusAmount, 2) . "\n";
    }

    echo "\n\n";
    echo "=================================================================\n";
    echo "✅ 리포트 생성 완료!\n";
    echo "=================================================================\n";

    // JSON 파일로 저장
    $reportData = [
        'generated_at' => date('Y-m-d H:i:s'),
        'total_sales' => $totalSales,
        'total_bonuses' => $totalBonuses,
        'bonus_by_type' => $bonusByType,
        'sales_details' => []
    ];

    foreach ($sales as $sale) {
        $bonuses = $db->select("
            SELECT
                b.*,
                receiver.user_id as receiver_code,
                receiver.name as receiver_name
            FROM bonuses b
            LEFT JOIN users receiver ON b.user_id = receiver.id
            WHERE b.from_user_id = ?
            ORDER BY b.bonus_type, b.level
        ", [$sale['buyer_id']]);

        $reportData['sales_details'][] = [
            'sale' => $sale,
            'bonuses' => $bonuses
        ];
    }

    $reportFile = __DIR__ . '/bonus-distribution-report-' . date('Y-m-d-His') . '.json';
    file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n📄 상세 리포트가 저장되었습니다: {$reportFile}\n";

} catch (Exception $e) {
    echo "\n❌ 오류 발생:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
