<?php
/**
 * 추천인 목록 조회 API
 * GET /api/dashboard/get-referrals.php
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

    // 디버그: 현재 사용자 정보 로그
    error_log("Get referrals - Current user ID: " . $currentUser['id']);
    error_log("Get referrals - Current user user_id: " . $currentUser['user_id']);

    // 직접 추천인 목록 조회 (users 테이블에서 referral_id가 현재 사용자 ID인 경우)
    // referral_id는 추천인의 내부 ID (users.id)를 참조
    $query = "SELECT u.id, u.user_id, u.name, u.email, u.package_id,
                     u.total_bonus, u.available_bonus, u.created_at,
                     p.name as package_name, p.price as package_price,
                     (SELECT COUNT(*) FROM users WHERE referral_id = u.id) as direct_referrals,
                     o.total_downline
              FROM users u
              LEFT JOIN packages p ON u.package_id = p.id
              LEFT JOIN organization o ON o.user_id = u.user_id
              WHERE u.referral_id = ?
              ORDER BY u.created_at DESC";

    $referrals = $db->select($query, [$currentUser['id']]);

    // 디버그: 조회 결과 로그
    error_log("Get referrals - Query result count: " . count($referrals));

    // 각 추천인의 추가 정보 계산
    foreach ($referrals as &$referral) {
        // 패키지 정보
        $referral['has_package'] = !empty($referral['package_id']) && $referral['package_id'] > 0;

        // 날짜 포맷
        $referral['joined_date'] = date('Y-m-d', strtotime($referral['created_at']));

        // 활성 상태 (패키지 구매 여부로 판단)
        $referral['is_active'] = $referral['has_package'];
    }

    // 통계 정보
    $stats = [
        'total_count' => count($referrals),
        'active_count' => count(array_filter($referrals, function($r) {
            return $r['is_active'];
        })),
        'total_bonus_generated' => array_sum(array_column($referrals, 'total_bonus'))
    ];

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => [
            'referrals' => $referrals,
            'stats' => $stats
        ]
    ]);

} catch (Exception $e) {
    error_log('Get referrals error: ' . $e->getMessage());
    error_log('Get referrals trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '추천인 목록 조회 중 오류가 발생했습니다.',
        'error_detail' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}
