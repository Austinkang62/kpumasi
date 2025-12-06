<?php
/**
 * 사용자 조직도 트리 조회 API
 * POST /api/organization/get-tree.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // JSON 데이터 파싱
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    error_log('User org-tree API - Received data: ' . print_r($data, true));

    if (!$data || empty($data['session_token'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
        ]);
        exit;
    }

    // 사용자 세션 검증 (sessions 테이블 사용)
    $db = Database::getInstance();
    $sessionToken = $data['session_token'];

    error_log('User org-tree API - Token: ' . substr($sessionToken, 0, 20) . '...');

    // sessions 테이블에서 유효한 세션 확인
    $sessionQuery = "SELECT u.id, u.user_id, u.name, u.email
                     FROM users u
                     INNER JOIN sessions s ON u.id = s.user_id
                     WHERE s.session_token = ? AND s.expires_at > NOW()";

    $user = $db->selectOne($sessionQuery, [$sessionToken]);

    error_log('User org-tree API - Session valid: ' . ($user ? 'YES' : 'NO'));
    if ($user) {
        error_log('User org-tree API - User: ' . $user['user_id']);
    }

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 세션입니다. 다시 로그인해주세요.',
            'debug' => [
                'current_time' => date('Y-m-d H:i:s'),
                'hint' => '세션이 만료되었을 수 있습니다.'
            ]
        ]);
        exit;
    }

    // 로그인한 사용자를 루트로 조직도 생성
    $tree = buildOrganizationTree($db, $user['id'], $user['user_id']);

    if (!$tree) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '조직도를 생성할 수 없습니다.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $tree,
        'user' => [
            'id' => $user['id'],
            'user_id' => $user['user_id'],
            'name' => $user['name']
        ]
    ]);

} catch (Exception $e) {
    error_log('User get organization tree error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '조직도 조회 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}

/**
 * 조직도 트리 구조 생성
 * @param $db 데이터베이스 인스턴스
 * @param $userId 현재 노드의 사용자 ID
 * @param $userLoginId 현재 노드의 로그인 ID
 * @param $rootUserId 루트 사용자의 ID (조직도 시작점)
 * @param $currentDepth 현재 깊이 (0부터 시작)
 */
