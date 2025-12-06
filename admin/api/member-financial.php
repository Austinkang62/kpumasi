<?php
/**
 * 회원별 재무 정보 API
 * 매출, 보너스 수령/지급 내역 조회
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
        // 회원 목록 조회
        $searchUserId = $_GET['user_id'] ?? '';
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;

        $whereClause = "WHERE 1=1";
        $params = [];

        if ($searchUserId) {
            $whereClause .= " AND u.user_id LIKE ?";
            $params[] = '%' . $searchUserId . '%';
        }

        // 전체 회원 수
        $totalCount = $db->selectOne(
            "SELECT COUNT(*) as cnt FROM users u " . $whereClause,
            $params
        );

        // 회원 목록 (매출/보너스 통계 포함)
        $listParams = array_merge($params, [$limit, $offset]);
        $members = $db->select("
            SELECT
                u.id,
                u.user_id,
                u.name,
                u.email,
                u.package_id,
                u.sponsor_id,
                u.referral_id as referral_user_id,
                u.available_bonus,
                u.avatar_points,
                u.total_bonus,
                u.total_withdrawn,
                u.created_at,
                (SELECT COUNT(*) FROM sales WHERE user_id = u.id) as sales_count,
                (SELECT COALESCE(SUM(amount), 0) FROM sales WHERE user_id = u.id) as total_sales,
                (SELECT COALESCE(SUM(amount), 0) FROM bonuses WHERE from_user_id = u.id AND bonus_type = 'referral') as referral_bonus,
                (SELECT COALESCE(SUM(amount), 0) FROM bonuses WHERE from_user_id = u.id AND bonus_type = 'edge') as edge_bonus,
                (SELECT COALESCE(SUM(amount), 0) FROM bonuses WHERE from_user_id = u.id AND bonus_type = 'matching') as matching_bonus,
                (SELECT COALESCE(SUM(amount), 0) FROM bonuses WHERE from_user_id = u.id AND bonus_type = 'rollup') as rollup_bonus,
                (SELECT COALESCE(
                    (SELECT GROUP_CONCAT(DISTINCT receiver.user_id SEPARATOR ', ')
                     FROM bonuses b
                     JOIN users receiver ON b.user_id = receiver.id
                     WHERE b.from_user_id = u.id AND b.bonus_type = 'referral'
                     LIMIT 1),
                    (SELECT referrer.user_id FROM users referrer WHERE referrer.id = u.referral_id LIMIT 1)
                )) as referral_id,
                (SELECT GROUP_CONCAT(DISTINCT receiver.user_id SEPARATOR ', ')
                 FROM bonuses b
                 JOIN users receiver ON b.user_id = receiver.id
                 WHERE b.from_user_id = u.id AND b.bonus_type = 'edge'
                 LIMIT 1) as edge_id,
                (SELECT GROUP_CONCAT(DISTINCT receiver.user_id SEPARATOR ', ')
                 FROM bonuses b
                 JOIN users receiver ON b.user_id = receiver.id
                 WHERE b.from_user_id = u.id AND b.bonus_type = 'matching'
                 LIMIT 1) as matching_id
            FROM users u
            {$whereClause}
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ", $listParams);

        ob_clean();
        echo json_encode([
            'success' => true,
            'members' => $members,
            'total' => intval($totalCount['cnt']),
            'page' => $page,
            'limit' => $limit
        ]);
        ob_end_flush();

    } elseif ($action === 'bonus_detail') {
        // 보너스 상세 내역
        $userId = intval($_GET['user_id'] ?? 0);
        $bonusType = $_GET['bonus_type'] ?? '';

        if (!$userId) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            ob_end_flush();
            exit;
        }

        // 보너스 상세 내역 조회
        $whereClause = "WHERE b.from_user_id = ?";
        $params = [$userId];

        if ($bonusType !== 'all') {
            $whereClause .= " AND b.bonus_type = ?";
            $params[] = $bonusType;
        }

        $bonuses = $db->select("
            SELECT
                b.bonus_type,
                b.level,
                b.amount,
                b.payment_type,
                b.created_at,
                u.user_id as receiver_id,
                u.name as receiver_name
            FROM bonuses b
            JOIN users u ON b.user_id = u.id
            {$whereClause}
            ORDER BY b.bonus_type, b.level, b.payment_type
        ", $params);

        ob_clean();
        echo json_encode([
            'success' => true,
            'bonuses' => $bonuses
        ]);
        ob_end_flush();

    } elseif ($action === 'check_matching_bonuses') {
        // 특정 from_user_id들의 매칭 보너스가 누구에게 발생했는지 조회
        $fromUserIds = $_GET['from_user_ids'] ?? '';

        if (!$fromUserIds) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'from_user_ids required']);
            ob_end_flush();
            exit;
        }

        // 콤마로 구분된 from_user_id를 배열로 변환
        $fromUserIdArray = array_map('trim', explode(',', $fromUserIds));

        $results = [];

        foreach ($fromUserIdArray as $fromUserId) {
            // 해당 from_user_id로 발생한 매칭 보너스 조회
            $matchingBonuses = $db->select("
                SELECT
                    b.id,
                    b.user_id,
                    b.from_user_id,
                    b.amount,
                    b.created_at,
                    receiver.user_id as receiver_code,
                    receiver.name as receiver_name,
                    giver.user_id as giver_code,
                    giver.name as giver_name
                FROM bonuses b
                JOIN users receiver ON b.user_id = receiver.id
                JOIN users giver ON b.from_user_id = giver.id
                WHERE b.from_user_id = (SELECT id FROM users WHERE user_id = ?)
                AND b.bonus_type = 'matching'
                ORDER BY b.created_at DESC
            ", [$fromUserId]);

            $results[$fromUserId] = [
                'count' => count($matchingBonuses),
                'bonuses' => $matchingBonuses
            ];
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'results' => $results
        ]);
        ob_end_flush();

    } elseif ($action === 'detail') {
        // 회원 상세 정보
        $userIdParam = $_GET['user_id'] ?? '';

        if (!$userIdParam) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            ob_end_flush();
            exit;
        }

        // user_id가 숫자면 id로, 문자열이면 user_id로 검색
        if (is_numeric($userIdParam)) {
            $whereClause = "u.id = ?";
            $userId = intval($userIdParam);
        } else {
            $whereClause = "u.user_id = ?";
            $userId = $userIdParam;
        }

        // 회원 기본 정보
        $member = $db->selectOne("
            SELECT
                u.*,
                p.name as package_name,
                p.price as package_price
            FROM users u
            LEFT JOIN packages p ON u.package_id = p.id
            WHERE {$whereClause}
        ", [$userId]);

        if (!$member) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Member not found']);
            ob_end_flush();
            exit;
        }

        // 실제 숫자 id 사용
        $memberId = $member['id'];

        // 매출 내역
        $sales = $db->select("
            SELECT *
            FROM sales
            WHERE user_id = ?
            ORDER BY created_at DESC
        ", [$memberId]);

        // 받은 보너스 (수령한 보너스)
        $receivedBonuses = $db->select("
            SELECT
                b.*,
                giver.user_id as giver_code,
                giver.name as giver_name
            FROM bonuses b
            LEFT JOIN users giver ON b.from_user_id = giver.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
        ", [$memberId]);

        // 받은 보너스 타입별 통계
        $receivedByType = $db->select("
            SELECT
                bonus_type,
                payment_type,
                COUNT(*) as count,
                SUM(amount) as total
            FROM bonuses
            WHERE user_id = ?
            GROUP BY bonus_type, payment_type
        ", [$memberId]);

        // 지급한 보너스 (내 매출로 발생한 보너스)
        $givenBonuses = $db->select("
            SELECT
                b.*,
                receiver.user_id as receiver_code,
                receiver.name as receiver_name
            FROM bonuses b
            LEFT JOIN users receiver ON b.user_id = receiver.id
            WHERE b.from_user_id = ?
            ORDER BY b.created_at DESC
        ", [$memberId]);

        // 지급한 보너스 타입별 통계
        $givenByType = $db->select("
            SELECT
                bonus_type,
                payment_type,
                COUNT(*) as count,
                SUM(amount) as total
            FROM bonuses
            WHERE from_user_id = ?
            GROUP BY bonus_type, payment_type
        ", [$memberId]);

        // 추천한 회원 목록
        $referrals = $db->select("
            SELECT
                id,
                user_id,
                name,
                package_id,
                created_at
            FROM users
            WHERE referral_id = ?
            ORDER BY created_at DESC
        ", [$memberId]);

        // 스폰서한 회원 목록
        $sponsored = $db->select("
            SELECT
                id,
                user_id,
                name,
                package_id,
                sponsor_position,
                created_at
            FROM users
            WHERE sponsor_id = ?
            ORDER BY created_at DESC
        ", [$member['user_id']]);

        ob_clean();
        echo json_encode([
            'success' => true,
            'member' => $member,
            'sales' => $sales,
            'received_bonuses' => $receivedBonuses,
            'received_by_type' => $receivedByType,
            'given_bonuses' => $givenBonuses,
            'given_by_type' => $givenByType,
            'referrals' => $referrals,
            'sponsored' => $sponsored
        ]);
        ob_end_flush();

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        ob_end_flush();
    }

} catch (Exception $e) {
    error_log('Member Financial API Error: ' . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    ob_end_flush();
}
