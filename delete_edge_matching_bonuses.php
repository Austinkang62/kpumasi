<?php
/**
 * 엣지/매칭 보너스 완전 삭제 스크립트
 */

// 타임아웃 설정
set_time_limit(300);
ini_set('max_execution_time', 300);

// 에러 출력
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';

echo "=== 엣지/매칭 보너스 완전 삭제 시작 ===\n\n";
flush();

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // 1. 현재 엣지/매칭 보너스 조회
    echo "1. 현재 엣지/매칭 보너스 조회 중...\n";
    $edgeBonuses = $db->select("SELECT * FROM bonuses WHERE bonus_type = 'edge'");
    $matchingBonuses = $db->select("SELECT * FROM bonuses WHERE bonus_type = 'matching'");
    echo "   엣지: " . count($edgeBonuses) . "건\n";
    echo "   매칭: " . count($matchingBonuses) . "건\n\n";
    flush();

    // 2. 엣지 보너스 잔액 차감
    echo "2. 엣지 보너스 잔액 차감 중...\n";
    foreach ($edgeBonuses as $bonus) {
        $amount = floatval($bonus['amount']);
        $userId = $bonus['user_id'];

        $db->update(
            "UPDATE users SET
                available_bonus = available_bonus - ?,
                total_bonus = total_bonus - ?,
                total_edge_bonus = GREATEST(total_edge_bonus - ?, 0)
             WHERE id = ?",
            [$amount, $amount, $amount, $userId]
        );
    }
    echo "   차감 완료\n\n";
    flush();

    // 3. 매칭 보너스 잔액 차감
    echo "3. 매칭 보너스 잔액 차감 중...\n";
    foreach ($matchingBonuses as $bonus) {
        $amount = floatval($bonus['amount']);
        $userId = $bonus['user_id'];

        $db->update(
            "UPDATE users SET
                available_bonus = available_bonus - ?,
                total_bonus = total_bonus - ?,
                total_matching_bonus = GREATEST(total_matching_bonus - ?, 0)
             WHERE id = ?",
            [$amount, $amount, $amount, $userId]
        );
    }
    echo "   차감 완료\n\n";
    flush();

    // 4. bonus_summary 초기화
    echo "4. bonus_summary 테이블 초기화 중...\n";
    $db->update(
        "UPDATE bonus_summary SET
            total_edge_bonus = 0,
            edge_count = 0,
            total_matching_bonus = 0,
            matching_count = 0"
    );
    echo "   초기화 완료\n\n";
    flush();

    // 5. bonuses 테이블에서 삭제
    echo "5. bonuses 테이블에서 삭제 중...\n";
    $edgeDeleted = $db->execute("DELETE FROM bonuses WHERE bonus_type = 'edge'");
    echo "   엣지 보너스 삭제: {$edgeDeleted}건\n";
    flush();

    $matchingDeleted = $db->execute("DELETE FROM bonuses WHERE bonus_type = 'matching'");
    echo "   매칭 보너스 삭제: {$matchingDeleted}건\n\n";
    flush();

    $db->commit();

    echo "=== 삭제 완료 ===\n";
    echo "엣지 보너스: {$edgeDeleted}건 삭제\n";
    echo "매칭 보너스: {$matchingDeleted}건 삭제\n";

} catch (Exception $e) {
    $db->rollback();
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
