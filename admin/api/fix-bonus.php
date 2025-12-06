<?php
/**
 * 보너스 수정 API
 * 잘못 지급된 보너스를 삭제하고 올바른 수령자에게 재발행
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

$action = $_POST['action'] ?? '';

try {
    $db = Database::getInstance();

    if ($action === 'fix_bonus') {
        // 파라미터 확인
        $bonusType = $_POST['bonus_type'] ?? '';
        $giverId = $_POST['giver_id'] ?? '';
        $wrongReceiverId = $_POST['wrong_receiver_id'] ?? '';
        $correctReceiverId = $_POST['correct_receiver_id'] ?? '';
        $packageAmount = floatval($_POST['package_amount'] ?? 0);

        if (!$bonusType || !$giverId || !$correctReceiverId || !$packageAmount) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => '필수 파라미터가 누락되었습니다.']);
            ob_end_flush();
            exit;
        }

        // 발생자 및 수령자 정보 조회
        $giver = $db->selectOne("SELECT id, user_id FROM users WHERE user_id = ?", [$giverId]);
        $correctReceiver = $db->selectOne("SELECT id, user_id FROM users WHERE user_id = ?", [$correctReceiverId]);

        if (!$giver || !$correctReceiver) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => '회원 정보를 찾을 수 없습니다.']);
            ob_end_flush();
            exit;
        }

        $db->beginTransaction();

        // 1단계: 잘못 지급된 보너스 삭제
        $deletedCount = 0;
        if ($wrongReceiverId) {
            $wrongReceiver = $db->selectOne("SELECT id, user_id FROM users WHERE user_id = ?", [$wrongReceiverId]);
            if ($wrongReceiver) {
                // 잘못 지급된 보너스 조회
                $wrongBonuses = $db->select(
                    "SELECT id, amount, payment_type FROM bonuses
                     WHERE from_user_id = ? AND user_id = ? AND bonus_type = ?",
                    [$giver['id'], $wrongReceiver['id'], $bonusType]
                );

                // 보너스 삭제
                foreach ($wrongBonuses as $bonus) {
                    $db->execute(
                        "DELETE FROM bonuses WHERE id = ?",
                        [$bonus['id']]
                    );

                    // 수령자의 잔액 조정 (캐시/아바타 구분)
                    if ($bonus['payment_type'] === 'cash') {
                        $db->execute(
                            "UPDATE users SET available_bonus = available_bonus - ? WHERE id = ?",
                            [$bonus['amount'], $wrongReceiver['id']]
                        );
                    } else if ($bonus['payment_type'] === 'avatar') {
                        $db->execute(
                            "UPDATE users SET avatar_points = avatar_points - ? WHERE id = ?",
                            [$bonus['amount'], $wrongReceiver['id']]
                        );
                    }

                    $deletedCount++;
                }
            }
        }

        // 2단계: 올바른 수령자에게 보너스 재발행
        $bonusAmount = $packageAmount * 0.25; // 25%
        $cashAmount = $bonusAmount * 0.65; // 65% 캐시
        $avatarPointAmount = $bonusAmount * 0.35; // 35% 아바타 포인트

        $bonusTypeLabel = strtoupper($bonusType);

        // 캐시 보너스 (65%)
        $cashBonusId = $db->insert(
            "INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
             VALUES (?, ?, ?, 'cash', ?, ?, ?, NOW())",
            [
                $correctReceiver['id'],
                $giver['id'],
                $bonusType,
                $cashAmount,
                $packageAmount,
                "{$bonusTypeLabel} 보너스 - 캐시 (65%) [수정]"
            ]
        );

        // 아바타 포인트 보너스 (35%)
        $avatarBonusId = $db->insert(
            "INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
             VALUES (?, ?, ?, 'avatar', ?, ?, ?, NOW())",
            [
                $correctReceiver['id'],
                $giver['id'],
                $bonusType,
                $avatarPointAmount,
                $packageAmount,
                "{$bonusTypeLabel} 보너스 - 아바타 포인트 (35%) [수정]"
            ]
        );

        // 수령자 잔액 업데이트
        $db->execute(
            "UPDATE users SET available_bonus = available_bonus + ? WHERE id = ?",
            [$cashAmount, $correctReceiver['id']]
        );

        $db->execute(
            "UPDATE users SET avatar_points = avatar_points + ? WHERE id = ?",
            [$avatarPointAmount, $correctReceiver['id']]
        );

        $db->commit();

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => '보너스 수정이 완료되었습니다.',
            'details' => [
                'deleted_count' => $deletedCount,
                'wrong_receiver' => $wrongReceiverId,
                'correct_receiver' => $correctReceiverId,
                'cash_amount' => $cashAmount,
                'avatar_amount' => $avatarPointAmount,
                'total_amount' => $bonusAmount
            ]
        ]);
        ob_end_flush();

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        ob_end_flush();
    }

} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollback();
    }
    error_log('Fix Bonus API Error: ' . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    ob_end_flush();
}
