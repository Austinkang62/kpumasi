<?php
/**
 * 보너스 검증 테스트 - 간단한 버전
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

try {
    $db = Database::getInstance();

    // 1단계: sales 조회 테스트
    $salesCount = $db->selectOne("SELECT COUNT(*) as cnt FROM sales WHERE status = 'completed'");

    // 2단계: bonuses 조회 테스트
    $bonusesCount = $db->selectOne("SELECT COUNT(*) as cnt FROM bonuses");

    // 3단계: 간단한 비교
    $totalSales = floatval($db->selectOne("SELECT COALESCE(SUM(amount), 0) as total FROM sales WHERE status = 'completed'")['total']);
    $totalBonuses = floatval($db->selectOne("SELECT COALESCE(SUM(amount), 0) as total FROM bonuses")['total']);

    ob_clean();
    echo json_encode([
        'success' => true,
        'test_results' => [
            'sales_count' => intval($salesCount['cnt']),
            'bonuses_count' => intval($bonusesCount['cnt']),
            'total_sales' => $totalSales,
            'total_bonuses' => $totalBonuses,
            'ratio' => $totalSales > 0 ? ($totalBonuses / $totalSales * 100) : 0
        ],
        'message' => 'Basic test completed successfully'
    ]);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
    ob_end_flush();
}
