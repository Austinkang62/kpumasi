<?php
/**
 * 출금 상세 내역 조회 API
 * GET /api/dashboard/get-withdrawals-detail.php
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
    $status = $_GET['status'] ?? null; // 'pending', 'approved', 'processing', 'completed', 'rejected'

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

    // 출금 내역 조회 쿼리 구성
    $whereClause = "user_id = ?";
    $params = [$currentUser['user_id']];

    if (!empty($status)) {
        $whereClause .= " AND status = ?";
        $params[] = $status;
    }

    // 전체 출금 내역 조회
    $query = "SELECT id, amount, fee, net_amount, withdraw_address,
                     currency, network, tx_hash, status,
                     created_at, approved_at, processed_at, completed_at,
                     rejected_at, reject_reason, admin_note
              FROM withdrawals
              WHERE {$whereClause}
              ORDER BY created_at DESC
              LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;

    $withdrawals = $db->select($query, $params);

    // 상태별 통계
    $statsQuery = "SELECT status,
                          COUNT(*) as count,
                          SUM(amount) as total_amount,
                          SUM(fee) as total_fee,
                          SUM(net_amount) as total_net_amount
                   FROM withdrawals
                   WHERE user_id = ?
                   GROUP BY status";

    $statusStats = $db->select($statsQuery, [$currentUser['user_id']]);

    // 통화별 통계
    $currencyStatsQuery = "SELECT currency,
                                  COUNT(*) as count,
                                  SUM(net_amount) as total_amount
                           FROM withdrawals
                           WHERE user_id = ? AND status = 'completed'
                           GROUP BY currency";

    $currencyStats = $db->select($currencyStatsQuery, [$currentUser['user_id']]);

    // 월별 통계 (최근 12개월)
    $monthlyStatsQuery = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                                 COUNT(*) as count,
                                 SUM(amount) as total_amount,
                                 SUM(fee) as total_fee,
                                 SUM(net_amount) as total_net_amount
                          FROM withdrawals
                          WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                          ORDER BY month DESC";

    $monthlyStats = $db->select($monthlyStatsQuery, [$currentUser['user_id']]);

    // 전체 카운트 (페이지네이션용)
    $countQuery = "SELECT COUNT(*) as total FROM withdrawals WHERE {$whereClause}";
    $countParams = array_slice($params, 0, -2); // limit, offset 제외
    $countResult = $db->selectOne($countQuery, $countParams);
    $totalCount = $countResult['total'];

    // 날짜 포맷팅 및 추가 정보
    foreach ($withdrawals as &$withdrawal) {
        $withdrawal['created_date'] = date('Y-m-d H:i', strtotime($withdrawal['created_at']));
        $withdrawal['approved_date'] = $withdrawal['approved_at'] ? date('Y-m-d H:i', strtotime($withdrawal['approved_at'])) : null;
        $withdrawal['processed_date'] = $withdrawal['processed_at'] ? date('Y-m-d H:i', strtotime($withdrawal['processed_at'])) : null;
        $withdrawal['completed_date'] = $withdrawal['completed_at'] ? date('Y-m-d H:i', strtotime($withdrawal['completed_at'])) : null;
        $withdrawal['rejected_date'] = $withdrawal['rejected_at'] ? date('Y-m-d H:i', strtotime($withdrawal['rejected_at'])) : null;

        // 상태 한글명
        $statusNames = [
            'pending' => '대기 중',
            'approved' => '승인됨',
            'processing' => '처리 중',
            'completed' => '완료',
            'rejected' => '거부됨'
        ];
        $withdrawal['status_name'] = $statusNames[$withdrawal['status']] ?? $withdrawal['status'];

        // 처리 진행도
        $statusProgress = [
            'pending' => 25,
            'approved' => 50,
            'processing' => 75,
            'completed' => 100,
            'rejected' => 0
        ];
        $withdrawal['progress'] = $statusProgress[$withdrawal['status']] ?? 0;

        // 통화 표시
        $withdrawal['currency_display'] = strtoupper($withdrawal['currency'] ?? 'USDT');
        $withdrawal['network_display'] = strtoupper($withdrawal['network'] ?? 'BNB Smart Chain');
    }

    // 전체 통계
    $overallStatsQuery = "SELECT
                            COUNT(*) as total_count,
                            SUM(amount) as total_requested,
                            SUM(fee) as total_fees,
                            SUM(net_amount) as total_received,
                            SUM(CASE WHEN status = 'completed' THEN net_amount ELSE 0 END) as completed_amount,
                            SUM(CASE WHEN status = 'pending' THEN net_amount ELSE 0 END) as pending_amount
                          FROM withdrawals
                          WHERE user_id = ?";

    $overallStats = $db->selectOne($overallStatsQuery, [$currentUser['user_id']]);

    // 평균 처리 시간 계산 (완료된 출금만)
    $avgProcessingTimeQuery = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours
                               FROM withdrawals
                               WHERE user_id = ? AND status = 'completed' AND completed_at IS NOT NULL";

    $avgTimeResult = $db->selectOne($avgProcessingTimeQuery, [$currentUser['user_id']]);
    $overallStats['avg_processing_hours'] = $avgTimeResult['avg_hours'] ? round($avgTimeResult['avg_hours'], 1) : null;

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => [
            'withdrawals' => $withdrawals,
            'pagination' => [
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ],
            'stats' => [
                'overall' => $overallStats,
                'by_status' => $statusStats,
                'by_currency' => $currencyStats,
                'monthly' => $monthlyStats
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log('Get withdrawals detail error: ' . $e->getMessage());
    error_log('Get withdrawals detail trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '출금 상세 내역 조회 중 오류가 발생했습니다.',
        'error_detail' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}
