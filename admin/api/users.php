<?php
/**
 * Admin Users Management API
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();

    if ($method === 'GET' && $action === 'list') {
        // 사용자 목록
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $search = $_GET['search'] ?? '';
        $offset = ($page - 1) * $limit;

        $whereClause = '';
        $params = [];

        if ($search) {
            $whereClause = "WHERE login_id LIKE ? OR email LIKE ? OR referral_code LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }

        // 총 사용자 수
        $totalQuery = "SELECT COUNT(*) as total FROM users $whereClause";
        $total = $db->selectOne($totalQuery, $params)['total'];

        // 사용자 목록
        $query = "
            SELECT
                user_id, login_id, email, referral_code, sponsor_id,
                package_type, package_status, total_sales, commission_earned,
                usdt_address, is_active, is_admin, created_at, last_login
            FROM users
            $whereClause
            ORDER BY created_at DESC
            LIMIT $limit OFFSET $offset
        ";

        $users = $db->select($query, $params);

        echo json_encode([
            'success' => true,
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);

    } elseif ($method === 'GET' && $action === 'detail') {
        // 사용자 상세 정보
        $userId = intval($_GET['user_id'] ?? 0);

        $user = $db->selectOne("SELECT * FROM users WHERE user_id = ?", [$userId]);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        // 구매 내역
        $purchases = $db->select("
            SELECT s.*, p.name, p.price
            FROM sales s
            JOIN packages p ON s.package_id = p.package_id
            WHERE s.user_id = ?
            ORDER BY s.created_at DESC
        ", [$userId]);

        // 보너스 내역
        $bonuses = $db->select("
            SELECT * FROM user_bonus_balance
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ", [$userId]);

        // 출금 내역
        $withdrawals = $db->select("
            SELECT * FROM withdrawals
            WHERE user_id = ?
            ORDER BY created_at DESC
        ", [$userId]);

        // 추천 회원
        $referrals = $db->select("
            SELECT user_id, login_id, email, created_at
            FROM users
            WHERE referral_id = ?
        ", [$userId]);

        echo json_encode([
            'success' => true,
            'user' => $user,
            'purchases' => $purchases,
            'bonuses' => $bonuses,
            'withdrawals' => $withdrawals,
            'referrals' => $referrals
        ]);

    } elseif ($method === 'POST' && $action === 'toggle-status') {
        // 사용자 활성화/비활성화
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = intval($data['user_id'] ?? 0);

        $user = $db->selectOne("SELECT is_active FROM users WHERE user_id = ?", [$userId]);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $newStatus = $user['is_active'] ? 0 : 1;

        $db->execute("UPDATE users SET is_active = ? WHERE user_id = ?", [$newStatus, $userId]);

        echo json_encode([
            'success' => true,
            'message' => 'Status updated',
            'is_active' => $newStatus
        ]);

    } elseif ($method === 'POST' && $action === 'toggle-admin') {
        // 관리자 권한 부여/제거
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = intval($data['user_id'] ?? 0);

        $user = $db->selectOne("SELECT is_admin FROM users WHERE user_id = ?", [$userId]);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $newStatus = $user['is_admin'] ? 0 : 1;

        $db->execute("UPDATE users SET is_admin = ? WHERE user_id = ?", [$newStatus, $userId]);

        echo json_encode([
            'success' => true,
            'message' => 'Admin status updated',
            'is_admin' => $newStatus
        ]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }

} catch (Exception $e) {
    error_log('Admin Users Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
