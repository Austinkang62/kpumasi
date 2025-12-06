<?php
/**
 * 특정 추천인 상세 정보 조회 API
 * GET /api/dashboard/get-referral-detail.php?user_id=AAA12000
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
    $targetUserId = $_GET['user_id'] ?? null;

    if (empty($sessionToken)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
        ]);
        exit;
    }

    if (empty($targetUserId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '조회할 회원 ID가 필요합니다.'
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

    // 대상 회원의 기본 정보 조회
    $query = "SELECT u.id, u.user_id, u.name, u.email, u.phone,
                     u.usdt_address, u.bnb_address, u.referral_id,
                     u.package_id, u.total_bonus, u.available_bonus,
                     u.is_avatar, u.created_at, u.last_login,
                     p.name as package_name, p.price as package_price,
                     o.level, o.position, o.total_downline,
                     o.left_child, o.right_child
              FROM users u
              LEFT JOIN packages p ON u.package_id = p.id
              LEFT JOIN organization o ON o.user_id = u.user_id
              WHERE u.user_id = ?";

    $detail = $db->selectOne($query, [$targetUserId]);

    if (!$detail) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '회원 정보를 찾을 수 없습니다.'
        ]);
        exit;
    }

    // 대상 회원의 직접 추천인 수
    $referralCountQuery = "SELECT COUNT(*) as count FROM users WHERE referral_id = ?";
    $referralCountResult = $db->selectOne($referralCountQuery, [$targetUserId]);
    $detail['direct_referrals'] = $referralCountResult['count'];

    // 대상 회원의 아바타 수
    $avatarCountQuery = "SELECT COUNT(*) as count FROM users WHERE referral_id = ? AND is_avatar = 1";
    $avatarCountResult = $db->selectOne($avatarCountQuery, [$targetUserId]);
    $detail['avatar_count'] = $avatarCountResult['count'];

    // 대상 회원의 최근 보너스 내역 (최근 10건)
    $bonusQuery = "SELECT b.id, b.amount, b.bonus_type, b.level, b.from_user_id,
                          b.created_at, u.user_id as from_user_code, u.name as from_user_name
                   FROM bonuses b
                   LEFT JOIN users u ON b.from_user_id = u.user_id
                   WHERE b.user_id = ?
                   ORDER BY b.created_at DESC
                   LIMIT 10";
    $recentBonuses = $db->select($bonusQuery, [$targetUserId]);

    // 대상 회원의 패키지 구매 내역
    $salesQuery = "SELECT s.id, s.package_id, s.amount, s.payment_method,
                          s.tx_hash, s.status, s.created_at, s.confirmed_at,
                          p.name as package_name
                   FROM sales s
                   LEFT JOIN packages p ON s.package_id = p.id
                   WHERE s.user_id = ?
                   ORDER BY s.created_at DESC";
    $purchases = $db->select($salesQuery, [$targetUserId]);

    // 대상 회원의 출금 내역
    $withdrawalQuery = "SELECT id, amount, fee, net_amount, withdraw_address,
                               status, created_at, processed_at
                        FROM withdrawals
                        WHERE user_id = ?
                        ORDER BY created_at DESC
                        LIMIT 5";
    $withdrawals = $db->select($withdrawalQuery, [$targetUserId]);

    // 날짜 포맷팅
    $detail['joined_date'] = date('Y-m-d H:i', strtotime($detail['created_at']));
    $detail['last_login_date'] = $detail['last_login'] ? date('Y-m-d H:i', strtotime($detail['last_login'])) : '-';
    $detail['has_package'] = !empty($detail['package_id']) && $detail['package_id'] > 0;

    // 보너스 통계
    $bonusStats = [
        'total_earned' => floatval($detail['total_bonus']),
        'available' => floatval($detail['available_bonus']),
        'withdrawn' => floatval($detail['total_bonus']) - floatval($detail['available_bonus']),
        'recent_count' => count($recentBonuses)
    ];

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => [
            'user_info' => $detail,
            'bonus_stats' => $bonusStats,
            'recent_bonuses' => $recentBonuses,
            'purchases' => $purchases,
            'withdrawals' => $withdrawals
        ]
    ]);

} catch (Exception $e) {
    error_log('Get referral detail error: ' . $e->getMessage());
    error_log('Get referral detail trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '회원 상세 정보 조회 중 오류가 발생했습니다.',
        'error_detail' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}
