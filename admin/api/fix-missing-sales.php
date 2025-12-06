<?php
/**
 * Sales 누락 건 수동 처리 API
 * POST /admin/api/fix-missing-sales.php
 */

require_once __DIR__ . '/../../config/database.php';

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
    $user = $pdo->query("SELECT id, user_id, package_id FROM users WHERE id = " . intval($userId))->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // 이미 sales가 있는지 확인
    $existingSale = $pdo->query("SELECT id FROM sales WHERE user_id = {$user['id']} AND amount = 100")->fetch(PDO::FETCH_ASSOC);

    if ($existingSale) {
        throw new Exception('Sales record already exists');
    }

    // Sales 레코드 생성
    $packageId = $user['package_id'] ?? 2;
    $amount = 100.00;

    $stmt = $pdo->prepare("
        INSERT INTO sales (user_id, package_id, amount, status, confirmed_at, created_at)
        VALUES (?, ?, ?, 'confirmed', NOW(), NOW())
    ");

    $stmt->execute([$user['id'], $packageId, $amount]);
    $saleId = $pdo->lastInsertId();

    // 보너스 배포
    require_once __DIR__ . '/../../classes/BonusDistributor.php';
    $bonusDistributor = new BonusDistributor();

    try {
        $bonusResult = $bonusDistributor->distributeAllBonuses($user['id'], $amount);
        $bonusMessage = "보너스 분배 완료: \${$bonusResult['total_distributed']}";
    } catch (Exception $e) {
        $bonusMessage = "보너스 분배 실패: " . $e->getMessage();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Sales 레코드 생성 완료',
        'data' => [
            'user_id' => $user['user_id'],
            'sale_id' => $saleId,
            'amount' => $amount,
            'bonus_message' => $bonusMessage
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
