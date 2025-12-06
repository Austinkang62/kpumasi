<?php
/**
 * 보너스 누락 건 수동 처리 API
 * POST /admin/api/fix-missing-bonuses.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/BonusDistributor.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? null;

    if (!$userId) {
        throw new Exception('user_id is required');
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // 사용자 확인
    $user = $pdo->query("SELECT id, user_id FROM users WHERE id = " . intval($userId))->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Sales 확인
    $sale = $pdo->query("SELECT id, amount FROM sales WHERE user_id = {$user['id']} AND amount = 100")->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new Exception('Sales record not found');
    }

    // 이미 보너스가 분배되었는지 확인
    $existingBonus = $pdo->query("SELECT COUNT(*) as cnt FROM bonuses WHERE from_user_id = {$user['id']}")->fetch(PDO::FETCH_ASSOC);

    if ($existingBonus['cnt'] > 0) {
        throw new Exception('Bonuses already distributed');
    }

    // 보너스 배포
    $bonusDistributor = new BonusDistributor();
    $bonusResult = $bonusDistributor->distributeAllBonuses($user['id'], $sale['amount']);

    echo json_encode([
        'success' => true,
        'message' => '보너스 분배 완료',
        'data' => [
            'user_id' => $user['user_id'],
            'sale_id' => $sale['id'],
            'total_distributed' => $bonusResult['total_distributed'],
            'bonus_count' => $bonusResult['total_bonuses'] ?? 0
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
