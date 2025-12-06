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
    error_log('===== Organization Tree API Start =====');

    // JSON 데이터 파싱
    $json = file_get_contents('php://input');
    error_log('Received JSON: ' . $json);

    $data = json_decode($json, true);
    error_log('Parsed data: ' . print_r($data, true));

    if (!$data || empty($data['session_token'])) {
        error_log('ERROR: Missing session token');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
        ]);
        exit;
    }

    // 사용자 세션 검증 (sessions 테이블 사용)
    error_log('Creating DB instance...');
    $db = Database::getInstance();
    error_log('DB instance created');

    $sessionToken = $data['session_token'];
    error_log('Session token: ' . substr($sessionToken, 0, 20) . '...');

    // sessions 테이블에서 유효한 세션 확인
    $sessionQuery = "SELECT u.id, u.user_id, u.name, u.email
                     FROM users u
                     INNER JOIN sessions s ON u.id = s.user_id
                     WHERE s.token = ? AND s.expires_at > NOW()";

    error_log('Executing session query...');
    $user = $db->selectOne($sessionQuery, [$sessionToken]);
    error_log('Session query result: ' . print_r($user, true));

    if (!$user) {
        error_log('ERROR: Invalid or expired session');
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

    error_log('User validated: ' . $user['user_id'] . ' (ID: ' . $user['id'] . ')');

    // 로그인한 사용자를 루트로 조직도 생성
    error_log('Building organization tree...');
    $tree = buildOrganizationTree($db, $user['id'], $user['user_id']);
    error_log('Tree built. Is null? ' . ($tree === null ? 'YES' : 'NO'));

    if (!$tree) {
        error_log('ERROR: Failed to build tree');
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '조직도를 생성할 수 없습니다.'
        ]);
        exit;
    }

    error_log('Tree structure: ' . json_encode($tree));
    error_log('Sending success response...');

    echo json_encode([
        'success' => true,
        'data' => $tree,
        'user' => [
            'id' => $user['id'],
            'user_id' => $user['user_id'],
            'name' => $user['name']
        ]
    ]);

    error_log('===== Organization Tree API Success =====');

} catch (Exception $e) {
    error_log('===== Organization Tree API ERROR =====');
    error_log('Error message: ' . $e->getMessage());
    error_log('Error file: ' . $e->getFile());
    error_log('Error line: ' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
    error_log('========================================');

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '조직도 조회 중 오류가 발생했습니다.',
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
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
    try {
        error_log("buildOrganizationTree called: userId=$userId, userLoginId=$userLoginId, depth=$currentDepth");

        // 첫 호출 시 루트 사용자 ID 저장
        if ($rootUserId === null) {
            $rootUserId = $userId;
            error_log("Root user ID set to: $rootUserId");
        }

        // 현재 사용자 정보
        $query = "SELECT id, user_id, name, email, is_avatar, referral_id, created_at FROM users WHERE id = ?";
        error_log("Fetching user info for ID: $userId");
        $userInfo = $db->selectOne($query, [$userId]);

        if (!$userInfo) {
            error_log("ERROR: User not found for ID: $userId");
            return null;
        }

        error_log("User info found: " . print_r($userInfo, true));
    } catch (Exception $e) {
        error_log("ERROR in buildOrganizationTree (user fetch): " . $e->getMessage());
        throw $e;
    }

    try {
        // 통계 조회 - user_statistics 테이블이 없으므로 실시간 계산
        error_log("Calculating statistics for user ID: $userId");

        // 직접 추천인 수 계산
        $totalReferralsQuery = "SELECT COUNT(*) as count FROM users WHERE referral_id = ?";
        $totalReferralsResult = $db->selectOne($totalReferralsQuery, [$userId]);
        $totalReferrals = (int)($totalReferralsResult['count'] ?? 0);

        $stats = ['total_referrals' => $totalReferrals];
        error_log("Calculated stats: total_referrals = $totalReferrals");

        // sales 테이블에서 실제 매출 합계 조회 (컬럼명: amount, 상태: status = 'completed')
        error_log("Fetching sales for user ID: $userId");
        $salesQuery = "SELECT COALESCE(SUM(amount), 0) as total_sales
                       FROM sales
                       WHERE user_id = ? AND status = 'completed'";
        $salesResult = $db->selectOne($salesQuery, [$userId]);
        $totalSales = (float)($salesResult['total_sales'] ?? 0);
        error_log("Total sales: $totalSales");

        // bonuses 테이블에서 보너스 합계 조회 (컬럼명: amount, 사용자: user_id)
        error_log("Fetching bonuses for user ID: $userId");
        $bonusQuery = "SELECT COALESCE(SUM(amount), 0) as total_bonus
                       FROM bonuses
                       WHERE user_id = ? AND status = 'paid'";
        $bonusResult = $db->selectOne($bonusQuery, [$userId]);
        $totalBonus = (float)($bonusResult['total_bonus'] ?? 0);
        error_log("Total bonus: $totalBonus");

        // 이 사용자 하위의 바이너리 트리 자식들 조회 (sponsor_id 기준)
        // sponsor_id는 users.user_id (VARCHAR)를 참조
        error_log("Fetching binary tree children for user_id: " . $userInfo['user_id']);
        $childrenQuery = "
            SELECT id, user_id, name, email, created_at, sponsor_position
            FROM users
            WHERE sponsor_id = ?
            ORDER BY sponsor_position ASC, created_at ASC
        ";
        $children = $db->select($childrenQuery, [$userInfo['user_id']]);
        error_log("Found " . count($children) . " binary tree children");
        error_log("Children details: " . json_encode(array_map(function($c) {
            return ['user_id' => $c['user_id'], 'position' => $c['sponsor_position']];
        }, $children)));

        // 추천인 수 계산은 referral_id 기준으로 유지 (직접 추천 통계용)
        error_log("Fetching referral count for stats (referral_id: $userId)");
        $referralsCountQuery = "SELECT COUNT(*) as count FROM users WHERE referral_id = ?";
        $referralsCountResult = $db->selectOne($referralsCountQuery, [$userId]);
        $referralsCount = (int)($referralsCountResult['count'] ?? 0);
        error_log("Found $referralsCount direct referrals");
    } catch (Exception $e) {
        error_log("ERROR in buildOrganizationTree (stats/sales/referrals): " . $e->getMessage());
        throw $e;
    }


    // 직추천인들의 매출 합계 (referral_id 기준)
    $directReferralsSales = 0;
    if ($referralsCount > 0) {
        // referral_id 기준으로 추천받은 사람들 조회
        $referralsQuery = "SELECT id FROM users WHERE referral_id = ?";
        $referrals = $db->select($referralsQuery, [$userId]);

        if (!empty($referrals)) {
            $referralIds = array_column($referrals, 'id');
            $placeholders = implode(',', array_fill(0, count($referralIds), '?'));
            $salesQuery = "SELECT SUM(amount) as total
                           FROM sales
                           WHERE user_id IN ($placeholders) AND status = 'completed'";
            $salesResult = $db->selectOne($salesQuery, $referralIds);
            $directReferralsSales = (float)($salesResult['total'] ?? 0);
        }
    }

    // 직접 추천 수 계산 (referral_id 기준)
    $directReferralsCount = $referralsCount;

    // 패키지 정보 조회 (users 테이블의 package_id 사용)
    $packageQuery = "SELECT p.name
                     FROM users u
                     LEFT JOIN packages p ON u.package_id = p.id
                     WHERE u.id = ?";
    $packageResult = $db->selectOne($packageQuery, [$userId]);
    $packageCode = $packageResult ? $packageResult['name'] : null;

    // 사용자의 sponsor_position 조회
    $sponsorPosQuery = "SELECT sponsor_position FROM users WHERE id = ?";
    $sponsorPosResult = $db->selectOne($sponsorPosQuery, [$userId]);
    $sponsorPosition = $sponsorPosResult ? (int)$sponsorPosResult['sponsor_position'] : 0;

    // 노드 정보 구성 - left/right 구조로 변경
    $node = [
        'id' => $userInfo['id'],
        'user_id' => $userInfo['user_id'],
        'name' => $userInfo['name'],
        'email' => $userInfo['email'],
        'is_avatar' => (int)($userInfo['is_avatar'] ?? 0),
        'referral_id' => $userInfo['referral_id'],
        'joined_date' => $userInfo['created_at'],
        'total_referrals' => $stats ? (int)$stats['total_referrals'] : 0,
        'total_earnings' => $totalBonus, // bonuses 테이블의 보너스 합계
        'total_sales' => $totalSales, // sales 테이블의 매출 합계
        'direct_referrals_count' => $directReferralsCount,
        'direct_referrals_sales' => $directReferralsSales,
        'package_code' => $packageCode,
        'sponsor_position' => $sponsorPosition, // 1=left, 2=right
        'children' => []
    ];

    // 동적 깊이 제한 계산
    // 루트 사용자의 직접 추천인 수 조회
    $referralCountQuery = "SELECT COUNT(*) as count FROM users WHERE referral_id = ?";
    $referralCountResult = $db->selectOne($referralCountQuery, [$rootUserId]);
    $rootReferralCount = (int)($referralCountResult['count'] ?? 0);

    // 기본 10단계 + (추천인 1명당 2단계 추가), 최대 25단계
    $maxDepth = min(10 + ($rootReferralCount * 2), 25);

    error_log("Dynamic max depth for root user $rootUserId: referrals=$rootReferralCount, maxDepth=$maxDepth");

    // 재귀적으로 하위 바이너리 트리 노드 추가 (sponsor_id 기준)
    // 바이너리 구조: 최대 2개 자식 (position 1=left, 2=right)
    if ($currentDepth < $maxDepth) {
        foreach ($children as $child) {
            $childNode = buildOrganizationTree($db, $child['id'], $child['user_id'], $rootUserId, $currentDepth + 1);
            if ($childNode) {
                // sponsor_position 값에 관계없이 순서대로 추가
                // (이미 sponsor_position ASC로 정렬되어 왔음)
                // position 1이 먼저, 2가 다음, NULL/0은 정렬 순서상 앞에 올 수 있음
                $node['children'][] = $childNode;
                error_log("Added child " . $child['user_id'] . " (position: " . ($child['sponsor_position'] ?? 'NULL') . ") to parent " . $userInfo['user_id']);
            }
        }
        error_log("Total children added for " . $userInfo['user_id'] . ": " . count($node['children']));
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
