<?php
/**
 * 모든 입금(Sales)에 대한 보너스 지급 검증 API
 * 각 sale에 대해 4가지 보너스가 정상 지급되었는지 확인
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

try {
    $db = Database::getInstance();

    // 모든 sales 조회 (완료된 것만)
    $sales = $db->select("
        SELECT
            s.id as sale_id,
            s.user_id,
            s.amount as package_amount,
            s.created_at,
            u.user_id as user_code,
            u.referral_id,
            u.sponsor_id,
            u.sponsor_position
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE s.status = 'completed'
        ORDER BY s.created_at ASC
    ");

    if (!$sales) {
        $sales = [];
    }

    $verificationResults = [];
    $totalIssues = 0;
    $totalSales = count($sales);
    $totalBonusExpected = 0;
    $totalBonusActual = 0;

    foreach ($sales as $sale) {
        $saleId = $sale['sale_id'];
        $userId = $sale['user_id'];
        $packageAmount = floatval($sale['package_amount']);
        $userCode = $sale['user_code'];

        $issues = [];
        $bonusExpected = 0;
        $bonusActual = 0;

        // 1. 추천 보너스 검증 (25%)
        $expectedReferral = $packageAmount * 0.25;

        if ($sale['referral_id']) {
            $bonusExpected += $expectedReferral;

            $actualReferral = $db->selectOne("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM bonuses
                WHERE from_user_id = ?
                AND bonus_type = 'referral'
                AND user_id = ?
            ", [$userId, $sale['referral_id']]);

            $actualAmount = floatval($actualReferral['total'] ?? 0);
            $bonusActual += $actualAmount;

            if (abs($actualAmount - $expectedReferral) > 0.01) {
                $issues[] = [
                    'type' => 'referral',
                    'expected' => $expectedReferral,
                    'actual' => $actualAmount,
                    'difference' => $expectedReferral - $actualAmount,
                    'receiver_id' => $sale['referral_id']
                ];
            }
        }

        // 2. 엣지 보너스 검증 (25%)
        $expectedEdge = $packageAmount * 0.25;

        // 엣지 보너스 수령자 찾기
        try {
            $edgeReceiverId = findEdgeReceiver($db, $userId, $sale['sponsor_id'], $sale['sponsor_position']);

            if ($edgeReceiverId) {
                $bonusExpected += $expectedEdge;

                $actualEdge = $db->selectOne("
                    SELECT COALESCE(SUM(amount), 0) as total
                    FROM bonuses
                    WHERE from_user_id = ?
                    AND bonus_type = 'edge'
                    AND user_id = ?
                ", [$userId, $edgeReceiverId]);

                $actualAmount = floatval($actualEdge['total'] ?? 0);
                $bonusActual += $actualAmount;

                if (abs($actualAmount - $expectedEdge) > 0.01) {
                    $issues[] = [
                        'type' => 'edge',
                        'expected' => $expectedEdge,
                        'actual' => $actualAmount,
                        'difference' => $expectedEdge - $actualAmount,
                        'receiver_id' => $edgeReceiverId
                    ];
                }
            }
        } catch (Exception $e) {
            // 엣지 보너스 검증 실패 - 건너뛰기
            error_log("Edge bonus verification failed for sale {$saleId}: " . $e->getMessage());
        }

        // 3. 매칭 보너스 검증 (25%)
        $expectedMatching = $packageAmount * 0.25;

        try {
            if (isset($edgeReceiverId) && $edgeReceiverId) {
                $edgeReceiverInfo = $db->selectOne("SELECT referral_id FROM users WHERE id = ?", [$edgeReceiverId]);

                if ($edgeReceiverInfo && $edgeReceiverInfo['referral_id']) {
                    $bonusExpected += $expectedMatching;

                    $actualMatching = $db->selectOne("
                        SELECT COALESCE(SUM(amount), 0) as total
                        FROM bonuses
                        WHERE from_user_id = ?
                        AND bonus_type = 'matching'
                        AND user_id = ?
                    ", [$userId, $edgeReceiverInfo['referral_id']]);

                    $actualAmount = floatval($actualMatching['total'] ?? 0);
                    $bonusActual += $actualAmount;

                    if (abs($actualAmount - $expectedMatching) > 0.01) {
                        $issues[] = [
                            'type' => 'matching',
                            'expected' => $expectedMatching,
                            'actual' => $actualAmount,
                            'difference' => $expectedMatching - $actualAmount,
                            'receiver_id' => $edgeReceiverInfo['referral_id']
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Matching bonus verification failed for sale {$saleId}: " . $e->getMessage());
        }

        // 4. 롤업 보너스 검증 (레벨당 1%, 최대 25레벨)
        try {
            $rollupReceivers = findRollupReceivers($db, $userId, $sale['sponsor_id']);
            $expectedRollupPerLevel = $packageAmount * 0.01;
            $expectedRollupTotal = count($rollupReceivers) * $expectedRollupPerLevel;
            $bonusExpected += $expectedRollupTotal;

            $actualRollupTotal = 0;
            foreach ($rollupReceivers as $level => $receiverId) {
                $actualRollup = $db->selectOne("
                    SELECT COALESCE(SUM(amount), 0) as total
                    FROM bonuses
                    WHERE from_user_id = ?
                    AND bonus_type = 'rollup'
                    AND user_id = ?
                    AND level = ?
                ", [$userId, $receiverId, $level]);

                $actualRollupTotal += floatval($actualRollup['total'] ?? 0);
            }

            $bonusActual += $actualRollupTotal;

            if (abs($actualRollupTotal - $expectedRollupTotal) > 0.01) {
                $issues[] = [
                    'type' => 'rollup',
                    'expected' => $expectedRollupTotal,
                    'actual' => $actualRollupTotal,
                    'difference' => $expectedRollupTotal - $actualRollupTotal,
                    'receivers_count' => count($rollupReceivers)
                ];
            }
        } catch (Exception $e) {
            error_log("Rollup bonus verification failed for sale {$saleId}: " . $e->getMessage());
        }

        $totalBonusExpected += $bonusExpected;
        $totalBonusActual += $bonusActual;

        if (!empty($issues)) {
            $totalIssues++;
            $verificationResults[] = [
                'sale_id' => $saleId,
                'user_id' => $userId,
                'user_code' => $userCode,
                'package_amount' => $packageAmount,
                'created_at' => $sale['created_at'],
                'expected_total' => $bonusExpected,
                'actual_total' => $bonusActual,
                'difference' => $bonusExpected - $bonusActual,
                'issues' => $issues,
                'status' => 'error'
            ];
        } else {
            $verificationResults[] = [
                'sale_id' => $saleId,
                'user_id' => $userId,
                'user_code' => $userCode,
                'package_amount' => $packageAmount,
                'created_at' => $sale['created_at'],
                'expected_total' => $bonusExpected,
                'actual_total' => $bonusActual,
                'status' => 'ok'
            ];
        }
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'summary' => [
            'total_sales' => $totalSales,
            'sales_with_issues' => $totalIssues,
            'sales_ok' => $totalSales - $totalIssues,
            'total_bonus_expected' => $totalBonusExpected,
            'total_bonus_actual' => $totalBonusActual,
            'total_difference' => $totalBonusExpected - $totalBonusActual
        ],
        'results' => $verificationResults
    ]);
    ob_end_flush();

} catch (Exception $e) {
    error_log('Bonus Verification Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Verification failed: ' . $e->getMessage(),
        'error_line' => $e->getLine(),
        'error_file' => basename($e->getFile())
    ]);
    ob_end_flush();
}

/**
 * 엣지 보너스 수령자 찾기
 */
