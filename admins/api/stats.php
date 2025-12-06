<?php
/**
 * Super Admin Statistics API
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// Super Admin 권한 확인
if (!isset($_SESSION['super_admin_logged_in']) || !$_SESSION['super_admin_logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

try {
    $db = Database::getInstance();

    // 전체 관리자 수
    $totalAdmins = $db->selectOne(
        "SELECT COUNT(*) as count FROM admins"
    )['count'];

    // Super Admin 수
    $superAdmins = $db->selectOne(
        "SELECT COUNT(*) as count FROM admins WHERE role = 'super_admin'"
    )['count'];

    // 활성 관리자 수
    $activeAdmins = $db->selectOne(
        "SELECT COUNT(*) as count FROM admins WHERE is_active = 1"
    )['count'];

    // 오늘 로그인한 관리자 수
    $todayLogins = $db->selectOne(
        "SELECT COUNT(DISTINCT admin_id) as count
         FROM admin_logs
         WHERE action IN ('login_success', 'super_login_success')
         AND DATE(created_at) = CURDATE()"
    )['count'];

    // 전체 사용자 수
    $totalUsers = $db->selectOne(
        "SELECT COUNT(*) as count FROM users"
    )['count'];

    // 총 매출 (sales 테이블이 있다고 가정)
    $totalSalesResult = $db->selectOne(
        "SELECT COALESCE(SUM(amount), 0) as total FROM sales WHERE status = 'completed'"
    );
    $totalSales = number_format($totalSalesResult['total'], 2);

    // 대기 중인 출금 (withdrawals 테이블이 있다고 가정)
    $pendingWithdrawals = $db->selectOne(
        "SELECT COUNT(*) as count FROM withdrawals WHERE status = 'pending'"
    )['count'];

    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => [
            'total_admins' => $totalAdmins,
            'super_admins' => $superAdmins,
            'active_admins' => $activeAdmins,
            'today_logins' => $todayLogins,
            'total_users' => $totalUsers,
            'total_sales' => $totalSales,
            'pending_withdrawals' => $pendingWithdrawals
        ]
    ]);
    ob_end_flush();

} catch (Exception $e) {
    error_log('Stats API Error: ' . $e->getMessage());

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => defined('APP_ENV') && APP_ENV === 'development' ? $e->getMessage() : null
    ]);
    ob_end_flush();
}
?>
