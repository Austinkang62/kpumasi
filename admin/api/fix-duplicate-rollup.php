<?php
/**
 * 중복 롤업 보너스 삭제
 * 같은 from_user_id에 대해 같은 user_id가 여러 레벨에서 보너스를 받은 경우 수정
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

    // 중복 롤업 보너스 찾기
    $duplicates = $db->select("
        SELECT
            from_user_id,
            user_id,
            COUNT(*) as duplicate_count,
            GROUP_CONCAT(level ORDER BY level) as levels,
            GROUP_CONCAT(id ORDER BY level) as bonus_ids
        FROM bonuses
        WHERE bonus_type = 'rollup'
        GROUP BY from_user_id, user_id
        HAVING COUNT(*) > 1
        ORDER BY duplicate_count DESC
    ");

    if (empty($duplicates)) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => '중복된 롤업 보너스가 없습니다.',
            'duplicates' => []
        ]);
        ob_end_flush();
        exit;
    }

    $fixMode = $_GET['fix'] ?? 'no';
    $deleted = [];
    $totalDeleted = 0;
    $totalRefunded = 0;

    foreach ($duplicates as $dup) {
        $bonusIds = explode(',', $dup['bonus_ids']);
        $levels = explode(',', $dup['levels']);

        // 첫 번째 보너스는 유지, 나머지는 삭제 대상
        $keepId = array_shift($bonusIds);
        $keepLevel = array_shift($levels);

        if ($fixMode === 'yes') {
            // 삭제할 보너스 조회 (금액 환불을 위해)
            foreach ($bonusIds as $bonusId) {
                $bonus = $db->selectOne(
                    "SELECT * FROM bonuses WHERE id = ?",
                    [$bonusId]
                );

                if ($bonus) {
                    $amount = floatval($bonus['amount']);
                    $userId = intval($bonus['user_id']);

                    // 보너스 삭제
                    $db->execute("DELETE FROM bonuses WHERE id = ?", [$bonusId]);

                    // 사용자 잔액 차감
                    if ($bonus['payment_type'] === 'cash') {
                        $db->execute(
                            "UPDATE users SET available_bonus = available_bonus - ?, total_bonus = total_bonus - ? WHERE id = ?",
                            [$amount, $amount, $userId]
                        );
                    } else {
                        $db->execute(
                            "UPDATE users SET avatar_points = avatar_points - ? WHERE id = ?",
                            [$amount, $userId]
                        );
                    }

                    $totalDeleted++;
                    $totalRefunded += $amount;
                }
            }
        }

        $deleted[] = [
            'from_user_id' => $dup['from_user_id'],
            'user_id' => $dup['user_id'],
            'duplicate_count' => intval($dup['duplicate_count']),
            'kept_level' => $keepLevel,
            'deleted_levels' => implode(', ', $levels),
            'deleted_ids' => $bonusIds
        ];
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => $fixMode === 'yes' ? "중복 보너스 삭제 완료" : "중복 보너스 발견 (삭제하려면 ?fix=yes 추가)",
        'fix_mode' => $fixMode,
        'total_duplicates' => count($duplicates),
        'total_deleted' => $totalDeleted,
        'total_refunded' => $totalRefunded,
        'duplicates' => $deleted
    ], JSON_PRETTY_PRINT);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
    ob_end_flush();
}
