<?php
/**
 * 강제 삭제 - 엣지/매칭 보너스
 */

set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== 엣지/매칭 보너스 강제 삭제 ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $conn->beginTransaction();

    // 1. 현재 데이터 확인
    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM bonuses WHERE bonus_type = 'edge'");
    $edgeCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM bonuses WHERE bonus_type = 'matching'");
    $matchingCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

    echo "현재 엣지: {$edgeCount}건\n";
    echo "현재 매칭: {$matchingCount}건\n\n";

    // 2. 엣지 보너스 잔액 차감
    echo "엣지 보너스 잔액 차감 중...\n";
    $stmt = $conn->query("
        SELECT user_id, SUM(amount) as total
        FROM bonuses
        WHERE bonus_type = 'edge' AND user_id > 0
        GROUP BY user_id
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userId = $row['user_id'];
        $amount = $row['total'];

        $updateStmt = $conn->prepare("
            UPDATE users SET
                available_bonus = available_bonus - ?,
                total_bonus = total_bonus - ?,
                total_edge_bonus = GREATEST(total_edge_bonus - ?, 0)
            WHERE id = ?
        ");
        $updateStmt->execute([$amount, $amount, $amount, $userId]);
    }
    echo "차감 완료\n\n";

    // 3. 매칭 보너스 잔액 차감
    echo "매칭 보너스 잔액 차감 중...\n";
    $stmt = $conn->query("
        SELECT user_id, SUM(amount) as total
        FROM bonuses
        WHERE bonus_type = 'matching' AND user_id > 0
        GROUP BY user_id
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $userId = $row['user_id'];
        $amount = $row['total'];

        $updateStmt = $conn->prepare("
            UPDATE users SET
                available_bonus = available_bonus - ?,
                total_bonus = total_bonus - ?,
                total_matching_bonus = GREATEST(total_matching_bonus - ?, 0)
            WHERE id = ?
        ");
        $updateStmt->execute([$amount, $amount, $amount, $userId]);
    }
    echo "차감 완료\n\n";

    // 4. bonus_summary 초기화
    echo "bonus_summary 초기화 중...\n";
    $conn->exec("
        UPDATE bonus_summary SET
            total_edge_bonus = 0,
            edge_count = 0,
            total_matching_bonus = 0,
            matching_count = 0
    ");
    echo "초기화 완료\n\n";

    // 5. 삭제
    echo "bonuses 테이블에서 삭제 중...\n";
    $stmt = $conn->exec("DELETE FROM bonuses WHERE bonus_type = 'edge'");
    echo "엣지 삭제: {$stmt}건\n";

    $stmt = $conn->exec("DELETE FROM bonuses WHERE bonus_type = 'matching'");
    echo "매칭 삭제: {$stmt}건\n\n";

    $conn->commit();

    echo "=== 삭제 완료 ===\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