function findEdgeReceiver($db, $userId, $sponsorId, $sponsorPosition) {
    if (!$sponsorId || !$sponsorPosition) {
        return null;
    }

    $currentUserId = $userId;
    $currentPosition = $sponsorPosition;
    $currentSponsorId = $sponsorId;

    while ($currentSponsorId) {
        $sponsor = $db->selectOne(
            "SELECT sponsor_id, sponsor_position FROM users WHERE id = ?",
            [$currentSponsorId]
        );

        if (!$sponsor || !$sponsor['sponsor_position']) {
            return $currentSponsorId; // 최상위 도달
        }

        if ($sponsor['sponsor_position'] != $currentPosition) {
            // 방향 바뀜 - 엣지 발견
            return $currentSponsorId;
        }

        $currentPosition = $sponsor['sponsor_position'];
        $currentSponsorId = $sponsor['sponsor_id'];
    }

    return null;
}

/**
 * 롤업 보너스 수령자 찾기 (최대 25레벨)
 */
function findRollupReceivers($db, $userId, $sponsorId) {
    $receivers = [];
    $currentSponsorId = $sponsorId;
    $level = 1;
    $processed = [];

    while ($currentSponsorId && $level <= 25) {
        // 무한루프 방지
        if (in_array($currentSponsorId, $processed)) {
            break;
        }
        $processed[] = $currentSponsorId;

        // 직접 추천인 수 확인
        $referralCount = $db->selectOne(
            "SELECT COUNT(*) as cnt FROM users WHERE referral_id = ?",
            [$currentSponsorId]
        );
        $count = intval($referralCount['cnt']);

        // 레벨 제한 확인
        $maxLevel = 10;
        if ($count >= 7) $maxLevel = 25;
        elseif ($count >= 6) $maxLevel = 22;
        elseif ($count >= 5) $maxLevel = 20;
        elseif ($count >= 4) $maxLevel = 18;
        elseif ($count >= 3) $maxLevel = 16;
        elseif ($count >= 2) $maxLevel = 14;
        elseif ($count >= 1) $maxLevel = 12;

        if ($level <= $maxLevel) {
            $receivers[$level] = $currentSponsorId;
        }

        // 다음 레벨로
        $nextSponsor = $db->selectOne(
            "SELECT sponsor_id FROM users WHERE id = ?",
            [$currentSponsorId]
        );

        $currentSponsorId = $nextSponsor ? $nextSponsor['sponsor_id'] : null;
        $level++;
    }

    return $receivers;
}
