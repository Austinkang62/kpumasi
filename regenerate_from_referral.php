<?php
/**
 * 추천 보너스 기반 엣지/매칭 보너스 재발생 스크립트
 * 추천 보너스의 from_user_id를 기준으로 엣지/매칭 보너스 재계산
 */

// 타임아웃 설정
set_time_limit(300);
ini_set('max_execution_time', 300);

// 에러 출력
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';

echo "=== 추천 보너스 기반 엣지/매칭 보너스 재발생 시작 ===\n\n";
flush();

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // 1. 추천 보너스 조회 (가입일 순)
    echo "1. 추천 보너스 조회 중...\n";
    $referralBonuses = $db->select(
        "SELECT b.*, u.user_id as from_user_code
         FROM bonuses b
         LEFT JOIN users u ON b.from_user_id = u.id
         WHERE b.bonus_type = 'referral'
         ORDER BY b.created_at ASC"
    );
    echo "   조회 완료: " . count($referralBonuses) . "건\n\n";
    flush();

    $edgeSuccessCount = 0;
    $edgeSkipCount = 0;
    $matchingSuccessCount = 0;
    $matchingSkipCount = 0;

    echo "2. 엣지/매칭 보너스 재발생 중...\n";
    flush();

    foreach ($referralBonuses as $referralBonus) {
        $newUserId = $referralBonus['from_user_id']; // 가입한 회원 ID
        $packageAmount = floatval($referralBonus['package_amount']); // 패키지 금액
        $bonusAmount = $packageAmount * 0.25; // 25%
        $createdAt = $referralBonus['created_at']; // 원래 가입 시각

        // === 1. 엣지 보너스 재발생 ===
        $newUser = $db->selectOne(
            "SELECT id, user_id, sponsor_id, sponsor_position FROM users WHERE id = ?",
            [$newUserId]
        );

        if (!$newUser || !$newUser['sponsor_position']) {
            $edgeSkipCount++;
            $matchingSkipCount++;
            continue;
        }

        // 엣지(꺾임) 찾기
        $currentUserId = $newUser['user_id'];
        $lastPosition = intval($newUser['sponsor_position']); // 1=좌측, 2=우측
        $level = 0;
        $edgeReceiver = null;

        while (true) {
            $parent = $db->selectOne(
                "SELECT id, user_id, sponsor_id, sponsor_position FROM users WHERE user_id = ?",
                [$currentUserId]
            );

            if (!$parent || !$parent['sponsor_id']) {
                // 루트까지 갔는데 꺾임 없음
                break;
            }

            $level++;
            $currentPosition = intval($parent['sponsor_position']);

            // 꺾임 발생 확인
            if ($level > 1 && $currentPosition !== $lastPosition) {
                // 꺾임 발견! 꺾인 지점의 부모 = 받는 사람
                $edgeReceiver = $db->selectOne(
                    "SELECT id, user_id as user_code, referral_id FROM users WHERE user_id = ?",
                    [$parent['sponsor_id']]
                );
                break;
            }

            $lastPosition = $currentPosition;
            $currentUserId = $parent['sponsor_id'];
        }

        if ($edgeReceiver) {
            // 엣지 보너스 기록
            $direction = $newUser['sponsor_position'] == 1 ? 'left' : 'right';
            $db->insert(
                "INSERT INTO bonuses (user_id, from_user_id, bonus_type, amount, package_amount, edge_position, description, status, created_at)
                 VALUES (?, ?, 'edge', ?, ?, ?, ?, 'paid', ?)",
                [
                    $edgeReceiver['id'],
                    $newUserId,
                    $bonusAmount,
                    $packageAmount,
                    $direction,
                    "바이너리 엣지 보너스 ({$direction}, 25%)",
                    $createdAt
                ]
            );

            // 엣지 보너스 잔액 업데이트
            $db->update(
                "UPDATE users SET
                    available_bonus = available_bonus + ?,
                    total_bonus = total_bonus + ?,
                    total_edge_bonus = total_edge_bonus + ?
                 WHERE id = ?",
                [$bonusAmount, $bonusAmount, $bonusAmount, $edgeReceiver['id']]
            );

            // bonus_summary 업데이트
            $db->execute(
                "INSERT INTO bonus_summary
                    (user_id, total_edge_bonus, edge_count, last_bonus_at)
                 VALUES (?, ?, 1, NOW())
                 ON DUPLICATE KEY UPDATE
                     total_edge_bonus = total_edge_bonus + ?,
                     edge_count = edge_count + 1,
                     last_bonus_at = NOW()",
                [$edgeReceiver['id'], $bonusAmount, $bonusAmount]
            );

            $edgeSuccessCount++;

            // === 2. 매칭 보너스 재발생 (엣지 수령자의 추천인) ===
            if ($edgeReceiver['referral_id']) {
                $matchingReceiver = $db->selectOne(
                    "SELECT id, user_id as user_code FROM users WHERE id = ?",
                    [$edgeReceiver['referral_id']]
                );

                if ($matchingReceiver) {
                    // 매칭 보너스 기록
                    $db->insert(
                        "INSERT INTO bonuses (user_id, from_user_id, bonus_type, amount, package_amount, description, status, created_at)
                         VALUES (?, ?, 'matching', ?, ?, ?, 'paid', ?)",
                        [
                            $matchingReceiver['id'],
                            $newUserId,
                            $bonusAmount,
                            $packageAmount,
                            "추천매칭 보너스 (엣지 수령자의 추천인, 패키지의 25%)",
                            $createdAt
                        ]
                    );

                    // 매칭 보너스 잔액 업데이트
                    $db->update(
                        "UPDATE users SET
                            available_bonus = available_bonus + ?,
                            total_bonus = total_bonus + ?,
                            total_matching_bonus = total_matching_bonus + ?
                         WHERE id = ?",
                        [$bonusAmount, $bonusAmount, $bonusAmount, $matchingReceiver['id']]
                    );

                    // bonus_summary 업데이트
                    $db->execute(
                        "INSERT INTO bonus_summary
                            (user_id, total_matching_bonus, matching_count, last_bonus_at)
                         VALUES (?, ?, 1, NOW())
                         ON DUPLICATE KEY UPDATE
                             total_matching_bonus = total_matching_bonus + ?,
                             matching_count = matching_count + 1,
                             last_bonus_at = NOW()",
                        [$matchingReceiver['id'], $bonusAmount, $bonusAmount]
                    );

                    $matchingSuccessCount++;
                } else {
                    $matchingSkipCount++;
                }
            } else {
                $matchingSkipCount++;
            }

        } else {
            // 엣지가 없는 경우도 기록 (투명성)
            // user_id = 0 (엣지 없음을 나타내는 특별한 값), status = 'cancelled' (미발생)
            $db->insert(
                "INSERT INTO bonuses (user_id, from_user_id, bonus_type, amount, package_amount, edge_position, description, status, created_at)
                 VALUES (0, ?, 'edge', 0, ?, '-', '엣지 미발생 (루트까지 방향 변경 없음)', 'cancelled', ?)",
                [$newUserId, $packageAmount, $createdAt]
            );

            $edgeSkipCount++;
            $matchingSkipCount++;
        }

        if (($edgeSuccessCount + $matchingSuccessCount) % 10 == 0) {
            echo "   진행 중... 엣지 {$edgeSuccessCount}건, 매칭 {$matchingSuccessCount}건\n";
            flush();
        }
    }

    echo "\n재발생 완료\n";
    echo "   - 엣지: {$edgeSuccessCount}건 성공, {$edgeSkipCount}건 스킵\n";
    echo "   - 매칭: {$matchingSuccessCount}건 성공, {$matchingSkipCount}건 스킵\n\n";
    flush();

    $db->commit();

    echo "=== 엣지/매칭 보너스 재발생 완료 ===\n";
    echo "엣지 보너스: {$edgeSuccessCount}건 성공, {$edgeSkipCount}건 스킵\n";
    echo "매칭 보너스: {$matchingSuccessCount}건 성공, {$matchingSkipCount}건 스킵\n";

} catch (Exception $e) {
    $db->rollback();
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
