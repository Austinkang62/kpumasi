<?php
/**
 * 엣지 보너스 디버깅 API
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$userId = $_GET['user_id'] ?? '';

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'user_id required']);
    exit;
}

try {
    $db = Database::getInstance();

    // 회원 조회
    $user = $db->selectOne("SELECT id, user_id FROM users WHERE user_id = ?", [$userId]);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // 엣지 보너스 조회
    $edgeBonuses = $db->select(
        "SELECT
            b.id,
            b.from_user_id,
            u.user_id as giver_code,
            u.sponsor_id,
            u.sponsor_position,
            b.edge_position,
            b.amount,
            b.payment_type,
            b.created_at
         FROM bonuses b
         JOIN users u ON b.from_user_id = u.id
         WHERE b.user_id = ? AND b.bonus_type = 'edge'
         ORDER BY b.created_at",
        [$user['id']]
    );

    // 각 발생자의 sponsor 정보 추가
    foreach ($edgeBonuses as &$bonus) {
        if ($bonus['sponsor_id']) {
            $sponsor = $db->selectOne("SELECT user_id FROM users WHERE id = ?", [$bonus['sponsor_id']]);
            $bonus['sponsor_code'] = $sponsor ? $sponsor['user_id'] : null;
        }
    }

    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'total_bonuses' => count($edgeBonuses),
        'bonuses' => $edgeBonuses
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
