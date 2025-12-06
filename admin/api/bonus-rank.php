<?php
/**
 * 보너스 랭킹 API
 * 회원별 보너스 수령 내역 조회 및 랭킹
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
        // 랭킹 목록 조회
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        $type = $_GET['type'] ?? 'total'; // total, cash, avatar
        $period = $_GET['period'] ?? 'all'; // all, today, week, month

        // 기간 조건 생성
        $dateCondition = "";
        $dateParams = [];

        switch ($period) {
            case 'today':
                $dateCondition = "AND DATE(b.created_at) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
        }

        // 회원별 보너스 합계 조회
        $rankingsQuery = "
            SELECT
                u.id,
                u.user_id,
                u.email,
                u.name,
                u.is_avatar,
                COUNT(DISTINCT b.id) as bonus_count,

                -- 총 보너스
                SUM(b.amount) as total_bonus,

                -- 캐시 보너스
                SUM(CASE WHEN b.payment_type = 'cash' THEN b.amount ELSE 0 END) as cash_bonus,

                -- 아바타 포인트
                SUM(CASE WHEN b.payment_type = 'avatar_point' THEN b.amount ELSE 0 END) as avatar_bonus,

                -- 보너스 타입별 합계
                SUM(CASE WHEN b.bonus_type = 'referral' THEN b.amount ELSE 0 END) as referral_bonus,
                SUM(CASE WHEN b.bonus_type = 'edge' THEN b.amount ELSE 0 END) as edge_bonus,
                SUM(CASE WHEN b.bonus_type = 'matching' THEN b.amount ELSE 0 END) as matching_bonus,
                SUM(CASE WHEN b.bonus_type = 'rollup' THEN b.amount ELSE 0 END) as rollup_bonus,

                -- 생성한 아바타 개수
                (SELECT COUNT(*) FROM avatars a WHERE a.parent_user_id = u.id) as avatar_count

            FROM users u
            INNER JOIN bonuses b ON u.id = b.user_id
            WHERE 1=1
            $dateCondition
            GROUP BY u.id, u.user_id, u.email, u.name, u.is_avatar
        ";

        // 정렬 기준 추가
        $orderBy = "ORDER BY ";
        switch ($type) {
            case 'cash':
                $orderBy .= "cash_bonus DESC";
                break;
            case 'avatar':
                $orderBy .= "avatar_bonus DESC";
                break;
            default:
                $orderBy .= "total_bonus DESC";
        }

        $rankingsQuery .= " $orderBy LIMIT ? OFFSET ?";
        $rankings = $db->select($rankingsQuery, [$limit, $offset]);

        // 전체 통계 계산
        $statsQuery = "
            SELECT
                COUNT(DISTINCT u.id) as total_users,
                SUM(b.amount) as total_bonus,
                AVG(user_totals.total) as avg_bonus,
                MAX(user_totals.total) as top_bonus
            FROM users u
            INNER JOIN bonuses b ON u.id = b.user_id
            LEFT JOIN (
                SELECT user_id, SUM(amount) as total
                FROM bonuses
                WHERE 1=1 $dateCondition
                GROUP BY user_id
            ) user_totals ON u.id = user_totals.user_id
            WHERE 1=1 $dateCondition
        ";

        $stats = $db->selectOne($statsQuery, []);

        // 전체 개수 (페이지네이션용)
        $totalCountQuery = "
            SELECT COUNT(DISTINCT u.id) as cnt
            FROM users u
            INNER JOIN bonuses b ON u.id = b.user_id
            WHERE 1=1 $dateCondition
        ";
        $totalCount = $db->selectOne($totalCountQuery, []);

        ob_clean();
        echo json_encode([
            'success' => true,
            'rankings' => $rankings,
            'stats' => [
                'total_users' => intval($stats['total_users'] ?? 0),
                'total_bonus' => floatval($stats['total_bonus'] ?? 0),
                'avg_bonus' => floatval($stats['avg_bonus'] ?? 0),
                'top_bonus' => floatval($stats['top_bonus'] ?? 0)
            ],
            'total' => intval($totalCount['cnt']),
            'page' => $page,
            'limit' => $limit
        ]);
        ob_end_flush();

    } elseif ($action === 'detail') {
        // 특정 회원의 보너스 상세 내역
        $userId = $_GET['user_id'] ?? '';
        $type = $_GET['type'] ?? 'total';
        $period = $_GET['period'] ?? 'all';

        if (!$userId) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            ob_end_flush();
            exit;
        }

        // 기간 조건 생성
        $dateCondition = "";

        switch ($period) {
            case 'today':
                $dateCondition = "AND DATE(b.created_at) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateCondition = "AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
        }

        // 회원 정보
        $user = $db->selectOne("
            SELECT id, user_id, email, name, is_avatar
            FROM users
            WHERE user_id = ?
        ", [$userId]);

        if (!$user) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User not found']);
            ob_end_flush();
            exit;
        }

        // 보너스 타입별 상세 통계
        $bonusDetails = [
            'referral' => null,
            'edge' => null,
            'matching' => null,
            'rollup' => null
        ];

        foreach (['referral', 'edge', 'matching', 'rollup'] as $bonusType) {
            $typeStats = $db->selectOne("
                SELECT
                    COUNT(*) as count,
                    SUM(amount) as total,
                    SUM(CASE WHEN payment_type = 'cash' THEN amount ELSE 0 END) as cash,
                    SUM(CASE WHEN payment_type = 'avatar_point' THEN amount ELSE 0 END) as avatar
                FROM bonuses
                WHERE user_id = ? AND bonus_type = ? $dateCondition
            ", [$user['id'], $bonusType]);

            if ($typeStats && intval($typeStats['count']) > 0) {
                $bonusDetails[$bonusType] = [
                    'count' => intval($typeStats['count']),
                    'total' => floatval($typeStats['total']),
                    'cash' => floatval($typeStats['cash']),
                    'avatar' => floatval($typeStats['avatar'])
                ];
            }
        }

        // 각 타입별 최근 보너스 내역
        $recentBonuses = [
            'referral' => [],
            'edge' => [],
            'matching' => [],
            'rollup' => []
        ];

        foreach (['referral', 'edge', 'matching', 'rollup'] as $bonusType) {
            $recent = $db->select("
                SELECT
                    b.id,
                    b.amount,
                    b.payment_type,
                    b.level,
                    b.created_at,
                    from_user.user_id as from_user_id
                FROM bonuses b
                LEFT JOIN users from_user ON b.from_user_id = from_user.id
                WHERE b.user_id = ? AND b.bonus_type = ? $dateCondition
                ORDER BY b.created_at DESC
                LIMIT 5
            ", [$user['id'], $bonusType]);

            if ($recent) {
                $recentBonuses[$bonusType] = $recent;
            }
        }

        // 아바타 발생 이력 조회 (기간 필터 없이 모든 이력 조회)
        $avatarHistory = $db->select("
            SELECT
                a.id,
                a.trigger_amount,
                a.created_at,
                avatar_user.user_id as avatar_user_id,
                avatar_user.is_avatar,
                parent_user.user_id as parent_user_id
            FROM avatars a
            LEFT JOIN users avatar_user ON a.avatar_user_id = avatar_user.id
            LEFT JOIN users parent_user ON a.parent_user_id = parent_user.id
            WHERE a.parent_user_id = ?
            ORDER BY a.created_at DESC
        ", [$user['id']]);

        error_log("Avatar History Query for user_id {$user['id']}: " . ($avatarHistory ? count($avatarHistory) : 0) . " records");

        ob_clean();
        echo json_encode([
            'success' => true,
            'user' => $user,
            'bonus_details' => $bonusDetails,
            'recent_bonuses' => $recentBonuses,
            'avatar_history' => $avatarHistory ?: []
        ]);
        ob_end_flush();

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        ob_end_flush();
    }

} catch (Exception $e) {
    error_log('Bonus Rank API Error: ' . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    ob_end_flush();
}
