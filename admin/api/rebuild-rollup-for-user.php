<?php
/**
 * 특정 회원의 롤업 보너스 재발행
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/BonusDistributor.php';

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
    $userId = intval($_GET['user_id'] ?? 0);

    if ($userId <= 0) {
        throw new Exception('유효하지 않은 사용자 ID입니다.');
    }

    // 사용자 정보 조회
    $user = $db->selectOne("SELECT id, user_id, sponsor_id FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        throw new Exception('사용자를 찾을 수 없습니다.');
    }

    // === 1단계: 이 사용자의 매출에 대한 기존 롤업 보너스 삭제 ===
    $existingBonuses = $db->select("
        SELECT b.id, b.user_id, b.amount, b.payment_type
        FROM bonuses b
        WHERE b.from_user_id = ? AND b.bonus_type = 'rollup'
    ", [$userId]);

    $deletedCount = count($existingBonuses);
    $deletedAmount = 0;

    foreach ($existingBonuses as $bonus) {
        $receiverId = intval($bonus['user_id']);
        $amount = floatval($bonus['amount']);
        $paymentType = $bonus['payment_type'];

        // 받은 사람의 잔액 차감
        if ($paymentType === 'cash') {
            $db->execute(
                "UPDATE users SET available_bonus = available_bonus - ?, total_bonus = total_bonus - ? WHERE id = ?",
                [$amount, $amount, $receiverId]
            );
        } else {
            $db->execute(
                "UPDATE users SET avatar_points = avatar_points - ? WHERE id = ?",
                [$amount, $receiverId]
            );
        }

        $deletedAmount += $amount;
    }

    // 보너스 삭제
    $db->execute("DELETE FROM bonuses WHERE from_user_id = ? AND bonus_type = 'rollup'", [$userId]);

    // === 2단계: 이 사용자의 매출 조회 ===
    $sales = $db->select("
        SELECT id, amount
        FROM sales
        WHERE user_id = ? AND status IN ('completed', 'confirmed')
        ORDER BY id
    ", [$userId]);

    // === 3단계: 각 매출에 대해 롤업 보너스 재발행 ===
    $distributor = new BonusDistributor();
    $createdCount = 0;
    $createdAmount = 0;

    foreach ($sales as $sale) {
        try {
            $distributor->distributeBonus(
                $userId,
                $user['sponsor_id'],
                floatval($sale['amount']),
                ['rollup']
            );
        } catch (Exception $e) {
            error_log("롤업 재발행 실패 (user_id={$userId}, sale_id={$sale['id']}): " . $e->getMessage());
        }
    }

    // === 4단계: 실제 생성된 롤업 보너스 조회 ===
    $newBonuses = $db->select("
        SELECT id, amount
        FROM bonuses
        WHERE from_user_id = ? AND bonus_type = 'rollup'
    ", [$userId]);

    $createdCount = count($newBonuses);
    foreach ($newBonuses as $bonus) {
        $createdAmount += floatval($bonus['amount']);
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => '롤업 보너스 재발행 완료',
        'user' => [
            'id' => $userId,
            'user_id' => $user['user_id']
        ],
        'deleted' => [
            'count' => $deletedCount,
            'total_amount' => round($deletedAmount, 2)
        ],
        'created' => [
            'count' => $createdCount,
            'total_amount' => round($createdAmount, 2),
            'sales_processed' => count($sales)
        ]
    ], JSON_PRETTY_PRINT);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
    ob_end_flush();
}
