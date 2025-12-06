<?php
/**
 * 사용자 프로필 조회 API
 * GET /api/user/get-profile.php
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

    // 사용자 정보 조회 (비밀번호 제외)
    $db = Database::getInstance();
    $query = "SELECT u.id, u.user_id, u.name, u.email, u.phone,
                     u.usdt_address, u.bnb_address, u.referral_id, u.sponsor_id,
                     u.package_id, u.email_verified, u.status,
                     u.total_bonus, u.available_bonus, u.total_withdrawn,
                     u.avatar_points,
                     u.direct_referrals, u.avatar_count, u.created_at,
                     p.name as package_name, p.price as package_price,
                     (SELECT COUNT(*) FROM users WHERE referral_id = u.id) as total_referrals,
                     (SELECT COUNT(*) FROM users child
                      WHERE child.is_avatar = 1
                      AND child.parent_account_id = u.id
                      AND (child.deleted_at IS NULL OR child.deleted_at = '')) as total_avatars
              FROM users u
              LEFT JOIN packages p ON u.package_id = p.id
              WHERE u.id = ?";

    $profile = $db->selectOne($query, [$currentUser['id']]);

    if (!$profile) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '사용자 정보를 찾을 수 없습니다.'
        ]);
        exit;
    }

    // 산하 회원수 계산 (sponsor_id 기준, 재귀적)
    function countDownlineRecursive($db, $sponsorId, &$maxDepth, $currentDepth = 1) {
        $children = $db->select(
            "SELECT user_id FROM users WHERE sponsor_id = ? AND deleted_at IS NULL",
            [$sponsorId]
        );

        $count = count($children);

        if ($count > 0 && $currentDepth > $maxDepth) {
            $maxDepth = $currentDepth;
        }

        foreach ($children as $child) {
            $count += countDownlineRecursive($db, $child['user_id'], $maxDepth, $currentDepth + 1);
        }

        return $count;
    }

    $maxDepth = 0;
    $totalDownline = countDownlineRecursive($db, $profile['user_id'], $maxDepth);

    $profile['total_downline'] = $totalDownline;
    $profile['max_level'] = $maxDepth;

    // BTC 잔액 조회 (아바타 테이블에서)
    $btcQuery = "SELECT COALESCE(SUM(btc_accumulated), 0) as btc_balance
                 FROM avatars
                 WHERE parent_user_id = ?";
    $btcInfo = $db->selectOne($btcQuery, [$profile['user_id']]);
    $profile['btc_balance'] = $btcInfo ? $btcInfo['btc_balance'] : 0;

    // 출금 내역 합계
    $withdrawalQuery = "SELECT COALESCE(SUM(net_amount), 0) as total_withdrawals
                        FROM withdrawals
                        WHERE user_id = ? AND status = 'completed'";
    $withdrawalInfo = $db->selectOne($withdrawalQuery, [$profile['user_id']]);
    $profile['total_withdrawals'] = $withdrawalInfo ? $withdrawalInfo['total_withdrawals'] : 0;

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => $profile
    ]);

} catch (Exception $e) {
    error_log('Get profile error: ' . $e->getMessage());
    error_log('Get profile trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '프로필 조회 중 오류가 발생했습니다.',
        'error_detail' => $e->getMessage(),
        'error_trace' => $e->getTraceAsString()
    ]);
}
