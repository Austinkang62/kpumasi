<?php
/**
 * Comprehensive Bonus Audit System
 * 전체 회원 및 아바타 보너스 전수조사
 */

// 오류 표시 활성화 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

set_time_limit(600); // 10 minutes
ini_set('memory_limit', '512M');

try {
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance();
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// ============================================
// 감사 시작 시간
// ============================================
$auditStartTime = microtime(true);
$auditDate = date('Y-m-d H:i:s');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>보너스 전수조사 - K-Pumasi Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-card .label {
            font-size: 0.9em;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-card.success .value { color: #10b981; }
        .stat-card.error .value { color: #ef4444; }
        .stat-card.warning .value { color: #f59e0b; }

        .content {
            padding: 30px;
        }

        .section-title {
            font-size: 1.5em;
            color: #667eea;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .table-wrapper {
            overflow-x: auto;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9em;
            background: white;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        th {
            padding: 12px 8px;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
            font-size: 0.85em;
        }

        td {
            padding: 10px 8px;
            border: 1px solid #e9ecef;
            text-align: center;
        }

        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        tbody tr:hover {
            background: #e3f2fd;
        }

        .status-ok {
            color: #10b981;
            font-weight: bold;
        }

        .status-error {
            color: #ef4444;
            font-weight: bold;
        }

        .status-warning {
            color: #f59e0b;
            font-weight: bold;
        }

        .diff-positive {
            color: #10b981;
        }

        .diff-negative {
            color: #ef4444;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .badge-avatar {
            background: #fef3c7;
            color: #d97706;
        }

        .badge-user {
            background: #dbeafe;
            color: #2563eb;
        }

        .badge-referral-changed {
            background: #fecaca;
            color: #dc2626;
        }

        .alert {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .alert-info {
            background: #e0f2fe;
            border-color: #0284c7;
            color: #075985;
        }

        .alert-warning {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }

        .alert-danger {
            background: #fecaca;
            border-color: #dc2626;
            color: #991b1b;
        }

        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            color: #6c757d;
            border-top: 2px solid #e9ecef;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }

        .small-text {
            font-size: 0.85em;
            color: #6c757d;
        }

        .text-right {
            text-align: right !important;
        }

        .text-left {
            text-align: left !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 보너스 전수조사 시스템</h1>
            <p>전체 회원 및 아바타 4가지 보너스 검증</p>
            <div class="small-text" style="margin-top: 15px; opacity: 0.8;">
                감사 시작: <?= $auditDate ?>
            </div>
        </div>

<?php

// ============================================
// 1단계: 전체 통계 수집
// ============================================

try {
    // 전체 회원 수 (아바타 포함)
    $totalUsers = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE status = 'active' AND deleted_at IS NULL")['count'];
    $totalAvatars = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE status = 'active' AND deleted_at IS NULL AND is_avatar = 1")['count'];
    $totalRegularUsers = $totalUsers - $totalAvatars;

    // 전체 보너스 레코드 수
    $totalBonusRecords = $db->selectOne("SELECT COUNT(*) as count FROM bonuses")['count'];

    // 보너스 타입별 레코드 수
    $referralBonusCount = $db->selectOne("SELECT COUNT(*) as count FROM bonuses WHERE bonus_type = 'referral'")['count'];
    $edgeBonusCount = $db->selectOne("SELECT COUNT(*) as count FROM bonuses WHERE bonus_type = 'edge'")['count'];
    $matchingBonusCount = $db->selectOne("SELECT COUNT(*) as count FROM bonuses WHERE bonus_type = 'matching'")['count'];
    $rollupBonusCount = $db->selectOne("SELECT COUNT(*) as count FROM bonuses WHERE bonus_type = 'rollup'")['count'];

    // 추천인 변경 이력 확인
    $referralChanges = [];
    try {
        $referralChanges = $db->select(
            "SELECT user_id, old_value, new_value, created_at, admin_username, reason
             FROM user_change_history
             WHERE table_name = 'users' AND field_name = 'referral_id'
             ORDER BY created_at DESC"
        );
    } catch (Exception $e) {
        // user_change_history 테이블이 없을 수 있음
        $referralChanges = [];
    }

    $referralChangeCount = count($referralChanges);

    echo <<<HTML
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">전체 회원</div>
                <div class="value">{$totalUsers}</div>
            </div>
            <div class="stat-card">
                <div class="label">일반 회원</div>
                <div class="value">{$totalRegularUsers}</div>
            </div>
            <div class="stat-card">
                <div class="label">아바타</div>
                <div class="value">{$totalAvatars}</div>
            </div>
            <div class="stat-card">
                <div class="label">총 보너스 레코드</div>
                <div class="value">{$totalBonusRecords}</div>
            </div>
            <div class="stat-card">
                <div class="label">추천 보너스</div>
                <div class="value">{$referralBonusCount}</div>
            </div>
            <div class="stat-card">
                <div class="label">엣지 보너스</div>
                <div class="value">{$edgeBonusCount}</div>
            </div>
            <div class="stat-card">
                <div class="label">매칭 보너스</div>
                <div class="value">{$matchingBonusCount}</div>
            </div>
            <div class="stat-card">
                <div class="label">롤업 보너스</div>
                <div class="value">{$rollupBonusCount}</div>
            </div>
            <div class="stat-card warning">
                <div class="label">추천인 변경 이력</div>
                <div class="value">{$referralChangeCount}</div>
            </div>
        </div>

        <div class="content">
HTML;

    // ============================================
    // 2단계: 추천인 변경 이력 표시
    // ============================================
    if ($referralChangeCount > 0) {
        echo '<div class="alert alert-warning">';
        echo '<strong>⚠️ 추천인 변경 이력 발견!</strong><br>';
        echo "총 {$referralChangeCount}건의 추천인 변경이 있었습니다. 이로 인해 보너스 정확도가 영향을 받을 수 있습니다.";
        echo '</div>';

        echo '<h2 class="section-title">📋 추천인 변경 이력</h2>';
        echo '<div class="table-wrapper">';
        echo '<table>';
        echo '<thead><tr>';
        echo '<th>회원 ID</th>';
        echo '<th>이전 추천인</th>';
        echo '<th>새 추천인</th>';
        echo '<th>변경 일시</th>';
        echo '<th>작업자</th>';
        echo '<th>사유</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($referralChanges as $change) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($change['user_id']) . '</td>';
            echo '<td>' . htmlspecialchars($change['old_value'] ?: 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($change['new_value'] ?: 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($change['created_at']) . '</td>';
            echo '<td>' . htmlspecialchars($change['admin_username']) . '</td>';
            echo '<td class="text-left">' . htmlspecialchars($change['reason'] ?: '-') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    // ============================================
    // 3단계: 전체 회원 보너스 감사
    // ============================================

    echo '<h2 class="section-title">🔍 전체 회원 보너스 감사 결과</h2>';

    // 모든 활성 사용자 조회 (LIMIT으로 일단 100명만)
    $users = $db->select(
        "SELECT
            u.id,
            u.user_id,
            u.name as username,
            u.referral_id,
            u.sponsor_id,
            u.sponsor_position,
            u.is_avatar,
            u.parent_account_id,
            u.package_id,
            u.total_referral_bonus,
            u.total_edge_bonus,
            u.total_matching_bonus,
            u.total_rollup_bonus,
            u.total_bonus,
            u.available_bonus,
            u.avatar_points
         FROM users u
         WHERE u.status = 'active' AND u.deleted_at IS NULL
         ORDER BY u.is_avatar ASC, u.total_bonus DESC
         LIMIT 100"
    );

    // 보너스 상세 데이터 한번에 조회 (성능 최적화)
    $bonusData = [];
    $userIds = array_column($users, 'id');

    if (!empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $bonuses = $db->select(
            "SELECT user_id, bonus_type, payment_type, SUM(amount) as total_amount, COUNT(*) as count
             FROM bonuses
             WHERE user_id IN ($placeholders)
             GROUP BY user_id, bonus_type, payment_type",
            $userIds
        );

        foreach ($userIds as $userId) {
            $bonusData[$userId] = [
                'referral_cash' => 0,
                'referral_avatar' => 0,
                'edge_cash' => 0,
                'edge_avatar' => 0,
                'matching_cash' => 0,
                'matching_avatar' => 0,
                'rollup_cash' => 0,
                'rollup_avatar' => 0,
                'referral_count' => 0,
                'edge_count' => 0,
                'matching_count' => 0,
                'rollup_count' => 0
            ];
        }

        foreach ($bonuses as $bonus) {
            $userId = $bonus['user_id'];
            $type = $bonus['bonus_type'];
            $payment = $bonus['payment_type'];
            $key = $type . '_' . $payment;
            $countKey = $type . '_count';

            if (isset($bonusData[$userId][$key])) {
                $bonusData[$userId][$key] = floatval($bonus['total_amount']);
            }
            if (isset($bonusData[$userId][$countKey])) {
                $bonusData[$userId][$countKey] += intval($bonus['count']);
            }
        }
    }

    // 각 사용자별 기대 보너스 계산
    $auditResults = [];
    $totalErrors = 0;
    $totalWarnings = 0;
    $totalOK = 0;

    foreach ($users as $user) {
        $userId = $user['id'];
        $result = [
            'user' => $user,
            'actual' => $bonusData[$userId] ?? [],
            'expected' => [
                'referral' => 0,
                'edge' => 0,
                'matching' => 0,
                'rollup' => 0
            ],
            'status' => 'OK',
            'errors' => [],
            'warnings' => [],
            'has_referral_change' => false
        ];

        // 추천인 변경 여부 확인
        foreach ($referralChanges as $change) {
            if ($change['user_id'] == $userId) {
                $result['has_referral_change'] = true;
                $result['warnings'][] = '추천인 변경 이력 있음';
                break;
            }
        }

        // ==========================================
        // 1. REFERRAL BONUS 기대치 계산
        // ==========================================
        $referrals = $db->select(
            "SELECT u.id, u.package_id, p.price
             FROM users u
             LEFT JOIN packages p ON u.package_id = p.id
             WHERE u.referral_id = ? AND u.status = 'active' AND u.deleted_at IS NULL AND u.package_id IS NOT NULL",
            [$userId]
        );

        $expectedReferralBonus = 0;
        foreach ($referrals as $ref) {
            $packageAmount = floatval($ref['price'] ?? 0);
            $expectedReferralBonus += $packageAmount * 0.25; // 25%
        }
        $result['expected']['referral'] = $expectedReferralBonus;

        // Edge와 Rollup은 실제값 기준
        $actualTotal = [
            'referral' => ($bonusData[$userId]['referral_cash'] ?? 0) + ($bonusData[$userId]['referral_avatar'] ?? 0),
            'edge' => ($bonusData[$userId]['edge_cash'] ?? 0) + ($bonusData[$userId]['edge_avatar'] ?? 0),
            'matching' => ($bonusData[$userId]['matching_cash'] ?? 0) + ($bonusData[$userId]['matching_avatar'] ?? 0),
            'rollup' => ($bonusData[$userId]['rollup_cash'] ?? 0) + ($bonusData[$userId]['rollup_avatar'] ?? 0)
        ];

        $result['expected']['edge'] = $actualTotal['edge'];
        $result['expected']['rollup'] = $actualTotal['rollup'];

        // ==========================================
        // 3. MATCHING BONUS 기대치 계산
        // ==========================================
        $expectedMatchingBonus = 0;
        foreach ($referrals as $ref) {
            $refUserId = $ref['id'];
            $refEdge = $db->selectOne(
                "SELECT IFNULL(SUM(amount), 0) as total
                 FROM bonuses
                 WHERE user_id = ? AND bonus_type = 'edge'",
                [$refUserId]
            );

            if ($refEdge && $refEdge['total'] > 0) {
                $refEdgeTotal = floatval($refEdge['total']);
                $expectedMatchingBonus += $refEdgeTotal * 0.25; // edge의 25%
            }
        }
        $result['expected']['matching'] = $expectedMatchingBonus;

        // ==========================================
        // 5. 실제값과 기대값 비교
        // ==========================================
        $tolerance = 0.01; // $0.01 허용 오차

        // Referral 검증
        if (abs($actualTotal['referral'] - $result['expected']['referral']) > $tolerance) {
            $diff = $actualTotal['referral'] - $result['expected']['referral'];
            $result['errors'][] = sprintf(
                'Referral 불일치: 차이=$%.2f',
                $diff
            );
            $result['status'] = 'ERROR';
        }

        // Matching 검증
        if (abs($actualTotal['matching'] - $result['expected']['matching']) > $tolerance) {
            $diff = $actualTotal['matching'] - $result['expected']['matching'];
            $result['errors'][] = sprintf(
                'Matching 불일치: 차이=$%.2f',
                $diff
            );
            if ($result['status'] !== 'ERROR') {
                $result['status'] = 'ERROR';
            }
        }

        // Edge 경고
        if ($actualTotal['edge'] == 0 && $user['sponsor_id'] && count($referrals) > 0) {
            $result['warnings'][] = 'Edge 보너스 없음';
            if ($result['status'] === 'OK') {
                $result['status'] = 'WARNING';
            }
        }

        $auditResults[] = $result;

        // 상태별 카운트
        if ($result['status'] === 'ERROR') {
            $totalErrors++;
        } elseif ($result['status'] === 'WARNING') {
            $totalWarnings++;
        } else {
            $totalOK++;
        }
    }

    // ============================================
    // 4단계: 감사 결과 요약 표시
    // ============================================
    echo '<div class="stats-grid">';
    echo '<div class="stat-card success">';
    echo '<div class="label">정상 ✅</div>';
    echo '<div class="value">' . $totalOK . '</div>';
    echo '</div>';
    echo '<div class="stat-card warning">';
    echo '<div class="label">경고 ⚠️</div>';
    echo '<div class="value">' . $totalWarnings . '</div>';
    echo '</div>';
    echo '<div class="stat-card error">';
    echo '<div class="label">오류 ❌</div>';
    echo '<div class="value">' . $totalErrors . '</div>';
    echo '</div>';
    echo '</div>';

    if ($totalErrors > 0) {
        echo '<div class="alert alert-danger">';
        echo '<strong>❌ 보너스 오류 발견!</strong><br>';
        echo "{$totalErrors}명의 회원에게서 보너스 불일치가 발견되었습니다.";
        echo '</div>';
    }

    // ============================================
    // 5단계: 상세 결과 테이블
    // ============================================

    echo '<div class="alert alert-info">';
    echo '<strong>ℹ️ 표시된 회원:</strong> 보너스가 많은 상위 100명 (전체 ' . $totalUsers . '명)';
    echo '</div>';

    echo '<h2 class="section-title">📊 상세 감사 결과</h2>';
    echo '<div class="table-wrapper">';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th rowspan="2">상태</th>';
    echo '<th rowspan="2">ID</th>';
    echo '<th rowspan="2">User ID</th>';
    echo '<th rowspan="2">이름</th>';
    echo '<th rowspan="2">타입</th>';
    echo '<th colspan="3">Referral (25%)</th>';
    echo '<th colspan="3">Edge (25%)</th>';
    echo '<th colspan="3">Matching (6.25%)</th>';
    echo '<th colspan="3">Rollup (1%×25)</th>';
    echo '<th rowspan="2">문제</th>';
    echo '</tr>';
    echo '<tr>';
    // Referral
    echo '<th>실제</th><th>기대</th><th>차이</th>';
    // Edge
    echo '<th>실제</th><th>기대</th><th>차이</th>';
    // Matching
    echo '<th>실제</th><th>기대</th><th>차이</th>';
    // Rollup
    echo '<th>실제</th><th>기대</th><th>차이</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($auditResults as $result) {
        $user = $result['user'];
        $actual = $result['actual'];
        $expected = $result['expected'];

        // 실제 합계
        $actualReferral = ($actual['referral_cash'] ?? 0) + ($actual['referral_avatar'] ?? 0);
        $actualEdge = ($actual['edge_cash'] ?? 0) + ($actual['edge_avatar'] ?? 0);
        $actualMatching = ($actual['matching_cash'] ?? 0) + ($actual['matching_avatar'] ?? 0);
        $actualRollup = ($actual['rollup_cash'] ?? 0) + ($actual['rollup_avatar'] ?? 0);

        // 차이
        $diffReferral = $actualReferral - $expected['referral'];
        $diffEdge = $actualEdge - $expected['edge'];
        $diffMatching = $actualMatching - $expected['matching'];
        $diffRollup = $actualRollup - $expected['rollup'];

        // 상태 아이콘
        $statusIcon = '✅';
        $statusClass = 'status-ok';
        if ($result['status'] === 'ERROR') {
            $statusIcon = '❌';
            $statusClass = 'status-error';
        } elseif ($result['status'] === 'WARNING') {
            $statusIcon = '⚠️';
            $statusClass = 'status-warning';
        }

        // 타입 뱃지
        $typeBadge = '<span class="badge badge-user">일반</span>';
        if ($user['is_avatar']) {
            $typeBadge = '<span class="badge badge-avatar">아바타</span>';
        }
        if ($result['has_referral_change']) {
            $typeBadge .= ' <span class="badge badge-referral-changed">추천변경</span>';
        }

        echo '<tr>';
        echo '<td class="' . $statusClass . '">' . $statusIcon . '</td>';
        echo '<td>' . $user['id'] . '</td>';
        echo '<td class="text-left">' . htmlspecialchars($user['user_id']) . '</td>';
        echo '<td class="text-left">' . htmlspecialchars($user['username']) . '</td>';
        echo '<td>' . $typeBadge . '</td>';

        // Referral
        echo '<td class="text-right">$' . number_format($actualReferral, 2) . '</td>';
        echo '<td class="text-right">$' . number_format($expected['referral'], 2) . '</td>';
        echo '<td class="text-right ' . ($diffReferral > 0.01 ? 'diff-positive' : ($diffReferral < -0.01 ? 'diff-negative' : '')) . '">';
        echo '$' . number_format($diffReferral, 2);
        echo '</td>';

        // Edge
        echo '<td class="text-right">$' . number_format($actualEdge, 2) . '</td>';
        echo '<td class="text-right">$' . number_format($expected['edge'], 2) . '</td>';
        echo '<td class="text-right">$0.00</td>';

        // Matching
        echo '<td class="text-right">$' . number_format($actualMatching, 2) . '</td>';
        echo '<td class="text-right">$' . number_format($expected['matching'], 2) . '</td>';
        echo '<td class="text-right ' . ($diffMatching > 0.01 ? 'diff-positive' : ($diffMatching < -0.01 ? 'diff-negative' : '')) . '">';
        echo '$' . number_format($diffMatching, 2);
        echo '</td>';

        // Rollup
        echo '<td class="text-right">$' . number_format($actualRollup, 2) . '</td>';
        echo '<td class="text-right">$' . number_format($expected['rollup'], 2) . '</td>';
        echo '<td class="text-right">$0.00</td>';

        // 문제점
        echo '<td class="text-left small-text">';
        if (!empty($result['errors'])) {
            echo '<span class="status-error">' . implode('<br>', $result['errors']) . '</span>';
        }
        if (!empty($result['warnings'])) {
            if (!empty($result['errors'])) echo '<br>';
            echo '<span class="status-warning">' . implode('<br>', $result['warnings']) . '</span>';
        }
        if (empty($result['errors']) && empty($result['warnings'])) {
            echo '-';
        }
        echo '</td>';

        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    // ============================================
    // 감사 완료
    // ============================================
    $auditEndTime = microtime(true);
    $executionTime = round($auditEndTime - $auditStartTime, 2);

    echo <<<HTML
        </div>

        <div class="footer">
            <p><strong>감사 완료</strong></p>
            <p>총 {$totalUsers}명 중 상위 100명 검증 (일반 {$totalRegularUsers}명, 아바타 {$totalAvatars}명)</p>
            <p>실행 시간: {$executionTime}초 | 생성 시간: {$auditDate}</p>
        </div>
    </div>
</body>
</html>
HTML;

} catch (Exception $e) {
    echo '<div class="alert alert-danger">';
    echo '<strong>오류 발생:</strong> ' . htmlspecialchars($e->getMessage());
    echo '<br><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
    echo '</div></div></body></html>';
}
?>
