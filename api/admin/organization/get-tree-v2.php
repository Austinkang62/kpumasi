<?php
/**
 * 관리자용 조직도 트리 조회 API
 * POST /api/admin/organization/get-tree.php
 */

session_start();

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../classes/Database.php';

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 캐시 방지 헤더
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

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
    error_log('===== Admin Organization Tree API Start =====');

    // 관리자 세션 검증
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        error_log('ERROR: Admin not logged in');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => '관리자 권한이 필요합니다.'
        ]);
        exit;
    }

    error_log('Admin validated: ' . $_SESSION['admin_username']);

    // JSON 데이터 파싱
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // 조회할 사용자 ID (없으면 전체 조직도의 루트)
    $userId = isset($data['user_id']) ? intval($data['user_id']) : null;

    error_log('Creating DB instance...');
    $db = Database::getInstance();
    error_log('DB instance created');

    // 루트 사용자 결정
    if ($userId) {
        // 특정 사용자 조회
        $rootUser = $db->selectOne("SELECT id, user_id, name, email FROM users WHERE id = ?", [$userId]);
        if (!$rootUser) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => '사용자를 찾을 수 없습니다.'
            ]);
            exit;
        }
    } else {
        // 최상위 루트 찾기 (sponsor_id가 NULL인 사용자)
        $rootUser = $db->selectOne("SELECT id, user_id, name, email FROM users WHERE sponsor_id IS NULL LIMIT 1");
        if (!$rootUser) {
            // 루트가 없으면 첫 번째 사용자
            $rootUser = $db->selectOne("SELECT id, user_id, name, email FROM users ORDER BY id ASC LIMIT 1");
        }
    }

    if (!$rootUser) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '사용자 데이터가 없습니다.'
        ]);
        exit;
    }

    error_log('Root user: ' . $rootUser['user_id'] . ' (ID: ' . $rootUser['id'] . ')');

    // 조직도 생성
    error_log('Building organization tree...');
    $tree = buildOrganizationTree($db, $rootUser['id'], $rootUser['user_id']);
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

    error_log('SUCCESS: Returning tree data');
    echo json_encode([
        'success' => true,
        'data' => $tree,
        'message' => '조직도를 성공적으로 불러왔습니다.'
    ]);

} catch (Exception $e) {
    error_log('EXCEPTION: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * 조직도 트리 구축 (재귀)
 */
function buildOrganizationTree($db, $userId, $userLoginId) {
    error_log('Building tree for user ID: ' . $userId);

    // 현재 사용자 정보 조회
    $query = "
        SELECT
            u.id,
            u.user_id,
            u.name,
            u.email,
            u.sponsor_id,
            u.sponsor_position,
            u.referral_id,
            u.created_at,
            COALESCE(
                (SELECT p.name FROM sales s
                 INNER JOIN packages p ON s.package_id = p.id
                 WHERE s.user_id = u.id
                 ORDER BY s.created_at DESC LIMIT 1),
                ''
            ) as package_code,
            (SELECT COUNT(*) FROM users WHERE referral_id = u.id) as direct_referrals_count,
            (SELECT COUNT(*) FROM users WHERE sponsor_id = u.id) as direct_sponsors_count,
            COALESCE((SELECT SUM(amount) FROM sales WHERE user_id = u.id AND status = 'completed'), 0) as total_sales
        FROM users u
        WHERE u.id = ?
    ";

    $user = $db->selectOne($query, [$userId]);

    if (!$user) {
        error_log('User not found: ' . $userId);
        return null;
    }

    error_log('Found user: ' . $user['user_id']);

    // 자식 노드 조회 (sponsor 기준)
    // sponsor_id는 users.user_id (VARCHAR)를 참조하므로 user_id를 사용
    $childrenQuery = "SELECT id, user_id FROM users WHERE sponsor_id = ? ORDER BY created_at ASC";
    $children = $db->select($childrenQuery, [$user['user_id']]);

    error_log('Children count for ' . $user['user_id'] . ': ' . count($children));

    $childNodes = [];
    foreach ($children as $child) {
        $childTree = buildOrganizationTree($db, $child['id'], $child['user_id']);
        if ($childTree) {
            $childNodes[] = $childTree;
        }
    }

    return [
        'id' => $user['id'],
        'user_id' => $user['user_id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'sponsor_id' => $user['sponsor_id'],
        'sponsor_position' => intval($user['sponsor_position']),
        'referral_id' => $user['referral_id'],
        'joined_date' => $user['created_at'],
        'package_code' => $user['package_code'],
        'direct_referrals_count' => intval($user['direct_referrals_count']),
        'direct_sponsors_count' => intval($user['direct_sponsors_count']),
        'total_sales' => floatval($user['total_sales']),
        'team_sales' => 0,
        'direct_referrals_sales' => 0,
        'total_earnings' => 0,
        'total_referrals' => 0,
        'children' => $childNodes
    ];
}
