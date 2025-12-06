<?php
/**
 * Bonus Generation Fix Handler
 * 보너스 발생 오류 수정 처리
 */

header('Content-Type: application/json');

// 오류 표시 비활성화 (JSON 응답만)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../classes/BonusDistributor.php';

    $db = Database::getInstance();

    // JSON 입력 받기
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['user_id'])) {
        throw new Exception('Invalid input');
    }

    $userId = intval($input['user_id']);
    $hasPackage = $input['has_package'] === 'true' || $input['has_package'] === true;

    // 사용자 정보 조회
    $user = $db->selectOne(
        "SELECT u.*, p.price
         FROM users u
         LEFT JOIN packages p ON u.package_id = p.id
         WHERE u.id = ?",
        [$userId]
    );

    if (!$user) {
        throw new Exception('사용자를 찾을 수 없습니다.');
    }

    $fixLog = [];

    // ============================================
    // 1. 패키지 미할당 케이스
    // ============================================
    if (!$hasPackage) {
        // 패키지가 없는 경우 - 매뉴얼 처리 필요
        echo json_encode([
            'success' => false,
            'error' => '패키지가 할당되지 않은 회원입니다. 먼저 패키지를 할당해주세요.',
            'suggestion' => '관리자 페이지에서 패키지를 할당한 후 다시 시도하세요.'
        ]);
        exit;
    }

    // ============================================
    // 2. 보너스 재계산 및 수정
    // ============================================
    $packageAmount = floatval($user['price']);

    if ($packageAmount <= 0) {
        throw new Exception('유효하지 않은 패키지 금액입니다.');
    }

    // BonusDistributor 사용하여 보너스 재분배
    $distributor = new BonusDistributor($db);

    // 기존 보너스 삭제 (이 사용자로부터 발생한 보너스만)
    $deletedBonuses = $db->execute(
        "DELETE FROM bonuses WHERE from_user_id = ?",
        [$userId]
    );

    $fixLog[] = "기존 보너스 {$deletedBonuses}건 삭제";

    // 보너스 재분배
    try {
        $result = $distributor->distributePackageBonuses($userId, $packageAmount);

        if ($result['success']) {
            $fixLog[] = "Referral 보너스: " . ($result['referral'] ?? 'N/A');
            $fixLog[] = "Edge 보너스: " . ($result['edge'] ?? 'N/A');
            $fixLog[] = "Matching 보너스: " . ($result['matching'] ?? 'N/A');
            $fixLog[] = "Rollup 보너스: " . ($result['rollup_count'] ?? 0) . "단계";
        } else {
            throw new Exception('보너스 재분배 실패: ' . ($result['error'] ?? 'Unknown error'));
        }
    } catch (Exception $e) {
        // BonusDistributor가 없거나 메서드가 없는 경우 수동 계산
        $fixLog[] = "수동 보너스 계산 시작";

        $bonusesAdded = 0;

        // 1. Referral Bonus (25%)
        if ($user['referral_id']) {
            $referralAmount = $packageAmount * 0.25;
            $db->execute(
                "INSERT INTO bonuses (user_id, from_user_id, bonus_type, amount, created_at)
                 VALUES (?, ?, 'referral', ?, NOW())",
                [$user['referral_id'], $userId, $referralAmount]
            );
            $bonusesAdded++;
            $fixLog[] = "Referral 보너스 추가: $" . number_format($referralAmount, 2);

            // Update referrer's total
            $db->execute(
                "UPDATE users
                 SET total_referral_bonus = total_referral_bonus + ?,
                     total_bonus = total_bonus + ?,
                     available_bonus = available_bonus + ?
                 WHERE id = ?",
                [$referralAmount, $referralAmount, $referralAmount, $user['referral_id']]
            );
        }

        // 2. Edge Bonus - 스폰서 체인에서 찾기 (복잡한 로직)
        // 간단히 처리: sponsor가 있으면 edge 보너스 지급
        if ($user['sponsor_id']) {
            // sponsor_id를 user_id로 변환
            $sponsor = $db->selectOne(
                "SELECT id FROM users WHERE user_id = ?",
                [$user['sponsor_id']]
            );

            if ($sponsor) {
                $edgeAmount = $packageAmount * 0.25;
                $edgePosition = $user['sponsor_position'] ?? 'left';

                $db->execute(
                    "INSERT INTO bonuses (user_id, from_user_id, bonus_type, amount, edge_position, created_at)
                     VALUES (?, ?, 'edge', ?, ?, NOW())",
                    [$sponsor['id'], $userId, $edgeAmount, $edgePosition]
                );
                $bonusesAdded++;
                $fixLog[] = "Edge 보너스 추가: $" . number_format($edgeAmount, 2);

                // Update sponsor's total
                $db->execute(
                    "UPDATE users
                     SET total_edge_bonus = total_edge_bonus + ?,
                         total_bonus = total_bonus + ?,
                         available_bonus = available_bonus + ?
                     WHERE id = ?",
                    [$edgeAmount, $edgeAmount, $edgeAmount, $sponsor['id']]
                );

                // 3. Matching Bonus - Edge 수령자의 추천인에게
                $edgeRecipient = $db->selectOne(
                    "SELECT referral_id FROM users WHERE id = ?",
                    [$sponsor['id']]
                );

                if ($edgeRecipient && $edgeRecipient['referral_id']) {
                    $matchingAmount = $packageAmount * 0.25; // 25%
                    $db->execute(
                        "INSERT INTO bonuses (user_id, from_user_id, bonus_type, amount, created_at)
                         VALUES (?, ?, 'matching', ?, NOW())",
                        [$edgeRecipient['referral_id'], $userId, $matchingAmount]
                    );
                    $bonusesAdded++;
                    $fixLog[] = "Matching 보너스 추가: $" . number_format($matchingAmount, 2);

                    // Update matching recipient's total
                    $db->execute(
                        "UPDATE users
                         SET total_matching_bonus = total_matching_bonus + ?,
                             total_bonus = total_bonus + ?,
                             available_bonus = available_bonus + ?
                         WHERE id = ?",
                        [$matchingAmount, $matchingAmount, $matchingAmount, $edgeRecipient['referral_id']]
                    );
                }
            }
        }

        // 4. Rollup Bonus - 스폰서 체인 상위로 1% 씩
        if ($user['sponsor_id']) {
            $currentSponsorUserId = $user['sponsor_id'];
            $rollupAmount = $packageAmount * 0.01;
            $level = 1;
            $maxLevels = 25;
            $rollupCount = 0;

            while ($currentSponsorUserId && $level <= $maxLevels) {
                $sponsorUser = $db->selectOne(
                    "SELECT id, sponsor_id FROM users WHERE user_id = ? AND status = 'active' AND deleted_at IS NULL",
                    [$currentSponsorUserId]
                );

                if (!$sponsorUser) break;

                // Rollup 보너스 지급
                $db->execute(
                    "INSERT INTO bonuses (user_id, from_user_id, bonus_type, amount, level, created_at)
                     VALUES (?, ?, 'rollup', ?, ?, NOW())",
                    [$sponsorUser['id'], $userId, $rollupAmount, $level]
                );

                // Update sponsor's total
                $db->execute(
                    "UPDATE users
                     SET total_rollup_bonus = total_rollup_bonus + ?,
                         total_bonus = total_bonus + ?,
                         available_bonus = available_bonus + ?
                     WHERE id = ?",
                    [$rollupAmount, $rollupAmount, $rollupAmount, $sponsorUser['id']]
                );

                $rollupCount++;
                $level++;
                $currentSponsorUserId = $sponsorUser['sponsor_id'];
            }

            $fixLog[] = "Rollup 보너스 {$rollupCount}단계 추가";
            $bonusesAdded += $rollupCount;
        }

        $fixLog[] = "총 {$bonusesAdded}건의 보너스 추가 완료";
    }

    // 성공 응답
    echo json_encode([
        'success' => true,
        'message' => implode("\n", $fixLog),
        'user_id' => $userId,
        'user_name' => $user['name'],
        'package_amount' => $packageAmount
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
