<?php
/**
 * 아바타 total_bonus 재계산 스크립트
 * bonuses 테이블 기준으로 users.total_bonus 업데이트
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // 모든 아바타 조회
    $avatars = $db->select(
        "SELECT id, user_id FROM users WHERE is_avatar = 1"
    );

    $updated = 0;
    foreach ($avatars as $avatar) {
        // bonuses 테이블에서 실제 보너스 합계 계산
        $bonusSum = $db->selectOne(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM bonuses
             WHERE user_id = ?
             AND bonus_type IN ('referral', 'edge', 'matching', 'rollup')
             AND amount > 0",
            [$avatar['id']]
        );

        $totalBonus = floatval($bonusSum['total']);

        // users 테이블 업데이트 (직접 PDO 사용)
        $stmt = $conn->prepare("UPDATE users SET total_bonus = ? WHERE id = ?");
        $stmt->execute([$totalBonus, $avatar['id']]);

        $updated++;
    }

    echo json_encode([
        'success' => true,
        'message' => "{$updated}개 아바타의 total_bonus를 재계산했습니다."
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
