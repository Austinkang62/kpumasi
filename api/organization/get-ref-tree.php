<?php
/**
 * 추천 조직도 트리 조회 API (referral_id 기반)
 * POST /api/organization/get-ref-tree.php
 * VERSION: 2025-11-25-v2
 */

// 디버깅: 모든 오류 표시
ini_set('display_errors', 0); // 화면 출력 끄기
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// 출력 버퍼링 시작
ob_start();

// 캐시 방지
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

try {
    file_put_contents(__DIR__ . '/debug_ref_tree.log', date('Y-m-d H:i:s') . " - Loading database.php\n", FILE_APPEND);
    require_once __DIR__ . '/../../config/database.php';

    file_put_contents(__DIR__ . '/debug_ref_tree.log', date('Y-m-d H:i:s') . " - Loading Database.php class\n", FILE_APPEND);
    require_once __DIR__ . '/../../classes/Database.php';

    file_put_contents(__DIR__ . '/debug_ref_tree.log', date('Y-m-d H:i:s') . " - All requires loaded successfully\n", FILE_APPEND);
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/debug_ref_tree.log', date('Y-m-d H:i:s') . " - FATAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}

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
    file_put_contents(__DIR__ . '/debug_ref_tree.log', "\n" . date('Y-m-d H:i:s') . " - API REQUEST START\n", FILE_APPEND);
    error_log('===== Referral Tree API Start =====');

    // JSON 데이터 파싱
    $json = file_get_contents('php://input');
    file_put_contents(__DIR__ . '/debug_ref_tree.log', date('H:i:s') . " - Received JSON: " . substr($json, 0, 100) . "...\n", FILE_APPEND);
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

    // 사용자 세션 검증
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
            'message' => '유효하지 않은 세션입니다. 다시 로그인해주세요.'
        ]);
        exit;
    }

    error_log('User validated: ' . $user['user_id'] . ' (ID: ' . $user['id'] . ')');

    // 로그인한 사용자를 루트로 추천 조직도 생성 (referral_id 기반)
    file_put_contents(__DIR__ . '/debug_ref_tree.log', date('H:i:s') . " - Building tree for user: " . $user['user_id'] . "\n", FILE_APPEND);
    error_log('Building referral tree...');
    $tree = buildReferralTree($db, $user['id'], $user['user_id']);
    file_put_contents(__DIR__ . '/debug_ref_tree.log', date('H:i:s') . " - Tree built. Children count: " . (isset($tree['children']) ? count($tree['children']) : 'N/A') . "\n", FILE_APPEND);
    error_log('Tree built. Is null? ' . ($tree === null ? 'YES' : 'NO'));

    if (!$tree) {
        error_log('ERROR: Failed to build tree');
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '추천 조직도를 생성할 수 없습니다.'
        ]);
        exit;
    }

    error_log('Tree structure: ' . json_encode($tree));
    error_log('Sending success response...');

    $response = json_encode([
        'success' => true,
        'data' => $tree,
        'user' => [
            'id' => $user['id'],
            'user_id' => $user['user_id'],
            'name' => $user['name']
        ]
    ]);

    // 출력 버퍼 클리어 및 응답 출력
    ob_clean();
    echo $response;
    ob_end_flush();

    error_log('===== Referral Tree API Success =====');

} catch (Exception $e) {
    $errorDetails = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];

    error_log('===== Referral Tree API ERROR =====');
    error_log('Error message: ' . $e->getMessage());
    error_log('Error file: ' . $e->getFile());
    error_log('Error line: ' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
    error_log('========================================');

    file_put_contents(__DIR__ . '/debug_ref_tree.log', date('H:i:s') . " - ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n", FILE_APPEND);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '추천 조직도 조회 중 오류가 발생했습니다.',
        'error' => $e->getMessage(),
        'error_details' => $errorDetails
    ]);
} catch (Throwable $e) {
    $errorDetails = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];

    file_put_contents(__DIR__ . '/debug_ref_tree.log', date('H:i:s') . " - FATAL: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n", FILE_APPEND);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error occurred',
        'error' => $e->getMessage(),
        'error_details' => $errorDetails
    ]);
}

/**
 * 추천 조직도 트리 구조 생성 (referral_id 기반)
 * @param $db 데이터베이스 인스턴스
 * @param $userId 현재 노드의 사용자 ID
 * @param $userLoginId 현재 노드의 로그인 ID
 * @param $rootUserId 루트 사용자의 ID
 * @param $currentDepth 현재 깊이
 */
