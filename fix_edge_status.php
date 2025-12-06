<?php
/**
 * 엣지 미발생 건의 status를 'no_edge'로 수정
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== 엣지 미발생 건 status 수정 ===\n\n";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // 먼저 대상 확인
    $check = $conn->query("
        SELECT id, user_id, status, CHAR_LENGTH(status) as status_len
        FROM bonuses
        WHERE bonus_type = 'edge'
        AND user_id = 0
    ");

    echo "=== 수정 대상 확인 ===\n";
    while ($row = $check->fetch(PDO::FETCH_ASSOC)) {
        $statusDisplay = $row['status'] === null ? 'NULL' : "'{$row['status']}'";
        echo "ID:{$row['id']} user_id:{$row['user_id']} status:{$statusDisplay} length:{$row['status_len']}\n";
    }
    echo "\n";

    // UPDATE 실행 - ID로 직접 업데이트
    $affected = 0;
    $stmt = $conn->prepare("UPDATE bonuses SET status = 'no_edge' WHERE id = ?");

    $stmt->execute([494]);
    $affected += $stmt->rowCount();

    $stmt->execute([495]);
    $affected += $stmt->rowCount();

    echo "수정 완료: {$affected}건\n\n";

    // 결과 확인
    $result = $conn->query("
        SELECT id, user_id, from_user_id, amount, status, description
        FROM bonuses
        WHERE bonus_type = 'edge'
        ORDER BY created_at DESC
    ");

    echo "=== 수정 후 엣지 보너스 목록 ===\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "ID:{$row['id']} user:{$row['user_id']} from:{$row['from_user_id']} \${$row['amount']} status:{$row['status']}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