function buildOrganizationTree($db, $userId, $userLoginId, $rootUserId = null, $currentDepth = 0) {
    // 첫 호출 시 루트 사용자 ID 저장
    if ($rootUserId === null) {
        $rootUserId = $userId;
    }

    // 현재 사용자 정보
    $query = "SELECT id, user_id, name, email, created_at FROM users WHERE id = ?";
    $userInfo = $db->selectOne($query, [$userId]);

    if (!$userInfo) {
        return null;
    }

    // 통계 조회
    $statsQuery = "SELECT total_referrals FROM user_statistics WHERE user_id = ?";
    $stats = $db->selectOne($statsQuery, [$userId]);

    // sales 테이블에서 실제 매출 합계 조회
    $salesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total_sales
                   FROM sales
                   WHERE user_id = ? AND payment_status = 'completed'";
    $salesResult = $db->selectOne($salesQuery, [$userId]);
    $totalSales = (float)($salesResult['total_sales'] ?? 0);

    // referral_bonuses 테이블에서 보너스 합계 조회
    $bonusQuery = "SELECT COALESCE(SUM(bonus_amount), 0) as total_bonus
                   FROM referral_bonuses
                   WHERE to_user_id = ?";
    $bonusResult = $db->selectOne($bonusQuery, [$userId]);
    $totalBonus = (float)($bonusResult['total_bonus'] ?? 0);

    // 이 사용자가 추천한 사람들 조회 (referral_id로 직접 조회)
    $referralsQuery = "
        SELECT id, user_id, name, email, created_at, referral_order
        FROM users
        WHERE referral_id = ?
        ORDER BY referral_order ASC, created_at ASC
    ";
    $referrals = $db->select($referralsQuery, [$userLoginId]);

    // 직추천인들의 매출 합계
    $directReferralsSales = 0;
    if (!empty($referrals)) {
        $referralIds = array_column($referrals, 'id');
        $placeholders = implode(',', array_fill(0, count($referralIds), '?'));
        $salesQuery = "SELECT SUM(total_amount) as total
                       FROM sales
                       WHERE user_id IN ($placeholders) AND payment_status = 'completed'";
        $salesResult = $db->selectOne($salesQuery, $referralIds);
        $directReferralsSales = (float)($salesResult['total'] ?? 0);
    }

    // referral_org 기준 직접 추천 수 (커미션 대상)
    $directReferralsCountQuery = "SELECT COUNT(*) as count FROM users WHERE referral_org = ?";
    $directReferralsCountResult = $db->selectOne($directReferralsCountQuery, [$userLoginId]);
    $directReferralsCount = (int)($directReferralsCountResult['count'] ?? 0);

    // 패키지 정보 조회
    $packageQuery = "SELECT p.product_code
                     FROM sales s
                     INNER JOIN products p ON s.product_id = p.id
                     WHERE s.user_id = ?
                     AND p.product_code IN ('PKG_50', 'PKG_100')
                     ORDER BY s.created_at ASC
                     LIMIT 1";
    $packageResult = $db->selectOne($packageQuery, [$userId]);
    $packageCode = $packageResult ? $packageResult['product_code'] : null;

    // 노드 정보 구성
    $node = [
        'id' => $userInfo['id'],
        'user_id' => $userInfo['user_id'],
        'name' => $userInfo['name'],
        'email' => $userInfo['email'],
        'joined_date' => $userInfo['created_at'],
        'total_referrals' => $stats ? (int)$stats['total_referrals'] : 0,
        'total_earnings' => $totalBonus, // bonuses 테이블의 보너스 합계
        'total_sales' => $totalSales, // sales 테이블의 매출 합계
        'direct_referrals_count' => $directReferralsCount,
        'direct_referrals_sales' => $directReferralsSales,
        'package_code' => $packageCode,
        'children' => []
    ];

    // 루트 사용자의 직접 추천 수 조회 (조직도 깊이 제한 결정)
    $rootDirectReferralsQuery = "SELECT user_id FROM users WHERE id = ?";
    $rootUserInfo = $db->selectOne($rootDirectReferralsQuery, [$rootUserId]);
    if ($rootUserInfo) {
        $rootDirectReferralsCountQuery = "SELECT COUNT(*) as count FROM users WHERE referral_org = ?";
        $rootDirectReferralsCountResult = $db->selectOne($rootDirectReferralsCountQuery, [$rootUserInfo['user_id']]);
        $rootDirectReferralsCount = (int)($rootDirectReferralsCountResult['count'] ?? 0);
    } else {
        $rootDirectReferralsCount = 0;
    }

    // 조직도 표시 깊이 제한 결정
    // 1명 추천: 5단계, 2명 추천: 10단계, 3명 이상: 15단계
    if ($rootDirectReferralsCount >= 3) {
        $maxDepth = 15;
    } elseif ($rootDirectReferralsCount >= 2) {
        $maxDepth = 10;
    } elseif ($rootDirectReferralsCount >= 1) {
        $maxDepth = 5;
    } else {
        $maxDepth = 0; // 추천인 없으면 본인만
    }

    // 재귀적으로 하위 노드 추가 (깊이 제한 적용)
    if ($currentDepth < $maxDepth) {
        foreach ($referrals as $referral) {
            $childNode = buildOrganizationTree($db, $referral['id'], $referral['user_id'], $rootUserId, $currentDepth + 1);
            if ($childNode) {
                $node['children'][] = $childNode;
            }
        }
    }

    // 팀원 매출 합계 계산 (모든 하위 노드의 매출)
    $node['team_sales'] = calculateTeamSales($node);

    return $node;
}

/**
 * 팀원 매출 합계 계산 (재귀)
 * 단계 제한이 이미 children에 적용되어 있으므로 모든 children의 매출을 합산
 */
function calculateTeamSales($node) {
    $total = 0;

    if (!empty($node['children'])) {
        foreach ($node['children'] as $child) {
            // 자식 노드의 본인 매출 (패키지 구매액)
            $total += ($child['total_sales'] ?? 0);
            // 자식 노드의 팀원 매출 (하위 팀원들의 매출)
            $total += ($child['team_sales'] ?? 0);
        }
    }

    return $total;
}

// Cache clear trigger - 2025-10-26