function buildReferralTree($db, $userId, $userLoginId, $rootUserId = null, $currentDepth = 0) {
    // 재귀 깊이 제한 (최대 3단계만)
    $MAX_DEPTH = 3;
    if ($currentDepth >= $MAX_DEPTH) {
        error_log("Max depth reached: $currentDepth");
        return null;
    }

    try {
        error_log("buildReferralTree called: userId=$userId, userLoginId=$userLoginId, depth=$currentDepth");

        // 첫 호출 시 루트 사용자 ID 저장
        if ($rootUserId === null) {
            $rootUserId = $userId;
            error_log("Root user ID set to: $rootUserId");
        }

        // 현재 사용자 정보
        $query = "SELECT id, user_id, name, email, created_at FROM users WHERE id = ?";
        error_log("Fetching user info for ID: $userId");
        $userInfo = $db->selectOne($query, [$userId]);

        if (!$userInfo) {
            error_log("ERROR: User not found for ID: $userId");
            return null;
        }

        error_log("User info found: " . print_r($userInfo, true));

        // 통계 계산
        error_log("Calculating statistics for user ID: $userId");

        // 직접 추천인 수 계산 (referral_id 기반)
        $totalReferralsQuery = "SELECT COUNT(*) as count FROM users WHERE referral_id = ?";
        $totalReferralsResult = $db->selectOne($totalReferralsQuery, [$userId]);
        $totalReferrals = (int)($totalReferralsResult['count'] ?? 0);

        error_log("Total referrals: $totalReferrals");

        // sales 테이블에서 실제 매출 합계 조회
        error_log("Fetching sales for user ID: $userId");
        $salesQuery = "SELECT COALESCE(SUM(amount), 0) as total_sales
                       FROM sales
                       WHERE user_id = ? AND status = 'completed'";
        $salesResult = $db->selectOne($salesQuery, [$userId]);
        $totalSales = (float)($salesResult['total_sales'] ?? 0);
        error_log("Total sales: $totalSales");

        // bonuses 테이블에서 보너스 합계 조회
        error_log("Fetching bonuses for user ID: $userId");
        $bonusQuery = "SELECT COALESCE(SUM(amount), 0) as total_bonus
                       FROM bonuses
                       WHERE user_id = ? AND status = 'paid'";
        $bonusResult = $db->selectOne($bonusQuery, [$userId]);
        $totalBonus = (float)($bonusResult['total_bonus'] ?? 0);
        error_log("Total bonus: $totalBonus");

        // referral_id 기반으로 자식들 조회 (추천받은 사람들)
        error_log("Fetching referral children for user_id: $userId");
        $childrenQuery = "
            SELECT id, user_id, name, email, created_at
            FROM users
            WHERE referral_id = ?
            ORDER BY created_at ASC
        ";
        $children = $db->select($childrenQuery, [$userId]);
        error_log("Found " . count($children) . " referral children");

        // 직추천인들의 매출 합계
        $directReferralsSales = 0;
        if ($totalReferrals > 0) {
            $referrals = $db->select("SELECT id FROM users WHERE referral_id = ?", [$userId]);

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

        // 패키지 정보 조회
        $packageQuery = "SELECT p.name
                         FROM users u
                         LEFT JOIN packages p ON u.package_id = p.id
                         WHERE u.id = ?";
        $packageResult = $db->selectOne($packageQuery, [$userId]);
        $packageCode = $packageResult ? $packageResult['name'] : null;

        // 노드 정보 구성
        $node = [
            'id' => $userInfo['id'],
            'user_id' => $userInfo['user_id'],
            'name' => $userInfo['name'],
            'email' => $userInfo['email'],
            'joined_date' => $userInfo['created_at'],
            'total_referrals' => $totalReferrals,
            'total_earnings' => $totalBonus,
            'total_sales' => $totalSales,
            'direct_referrals_count' => $totalReferrals,
            'direct_referrals_sales' => $directReferralsSales,
            'package_code' => $packageCode,
            'children' => []
        ];

        // 추천 조직도는 단계 제한 없음
        // 재귀적으로 하위 추천 노드 추가
        $logMsg = "Processing " . count($children) . " children for user " . $userInfo['user_id'] . " (ID: $userId, depth: $currentDepth)\n";
        file_put_contents(__DIR__ . '/debug_ref_tree.log', date('H:i:s') . " - $logMsg", FILE_APPEND);

        if (count($children) > 0) {
            foreach ($children as $child) {
                file_put_contents(__DIR__ . '/debug_ref_tree.log', date('H:i:s') . " -   Processing child: " . $child['user_id'] . "\n", FILE_APPEND);
                $childNode = buildReferralTree($db, $child['id'], $child['user_id'], $rootUserId, $currentDepth + 1);
                if ($childNode) {
                    $node['children'][] = $childNode;
                    file_put_contents(__DIR__ . '/debug_ref_tree.log', date('H:i:s') . " -   ✓ Child added\n", FILE_APPEND);
                } else {
                    file_put_contents(__DIR__ . '/debug_ref_tree.log', date('H:i:s') . " -   ✗ Child NULL!\n", FILE_APPEND);
                }
            }
        }

        file_put_contents(__DIR__ . '/debug_ref_tree.log', date('H:i:s') . " - Node complete: " . $userInfo['user_id'] . " has " . count($node['children']) . " children\n", FILE_APPEND);

        // 팀원 매출 합계 계산
        $node['team_sales'] = calculateTeamSales($node);

        return $node;

    } catch (Exception $e) {
        error_log("ERROR in buildReferralTree: " . $e->getMessage());
        throw $e;
    }
}

/**
 * 팀원 매출 합계 계산 (재귀)
 */
function calculateTeamSales($node) {
    $total = 0;

    if (!empty($node['children'])) {
        foreach ($node['children'] as $child) {
            $total += ($child['total_sales'] ?? 0);
            $total += ($child['team_sales'] ?? 0);
        }
    }

    return $total;
}
