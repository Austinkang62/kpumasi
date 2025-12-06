<?php
/**
 * 엣지 보너스 + 추천매칭 보너스 재발생 스크립트
 * 기존 엣지/매칭 보너스를 삭제하고 올바른 로직으로 재계산
 */

// 타임아웃 설정
set_time_limit(300); // 5분
ini_set('max_execution_time', 300);

// 에러 출력
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/BonusDistributor.php';

echo "=== 엣지 + 매칭 보너스 재발생 시작 ===\n\n";
flush(); // 즉시 출력

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // 1. 기존 엣지/매칭 보너스 데이터 백업
    echo "1. 기존 엣지/매칭 보너스 백업 중...\n";
    $oldEdgeBonuses = $db->select("SELECT * FROM bonuses WHERE bonus_type = 'edge'");
    $oldMatchingBonuses = $db->select("SELECT * FROM bonuses WHERE bonus_type = 'matching'");
    echo "   백업 완료: 엣지 " . count($oldEdgeBonuses) . "건, 매칭 " . count($oldMatchingBonuses) . "건\n\n";

    // 2. 기존 엣지 보너스로 증가된 잔액 차감
    echo "2. 기존 엣지 보너스 잔액 차감 중...\n";
    foreach ($oldEdgeBonuses as $bonus) {
        $amount = floatval($bonus['amount']);
        $userId = $bonus['user_id'];

        $db->update(
            "UPDATE users SET
                available_bonus = available_bonus - ?,
                total_bonus = total_bonus - ?,
                total_edge_bonus = total_edge_bonus - ?
             WHERE id = ?",
            [$amount, $amount, $amount, $userId]
        );
    }
    echo "   차감 완료\n\n";

    // 3. 기존 매칭 보너스로 증가된 잔액 차감
    echo "3. 기존 매칭 보너스 잔액 차감 중...\n";
    foreach ($oldMatchingBonuses as $bonus) {
        $amount = floatval($bonus['amount']);
        $userId = $bonus['user_id'];

        $db->update(
            "UPDATE users SET
                available_bonus = available_bonus - ?,
                total_bonus = total_bonus - ?,
                total_matching_bonus = total_matching_bonus - ?
             WHERE id = ?",
            [$amount, $amount, $amount, $userId]
        );
    }
    echo "   차감 완료\n\n";

    // 4. bonus_summary 테이블의 엣지/매칭 보너스 초기화
    echo "4. bonus_summary 테이블 엣지/매칭 보너스 초기화 중...\n";
    $db->update(
        "UPDATE bonus_summary SET
            total_edge_bonus = 0,
            edge_count = 0,
            total_matching_bonus = 0,
            matching_count = 0"
    );
    echo "   초기화 완료\n\n";

    // 5. 기존 엣지/매칭 보너스 삭제
    echo "5. 기존 엣지/매칭 보너스 삭제 중...\n";
    flush();
    $db->execute("DELETE FROM bonuses WHERE bonus_type = 'edge'");
    echo "   엣지 보너스 삭제 완료\n";
    flush();
    $db->execute("DELETE FROM bonuses WHERE bonus_type = 'matching'");
    echo "   매칭 보너스 삭제 완료\n\n";
    flush();

    // 6. 모든 회원을 가입일 순으로 조회하여 엣지/매칭 보너스 재발생
    echo "6. 엣지 + 매칭 보너스 재발생 중...\n";
    $users = $db->select(
        "SELECT id, user_id, sponsor_id, created_at
         FROM users
         WHERE sponsor_id IS NOT NULL
         ORDER BY created_at ASC"
    );

    $distributor = new BonusDistributor();
    $edgeSuccessCount = 0;
    $edgeSkipCount = 0;
    $matchingSuccessCount = 0;
    $matchingSkipCount = 0;

    foreach ($users as $user) {
        $newUserId = $user['id'];
        $sponsorCode = $user['sponsor_id'];
        $packageAmount = 100.00; // 기본 패키지 금액

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

        $bonusAmount = $packageAmount * 0.25; // 25%

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
                "INSERT INTO bonuses (user_id, from_user_id, bonus_type, amount, package_amount, edge_position, description, created_at)
                 VALUES (?, ?, 'edge', ?, ?, ?, ?, ?)",
                [
                    $edgeReceiver['id'],
                    $newUserId,
                    $bonusAmount,
                    $packageAmount,
                    $direction,
                    "바이너리 엣지 보너스 ({$direction}, 25%)",
                    $user['created_at']
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
                     total_edge_bonus = total_edge_bonus + VALUES(total_edge_bonus),
                     edge_count = edge_count + 1,
                     last_bonus_at = NOW()",
                [$edgeReceiver['id'], $bonusAmount]
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
                        "INSERT INTO bonuses (user_id, from_user_id, bonus_type, amount, package_amount, description, created_at)
                         VALUES (?, ?, 'matching', ?, ?, ?, ?)",
                        [
                            $matchingReceiver['id'],
                            $newUserId,
                            $bonusAmount,
                            $packageAmount,
                            "추천매칭 보너스 (엣지 수령자의 추천인, 패키지의 25%)",
                            $user['created_at']
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
                             total_matching_bonus = total_matching_bonus + VALUES(total_matching_bonus),
                             matching_count = matching_count + 1,
                             last_bonus_at = NOW()",
                        [$matchingReceiver['id'], $bonusAmount]
                    );

                    $matchingSuccessCount++;
                } else {
                    $matchingSkipCount++;
                }
            } else {
                $matchingSkipCount++;
            }

        } else {
            $edgeSkipCount++;
            $matchingSkipCount++;
        }

        if (($edgeSuccessCount + $matchingSuccessCount) % 20 == 0) {
            echo "   진행 중... 엣지 {$edgeSuccessCount}건, 매칭 {$matchingSuccessCount}건 재발생 완료\n";
        }
    }

    echo "   재발생 완료\n";
    echo "   - 엣지: {$edgeSuccessCount}건 성공, {$edgeSkipCount}건 스킵\n";
    echo "   - 매칭: {$matchingSuccessCount}건 성공, {$matchingSkipCount}건 스킵\n\n";

    $db->commit();
    echo "=== 엣지 + 매칭 보너스 재발생 완료 ===\n";
    echo "엣지 보너스: {$edgeSuccessCount}건 성공, {$edgeSkipCount}건 스킵\n";
    echo "매칭 보너스: {$matchingSuccessCount}건 성공, {$matchingSkipCount}건 스킵\n";

} catch (Exception $e) {
    $db->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
