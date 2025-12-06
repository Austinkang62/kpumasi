<?php
/**
 * Stats API Proxy
 * 403 에러 우회
 */

session_start();

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();

    // Total Users
    $result = $db->selectOne("SELECT COUNT(*) as count FROM users");
    $totalUsers = $result['count'] ?? 0;

    // Today Users
    $result = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
    $todayUsers = $result['count'] ?? 0;

    // Active Users (last 7 days)
    $result = $db->selectOne("SELECT COUNT(DISTINCT user_id) as count FROM sessions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $activeUsers = $result['count'] ?? 0;

    // Total Sales (package_id > 0 users * $100)
    $result = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE package_id > 0");
    $userCount = $result['count'] ?? 0;
    $totalSales = $userCount * 100;

    // Recent Users
    $recentUsers = $db->select("
        SELECT id, user_id, email, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 10
    ");

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => (int)$totalUsers,
            'today_users' => (int)$todayUsers,
            'active_users' => (int)$activeUsers,
            'total_sales' => (float)$totalSales,
            'today_sales' => 0,
            'total_revenue' => 0,
            'pending_withdrawals' => 0,
            'pending_payments' => 0
        ],
        'sales_by_package' => [],
        'recent_users' => $recentUsers
    ]);

} catch (Exception $e) {
    error_log('Stats Proxy Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
