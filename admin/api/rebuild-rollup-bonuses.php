<?php
/**
 * 롤업 보너스 재발행
 * 1. 기존 롤업 보너스 전체 삭제 및 잔액 환불
 * 2. 모든 완료된 sales에 대해 롤업 보너스 재계산 및 지급
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
    $mode = $_GET['mode'] ?? 'preview'; // preview 또는 execute

    // === 1단계: 기존 롤업 보너스 조회 ===
    $existingBonuses = $db->select("
        SELECT id, user_id, amount, payment_type
        FROM bonuses
        WHERE bonus_type = 'rollup'
        ORDER BY id
    ");

    $totalExisting = count($existingBonuses);
    $totalRefundAmount = 0;

    foreach ($existingBonuses as $bonus) {
        $totalRefundAmount += floatval($bonus['amount']);
    }

    if ($mode === 'preview') {
        ob_clean();
        echo json_encode([
            'success' => true,
            'mode' => 'preview',
            'message' => '미리보기 모드 - 실제 삭제하려면 ?mode=execute 사용',
            'existing_bonuses' => [
                'count' => $totalExisting,
                'total_amount' => $totalRefundAmount
            ]
        ], JSON_PRETTY_PRINT);
        ob_end_flush();
        exit;
    }

    // === 2단계: 기존 롤업 보너스 삭제 및 환불 ===
    $refundedUsers = [];
    foreach ($existingBonuses as $bonus) {
        $userId = intval($bonus['user_id']);
        $amount = floatval($bonus['amount']);
        $paymentType = $bonus['payment_type'];

        // 사용자 잔액 차감
        if ($paymentType === 'cash') {
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

        if (!isset($refundedUsers[$userId])) {
            $refundedUsers[$userId] = 0;
        }
        $refundedUsers[$userId] += $amount;
    }

    // 롤업 보너스 전체 삭제
    $db->execute("DELETE FROM bonuses WHERE bonus_type = 'rollup'");

    // === 3단계: 모든 완료된 sales 조회 ===
    $sales = $db->select("
        SELECT
            s.id,
            s.user_id,
            s.amount,
            u.user_id as user_code,
            u.sponsor_id,
            u.package_id
        FROM sales s
        JOIN users u ON s.user_id = u.id
        WHERE s.status IN ('completed', 'confirmed')
        ORDER BY s.id
    ");

    // === 4단계: 각 sale에 대해 롤업 보너스 재발행 ===
    $distributor = new BonusDistributor();
    $errors = [];

    foreach ($sales as $sale) {
        try {
            // distributeRollupBonus 호출
            $distributor->distributeBonus(
                intval($sale['user_id']),
                $sale['sponsor_id'],
                floatval($sale['amount']),
                ['rollup'] // 롤업만 재발행
            );
        } catch (Exception $e) {
            $errors[] = [
                'sale_id' => $sale['id'],
                'user_code' => $sale['user_code'],
                'error' => $e->getMessage()
            ];
        }
    }

    // === 5단계: 실제 생성된 롤업 보너스 조회 ===
    $newBonuses = $db->select("
        SELECT id, user_id, amount, payment_type, level
        FROM bonuses
        WHERE bonus_type = 'rollup'
        ORDER BY id
    ");

    $totalNewAmount = 0;
    foreach ($newBonuses as $bonus) {
        $totalNewAmount += floatval($bonus['amount']);
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'mode' => 'execute',
        'message' => '롤업 보너스 재발행 완료',
        'deleted' => [
            'count' => $totalExisting,
            'total_amount' => $totalRefundAmount,
            'refunded_users' => count($refundedUsers)
        ],
        'created' => [
            'count' => count($newBonuses),
            'total_amount' => $totalNewAmount,
            'sales_processed' => count($sales)
        ],
        'errors' => $errors
    ], JSON_PRETTY_PRINT);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
    ob_end_flush();
}
