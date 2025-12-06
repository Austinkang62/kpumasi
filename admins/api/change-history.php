<?php
/**
 * Super Admin - Change History API
 * 회원 변경 이력 조회 및 통계
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/UserChangeLogger.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// Super Admin 권한 확인
if (!isset($_SESSION['super_admin_logged_in']) || !$_SESSION['super_admin_logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    $logger = new UserChangeLogger($db, $_SESSION['super_admin_id'], $_SESSION['super_admin_username']);

    if ($method === 'GET' && $action === 'list') {
        // 변경 이력 목록 조회 (필터링 지원)
        $limit = intval($_GET['limit'] ?? 100);
        $offset = intval($_GET['offset'] ?? 0);
        $searchUser = $_GET['search_user'] ?? '';
        $changeType = $_GET['change_type'] ?? '';
        $admin = $_GET['admin'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $whereConditions = [];
        $params = [];

        // 사용자 검색
        if (!empty($searchUser)) {
            $whereConditions[] = "(h.user_id = ? OR u.user_id LIKE ?)";
            $params[] = intval($searchUser);
            $params[] = "%{$searchUser}%";
        }

        // 변경 유형 필터
        if (!empty($changeType)) {
            $whereConditions[] = "h.change_type = ?";
            $params[] = $changeType;
        }

        // 관리자 필터
        if (!empty($admin)) {
            $whereConditions[] = "h.admin_username = ?";
            $params[] = $admin;
        }

        // 날짜 범위 필터
        if (!empty($dateFrom)) {
            $whereConditions[] = "DATE(h.created_at) >= ?";
            $params[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $whereConditions[] = "DATE(h.created_at) <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // 이력 조회
        $history = $db->select(
            "SELECT h.*, u.user_id as user_login_id, a.full_name as admin_full_name
             FROM user_change_history h
             LEFT JOIN users u ON h.user_id = u.id
             LEFT JOIN admins a ON h.admin_id = a.admin_id
             {$whereClause}
             ORDER BY h.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        // 전체 개수
        $total = $db->selectOne(
            "SELECT COUNT(*) as count
             FROM user_change_history h
             LEFT JOIN users u ON h.user_id = u.user_id
             {$whereClause}",
            $params
        )['count'];

        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => $history,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
        ob_end_flush();

    } elseif ($method === 'GET' && $action === 'today-stats') {
        // 오늘의 변경 통계
        $stats = $logger->getTodayStats();

        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        ob_end_flush();

    } elseif ($method === 'GET' && $action === 'admin-stats') {
        // 관리자별 작업 통계
        $days = intval($_GET['days'] ?? 30);
        $stats = $logger->getAdminStats($days);

        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        ob_end_flush();

    } elseif ($method === 'GET' && $action === 'user-history') {
        // 특정 사용자의 변경 이력
        $userId = intval($_GET['user_id'] ?? 0);
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);

        if ($userId <= 0) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid user_id']);
            ob_end_flush();
            exit;
        }

        $history = $logger->getUserHistory($userId, $limit, $offset);

        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => $history,
            'count' => count($history)
        ]);
        ob_end_flush();

    } elseif ($method === 'GET' && $action === 'field-history') {
        // 특정 필드의 변경 이력
        $fieldName = $_GET['field_name'] ?? '';
        $limit = intval($_GET['limit'] ?? 100);

        if (empty($fieldName)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Field name required']);
            ob_end_flush();
            exit;
        }

        $history = $logger->getFieldHistory($fieldName, $limit);

        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => $history,
            'count' => count($history)
        ]);
        ob_end_flush();

    } elseif ($method === 'GET' && $action === 'summary') {
        // 전체 요약 통계
        $summary = [
            'total_changes' => $db->selectOne(
                "SELECT COUNT(*) as count FROM user_change_history"
            )['count'],
            'total_users_affected' => $db->selectOne(
                "SELECT COUNT(DISTINCT user_id) as count FROM user_change_history"
            )['count'],
            'total_admins_active' => $db->selectOne(
                "SELECT COUNT(DISTINCT admin_id) as count FROM user_change_history"
            )['count'],
            'today' => $logger->getTodayStats(),
            'this_week' => $db->selectOne(
                "SELECT COUNT(*) as count
                 FROM user_change_history
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )['count'],
            'this_month' => $db->selectOne(
                "SELECT COUNT(*) as count
                 FROM user_change_history
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )['count'],
            'by_type' => $db->select(
                "SELECT change_type, COUNT(*) as count
                 FROM user_change_history
                 GROUP BY change_type
                 ORDER BY count DESC"
            ),
            'top_admins' => $db->select(
                "SELECT admin_username, COUNT(*) as change_count
                 FROM user_change_history
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY admin_username
                 ORDER BY change_count DESC
                 LIMIT 10"
            )
        ];

        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => $summary
        ]);
        ob_end_flush();

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        ob_end_flush();
    }

} catch (Exception $e) {
    error_log('Change History API Error: ' . $e->getMessage());

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => defined('APP_ENV') && APP_ENV === 'development' ? $e->getMessage() : null
    ]);
    ob_end_flush();
}
?>
