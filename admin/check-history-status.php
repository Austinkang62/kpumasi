<?php
/**
 * 히스토리 저장 상태 확인 스크립트
 * 브라우저에서 실행하여 현재 상태 확인
 */

require_once __DIR__ . '/../config/database.php';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>히스토리 저장 상태 확인</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .section h2 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin: 5px;
        }
        .status-success {
            background: #d1fae5;
            color: #065f46;
        }
        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: 800;
            color: #667eea;
        }
        .stat-label {
            color: #6b7280;
            margin-top: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }
        pre {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9em;
        }
        .info-box {
            background: #dbeafe;
            color: #1e40af;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #3b82f6;
        }
        .test-button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            margin: 10px 5px;
        }
        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 히스토리 저장 상태 확인</h1>

<?php
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // ========================================
    // 1. 테이블 존재 확인
    // ========================================
    echo '<div class="section">';
    echo '<h2>1️⃣ 테이블 존재 확인</h2>';

    $tables = [
        'user_change_history' => '회원 정보 변경 이력',
        'admin_logs' => '관리자 활동 로그'
    ];

    foreach ($tables as $tableName => $description) {
        $result = $db->selectOne(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = 'ai22' AND TABLE_NAME = ?",
            [$tableName]
        );
        if ($result) {
            echo "<span class='status status-success'>✓ {$description} ({$tableName})</span>";
        } else {
            echo "<span class='status status-error'>✗ {$description} ({$tableName}) - 테이블 없음</span>";
        }
    }
    echo '</div>';

    // ========================================
    // 2. 회원 변경 이력 통계
    // ========================================
    echo '<div class="section">';
    echo '<h2>2️⃣ 회원 변경 이력 (user_change_history)</h2>';

    // 테이블 존재 확인
    $tableExists = $db->selectOne(
        "SELECT TABLE_NAME FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = 'ai22' AND TABLE_NAME = 'user_change_history'"
    );

    if ($tableExists) {
        // 전체 레코드 수
        $totalRecords = $db->selectOne("SELECT COUNT(*) as count FROM user_change_history")['count'];

        // 오늘 레코드 수
        $todayRecords = $db->selectOne(
            "SELECT COUNT(*) as count FROM user_change_history WHERE DATE(created_at) = CURDATE()"
        )['count'];

        // 이번 주 레코드 수
        $weekRecords = $db->selectOne(
            "SELECT COUNT(*) as count FROM user_change_history
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        )['count'];

        // 영향받은 사용자 수
        $affectedUsers = $db->selectOne(
            "SELECT COUNT(DISTINCT user_id) as count FROM user_change_history"
        )['count'];

        // 작업한 관리자 수
        $activeAdmins = $db->selectOne(
            "SELECT COUNT(DISTINCT admin_id) as count FROM user_change_history"
        )['count'];

        echo '<div class="stat-grid">';
        echo '<div class="stat-box"><div class="stat-number">' . $totalRecords . '</div><div class="stat-label">전체 변경</div></div>';
        echo '<div class="stat-box"><div class="stat-number">' . $todayRecords . '</div><div class="stat-label">오늘 변경</div></div>';
        echo '<div class="stat-box"><div class="stat-number">' . $weekRecords . '</div><div class="stat-label">이번 주</div></div>';
        echo '<div class="stat-box"><div class="stat-number">' . $affectedUsers . '</div><div class="stat-label">영향받은 회원</div></div>';
        echo '<div class="stat-box"><div class="stat-number">' . $activeAdmins . '</div><div class="stat-label">작업한 관리자</div></div>';
        echo '</div>';

        if ($totalRecords > 0) {
            // 변경 유형별 통계
            $typeStats = $db->select(
                "SELECT change_type, COUNT(*) as count
                 FROM user_change_history
                 GROUP BY change_type
                 ORDER BY count DESC"
            );

            if (!empty($typeStats)) {
                echo '<h3>📊 변경 유형별 통계</h3>';
                echo '<table>';
                echo '<thead><tr><th>변경 유형</th><th>건수</th></tr></thead>';
                echo '<tbody>';
                foreach ($typeStats as $stat) {
                    echo "<tr><td>{$stat['change_type']}</td><td><strong>{$stat['count']}</strong></td></tr>";
                }
                echo '</tbody></table>';
            }

            // 최근 변경 이력 5건
            $recentChanges = $db->select(
                "SELECT h.*, u.user_id as user_login
                 FROM user_change_history h
                 LEFT JOIN users u ON h.user_id = u.id
                 ORDER BY h.created_at DESC
                 LIMIT 5"
            );

            if (!empty($recentChanges)) {
                echo '<h3>📝 최근 변경 이력 (최근 5건)</h3>';
                echo '<table>';
                echo '<thead><tr><th>시간</th><th>관리자</th><th>회원</th><th>변경 유형</th><th>필드</th><th>이전 → 새 값</th></tr></thead>';
                echo '<tbody>';
                foreach ($recentChanges as $change) {
                    $oldVal = strlen($change['old_value']) > 30 ? substr($change['old_value'], 0, 30) . '...' : $change['old_value'];
                    $newVal = strlen($change['new_value']) > 30 ? substr($change['new_value'], 0, 30) . '...' : $change['new_value'];

                    echo "<tr>";
                    echo "<td>" . date('m-d H:i', strtotime($change['created_at'])) . "</td>";
                    echo "<td>{$change['admin_username']}</td>";
                    echo "<td>{$change['user_id']}</td>";
                    echo "<td>{$change['change_type']}</td>";
                    echo "<td>{$change['field_name']}</td>";
                    echo "<td><small>{$oldVal}</small> → <small>{$newVal}</small></td>";
                    echo "</tr>";
                }
                echo '</tbody></table>';
            }
        } else {
            echo '<div class="info-box">';
            echo '📝 아직 저장된 변경 이력이 없습니다.<br>';
            echo '관리자가 회원 정보를 수정하면 자동으로 기록됩니다.';
            echo '</div>';
        }
    } else {
        echo '<div class="status status-error">테이블이 존재하지 않습니다!</div>';
        echo '<div class="info-box">';
        echo '<strong>해결 방법:</strong><br>';
        echo '1. HeidiSQL에서 다음 SQL 실행:<br>';
        echo '<code>/mnt/c/app/pum1202/www/database/create_user_change_history.sql</code>';
        echo '</div>';
    }

    echo '</div>';

    // ========================================
    // 3. 관리자 활동 로그 통계
    // ========================================
    echo '<div class="section">';
    echo '<h2>3️⃣ 관리자 활동 로그 (admin_logs)</h2>';

    $tableExists = $db->selectOne(
        "SELECT TABLE_NAME FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = 'ai22' AND TABLE_NAME = 'admin_logs'"
    );

    if ($tableExists) {
        // 전체 레코드 수
        $totalLogs = $db->selectOne("SELECT COUNT(*) as count FROM admin_logs")['count'];

        // 오늘 로그 수
        $todayLogs = $db->selectOne(
            "SELECT COUNT(*) as count FROM admin_logs WHERE DATE(created_at) = CURDATE()"
        )['count'];

        // 이번 주 로그 수
        $weekLogs = $db->selectOne(
            "SELECT COUNT(*) as count FROM admin_logs
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        )['count'];

        // 활동 관리자 수
        $activeAdmins = $db->selectOne(
            "SELECT COUNT(DISTINCT admin_id) as count FROM admin_logs"
        )['count'];

        // 오늘 로그인 수
        $todayLogins = $db->selectOne(
            "SELECT COUNT(*) as count FROM admin_logs
             WHERE action IN ('login_success', 'super_login_success')
             AND DATE(created_at) = CURDATE()"
        )['count'];

        echo '<div class="stat-grid">';
        echo '<div class="stat-box"><div class="stat-number">' . $totalLogs . '</div><div class="stat-label">전체 로그</div></div>';
        echo '<div class="stat-box"><div class="stat-number">' . $todayLogs . '</div><div class="stat-label">오늘 활동</div></div>';
        echo '<div class="stat-box"><div class="stat-number">' . $weekLogs . '</div><div class="stat-label">이번 주</div></div>';
        echo '<div class="stat-box"><div class="stat-number">' . $activeAdmins . '</div><div class="stat-label">활동 관리자</div></div>';
        echo '<div class="stat-box"><div class="stat-number">' . $todayLogins . '</div><div class="stat-label">오늘 로그인</div></div>';
        echo '</div>';

        if ($totalLogs > 0) {
            // 활동 유형별 통계
            $actionStats = $db->select(
                "SELECT action, COUNT(*) as count
                 FROM admin_logs
                 GROUP BY action
                 ORDER BY count DESC
                 LIMIT 10"
            );

            if (!empty($actionStats)) {
                echo '<h3>📊 활동 유형별 통계 (Top 10)</h3>';
                echo '<table>';
                echo '<thead><tr><th>활동 유형</th><th>건수</th></tr></thead>';
                echo '<tbody>';
                foreach ($actionStats as $stat) {
                    echo "<tr><td>{$stat['action']}</td><td><strong>{$stat['count']}</strong></td></tr>";
                }
                echo '</tbody></table>';
            }

            // 최근 활동 5건
            $recentLogs = $db->select(
                "SELECT l.*, a.username
                 FROM admin_logs l
                 LEFT JOIN admins a ON l.admin_id = a.admin_id
                 ORDER BY l.created_at DESC
                 LIMIT 5"
            );

            if (!empty($recentLogs)) {
                echo '<h3>📝 최근 활동 (최근 5건)</h3>';
                echo '<table>';
                echo '<thead><tr><th>시간</th><th>관리자</th><th>활동</th><th>IP 주소</th></tr></thead>';
                echo '<tbody>';
                foreach ($recentLogs as $log) {
                    echo "<tr>";
                    echo "<td>" . date('m-d H:i:s', strtotime($log['created_at'])) . "</td>";
                    echo "<td>{$log['username']}</td>";
                    echo "<td>{$log['action']}</td>";
                    echo "<td>{$log['ip_address']}</td>";
                    echo "</tr>";
                }
                echo '</tbody></table>';
            }
        } else {
            echo '<div class="info-box">';
            echo '📝 아직 저장된 활동 로그가 없습니다.<br>';
            echo '관리자가 로그인하거나 작업을 수행하면 자동으로 기록됩니다.';
            echo '</div>';
        }
    } else {
        echo '<div class="status status-error">테이블이 존재하지 않습니다!</div>';
    }

    echo '</div>';

    // ========================================
    // 4. 테이블 구조 확인
    // ========================================
    echo '<div class="section">';
    echo '<h2>4️⃣ 테이블 구조</h2>';

    // user_change_history 구조
    if ($db->selectOne(
        "SELECT TABLE_NAME FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = 'ai22' AND TABLE_NAME = 'user_change_history'"
    )) {
        echo '<h3>user_change_history</h3>';
        $columns = $db->select("DESCRIBE user_change_history");
        echo '<table>';
        echo '<thead><tr><th>필드</th><th>타입</th><th>Null</th><th>Key</th><th>Extra</th></tr></thead>';
        echo '<tbody>';
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
    }

    echo '</div>';

    // ========================================
    // 5. 액션 버튼
    // ========================================
    echo '<div class="section">';
    echo '<h2>5️⃣ 바로가기</h2>';
    echo '<a href="../admins/pages/change-history.php" class="test-button">📋 회원 변경 이력 보기</a>';
    echo '<a href="../admins/pages/activity-logs.php" class="test-button">📝 활동 로그 보기</a>';
    echo '<a href="../admins/index.php" class="test-button">🏠 Super Admin 대시보드</a>';
    echo '</div>';

} catch (Exception $e) {
    echo '<div class="section">';
    echo '<div class="status status-error">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}
?>

    </div>
</body>
</html>
