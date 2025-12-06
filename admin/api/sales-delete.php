<?php
/**
 * 매출 삭제 API
 * POST /admin/api/sales-delete.php
 * - 매출과 연관된 모든 보너스, 사용자 잔액 롤백 및 삭제
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$db = null;

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $saleId = $data['sale_id'] ?? null;

    if (!$saleId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '매출 ID가 필요합니다.'
        ]);
        exit;
    }

    $db = Database::getInstance();
    $db->beginTransaction();

    // 1. 매출 정보 확인
    $sale = $db->selectOne("SELECT * FROM sales WHERE id = ?", [$saleId]);

    if (!$sale) {
        $db->rollback();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '매출을 찾을 수 없습니다.'
        ]);
        exit;
    }

    // 2. 생성된 아바타 확인 (있으면 경고)
    // 주의: avatars 테이블에 trigger_sale_id 컬럼이 없으므로 체크 불가
    // TODO: 향후 avatars 테이블에 trigger_sale_id 컬럼 추가 시 활성화
    /*
    $avatars = $db->select("
        SELECT * FROM avatars
        WHERE trigger_sale_id = ?
    ", [$saleId]);

    if (count($avatars) > 0) {
        $db->rollback();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '이 매출로 생성된 아바타가 있습니다. 먼저 아바타를 삭제해야 합니다.',
            'avatars' => $avatars
        ]);
        exit;
    }
    */

    // 3. 이 매출로 발생한 모든 보너스 조회
    // bonuses 테이블에는 sale_id가 없으므로, from_user_id와 시간 범위로 조회
    // 매출 발생 시간 전후 5분 이내에 생성된 보너스를 해당 매출의 보너스로 간주
    $bonuses = $db->select("
        SELECT
            b.*,
            u.is_avatar,
            u.referral_id as parent_id
        FROM bonuses b
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.from_user_id = ?
        AND b.created_at >= DATE_SUB(?, INTERVAL 5 MINUTE)
        AND b.created_at <= DATE_ADD(?, INTERVAL 5 MINUTE)
    ", [$sale['user_id'], $sale['created_at'], $sale['created_at']]);

    $rollbackSummary = [];

    // 4. 각 보너스에 대해 사용자 잔액 롤백
    foreach ($bonuses as $bonus) {
        $userId = $bonus['user_id'];
        $amount = (float)$bonus['amount'];
        $bonusType = $bonus['bonus_type'];
        $isAvatar = $bonus['is_avatar'];
        $parentId = $bonus['parent_id'];

        // 보너스 분배: 65% cash, 35% avatar points
        $cashBonus = $amount * 0.65;
        $avatarPoints = $amount * 0.35;

        // 아바타의 경우: 캐시는 부모에게, 포인트는 아바타에게 지급되었음
        if ($isAvatar && $parentId) {
            // 부모에게서 캐시 차감
            $db->execute("
                UPDATE users
                SET
                    total_bonus = total_bonus - ?,
                    available_bonus = available_bonus - ?
                WHERE id = ?
            ", [$cashBonus, $cashBonus, $parentId]);

            // 아바타에게서 포인트 차감
            $db->execute("
                UPDATE users
                SET avatar_points = avatar_points - ?
                WHERE id = ?
            ", [$avatarPoints, $userId]);

            $rollbackSummary[] = [
                'user_id' => $parentId,
                'type' => 'cash_rollback',
                'amount' => $cashBonus
            ];
            $rollbackSummary[] = [
                'user_id' => $userId,
                'type' => 'points_rollback',
                'amount' => $avatarPoints
            ];
        } else {
            // 일반 회원: 모두 본인에게서 차감
            $db->execute("
                UPDATE users
                SET
                    total_bonus = total_bonus - ?,
                    available_bonus = available_bonus - ?,
                    avatar_points = avatar_points - ?
                WHERE id = ?
            ", [$amount, $cashBonus, $avatarPoints, $userId]);

            $rollbackSummary[] = [
                'user_id' => $userId,
                'type' => 'full_rollback',
                'amount' => $amount,
                'cash' => $cashBonus,
                'points' => $avatarPoints
            ];
        }

        // 보너스 타입별 total 차감
        $typeColumn = 'total_' . $bonusType . '_bonus';
        $db->execute("
            UPDATE users
            SET {$typeColumn} = {$typeColumn} - ?
            WHERE id = ?
        ", [$amount, $userId]);
    }

    // 5. 보너스 삭제 (from_user_id와 시간 범위로 삭제)
    $db->execute("
        DELETE FROM bonuses
        WHERE from_user_id = ?
        AND created_at >= DATE_SUB(?, INTERVAL 5 MINUTE)
        AND created_at <= DATE_ADD(?, INTERVAL 5 MINUTE)
    ", [$sale['user_id'], $sale['created_at'], $sale['created_at']]);

    // 6. 매출 삭제
    $db->execute("DELETE FROM sales WHERE id = ?", [$saleId]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => '매출이 성공적으로 삭제되었습니다.',
        'data' => [
            'sale_id' => $saleId,
            'sale_amount' => $sale['amount'],
            'bonuses_deleted' => count($bonuses),
            'rollback_summary' => $rollbackSummary
        ]
    ]);

} catch (Exception $e) {
    if ($db) {
        $db->rollback();
    }

    error_log('Sales delete error: ' . $e->getMessage());
    error_log('Sales delete trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '매출 삭제 중 오류가 발생했습니다.',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
