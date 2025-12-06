<?php
/**
 * Admin Statistics API
 */

// 출력 버퍼링 시작
ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// 관리자 인증 확인 (admins 테이블 사용)
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

try {
    $db = Database::getInstance();

    // 각 쿼리를 try-catch로 개별 보호
    $totalUsers = 0;
    try {
        $result = $db->selectOne("SELECT COUNT(*) as count FROM users");
        $totalUsers = $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log('Total users query error: ' . $e->getMessage());
    }

    $todayUsers = 0;
    try {
        $result = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
        $todayUsers = $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log('Today users query error: ' . $e->getMessage());
    }

    $activeUsers = 0;
    try {
        $result = $db->selectOne("SELECT COUNT(DISTINCT user_id) as count FROM sessions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $activeUsers = $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log('Active users query error: ' . $e->getMessage());
    }

    $totalSales = 0;
    try {
        $result = $db->selectOne("SELECT COUNT(*) as count FROM sales WHERE status = 'completed'");
        $totalSales = $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log('Total sales query error: ' . $e->getMessage());
    }

    $todaySales = 0;
    try {
        $result = $db->selectOne("SELECT COUNT(*) as count FROM sales WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
        $todaySales = $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log('Today sales query error: ' . $e->getMessage());
    }

    $totalRevenue = 0;
    try {
        $result = $db->selectOne("SELECT SUM(amount) as total FROM sales WHERE status = 'completed'");
        $totalRevenue = $result['total'] ?? 0;
    } catch (Exception $e) {
        error_log('Total revenue query error: ' . $e->getMessage());
    }

    $pendingWithdrawals = 0;
    try {
        $result = $db->selectOne("SELECT COUNT(*) as count FROM withdrawals WHERE status = 'pending'");
        $pendingWithdrawals = $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log('Pending withdrawals query error: ' . $e->getMessage());
    }

    $pendingPayments = 0;
    try {
        $result = $db->selectOne("SELECT COUNT(*) as count FROM sales WHERE status = 'pending'");
        $pendingPayments = $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log('Pending payments query error: ' . $e->getMessage());
    }

    $salesByPackage = [];
    try {
        $salesByPackage = $db->select("
            SELECT p.name, p.price, COUNT(*) as count, SUM(s.amount) as total
            FROM sales s
            JOIN packages p ON s.package_id = p.id
            WHERE s.status = 'completed'
            GROUP BY s.package_id
        ");
    } catch (Exception $e) {
        error_log('Sales by package query error: ' . $e->getMessage());
    }

    $recentUsers = [];
    try {
        $recentUsers = $db->select("
            SELECT id, user_id, email, created_at
            FROM users
            ORDER BY created_at DESC
            LIMIT 10
        ");
    } catch (Exception $e) {
        error_log('Recent users query error: ' . $e->getMessage());
    }

    // 롤업 보너스 통계
    $rollupStats = [
        'sales_with_rollup' => 0,
        'total_bonuses' => 0,
        'total_amount' => 0,
        'avg_level' => 0,
        'max_level' => 0,
        'min_level' => 0
    ];
    try {
        $result = $db->selectOne("
            SELECT
                COUNT(DISTINCT from_user_id) as sales_with_rollup,
                COUNT(*) as total_bonuses,
                SUM(amount) as total_amount,
                AVG(level) as avg_level,
                MAX(level) as max_level,
                MIN(level) as min_level
            FROM bonuses
            WHERE bonus_type = 'rollup'
        ");
        if ($result) {
            $rollupStats = [
                'sales_with_rollup' => (int)($result['sales_with_rollup'] ?? 0),
                'total_bonuses' => (int)($result['total_bonuses'] ?? 0),
                'total_amount' => (float)($result['total_amount'] ?? 0),
                'avg_level' => round((float)($result['avg_level'] ?? 0), 2),
                'max_level' => (int)($result['max_level'] ?? 0),
                'min_level' => (int)($result['min_level'] ?? 0)
            ];
        }
    } catch (Exception $e) {
        error_log('Rollup stats query error: ' . $e->getMessage());
    }

    // 롤업 레벨별 분포
    $rollupLevelDist = [];
    try {
        $rollupLevelDist = $db->select("
            SELECT level, COUNT(*) as cnt, SUM(amount) as total
            FROM bonuses
            WHERE bonus_type = 'rollup'
            GROUP BY level
            ORDER BY level
        ");
    } catch (Exception $e) {
        error_log('Rollup level distribution query error: ' . $e->getMessage());
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => (int)$totalUsers,
            'today_users' => (int)$todayUsers,
            'active_users' => (int)$activeUsers,
            'total_sales' => (int)$totalSales,
            'today_sales' => (int)$todaySales,
            'total_revenue' => (float)$totalRevenue,
            'pending_withdrawals' => (int)$pendingWithdrawals,
            'pending_payments' => (int)$pendingPayments
        ],
        'sales_by_package' => $salesByPackage,
        'recent_users' => $recentUsers,
        'rollup_stats' => $rollupStats,
        'rollup_level_distribution' => $rollupLevelDist
    ]);
    ob_end_flush();

} catch (Exception $e) {
    error_log('Admin Stats Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    ob_clean();
    // 상세 오류 표시 (디버깅용)
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    ob_end_flush();
}
