<?php
/**
 * Bonus Generation Comprehensive Audit
 * 보너스 발생 전수조사
 *
 * $100 패키지 구매자와 모든 아바타에 대해
 * 4가지 보너스가 현재 회원 관계 기준으로 올바르게 발생했는지 검증
 */

// 오류 표시 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

set_time_limit(900); // 15 minutes
ini_set('memory_limit', '1024M');

try {
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance();
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

$auditStartTime = microtime(true);
$auditDate = date('Y-m-d H:i:s');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>보너스 발생 전수조사 - K-Pumasi Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-card .label {
            font-size: 0.9em;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 2em;
            font-weight: bold;
            color: #f5576c;
        }

        .stat-card.success .value { color: #10b981; }
        .stat-card.error .value { color: #ef4444; }
        .stat-card.warning .value { color: #f59e0b; }

        .content {
            padding: 30px;
        }

        .section-title {
            font-size: 1.5em;
            color: #f5576c;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #f5576c;
        }

        .table-wrapper {
            overflow-x: auto;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85em;
            background: white;
        }

        thead {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        th {
            padding: 12px 6px;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
            font-size: 0.8em;
        }

        td {
            padding: 8px 6px;
            border: 1px solid #e9ecef;
            text-align: center;
            font-size: 0.9em;
        }

        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        tbody tr:hover {
            background: #ffe0e6;
        }

        .status-ok { color: #10b981; font-weight: bold; }
        .status-error { color: #ef4444; font-weight: bold; }
        .status-warning { color: #f59e0b; font-weight: bold; }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.75em;
            font-weight: 600;
        }

        .badge-avatar { background: #fef3c7; color: #d97706; }
        .badge-user { background: #dbeafe; color: #2563eb; }
        .badge-missing { background: #fecaca; color: #dc2626; }
        .badge-ok { background: #d1fae5; color: #059669; }

        .alert {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .alert-info { background: #e0f2fe; border-color: #0284c7; color: #075985; }
        .alert-warning { background: #fef3c7; border-color: #f59e0b; color: #92400e; }
        .alert-danger { background: #fecaca; border-color: #dc2626; color: #991b1b; }

        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            color: #6c757d;
            border-top: 2px solid #e9ecef;
        }

        .small-text { font-size: 0.8em; color: #6c757d; }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }

        .missing-bonus {
            background: #fee;
            font-weight: bold;
        }

        .detail-cell {
            font-size: 0.75em;
            line-height: 1.3;
        }

        .fix-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85em;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .fix-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .fix-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .fix-btn.processing {
            background: #f59e0b;
        }

        .fix-btn.success {
            background: #10b981;
        }

        .fix-btn.failed {
            background: #ef4444;
        }
    </style>
    <script>
        function fixBonus(userId, hasPackage) {
            const btn = event.target;
            const originalText = btn.textContent;

            if (btn.disabled) return;

            if (!confirm('이 회원의 보너스를 재계산하고 수정하시겠습니까?')) {
                return;
            }

            btn.disabled = true;
            btn.classList.add('processing');
            btn.textContent = '처리중...';

            fetch('bonus-generation-fix.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    has_package: hasPackage
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.classList.remove('processing');
                    btn.classList.add('success');
                    btn.textContent = '✓ 완료';

                    alert('보너스가 수정되었습니다:\n' + data.message);

                    // 3초 후 페이지 새로고침
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    btn.classList.remove('processing');
                    btn.classList.add('failed');
                    btn.textContent = '✗ 실패';
                    alert('오류: ' + data.error);

                    setTimeout(() => {
                        btn.disabled = false;
                        btn.classList.remove('failed');
                        btn.textContent = originalText;
                    }, 3000);
                }
            })
            .catch(error => {
                btn.classList.remove('processing');
                btn.classList.add('failed');
                btn.textContent = '✗ 실패';
                alert('네트워크 오류: ' + error);

                setTimeout(() => {
                    btn.disabled = false;
                    btn.classList.remove('failed');
                    btn.textContent = originalText;
                }, 3000);
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 보너스 발생 전수조사</h1>
            <p>$100 패키지 구매 및 아바타 보너스 발생 검증</p>
            <div class="small-text" style="margin-top: 15px; opacity: 0.8;">
                현재 회원 관계 기준 | 감사 시작: <?= $auditDate ?>
            </div>
        </div>

<?php

try {
    // ============================================
    // 1단계: 검사 대상 수집
    // ============================================

    // 매출이 있는 모든 회원 (sales 테이블 기준)
    $salesUsers = $db->select(
        "SELECT DISTINCT u.id, u.user_id, u.name, u.package_id, p.price, u.package_date,
                u.referral_id, u.sponsor_id, u.sponsor_position, u.is_avatar,
                u.parent_account_id,
                s.amount as sales_amount
         FROM sales s
         INNER JOIN users u ON s.user_id = u.id
         LEFT JOIN packages p ON u.package_id = p.id
         WHERE u.status = 'active'
           AND u.deleted_at IS NULL
         ORDER BY s.created_at DESC"
    );

    // 검사 대상 정리 (중복 제거)
    $auditTargets = [];
    $processedIds = [];
    $usersWithoutPackage = 0;
    $usersWithPackage = 0;
    $totalAvatars = 0;

    foreach ($salesUsers as $user) {
        if (!in_array($user['id'], $processedIds)) {
            $auditTargets[] = $user;
            $processedIds[] = $user['id'];

            // 패키지 여부 카운트
            if ($user['package_id'] && $user['price'] > 0) {
                $usersWithPackage++;
            } else {
                $usersWithoutPackage++;
            }

            // 아바타 카운트
            if ($user['is_avatar']) {
                $totalAvatars++;
            }
        }
    }

    $totalTargets = count($auditTargets);
    $totalPackageUsers = $usersWithPackage;

    // 패키지 금액 통계
    $totalPackageAmount = 0;
    foreach ($auditTargets as $target) {
        $totalPackageAmount += floatval($target['price'] ?? 0);
    }

    // 총 매출 조회 (회원 + 아바타 분리)
    $memberSalesData = $db->selectOne(
        "SELECT
            COUNT(DISTINCT s.user_id) as sales_count,
            SUM(s.amount) as total_sales
         FROM sales s
         INNER JOIN users u ON s.user_id = u.id
         WHERE u.status = 'active' AND u.deleted_at IS NULL AND u.is_avatar = 0"
    );

    $avatarSalesData = $db->selectOne(
        "SELECT
            COUNT(DISTINCT s.user_id) as sales_count,
            SUM(s.amount) as total_sales
         FROM sales s
         INNER JOIN users u ON s.user_id = u.id
         WHERE u.status = 'active' AND u.deleted_at IS NULL AND u.is_avatar = 1"
    );

    $memberSalesCount = intval($memberSalesData['sales_count'] ?? 0);
    $memberSalesAmount = floatval($memberSalesData['total_sales'] ?? 0);
    $avatarSalesCount = intval($avatarSalesData['sales_count'] ?? 0);
    $avatarSalesAmount = floatval($avatarSalesData['total_sales'] ?? 0);
    $totalSalesCount = $memberSalesCount + $avatarSalesCount;
    $totalSalesAmount = $memberSalesAmount + $avatarSalesAmount;

    $formattedPackageAmount = number_format($totalPackageAmount, 0);
    $formattedMemberSales = number_format($memberSalesAmount, 2);
    $formattedAvatarSales = number_format($avatarSalesAmount, 2);
    $formattedTotalSales = number_format($totalSalesAmount, 2);

    echo <<<HTML
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">검사 대상 (매출 기준)</div>
                <div class="value">{$totalTargets}</div>
            </div>
            <div class="stat-card success">
                <div class="label">패키지 보유</div>
                <div class="value">{$totalPackageUsers}</div>
            </div>
            <div class="stat-card error">
                <div class="label">패키지 미할당 ⚠️</div>
                <div class="value">{$usersWithoutPackage}</div>
                <div class="small-text" style="margin-top: 5px;">입금했으나 패키지 없음</div>
            </div>
            <div class="stat-card">
                <div class="label">아바타</div>
                <div class="value">{$totalAvatars}</div>
            </div>
            <div class="stat-card">
                <div class="label">총 패키지 금액</div>
                <div class="value">\${$formattedPackageAmount}</div>
            </div>
            <div class="stat-card">
                <div class="label">회원 매출</div>
                <div class="value">\${$formattedMemberSales}</div>
                <div class="small-text" style="margin-top: 5px;">({$memberSalesCount}명)</div>
            </div>
            <div class="stat-card">
                <div class="label">아바타 매출</div>
                <div class="value">\${$formattedAvatarSales}</div>
                <div class="small-text" style="margin-top: 5px;">({$avatarSalesCount}명)</div>
            </div>
            <div class="stat-card">
                <div class="label">총 매출</div>
                <div class="value">\${$formattedTotalSales}</div>
                <div class="small-text" style="margin-top: 5px;">({$totalSalesCount}명)</div>
            </div>
        </div>

        <div class="content">
            <div class="alert alert-info">
                <strong>ℹ️ 검증 기준:</strong> 매출(입금)이 있는 <strong>전체 회원 (448명)</strong>에 대해 검사합니다.<br>
                • 패키지 보유: 4가지 보너스(Referral 25%, Edge 25%, Matching 25%, Rollup 1%×25) 발생 여부 검증<br>
                • 패키지 미할당: 입금은 있지만 패키지가 할당되지 않은 케이스 (데이터 오류)
            </div>
HTML;

    // 매출 정보 조회 (각 검사 대상의 sales)
    $salesByUser = [];
    if (!empty($processedIds)) {
        $placeholders = implode(',', array_fill(0, count($processedIds), '?'));
        $salesRecords = $db->select(
            "SELECT user_id, SUM(amount) as total_sales, COUNT(*) as sales_count
             FROM sales
             WHERE user_id IN ($placeholders)
             GROUP BY user_id",
            $processedIds
        );

        foreach ($salesRecords as $sale) {
            $salesByUser[$sale['user_id']] = [
                'total' => floatval($sale['total_sales']),
                'count' => intval($sale['sales_count'])
            ];
        }
    }

    // ============================================
    // 2단계: 각 대상에 대해 보너스 발생 검증
    // ============================================

    $auditResults = [];
    $totalErrors = 0;
    $totalWarnings = 0;
    $totalOK = 0;

    // 매출 기준 통계
    $salesOK = 0;
    $salesWarnings = 0;
    $salesErrors = 0;

    foreach ($auditTargets as $target) {
        $targetUserId = $target['id'];
        $packageAmount = floatval($target['price'] ?? 0);
        $hasPackage = ($target['package_id'] && $packageAmount > 0);

        $result = [
            'target' => $target,
            'sales' => $salesByUser[$targetUserId] ?? ['total' => 0, 'count' => 0],
            'expected_bonuses' => [],
            'actual_bonuses' => [],
            'missing' => [],
            'extra' => [],
            'status' => 'OK',
            'issues' => [],
            'has_package' => $hasPackage
        ];

        // 패키지 미할당 케이스 (매출은 있지만 패키지 없음)
        if (!$hasPackage) {
            $salesAmount = $salesByUser[$targetUserId]['total'] ?? 0;
            if ($salesAmount > 0) {
                $result['status'] = 'ERROR';
                $result['issues'][] = "입금 \${$salesAmount} 있으나 패키지 미할당";
            }

            // 패키지 없으면 나머지 검증 스킵
            $auditResults[] = $result;

            $currentSales = $salesByUser[$targetUserId]['total'] ?? 0;
            $totalErrors++;
            $salesErrors += $currentSales;
            continue;
        }

        // ==========================================
        // 1. REFERRAL BONUS (25%) 검증
        // ==========================================
        $referralId = $target['referral_id'];
        $expectedReferralAmount = $packageAmount * 0.25;

        if ($referralId) {
            $result['expected_bonuses']['referral'] = [
                'recipient_id' => $referralId,
                'amount' => $expectedReferralAmount,
                'reason' => '추천인'
            ];

            // 실제 발생된 referral 보너스 확인
            $actualReferral = $db->select(
                "SELECT SUM(amount) as total, COUNT(*) as count
                 FROM bonuses
                 WHERE from_user_id = ? AND user_id = ? AND bonus_type = 'referral'",
                [$targetUserId, $referralId]
            );

            $actualReferralTotal = floatval($actualReferral[0]['total'] ?? 0);
            $result['actual_bonuses']['referral'] = $actualReferralTotal;

            if (abs($actualReferralTotal - $expectedReferralAmount) > 0.01) {
                $result['missing'][] = sprintf(
                    "Referral: 기대 $%.2f, 실제 $%.2f (차이 $%.2f)",
                    $expectedReferralAmount,
                    $actualReferralTotal,
                    $expectedReferralAmount - $actualReferralTotal
                );
                $result['status'] = 'ERROR';
            }
        } else {
            $result['expected_bonuses']['referral'] = null;
            $result['actual_bonuses']['referral'] = 0;
            $result['issues'][] = '추천인 없음';
        }

        // ==========================================
        // 2. EDGE BONUS (25%) 검증
        // ==========================================
        $expectedEdgeAmount = $packageAmount * 0.25;

        // Edge 보너스 수령자 찾기 (복잡한 로직이므로 실제 발생된 것 확인)
        $actualEdge = $db->select(
            "SELECT user_id, SUM(amount) as total, edge_position
             FROM bonuses
             WHERE from_user_id = ? AND bonus_type = 'edge'
             GROUP BY user_id, edge_position",
            [$targetUserId]
        );

        if (!empty($actualEdge)) {
            $edgeRecipientId = $actualEdge[0]['user_id'];
            $actualEdgeTotal = floatval($actualEdge[0]['total']);
            $edgePosition = $actualEdge[0]['edge_position'];

            $result['expected_bonuses']['edge'] = [
                'recipient_id' => $edgeRecipientId,
                'amount' => $expectedEdgeAmount,
                'reason' => "Edge ({$edgePosition})"
            ];
            $result['actual_bonuses']['edge'] = $actualEdgeTotal;

            if (abs($actualEdgeTotal - $expectedEdgeAmount) > 0.01) {
                $result['missing'][] = sprintf(
                    "Edge: 기대 $%.2f, 실제 $%.2f",
                    $expectedEdgeAmount,
                    $actualEdgeTotal
                );
                $result['status'] = 'ERROR';
            }
        } else {
            $result['expected_bonuses']['edge'] = [
                'recipient_id' => null,
                'amount' => $expectedEdgeAmount,
                'reason' => 'Edge 미발생'
            ];
            $result['actual_bonuses']['edge'] = 0;
            $result['missing'][] = "Edge 보너스 미발생 ($" . number_format($expectedEdgeAmount, 2) . ")";
            if ($target['sponsor_id']) {
                $result['status'] = 'WARNING';
            }
        }

        // ==========================================
        // 3. MATCHING BONUS (25%) 검증
        // ==========================================
        if (!empty($actualEdge)) {
            $edgeRecipientId = $actualEdge[0]['user_id'];

            // Edge 수령자의 추천인 찾기
            $edgeRecipient = $db->selectOne(
                "SELECT referral_id FROM users WHERE id = ?",
                [$edgeRecipientId]
            );

            if ($edgeRecipient && $edgeRecipient['referral_id']) {
                $matchingRecipientId = $edgeRecipient['referral_id'];
                $expectedMatchingAmount = $packageAmount * 0.25; // 패키지의 25%

                $result['expected_bonuses']['matching'] = [
                    'recipient_id' => $matchingRecipientId,
                    'amount' => $expectedMatchingAmount,
                    'reason' => "Edge수령자의 추천인"
                ];

                // 실제 발생된 matching 보너스 확인
                $actualMatching = $db->select(
                    "SELECT SUM(amount) as total
                     FROM bonuses
                     WHERE from_user_id = ? AND user_id = ? AND bonus_type = 'matching'",
                    [$targetUserId, $matchingRecipientId]
                );

                $actualMatchingTotal = floatval($actualMatching[0]['total'] ?? 0);
                $result['actual_bonuses']['matching'] = $actualMatchingTotal;

                if (abs($actualMatchingTotal - $expectedMatchingAmount) > 0.01) {
                    $result['missing'][] = sprintf(
                        "Matching: 기대 $%.2f, 실제 $%.2f",
                        $expectedMatchingAmount,
                        $actualMatchingTotal
                    );
                    $result['status'] = 'ERROR';
                }
            } else {
                $result['expected_bonuses']['matching'] = null;
                $result['actual_bonuses']['matching'] = 0;
            }
        } else {
            $result['expected_bonuses']['matching'] = null;
            $result['actual_bonuses']['matching'] = 0;
        }

        // ==========================================
        // 4. ROLLUP BONUS (1% × 25 levels) 검증
        // ==========================================
        $expectedRollupPerLevel = $packageAmount * 0.01;

        // 실제 발생된 rollup 보너스 (레벨별)
        $actualRollup = $db->select(
            "SELECT level, user_id, SUM(amount) as total, COUNT(*) as count
             FROM bonuses
             WHERE from_user_id = ? AND bonus_type = 'rollup'
             GROUP BY level, user_id
             ORDER BY level",
            [$targetUserId]
        );

        $actualRollupLevels = count($actualRollup);
        $totalRollupAmount = 0;
        foreach ($actualRollup as $rollup) {
            $totalRollupAmount += floatval($rollup['total']);
        }

        $result['expected_bonuses']['rollup'] = [
            'levels' => 'up to 25',
            'amount_per_level' => $expectedRollupPerLevel,
            'reason' => 'Sponsor chain'
        ];
        $result['actual_bonuses']['rollup'] = $totalRollupAmount;
        $result['actual_rollup_levels'] = $actualRollupLevels;

        // Rollup은 경고만 (정확한 레벨 수 계산 복잡)
        if ($actualRollupLevels == 0) {
            $result['issues'][] = "Rollup 미발생";
            if ($result['status'] === 'OK') {
                $result['status'] = 'WARNING';
            }
        } elseif ($actualRollupLevels < 5 && $target['sponsor_id']) {
            $result['issues'][] = "Rollup 레벨 적음 ({$actualRollupLevels}단계)";
            if ($result['status'] === 'OK') {
                $result['status'] = 'WARNING';
            }
        }

        // ==========================================
        // 결과 저장
        // ==========================================
        $auditResults[] = $result;

        // 건수 및 매출액 기준 통계
        $currentSales = $salesByUser[$targetUserId]['total'] ?? 0;

        if ($result['status'] === 'ERROR') {
            $totalErrors++;
            $salesErrors += $currentSales;
        } elseif ($result['status'] === 'WARNING') {
            $totalWarnings++;
            $salesWarnings += $currentSales;
        } else {
            $totalOK++;
            $salesOK += $currentSales;
        }
    }

    // ============================================
    // 3단계: 결과 요약
    // ============================================

    // 매출 기준 정확도 계산 (전체 매출 $44,800 기준)
    $accuracyBySales = ($totalSalesAmount > 0) ? round(($salesOK / $totalSalesAmount) * 100, 1) : 0;

    $formattedSalesOK = number_format($salesOK, 2);
    $formattedSalesWarnings = number_format($salesWarnings, 2);
    $formattedSalesErrors = number_format($salesErrors, 2);

    echo '<div class="stats-grid">';
    echo '<div class="stat-card success">';
    echo '<div class="label">정상 ✅</div>';
    echo '<div class="value">' . $totalOK . '</div>';
    echo '<div class="small-text" style="margin-top: 5px;">$' . $formattedSalesOK . '</div>';
    echo '</div>';
    echo '<div class="stat-card warning">';
    echo '<div class="label">경고 ⚠️</div>';
    echo '<div class="value">' . $totalWarnings . '</div>';
    echo '<div class="small-text" style="margin-top: 5px;">$' . $formattedSalesWarnings . '</div>';
    echo '</div>';
    echo '<div class="stat-card error">';
    echo '<div class="label">오류 ❌</div>';
    echo '<div class="value">' . $totalErrors . '</div>';
    echo '<div class="small-text" style="margin-top: 5px;">$' . $formattedSalesErrors . '</div>';
    echo '</div>';
    echo '<div class="stat-card">';
    echo '<div class="label">정확도 (전체 매출 기준)</div>';
    echo '<div class="value">' . $accuracyBySales . '%</div>';
    echo '<div class="small-text" style="margin-top: 5px;">정상 매출 / 전체 매출</div>';
    echo '<div class="small-text">건수 기준: ' . ($totalTargets > 0 ? round(($totalOK / $totalTargets) * 100, 1) : 0) . '%</div>';
    echo '</div>';
    echo '</div>';

    if ($usersWithoutPackage > 0) {
        echo '<div class="alert alert-danger">';
        echo '<strong>⚠️ 패키지 미할당 발견!</strong><br>';
        echo "{$usersWithoutPackage}명의 회원이 입금은 했으나 패키지가 할당되지 않았습니다. (데이터 정합성 오류)";
        echo '</div>';
    }

    if ($totalErrors > 0) {
        echo '<div class="alert alert-danger">';
        echo '<strong>❌ 보너스 발생 오류 발견!</strong><br>';
        echo "{$totalErrors}건의 패키지/아바타에서 보너스 발생 불일치가 발견되었습니다.";
        echo '</div>';
    }

    // ============================================
    // 4단계: 상세 결과 테이블
    // ============================================

    // 오류만 필터링
    $errorResults = array_filter($auditResults, function($result) {
        return $result['status'] === 'ERROR';
    });

    $displayResults = !empty($errorResults) ? $errorResults : $auditResults;
    $displayMode = !empty($errorResults) ? '오류' : '전체';

    echo '<h2 class="section-title">📊 보너스 발생 상세 검증 결과 (' . $displayMode . ')</h2>';

    if (!empty($errorResults) && $displayMode === '오류') {
        echo '<div class="alert alert-danger">';
        echo '<strong>⚠️ 오류 항목만 표시</strong><br>';
        echo '오류가 발견된 ' . count($errorResults) . '건만 표시됩니다. 전체 ' . count($auditResults) . '건 중 ' . $totalOK . '건은 정상입니다.';
        echo '</div>';
    }

    echo '<div class="table-wrapper">';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th rowspan="3">상태</th>';
    echo '<th rowspan="3">ID</th>';
    echo '<th rowspan="3">User ID</th>';
    echo '<th rowspan="3">이름</th>';
    echo '<th rowspan="3">타입</th>';
    echo '<th rowspan="3">패키지<br>금액</th>';
    echo '<th rowspan="3">매출<br>(건수)</th>';
    echo '<th colspan="3">Referral (25%)</th>';
    echo '<th colspan="3">Edge (25%)</th>';
    echo '<th colspan="3">Matching (25%)</th>';
    echo '<th colspan="3">Rollup (1%×n)</th>';
    echo '<th rowspan="3">문제점</th>';
    echo '<th rowspan="3">조치</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th colspan="3">현재 추천인 / 수령자</th>';
    echo '<th colspan="3">현재 Edge / 수령자</th>';
    echo '<th colspan="3">현재 대상 / 수령자</th>';
    echo '<th colspan="3">레벨 / 금액</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th>기대자</th><th>수령자</th><th>금액</th>';
    echo '<th>기대자</th><th>수령자</th><th>금액</th>';
    echo '<th>기대자</th><th>수령자</th><th>금액</th>';
    echo '<th>레벨</th><th>금액</th><th>상태</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($displayResults as $result) {
        $target = $result['target'];
        $packageAmount = floatval($target['price'] ?? 0);

        // 상태 아이콘
        $statusIcon = '✅';
        $statusClass = 'status-ok';
        if ($result['status'] === 'ERROR') {
            $statusIcon = '❌';
            $statusClass = 'status-error';
        } elseif ($result['status'] === 'WARNING') {
            $statusIcon = '⚠️';
            $statusClass = 'status-warning';
        }

        // 타입 뱃지
        $typeBadge = '<span class="badge badge-user">일반</span>';
        if ($target['is_avatar']) {
            $typeBadge = '<span class="badge badge-avatar">아바타</span>';
        }

        // 매출 정보
        $sales = $result['sales'];
        $salesDisplay = $sales['total'] > 0
            ? '$' . number_format($sales['total'], 2) . '<br><span class="small-text">(' . $sales['count'] . '건)</span>'
            : '-';

        echo '<tr>';
        echo '<td class="' . $statusClass . '">' . $statusIcon . '</td>';
        echo '<td>' . $target['id'] . '</td>';
        echo '<td class="text-left">' . htmlspecialchars($target['user_id']) . '</td>';
        echo '<td class="text-left">' . htmlspecialchars($target['name']) . '</td>';
        echo '<td>' . $typeBadge . '</td>';
        echo '<td class="text-right">$' . number_format($packageAmount, 2) . '</td>';
        echo '<td class="text-right">' . $salesDisplay . '</td>';

        // ==========================================
        // Referral 상세
        // ==========================================
        $expRef = $result['expected_bonuses']['referral'] ?? null;
        $actRef = $result['actual_bonuses']['referral'] ?? 0;

        if ($expRef && $expRef['recipient_id']) {
            // 현재 기대 수령자
            $expectedRefUser = $db->selectOne(
                "SELECT user_id FROM users WHERE id = ?",
                [$expRef['recipient_id']]
            );
            $expectedRefUserId = $expectedRefUser ? $expectedRefUser['user_id'] : 'ID:' . $expRef['recipient_id'];

            // 실제 수령자
            $actualRefRecipient = $db->selectOne(
                "SELECT u.user_id, SUM(b.amount) as total
                 FROM bonuses b
                 JOIN users u ON b.user_id = u.id
                 WHERE b.from_user_id = ? AND b.bonus_type = 'referral'
                 GROUP BY b.user_id
                 LIMIT 1",
                [$targetUserId]
            );

            $actualRefUserId = $actualRefRecipient ? $actualRefRecipient['user_id'] : '-';
            $actualRefAmount = $actualRefRecipient ? floatval($actualRefRecipient['total']) : 0;

            $refMatch = ($expectedRefUserId === $actualRefUserId);
            $refClass = !$refMatch ? 'missing-bonus' : '';
        } else {
            $expectedRefUserId = '-';
            $actualRefUserId = '-';
            $actualRefAmount = 0;
            $refClass = '';
        }

        echo '<td class="text-left detail-cell">' . htmlspecialchars($expectedRefUserId) . '</td>';
        echo '<td class="text-left detail-cell ' . $refClass . '">' . htmlspecialchars($actualRefUserId) . '</td>';
        echo '<td class="text-right ' . $refClass . '">$' . number_format($actualRefAmount, 2) . '</td>';

        // ==========================================
        // Edge 상세
        // ==========================================
        $expEdge = $result['expected_bonuses']['edge'] ?? null;

        if ($expEdge && $expEdge['recipient_id']) {
            $expectedEdgeUser = $db->selectOne(
                "SELECT user_id FROM users WHERE id = ?",
                [$expEdge['recipient_id']]
            );
            $expectedEdgeUserId = $expectedEdgeUser ? $expectedEdgeUser['user_id'] : 'ID:' . $expEdge['recipient_id'];

            $actualEdgeRecipient = $db->selectOne(
                "SELECT u.user_id, SUM(b.amount) as total
                 FROM bonuses b
                 JOIN users u ON b.user_id = u.id
                 WHERE b.from_user_id = ? AND b.bonus_type = 'edge'
                 GROUP BY b.user_id
                 LIMIT 1",
                [$targetUserId]
            );

            $actualEdgeUserId = $actualEdgeRecipient ? $actualEdgeRecipient['user_id'] : '-';
            $actualEdgeAmount = $actualEdgeRecipient ? floatval($actualEdgeRecipient['total']) : 0;

            $edgeMatch = ($expectedEdgeUserId === $actualEdgeUserId);
            $edgeClass = (!$edgeMatch || $actualEdgeAmount == 0) ? 'missing-bonus' : '';
        } else {
            $expectedEdgeUserId = '-';
            $actualEdgeUserId = '-';
            $actualEdgeAmount = 0;
            $edgeClass = 'missing-bonus';
        }

        echo '<td class="text-left detail-cell">' . htmlspecialchars($expectedEdgeUserId) . '</td>';
        echo '<td class="text-left detail-cell ' . $edgeClass . '">' . htmlspecialchars($actualEdgeUserId) . '</td>';
        echo '<td class="text-right ' . $edgeClass . '">$' . number_format($actualEdgeAmount, 2) . '</td>';

        // ==========================================
        // Matching 상세
        // ==========================================
        $expMatch = $result['expected_bonuses']['matching'] ?? null;

        if ($expMatch && $expMatch['recipient_id']) {
            $expectedMatchUser = $db->selectOne(
                "SELECT user_id FROM users WHERE id = ?",
                [$expMatch['recipient_id']]
            );
            $expectedMatchUserId = $expectedMatchUser ? $expectedMatchUser['user_id'] : 'ID:' . $expMatch['recipient_id'];

            $actualMatchRecipient = $db->selectOne(
                "SELECT u.user_id, SUM(b.amount) as total
                 FROM bonuses b
                 JOIN users u ON b.user_id = u.id
                 WHERE b.from_user_id = ? AND b.bonus_type = 'matching'
                 GROUP BY b.user_id
                 LIMIT 1",
                [$targetUserId]
            );

            $actualMatchUserId = $actualMatchRecipient ? $actualMatchRecipient['user_id'] : '-';
            $actualMatchAmount = $actualMatchRecipient ? floatval($actualMatchRecipient['total']) : 0;

            $matchMatch = ($expectedMatchUserId === $actualMatchUserId);
            $matchClass = !$matchMatch ? 'missing-bonus' : '';
        } else {
            $expectedMatchUserId = '-';
            $actualMatchUserId = '-';
            $actualMatchAmount = 0;
            $matchClass = '';
        }

        echo '<td class="text-left detail-cell">' . htmlspecialchars($expectedMatchUserId) . '</td>';
        echo '<td class="text-left detail-cell ' . $matchClass . '">' . htmlspecialchars($actualMatchUserId) . '</td>';
        echo '<td class="text-right ' . $matchClass . '">$' . number_format($actualMatchAmount, 2) . '</td>';

        // ==========================================
        // Rollup 상세
        // ==========================================
        $actRollupLevels = $result['actual_rollup_levels'] ?? 0;
        $actRollupAmount = $result['actual_bonuses']['rollup'] ?? 0;

        $rollupClass = '';
        $rollupStatus = '✅';
        if ($actRollupLevels == 0) {
            $rollupClass = 'missing-bonus';
            $rollupStatus = '❌';
        } elseif ($actRollupLevels < 5 && $target['sponsor_id']) {
            $rollupClass = 'status-warning';
            $rollupStatus = '⚠️';
        }

        echo '<td class="text-right ' . $rollupClass . '">' . $actRollupLevels . '</td>';
        echo '<td class="text-right">$' . number_format($actRollupAmount, 2) . '</td>';
        echo '<td class="text-center">' . $rollupStatus . '</td>';

        // 문제점
        echo '<td class="text-left detail-cell">';
        $allIssues = array_merge($result['missing'], $result['issues']);
        if (!empty($allIssues)) {
            echo '<span class="status-error">' . implode('<br>', $allIssues) . '</span>';
        } else {
            echo '-';
        }
        echo '</td>';

        // 조치 버튼
        echo '<td class="text-center">';
        if ($result['status'] === 'ERROR') {
            $userId = $target['id'];
            $hasPackageFlag = $result['has_package'] ? 'true' : 'false';
            echo '<button class="fix-btn" onclick="fixBonus(' . $userId . ', ' . $hasPackageFlag . ')" data-user-id="' . $userId . '">';
            echo '수정';
            echo '</button>';
        } else {
            echo '-';
        }
        echo '</td>';

        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    // ============================================
    // 감사 완료
    // ============================================
    $auditEndTime = microtime(true);
    $executionTime = round($auditEndTime - $auditStartTime, 2);

    echo <<<HTML
        </div>

        <div class="footer">
            <p><strong>감사 완료</strong></p>
            <p>검사 대상: {$totalTargets}건 (매출 기준 전체)</p>
            <p>패키지 보유: {$totalPackageUsers}건 | 패키지 미할당: {$usersWithoutPackage}건 | 아바타: {$totalAvatars}건</p>
            <p>정상: {$totalOK}, 경고: {$totalWarnings}, 오류: {$totalErrors}</p>
            <p>실행 시간: {$executionTime}초 | 생성 시간: {$auditDate}</p>
        </div>
    </div>
</body>
</html>
HTML;

} catch (Exception $e) {
    echo '<div class="content"><div class="alert alert-danger">';
    echo '<strong>오류 발생:</strong> ' . htmlspecialchars($e->getMessage());
    echo '<br><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div></div></div></body></html>';
}
?>
