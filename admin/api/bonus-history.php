<?php
/**
 * 보너스 히스토리 API
 * 매출별 보너스 발생 내역 조회
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

$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();

    if ($action === 'list') {
        // 매출 목록 조회 (최신순)
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        $searchUserId = $_GET['user_id'] ?? '';

        // WHERE 조건 및 파라미터 준비
        $whereClause = '';
        $countParams = [];
        $statsParams = [];
        $listParams = [];

        if ($searchUserId) {
            $whereClause = " WHERE u.user_id LIKE ?";
            $searchParam = '%' . $searchUserId . '%';
            $countParams = [$searchParam];
            $statsParams = [$searchParam];
            $listParams = [$searchParam];
        }

        // 전체 매출 수 (필터링 적용)
        $totalCount = $db->selectOne(
            "SELECT COUNT(*) as cnt FROM sales s LEFT JOIN users u ON s.user_id = u.id" . $whereClause,
            $countParams
        );

        // 전체 통계 계산 (필터링 적용)
        $totalStats = $db->selectOne("
            SELECT
                COUNT(*) as total_sales,
                SUM(s.amount) as total_amount
            FROM sales s
            LEFT JOIN users u ON s.user_id = u.id" . $whereClause,
            $statsParams
        );

        // 전체 보너스 합계 (필터링 적용)
        if ($searchUserId) {
            $totalBonusStats = $db->selectOne("
                SELECT SUM(b.amount) as total_bonus
                FROM bonuses b
                LEFT JOIN users u ON b.from_user_id = u.id
                WHERE u.user_id LIKE ?
            ", [$searchParam]);
        } else {
            $totalBonusStats = $db->selectOne("
                SELECT SUM(amount) as total_bonus
                FROM bonuses
            ");
        }

        // 전체 아바타 생성 수 (전체 통계, 필터링 안함)
        $totalAvatarsStats = $db->selectOne("
            SELECT COUNT(*) as total_avatars
            FROM users
            WHERE name LIKE 'Avatar%' AND (deleted_at IS NULL OR deleted_at = '')
        ");

        // 매출 목록 (필터링 적용)
        $listParams[] = $limit;
        $listParams[] = $offset;

        $sales = $db->select("
            SELECT
                s.id,
                s.user_id as user_internal_id,
                u.user_id,
                u.name,
                u.is_avatar,
                s.package_id,
                s.amount,
                s.payment_method,
                s.txid,
                s.status,
                s.confirmed_at,
                s.created_at
            FROM sales s
            LEFT JOIN users u ON s.user_id = u.id" . $whereClause . "
            ORDER BY s.created_at DESC
            LIMIT ? OFFSET ?
        ", $listParams);

        // 각 매출별 보너스 통계
        foreach ($sales as &$sale) {
            $saleId = $sale['id'];

            // 보너스 통계
            $bonusStats = $db->select("
                SELECT
                    bonus_type,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                FROM bonuses
                WHERE from_user_id = ?
                GROUP BY bonus_type
            ", [$sale['user_internal_id']]);

            $sale['bonus_summary'] = [
                'referral' => 0,
                'edge' => 0,
                'matching' => 0,
                'rollup' => 0,
                'total_count' => 0,
                'total_amount' => 0
            ];

            foreach ($bonusStats as $stat) {
                $sale['bonus_summary'][$stat['bonus_type']] = floatval($stat['total_amount']);
                $sale['bonus_summary']['total_count'] += intval($stat['count']);
                $sale['bonus_summary']['total_amount'] += floatval($stat['total_amount']);
            }

            // 아바타 생성 여부
            $avatarCreated = $db->selectOne("
                SELECT COUNT(*) as cnt
                FROM avatars
                WHERE created_at >= ? AND created_at <= DATE_ADD(?, INTERVAL 5 MINUTE)
            ", [$sale['created_at'], $sale['created_at']]);

            $sale['avatars_created'] = intval($avatarCreated['cnt']);
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'sales' => $sales,
            'total' => intval($totalCount['cnt']),
            'page' => $page,
            'limit' => $limit,
            'stats' => [
                'total_sales' => intval($totalStats['total_sales']),
                'total_amount' => floatval($totalStats['total_amount'] ?? 0),
                'total_bonus' => floatval($totalBonusStats['total_bonus'] ?? 0),
                'total_avatars' => intval($totalAvatarsStats['total_avatars'] ?? 0)
            ]
        ]);
        ob_end_flush();

    } elseif ($action === 'detail') {
        // 특정 매출의 보너스 상세 내역
        $saleId = intval($_GET['sale_id'] ?? 0);

        if (!$saleId) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Sale ID required']);
            ob_end_flush();
            exit;
        }

        // 매출 정보
        $sale = $db->selectOne("
            SELECT
                s.*,
                u.user_id,
                u.name,
                u.is_avatar
            FROM sales s
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ", [$saleId]);

        if (!$sale) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Sale not found']);
            ob_end_flush();
            exit;
        }

        // 보너스 상세 내역
        $bonuses = $db->select("
            SELECT
                b.id,
                b.bonus_type,
                b.amount,
                b.level,
                b.status,
                b.created_at,
                receiver.user_id as receiver_user_id,
                receiver.name as receiver_name,
                receiver.is_avatar as receiver_is_avatar,
                giver.user_id as giver_user_id,
                giver.name as giver_name
            FROM bonuses b
            LEFT JOIN users receiver ON b.user_id = receiver.id
            LEFT JOIN users giver ON b.from_user_id = giver.id
            WHERE b.from_user_id = ?
            ORDER BY b.bonus_type, b.level
        ", [$sale['user_id']]);

        // 보너스를 타입별로 그룹화
        $bonusByType = [
            'referral' => [],
            'edge' => [],
            'matching' => [],
            'rollup' => []
        ];

        foreach ($bonuses as $bonus) {
            $bonusByType[$bonus['bonus_type']][] = $bonus;
        }

        // 아바타 생성 내역
        $avatars = $db->select("
            SELECT
                a.*,
                parent.user_id as parent_user_id,
                avatar.user_id as avatar_user_id
            FROM avatars a
            LEFT JOIN users parent ON a.parent_user_id = parent.id
            LEFT JOIN users avatar ON a.avatar_user_id = avatar.id
            WHERE a.created_at >= ? AND a.created_at <= DATE_ADD(?, INTERVAL 5 MINUTE)
            ORDER BY a.created_at
        ", [$sale['created_at'], $sale['created_at']]);

        ob_clean();
        echo json_encode([
            'success' => true,
            'sale' => $sale,
            'bonuses' => $bonuses,
            'bonus_by_type' => $bonusByType,
            'avatars' => $avatars
        ]);
        ob_end_flush();

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        ob_end_flush();
    }

} catch (Exception $e) {
    error_log('Bonus History API Error: ' . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    ob_end_flush();
}
