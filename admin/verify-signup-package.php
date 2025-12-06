<?php
/**
 * 회원가입 시 $100 패키지 처리 검증
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'summary' => [],
        'recent_signups' => [],
        'missing_sales' => [],
        'missing_bonuses' => [],
        'correct_records' => []
    ];

    // 1. 최근 30일 회원가입 통계
    $signupStats = $pdo->query("
        SELECT
            COUNT(*) as total_users,
            COUNT(CASE WHEN package_id = 2 THEN 1 END) as package_100_users,
            COUNT(CASE WHEN package_id IS NULL THEN 1 END) as no_package_users
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND is_avatar = 0
    ")->fetch(PDO::FETCH_ASSOC);

    $report['summary']['signups_last_30days'] = $signupStats;

    // 2. Sales 테이블 매칭 통계
    $salesStats = $pdo->query("
        SELECT
            COUNT(DISTINCT u.id) as users_with_sales,
            COUNT(s.id) as total_sales,
            SUM(s.amount) as total_amount
        FROM users u
        LEFT JOIN sales s ON u.id = s.user_id AND s.amount = 100
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND u.is_avatar = 0
        AND s.id IS NOT NULL
    ")->fetch(PDO::FETCH_ASSOC);

    $report['summary']['sales_records'] = $salesStats;

    // 3. 최근 회원가입 상세 (최근 20명)
    $recentSignups = $pdo->query("
        SELECT
            u.id,
            u.user_id,
            u.email,
            u.package_id,
            u.created_at,
            (SELECT COUNT(*) FROM sales WHERE user_id = u.id AND amount = 100) as sales_count,
            (SELECT SUM(amount) FROM sales WHERE user_id = u.id) as total_sales,
            (SELECT COUNT(*) FROM bonuses WHERE from_user_id = u.id) as bonus_count,
            (SELECT SUM(amount) FROM bonuses WHERE from_user_id = u.id) as total_bonuses
        FROM users u
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND u.is_avatar = 0
        ORDER BY u.created_at DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['recent_signups'] = $recentSignups;

    // 4. Sales 누락 건 (회원가입했지만 $100 매출 없음)
    $missingSales = $pdo->query("
        SELECT
            u.id,
            u.user_id,
            u.email,
            u.package_id,
            u.created_at,
            (SELECT COUNT(*) FROM sales WHERE user_id = u.id) as sales_count
        FROM users u
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND u.is_avatar = 0
        AND u.package_id = 2
        AND NOT EXISTS (
            SELECT 1 FROM sales
            WHERE user_id = u.id
            AND amount = 100
        )
        ORDER BY u.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['missing_sales'] = $missingSales;
    $report['summary']['missing_sales_count'] = count($missingSales);

    // 5. 보너스 누락 건 (매출은 있지만 보너스 없음)
    $missingBonuses = $pdo->query("
        SELECT
            u.id,
            u.user_id,
            u.email,
            s.id as sale_id,
            s.amount,
            s.created_at as sale_date,
            (SELECT COUNT(*) FROM bonuses WHERE from_user_id = u.id) as bonus_count
        FROM users u
        INNER JOIN sales s ON u.id = s.user_id
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND u.is_avatar = 0
        AND s.amount = 100
        AND NOT EXISTS (
            SELECT 1 FROM bonuses
            WHERE from_user_id = u.id
        )
        ORDER BY s.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['missing_bonuses'] = $missingBonuses;
    $report['summary']['missing_bonuses_count'] = count($missingBonuses);

    // 6. 정상 처리된 케이스 개수 (전체)
    $correctCount = $pdo->query("
        SELECT COUNT(DISTINCT u.id) as total
        FROM users u
        INNER JOIN sales s ON u.id = s.user_id
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND u.is_avatar = 0
        AND s.amount = 100
        AND EXISTS (
            SELECT 1 FROM bonuses
            WHERE from_user_id = u.id
        )
    ")->fetch(PDO::FETCH_ASSOC);

    $report['summary']['correct_records_count'] = $correctCount['total'];

    // 6-1. 정상 처리된 케이스 상세 (최근 10건만)
    $correctRecords = $pdo->query("
        SELECT
            u.id,
            u.user_id,
            u.email,
            u.created_at,
            s.id as sale_id,
            s.amount,
            (SELECT COUNT(*) FROM bonuses WHERE from_user_id = u.id) as bonus_count,
            (SELECT SUM(amount) FROM bonuses WHERE from_user_id = u.id) as total_bonus_amount
        FROM users u
        INNER JOIN sales s ON u.id = s.user_id
        WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND u.is_avatar = 0
        AND s.amount = 100
        AND EXISTS (
            SELECT 1 FROM bonuses
            WHERE from_user_id = u.id
        )
        ORDER BY u.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['correct_records'] = $correctRecords;

    // 7. 보너스 과다 배정 (100% 이상)
    $overAllocatedBonuses = $pdo->query("
        SELECT
            s.id as sale_id,
            u.id,
            u.user_id,
            u.email,
            s.amount as sale_amount,
            s.created_at as sale_date,
            (SELECT SUM(amount) FROM bonuses WHERE from_user_id = u.id) as total_bonuses,
            ROUND((SELECT SUM(amount) FROM bonuses WHERE from_user_id = u.id) / s.amount * 100, 2) as bonus_percentage
        FROM sales s
        INNER JOIN users u ON s.user_id = u.id
        WHERE s.amount = 100
        AND u.is_avatar = 0
        AND u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND (SELECT SUM(amount) FROM bonuses WHERE from_user_id = u.id) >= s.amount
        ORDER BY bonus_percentage DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $report['over_allocated_bonuses'] = $overAllocatedBonuses;
    $report['summary']['over_allocated_count'] = count($overAllocatedBonuses);

    // 8. 전체 상태 평가
    $totalSignups = $signupStats['total_users'];
    $totalCorrect = $report['summary']['correct_records_count'];
    $totalMissing = $report['summary']['missing_sales_count'] + $report['summary']['missing_bonuses_count'];

    $report['summary']['status'] = 'unknown';
    if ($totalSignups > 0) {
        $successRate = ($totalCorrect / $totalSignups) * 100;
        if ($successRate >= 95) {
            $report['summary']['status'] = 'excellent';
        } elseif ($successRate >= 80) {
            $report['summary']['status'] = 'good';
        } elseif ($successRate >= 50) {
            $report['summary']['status'] = 'warning';
        } else {
            $report['summary']['status'] = 'critical';
        }
        $report['summary']['success_rate'] = round($successRate, 2);
    }

    echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
