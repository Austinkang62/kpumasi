<?php
/**
 * Admin Stats - 403 우회용
 */

session_start();

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();

    // Total Users
    $result = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL");
    $totalUsers = $result['count'] ?? 0;

    // Today Users
    $result = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE() AND deleted_at IS NULL");
    $todayUsers = $result['count'] ?? 0;

    // Active Users (last 7 days)
    $result = $db->selectOne("SELECT COUNT(DISTINCT user_id) as count FROM sessions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $activeUsers = $result['count'] ?? 0;

    // Total Sales - sales 테이블에서 실제 매출 조회
    $result = $db->selectOne("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM sales WHERE status = 'completed'");
    $totalSalesCount = $result['count'] ?? 0;
    $totalSales = floatval($result['total'] ?? 0);

    // Today Sales
    $result = $db->selectOne("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM sales WHERE DATE(created_at) = CURDATE() AND status = 'completed'");
    $todaySales = floatval($result['total'] ?? 0);

    // Recent Users
    $recentUsers = $db->select("
        SELECT id, user_id, email, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 10
    ");

    // Total Bonuses
    $result = $db->selectOne("SELECT COALESCE(SUM(amount), 0) as total FROM bonuses WHERE status = 'paid'");
    $totalBonuses = floatval($result['total'] ?? 0);

    // Pending Withdrawals
    $result = $db->selectOne("SELECT COUNT(*) as count FROM withdrawals WHERE status = 'pending'");
    $pendingWithdrawals = $result['count'] ?? 0;

    // Total Avatars
    $result = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE is_avatar = 1");
    $totalAvatars = $result['count'] ?? 0;

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => (int)$totalUsers,
            'today_users' => (int)$todayUsers,
            'active_users' => (int)$activeUsers,
            'total_sales' => (float)$totalSales,
            'total_sales_count' => (int)$totalSalesCount,
            'today_sales' => (float)$todaySales,
            'total_bonuses' => (float)$totalBonuses,
            'total_avatars' => (int)$totalAvatars,
            'pending_withdrawals' => (int)$pendingWithdrawals
        ],
        'sales_by_package' => [],
        'recent_users' => $recentUsers
    ]);

} catch (Exception $e) {
    error_log('Stats Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
