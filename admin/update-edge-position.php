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

    // edge_position이 null인 엣지 보너스 조회
    $bonuses = $db->select(
        "SELECT b.id, b.user_id, b.from_user_id, b.bonus_type
         FROM bonuses b
         WHERE b.bonus_type = 'edge' AND (b.edge_position IS NULL OR b.edge_position = '')",
        []
    );

    echo "처리할 보너스: " . count($bonuses) . "건\n\n";

    $updated = 0;
    $skipped = 0;

    foreach ($bonuses as $bonus) {
        echo "보너스 ID {$bonus['id']} 처리 중...\n";

        // 보너스 발생자 정보 (발생자의 sponsor_position 사용)
        $giver = $db->selectOne(
            "SELECT id, user_id, sponsor_position FROM users WHERE id = ?",
            [$bonus['from_user_id']]
        );

        if (!$giver) {
            echo "  ❌ 발생자를 찾을 수 없음\n";
            $skipped++;
            continue;
        }

        echo "  발생자: {$giver['user_id']}, sponsor_position: {$giver['sponsor_position']}\n";

        // 발생자의 sponsor_position을 edge_position으로 사용
        // (BonusDistributor.php의 로직과 동일)
        $edgePosition = null;

        if ($giver['sponsor_position'] == 1) {
            $edgePosition = 'left';
        } else if ($giver['sponsor_position'] == 2) {
            $edgePosition = 'right';
        }

        if ($edgePosition) {
            // edge_position 업데이트
            $db->execute(
                "UPDATE bonuses SET edge_position = ? WHERE id = ?",
                [$edgePosition, $bonus['id']]
            );
            echo "  ✅ 업데이트 완료: position {$giver['sponsor_position']} → {$edgePosition}\n";
            $updated++;
        } else {
            echo "  ⚠️ sponsor_position이 없음 (스킵)\n";
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
