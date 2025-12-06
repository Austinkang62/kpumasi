<?php
/**
 * 아바타 목록 조회 API
 * GET /api/dashboard/get-avatars.php
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

    // 아바타 목록 조회
    $query = "SELECT u.id, u.user_id, u.name, u.created_at,
                     u.total_bonus, u.available_bonus, u.package_id,
                     p.name as package_name, p.price as package_price
              FROM users u
              LEFT JOIN packages p ON u.package_id = p.id
              WHERE u.parent_account_id = ?
                AND u.is_avatar = 1
                AND (u.deleted_at IS NULL OR u.deleted_at = '')
              ORDER BY u.created_at DESC";

    $avatars = $db->select($query, [$currentUser['id']]);

    // 각 아바타의 추가 정보 계산
    foreach ($avatars as &$avatar) {
        // 날짜 포맷
        $avatar['created_date'] = date('Y-m-d', strtotime($avatar['created_at']));

        // 아바타의 직접 추천인 수
        $referralCountQuery = "SELECT COUNT(*) as count FROM users WHERE referral_id = ?";
        $referralCountResult = $db->selectOne($referralCountQuery, [$avatar['id']]);
        $avatar['direct_referrals'] = $referralCountResult['count'] ?? 0;

        // 상태
        $avatar['status'] = 'active';
        $avatar['status_text'] = '활성';
    }

    // 통계 정보
    $stats = [
        'total_count' => count($avatars),
        'active_count' => count($avatars),
        'total_bonus_generated' => array_sum(array_column($avatars, 'total_bonus'))
    ];

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => [
            'avatars' => $avatars,
            'stats' => $stats
        ]
    ]);

} catch (Exception $e) {
    error_log('Get avatars error: ' . $e->getMessage());
    error_log('Get avatars trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '아바타 목록 조회 중 오류가 발생했습니다.',
        'error_detail' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}
