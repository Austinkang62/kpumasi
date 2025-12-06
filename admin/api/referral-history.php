<?php
/**
 * 추천인 변경 이력 조회 API
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();

    if ($method === 'GET' && $action === 'list') {
        // 특정 회원의 추천인 변경 이력 조회
        $userId = $_GET['user_id'] ?? '';

        if (empty($userId)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            ob_end_flush();
            exit;
        }

        // 추천인 & 후원인 변경 이력 조회
        $history = $db->select("
            SELECT
                id,
                user_id,
                change_type,
                old_referral_id,
                old_referral_user_id,
                new_referral_id,
                new_referral_user_id,
                old_sponsor_id,
                new_sponsor_id,
                old_sponsor_position,
                new_sponsor_position,
                changed_by,
                change_reason,
                ip_address,
                created_at
            FROM referral_change_history
            WHERE user_id = ?
            ORDER BY created_at DESC
        ", [$userId]);

        ob_clean();
        echo json_encode([
            'success' => true,
            'history' => $history
        ]);
        ob_end_flush();

    } elseif ($method === 'GET' && $action === 'all') {
        // 전체 추천인 변경 이력 조회 (페이징)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        // 전체 개수
        $totalResult = $db->selectOne("SELECT COUNT(*) as count FROM referral_change_history");
        $total = $totalResult['count'];

        // 이력 목록
        $history = $db->select("
            SELECT
                id,
                user_id,
                change_type,
                old_referral_id,
                old_referral_user_id,
                new_referral_id,
                new_referral_user_id,
                old_sponsor_id,
                new_sponsor_id,
                old_sponsor_position,
                new_sponsor_position,
                changed_by,
                change_reason,
                ip_address,
                created_at
            FROM referral_change_history
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ", [$limit, $offset]);

        ob_clean();
        echo json_encode([
            'success' => true,
            'history' => $history,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
        ob_end_flush();

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        ob_end_flush();
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    ob_end_flush();
}
