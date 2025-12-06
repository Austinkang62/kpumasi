<?php
/**
 * 수익 상세 내역 조회 API
 * GET /api/dashboard/get-earnings-detail.php
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
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $bonusType = $_GET['bonus_type'] ?? null; // 'direct', 'binary', 'avatar' 등

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

    // 보너스 내역 조회 쿼리 구성
    $whereClause = "b.user_id = ?";
    $params = [$currentUser['user_id']];

    if (!empty($bonusType)) {
        $whereClause .= " AND b.bonus_type = ?";
        $params[] = $bonusType;
    }

    // 전체 보너스 내역 조회
    $query = "SELECT b.id, b.amount, b.bonus_type, b.level, b.from_user_id,
                     b.created_at, b.description,
                     u.user_id as from_user_code, u.name as from_user_name,
                     p.name as from_user_package
              FROM bonuses b
              LEFT JOIN users u ON b.from_user_id = u.user_id
              LEFT JOIN packages p ON u.package_id = p.id
              WHERE {$whereClause}
              ORDER BY b.created_at DESC
              LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;

    $bonuses = $db->select($query, $params);

    // 보너스 타입별 통계
    $statsQuery = "SELECT bonus_type,
                          COUNT(*) as count,
                          SUM(amount) as total_amount,
                          AVG(amount) as avg_amount,
                          MIN(amount) as min_amount,
                          MAX(amount) as max_amount
                   FROM bonuses
                   WHERE user_id = ?
                   GROUP BY bonus_type";

    $typeStats = $db->select($statsQuery, [$currentUser['user_id']]);

    // 레벨별 통계
    $levelStatsQuery = "SELECT level,
                               COUNT(*) as count,
                               SUM(amount) as total_amount
                        FROM bonuses
                        WHERE user_id = ?
                        GROUP BY level
                        ORDER BY level ASC";

    $levelStats = $db->select($levelStatsQuery, [$currentUser['user_id']]);

    // 일별 통계 (최근 30일)
    $dailyStatsQuery = "SELECT DATE(created_at) as date,
                               COUNT(*) as count,
                               SUM(amount) as total_amount
                        FROM bonuses
                        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY DATE(created_at)
                        ORDER BY date DESC";

    $dailyStats = $db->select($dailyStatsQuery, [$currentUser['user_id']]);

    // 월별 통계 (최근 12개월)
    $monthlyStatsQuery = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                                 COUNT(*) as count,
                                 SUM(amount) as total_amount
                          FROM bonuses
                          WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                          ORDER BY month DESC";

    $monthlyStats = $db->select($monthlyStatsQuery, [$currentUser['user_id']]);

    // 전체 카운트 (페이지네이션용)
    $countQuery = "SELECT COUNT(*) as total FROM bonuses WHERE {$whereClause}";
    $countParams = array_slice($params, 0, -2); // limit, offset 제외
    $countResult = $db->selectOne($countQuery, $countParams);
    $totalCount = $countResult['total'];

    // 날짜 포맷팅
    foreach ($bonuses as &$bonus) {
        $bonus['created_date'] = date('Y-m-d H:i', strtotime($bonus['created_at']));

        // 보너스 타입 한글명
        $typeNames = [
            'direct' => '직접 추천 보너스',
            'binary' => 'Binary 조직 보너스',
            'avatar' => '아바타 보너스',
            'matching' => '매칭 보너스',
            'leadership' => '리더십 보너스'
        ];
        $bonus['bonus_type_name'] = $typeNames[$bonus['bonus_type']] ?? $bonus['bonus_type'];
    }

    // 전체 통계
    $overallStats = [
        'total_earned' => floatval($currentUser['total_bonus']),
        'total_count' => $totalCount,
        'avg_per_bonus' => $totalCount > 0 ? floatval($currentUser['total_bonus']) / $totalCount : 0
    ];

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => [
            'bonuses' => $bonuses,
            'pagination' => [
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ],
            'stats' => [
                'overall' => $overallStats,
                'by_type' => $typeStats,
                'by_level' => $levelStats,
                'daily' => $dailyStats,
                'monthly' => $monthlyStats
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log('Get earnings detail error: ' . $e->getMessage());
    error_log('Get earnings detail trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '수익 상세 내역 조회 중 오류가 발생했습니다.',
        'error_detail' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}
