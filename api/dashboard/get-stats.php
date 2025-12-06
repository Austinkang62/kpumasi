<?php
/**
 * 대시보드 통계 API
 * GET /api/dashboard/get-stats.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GET 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // 세션 토큰 검증
    $sessionToken = $_GET['session_token'] ?? null;

    if (empty($sessionToken)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
        ]);
        exit;
    }

    // 세션 검증
    $user = new User();
    $currentUser = $user->validateSession($sessionToken);

    if (!$currentUser) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 세션입니다.'
        ]);
        exit;
    }

    $db = Database::getInstance();
    $userId = $currentUser['id'];

    // 1. 내 패키지 정보 조회 (구매한 모든 패키지)
    $packagesQuery = "SELECT p.product_name, p.product_code, p.price, s.created_at as purchase_date
                      FROM sales s
                      INNER JOIN products p ON s.product_id = p.id
                      WHERE s.user_id = ? AND s.payment_status = 'completed'
                      ORDER BY s.created_at DESC";
    $packages = $db->select($packagesQuery, [$userId]);

    // 2. 직추천수 (1레벨)
    $directReferralsQuery = "SELECT COUNT(*) as count FROM referrals WHERE referrer_id = ?";
    $directReferralsResult = $db->selectOne($directReferralsQuery, [$userId]);
    $directReferralsCount = (int)($directReferralsResult['count'] ?? 0);

    // 3. 팀원수 (15레벨까지 재귀 조회)
    // 재귀 CTE를 사용하여 모든 하위 추천인 조회
    $teamMembersQuery = "
        WITH RECURSIVE team_tree AS (
            -- 1단계: 직추천
            SELECT referee_id as member_id, 1 as level
            FROM referrals
            WHERE referrer_id = ?

            UNION ALL

            -- 2-15단계: 재귀적으로 하위 추천인 조회
            SELECT r.referee_id, tt.level + 1
            FROM referrals r
            INNER JOIN team_tree tt ON r.referrer_id = tt.member_id
            WHERE tt.level < 15
        )
        SELECT COUNT(DISTINCT member_id) as count FROM team_tree
    ";

    // MariaDB 10.1.13에서 재귀 CTE가 지원되지 않을 수 있으므로 대안 방법 사용
    // 재귀 함수로 팀원 수집
    function getTeamMembers($db, $referrerId, $currentLevel = 1, $maxLevel = 15, &$allMembers = []) {
        if ($currentLevel > $maxLevel) {
            return $allMembers;
        }

        $query = "SELECT referee_id FROM referrals WHERE referrer_id = ?";
        $members = $db->select($query, [$referrerId]);

        foreach ($members as $member) {
            $memberId = $member['referee_id'];
            if (!in_array($memberId, $allMembers)) {
                $allMembers[] = $memberId;
                // 재귀 호출
                getTeamMembers($db, $memberId, $currentLevel + 1, $maxLevel, $allMembers);
            }
        }

        return $allMembers;
    }

    $teamMembers = getTeamMembers($db, $userId);
    $teamMembersCount = count($teamMembers);

    // 4. 직추천 매출합 (1레벨)
    $directReferralIds = [];
    $directQuery = "SELECT referee_id FROM referrals WHERE referrer_id = ?";
    $directResults = $db->select($directQuery, [$userId]);
    foreach ($directResults as $row) {
        $directReferralIds[] = $row['referee_id'];
    }

    $directSalesTotal = 0;
    if (!empty($directReferralIds)) {
        $placeholders = implode(',', array_fill(0, count($directReferralIds), '?'));
        $directSalesQuery = "SELECT SUM(total_amount) as total
                             FROM sales
                             WHERE user_id IN ($placeholders) AND payment_status = 'completed'";
        $directSalesResult = $db->selectOne($directSalesQuery, $directReferralIds);
        $directSalesTotal = (float)($directSalesResult['total'] ?? 0);
    }

    // 5. 팀원 매출합 (15레벨까지)
    $teamSalesTotal = 0;
    if (!empty($teamMembers)) {
        $placeholders = implode(',', array_fill(0, count($teamMembers), '?'));
        $teamSalesQuery = "SELECT SUM(total_amount) as total
                           FROM sales
                           WHERE user_id IN ($placeholders) AND payment_status = 'completed'";
        $teamSalesResult = $db->selectOne($teamSalesQuery, $teamMembers);
        $teamSalesTotal = (float)($teamSalesResult['total'] ?? 0);
    }

    // 6. 내 총 구매금액
    $myTotalQuery = "SELECT SUM(total_amount) as total
                     FROM sales
                     WHERE user_id = ? AND payment_status = 'completed'";
    $myTotalResult = $db->selectOne($myTotalQuery, [$userId]);
    $myTotal = (float)($myTotalResult['total'] ?? 0);

    // 응답 데이터 생성
    echo json_encode([
        'success' => true,
        'data' => [
            'user_id' => $currentUser['user_id'] ?? $currentUser['id'],
            'packages' => $packages,
            'package_count' => count($packages),
            'my_total' => $myTotal,
            'direct_referrals_count' => $directReferralsCount,
            'team_members_count' => $teamMembersCount,
            'direct_sales_total' => $directSalesTotal,
            'team_sales_total' => $teamSalesTotal
        ]
    ]);

} catch (Exception $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
    error_log('Dashboard stats trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '통계 조회 중 오류가 발생했습니다.',
        'error_detail' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}
