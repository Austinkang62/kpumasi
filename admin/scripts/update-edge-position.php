<?php
/**
 * 엣지 보너스의 edge_position 업데이트 스크립트
 * edge_position이 null인 기존 보너스 데이터를 수정합니다.
 */

require_once __DIR__ . '/../../config/database.php';

$db = Database::getInstance();

echo "=== 엣지 보너스 edge_position 업데이트 시작 ===\n\n";

try {
    $db->beginTransaction();

    // 모든 엣지 보너스 조회 (재계산)
    $bonuses = $db->select(
        "SELECT b.id, b.user_id, b.from_user_id, b.bonus_type
         FROM bonuses b
         WHERE b.bonus_type = 'edge'",
        []
    );

    echo "처리할 보너스: " . count($bonuses) . "건\n\n";

    $updated = 0;
    $skipped = 0;

    foreach ($bonuses as $bonus) {
        echo "보너스 ID {$bonus['id']} 처리 중...\n";

        // 보너스 수령자 정보
        $receiver = $db->selectOne(
            "SELECT id, user_id FROM users WHERE id = ?",
            [$bonus['user_id']]
        );

        if (!$receiver) {
            echo "  ❌ 수령자를 찾을 수 없음\n";
            $skipped++;
            continue;
        }

        // 보너스 발생자 정보
        $giver = $db->selectOne(
            "SELECT id, user_id, sponsor_id FROM users WHERE id = ?",
            [$bonus['from_user_id']]
        );

        if (!$giver) {
            echo "  ❌ 발생자를 찾을 수 없음\n";
            $skipped++;
            continue;
        }

        echo "  수령자: {$receiver['user_id']}, 발생자: {$giver['user_id']}\n";

        // 발생자의 sponsor 체인을 따라 올라가며 수령자의 직접 하위를 찾음
        $currentUserId = $giver['id'];
        $currentUserSponsorId = $giver['sponsor_id'];
        $visitedUsers = [];
        $edgePosition = null;
        $maxDepth = 50;
        $depth = 0;

        while ($currentUserSponsorId && $depth < $maxDepth) {
            // 무한루프 방지
            if (in_array($currentUserId, $visitedUsers)) {
                echo "  ⚠️ 순환 참조 감지\n";
                break;
            }
            $visitedUsers[] = $currentUserId;

            // 현재 노드의 sponsor가 수령자인지 확인
            if ($currentUserSponsorId === $receiver['id']) {
                // 수령자의 직접 하위를 찾았음!
                $directChild = $db->selectOne(
                    "SELECT user_id, sponsor_position FROM users WHERE id = ?",
                    [$currentUserId]
                );

                if ($directChild && $directChild['sponsor_position']) {
                    $edgePosition = ($directChild['sponsor_position'] == 1) ? 'left' : 'right';
                    echo "  ✓ 직접 하위: {$directChild['user_id']} (position: {$directChild['sponsor_position']}) → {$edgePosition}\n";
                    break;
                }
            }

            // 다음 상위로 이동
            $currentUser = $db->selectOne(
                "SELECT id, sponsor_id FROM users WHERE id = ?",
                [$currentUserSponsorId]
            );

            if (!$currentUser) {
                break;
            }

            $currentUserId = $currentUser['id'];
            $currentUserSponsorId = $currentUser['sponsor_id'];
            $depth++;
        }

        if ($edgePosition) {
            $db->execute(
                "UPDATE bonuses SET edge_position = ? WHERE id = ?",
                [$edgePosition, $bonus['id']]
            );
            echo "  ✅ 업데이트 완료: {$edgePosition}\n";
            $updated++;
        } else {
            echo "  ⚠️ 레그를 확인할 수 없음 (스킵)\n";
            $skipped++;
        }

        echo "\n";
    }

    $db->commit();

    echo "=== 완료 ===\n";
    echo "업데이트: {$updated}건\n";
    echo "스킵: {$skipped}건\n";
    echo "총: " . count($bonuses) . "건\n";

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollback();
    }
    echo "❌ 오류 발생: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
