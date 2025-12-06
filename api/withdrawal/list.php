<?php
/**
 * 출금 내역 조회 API
 * GET /api/withdrawal/list.php?session_token=xxx
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
    // 세션 토큰 확인
    $sessionToken = $_GET['session_token'] ?? '';

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

    $userId = $currentUser['id'];
    $db = Database::getInstance();

    // 페이지네이션 파라미터
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;

    // 상태 필터 (선택사항)
    $status = $_GET['status'] ?? '';
    $statusCondition = '';
    $params = [$userId];

    if (!empty($status) && in_array($status, ['pending', 'approved', 'completed', 'rejected'])) {
        $statusCondition = ' AND status = ?';
        $params[] = $status;
    }

    // 전체 개수 조회
    $countQuery = "
        SELECT COUNT(*) as total
        FROM withdrawals
        WHERE user_id = ?
        {$statusCondition}
    ";
    $countResult = $db->selectOne($countQuery, $params);
    $totalCount = (int)($countResult['total'] ?? 0);

    // 출금 내역 조회
    $listQuery = "
        SELECT
            id,
            amount,
            usdt_address,
            status,
            requested_at,
            processed_at,
            reject_reason,
            created_at
        FROM withdrawals
        WHERE user_id = ?
        {$statusCondition}
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ";

    $queryParams = $params;
    $queryParams[] = $limit;
    $queryParams[] = $offset;

    $withdrawals = $db->select($listQuery, $queryParams);

    // 상태별 통계
    $statsQuery = "
        SELECT
            status,
            COUNT(*) as count,
            COALESCE(SUM(amount), 0) as total_amount
        FROM withdrawals
        WHERE user_id = ?
        GROUP BY status
    ";
    $stats = $db->select($statsQuery, [$userId]);

    // 통계 데이터 포맷팅
    $statistics = [
        'pending' => ['count' => 0, 'amount' => 0],
        'approved' => ['count' => 0, 'amount' => 0],
        'completed' => ['count' => 0, 'amount' => 0],
        'rejected' => ['count' => 0, 'amount' => 0],
    ];

    foreach ($stats as $stat) {
        $status = $stat['status'];
        $statistics[$status] = [
            'count' => (int)$stat['count'],
            'amount' => (float)$stat['total_amount']
        ];
    }

    // 응답 데이터
    echo json_encode([
        'success' => true,
        'data' => [
            'withdrawals' => $withdrawals ?: [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $totalCount,
                'total_pages' => $totalCount > 0 ? ceil($totalCount / $limit) : 0
            ],
            'statistics' => $statistics
        ]
    ]);

} catch (Exception $e) {
    error_log('Withdrawal list error: ' . $e->getMessage());
    error_log('Withdrawal list trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '출금 내역 조회 중 오류가 발생했습니다.',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
