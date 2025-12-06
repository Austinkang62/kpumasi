<?php
/**
 * 아바타 보너스 디버그 API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

try {
    $db = Database::getInstance();

    // 아바타 목록 조회
    $avatars = $db->select(
        "SELECT id, user_id, name FROM users WHERE is_avatar = 1 LIMIT 5"
    );

    $result = [];
    foreach ($avatars as $avatar) {
        // 각 아바타의 보너스 조회
        $bonuses = $db->select(
            "SELECT bonus_type, amount, created_at
             FROM bonuses
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT 5",
            [$avatar['id']]
        );

        // 총 보너스 합계
        $totalBonus = $db->selectOne(
            "SELECT
                COALESCE(SUM(amount), 0) as total,
                COUNT(*) as count
             FROM bonuses
             WHERE user_id = ?",
            [$avatar['id']]
        );

        $result[] = [
            'avatar_id' => $avatar['user_id'],
            'avatar_name' => $avatar['name'],
            'total_bonus' => floatval($totalBonus['total']),
            'bonus_count' => intval($totalBonus['count']),
            'recent_bonuses' => $bonuses
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
