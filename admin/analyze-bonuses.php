<?php
/**
 * 보너스 지급 내역 분석 스크립트
 * 최근 매출에 대한 보너스 지급이 정확한지 검증
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><meta charset='utf-8'><style>
body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #444; padding: 8px; text-align: left; }
th { background: #2d2d2d; }
.error { color: #ff4444; font-weight: bold; }
.warning { color: #ffaa00; }
.success { color: #44ff44; }
.section { margin: 30px 0; padding: 20px; background: #2d2d2d; border-radius: 8px; }
h2 { color: #60a5fa; }
h3 { color: #a78bfa; }
</style></head><body>";

try {
    $db = Database::getInstance();

    echo "<h1>🔍 보너스 지급 분석 리포트</h1>";

    // 최근 매출 10건 조회
    $recentSales = $db->select("
        SELECT
            s.id as sale_id,
            s.user_id,
            s.amount,
            s.created_at,
            u.user_id as user_code,
            u.name,
            u.referral_id,
            u.sponsor_id,
            u.sponsor_position
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE s.status = 'completed'
        ORDER BY s.created_at DESC
        LIMIT 10
    ");

    echo "<div class='section'>";
    echo "<h2>📊 최근 매출 10건</h2>";
    echo "<table>";
    echo "<tr>
        <th>Sale ID</th>
        <th>회원코드</th>
        <th>이름</th>
        <th>금액</th>
        <th>Referral ID</th>
        <th>Sponsor ID</th>
        <th>Position</th>
        <th>등록일</th>
    </tr>";

    foreach ($recentSales as $sale) {
        echo "<tr>";
        echo "<td>{$sale['sale_id']}</td>";
        echo "<td>{$sale['user_code']}</td>";
        echo "<td>{$sale['name']}</td>";
        echo "<td>\${$sale['amount']}</td>";
        echo "<td>{$sale['referral_id']}</td>";
        echo "<td>{$sale['sponsor_id']}</td>";
        echo "<td>{$sale['sponsor_position']}</td>";
        echo "<td>{$sale['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table></div>";

    // 각 매출에 대한 보너스 분석
    foreach ($recentSales as $sale) {
        echo "<div class='section'>";
        echo "<h2>💰 Sale #{$sale['sale_id']} - {$sale['user_code']} (\${$sale['amount']})</h2>";

        $saleUserId = $sale['user_id'];
        $saleAmount = floatval($sale['amount']);

        // 이 매출로 발생한 모든 보너스 조회
        $bonuses = $db->select("
            SELECT
                b.id,
                b.user_id,
                b.bonus_type,
                b.payment_type,
                b.amount,
                b.level,
                b.edge_position,
                b.created_at,
                u.user_id as receiver_code,
                u.name as receiver_name
            FROM bonuses b
            JOIN users u ON b.user_id = u.id
            WHERE b.from_user_id = ?
            ORDER BY b.bonus_type, b.level, b.payment_type
        ", [$saleUserId]);

        // 보너스 타입별 집계
        $summary = [
            'referral' => ['cash' => 0, 'avatar_point' => 0, 'count' => 0],
            'edge' => ['cash' => 0, 'avatar_point' => 0, 'count' => 0],
            'matching' => ['cash' => 0, 'avatar_point' => 0, 'count' => 0],
            'rollup' => ['cash' => 0, 'avatar_point' => 0, 'count' => 0, 'levels' => 0]
        ];

        $rollupLevels = [];

        foreach ($bonuses as $bonus) {
            $type = $bonus['bonus_type'];
            $paymentType = $bonus['payment_type'];
            $amount = floatval($bonus['amount']);

            if (isset($summary[$type])) {
                $summary[$type][$paymentType] += $amount;
                $summary[$type]['count']++;

                if ($type === 'rollup' && $bonus['level']) {
                    if (!isset($rollupLevels[$bonus['level']])) {
                        $rollupLevels[$bonus['level']] = ['cash' => 0, 'avatar_point' => 0];
                        $summary[$type]['levels']++;
                    }
                    $rollupLevels[$bonus['level']][$paymentType] += $amount;
                }
            }
        }

        // 기대값 계산
        $expectedReferral = $saleAmount * 0.25; // 25%
        $expectedEdge = $saleAmount * 0.25; // 25%
        $expectedMatching = $saleAmount * 0.25; // 25%
        $expectedRollupPerLevel = $saleAmount * 0.01; // 1%

        echo "<h3>📈 보너스 요약</h3>";
        echo "<table>";
        echo "<tr>
            <th>타입</th>
            <th>캐시</th>
            <th>아바타포인트</th>
            <th>합계</th>
            <th>기대값</th>
            <th>건수</th>
            <th>상태</th>
        </tr>";

        // Referral
        $referralTotal = $summary['referral']['cash'] + $summary['referral']['avatar_point'];
        $referralStatus = abs($referralTotal - $expectedReferral) < 0.01 ?
            "<span class='success'>✅ 정상</span>" :
            "<span class='error'>❌ 오류 (차이: $" . number_format($expectedReferral - $referralTotal, 2) . ")</span>";

        echo "<tr>";
        echo "<td>Referral</td>";
        echo "<td>\${$summary['referral']['cash']}</td>";
        echo "<td>\${$summary['referral']['avatar_point']}</td>";
        echo "<td>\${$referralTotal}</td>";
        echo "<td>\${$expectedReferral}</td>";
        echo "<td>{$summary['referral']['count']}</td>";
        echo "<td>{$referralStatus}</td>";
        echo "</tr>";

        // Edge
        $edgeTotal = $summary['edge']['cash'] + $summary['edge']['avatar_point'];
        $edgeStatus = abs($edgeTotal - $expectedEdge) < 0.01 ?
            "<span class='success'>✅ 정상</span>" :
            "<span class='error'>❌ 오류 (차이: $" . number_format($expectedEdge - $edgeTotal, 2) . ")</span>";

        echo "<tr>";
        echo "<td>Edge</td>";
        echo "<td>\${$summary['edge']['cash']}</td>";
        echo "<td>\${$summary['edge']['avatar_point']}</td>";
        echo "<td>\${$edgeTotal}</td>";
        echo "<td>\${$expectedEdge}</td>";
        echo "<td>{$summary['edge']['count']}</td>";
        echo "<td>{$edgeStatus}</td>";
        echo "</tr>";

        // Matching
        $matchingTotal = $summary['matching']['cash'] + $summary['matching']['avatar_point'];
        $matchingStatus = abs($matchingTotal - $expectedMatching) < 0.01 ?
            "<span class='success'>✅ 정상</span>" :
            "<span class='error'>❌ 오류 (차이: $" . number_format($expectedMatching - $matchingTotal, 2) . ")</span>";

        echo "<tr>";
        echo "<td>Matching</td>";
        echo "<td>\${$summary['matching']['cash']}</td>";
        echo "<td>\${$summary['matching']['avatar_point']}</td>";
        echo "<td>\${$matchingTotal}</td>";
        echo "<td>\${$expectedMatching}</td>";
        echo "<td>{$summary['matching']['count']}</td>";
        echo "<td>{$matchingStatus}</td>";
        echo "</tr>";

        // Rollup
        $rollupTotal = $summary['rollup']['cash'] + $summary['rollup']['avatar_point'];
        $rollupLevelCount = $summary['rollup']['levels'];
        $expectedRollupTotal = $expectedRollupPerLevel * $rollupLevelCount;
        $rollupStatus = abs($rollupTotal - $expectedRollupTotal) < 0.01 ?
            "<span class='success'>✅ 정상 ({$rollupLevelCount}단계)</span>" :
            "<span class='error'>❌ 오류 (차이: $" . number_format($expectedRollupTotal - $rollupTotal, 2) . ")</span>";

        echo "<tr>";
        echo "<td>Rollup</td>";
        echo "<td>\${$summary['rollup']['cash']}</td>";
        echo "<td>\${$summary['rollup']['avatar_point']}</td>";
        echo "<td>\${$rollupTotal}</td>";
        echo "<td>\${$expectedRollupTotal} ({$rollupLevelCount}단계)</td>";
        echo "<td>{$summary['rollup']['count']}</td>";
        echo "<td>{$rollupStatus}</td>";
        echo "</tr>";

        // 총합
        $grandTotal = $referralTotal + $edgeTotal + $matchingTotal + $rollupTotal;
        $expectedGrandTotal = $expectedReferral + $expectedEdge + $expectedMatching + $expectedRollupTotal;

        echo "<tr style='background: #3d3d3d; font-weight: bold;'>";
        echo "<td>총합</td>";
        echo "<td colspan='2'></td>";
        echo "<td>\${$grandTotal}</td>";
        echo "<td>\${$expectedGrandTotal}</td>";
        echo "<td></td>";
        echo "<td></td>";
        echo "</tr>";

        echo "</table>";

        // Rollup 레벨별 상세
        if (!empty($rollupLevels)) {
            echo "<h3>📊 Rollup 레벨별 상세</h3>";
            echo "<table>";
            echo "<tr><th>Level</th><th>캐시</th><th>아바타포인트</th><th>합계</th><th>기대값</th><th>상태</th></tr>";

            ksort($rollupLevels);
            foreach ($rollupLevels as $level => $amounts) {
                $levelTotal = $amounts['cash'] + $amounts['avatar_point'];
                $levelStatus = abs($levelTotal - $expectedRollupPerLevel) < 0.01 ?
                    "<span class='success'>✅</span>" :
                    "<span class='error'>❌</span>";

                echo "<tr>";
                echo "<td>Level {$level}</td>";
                echo "<td>\${$amounts['cash']}</td>";
                echo "<td>\${$amounts['avatar_point']}</td>";
                echo "<td>\${$levelTotal}</td>";
                echo "<td>\${$expectedRollupPerLevel}</td>";
                echo "<td>{$levelStatus}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

        // 보너스 상세 내역
        echo "<h3>📋 보너스 상세 내역</h3>";
        echo "<table>";
        echo "<tr>
            <th>ID</th>
            <th>받은사람</th>
            <th>타입</th>
            <th>지급형태</th>
            <th>금액</th>
            <th>레벨</th>
            <th>등록일</th>
        </tr>";

        foreach ($bonuses as $bonus) {
            echo "<tr>";
            echo "<td>{$bonus['id']}</td>";
            echo "<td>{$bonus['receiver_code']} ({$bonus['receiver_name']})</td>";
            echo "<td>{$bonus['bonus_type']}</td>";
            echo "<td>{$bonus['payment_type']}</td>";
            echo "<td>\${$bonus['amount']}</td>";
            echo "<td>" . ($bonus['level'] ?: '-') . "</td>";
            echo "<td>{$bonus['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ 오류 발생</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
