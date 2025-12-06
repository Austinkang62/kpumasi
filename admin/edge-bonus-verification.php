<?php
/**
 * 엣지 보너스 발생 검증표
 * 현재 발생된 매출에 대한 엣지 보너스 검증
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

// 세션 시작
session_start();

// 관리자 인증 확인 (주석 처리하여 누구나 볼 수 있도록)
// if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
//     die('Unauthorized');
// }
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>엣지 보너스 발생 검증표</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 20px;
        }
        .container {
            max-width: 1800px;
            margin: 0 auto;
        }
        h1 {
            color: #f8fafc;
            margin-bottom: 30px;
            font-size: 2em;
        }
        h2 {
            color: #60a5fa;
            margin: 30px 0 15px;
            font-size: 1.5em;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #1e293b;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        th {
            background: #0f172a;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.9em;
            text-transform: uppercase;
            border-bottom: 2px solid #3b82f6;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        tr:hover td {
            background: rgba(59, 130, 246, 0.05);
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .badge-warning { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .badge-info { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .highlight {
            background: rgba(251, 191, 36, 0.1);
            border-left: 3px solid #fbbf24;
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border-left: 3px solid #ef4444;
        }
        .success {
            background: rgba(16, 185, 129, 0.1);
            border-left: 3px solid #10b981;
        }
        .section {
            background: #1e293b;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .warning-box {
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        code {
            background: rgba(15, 23, 42, 0.5);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #60a5fa;
        }
        .tree-view {
            font-family: 'Courier New', monospace;
            background: #0f172a;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            white-space: pre;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 엣지 보너스 발생 검증표</h1>

        <div class="info-box">
            <strong>📌 엣지 보너스 발생 조건:</strong><br>
            • Binary tree에서 상위로 올라가면서 처음 방향이 바뀐 지점의 부모에게 25% 지급<br>
            • organization 테이블의 position 필드 사용 (left/right)<br>
            • position이 바뀌는 순간 = 엣지(꺾임) 발생
        </div>

        <?php
        // 1. 현재 매출 데이터 조회
        echo "<h2>📊 1. 현재 발생된 매출 데이터</h2>";

        $sales = $db->select("
            SELECT s.id, s.user_id, u.user_id as user_code, s.amount, s.created_at, s.status
            FROM sales s
            JOIN users u ON s.user_id = u.id
            WHERE s.deleted_at IS NULL
            ORDER BY s.created_at DESC
            LIMIT 50
        ");

        if (empty($sales)) {
            echo "<div class='warning-box'>⚠️ 발생된 매출이 없습니다.</div>";
        } else {
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>Sale ID</th><th>User ID (internal)</th><th>User Code</th><th>Amount</th><th>Status</th><th>Created At</th>";
            echo "</tr></thead><tbody>";

            foreach ($sales as $sale) {
                echo "<tr>";
                echo "<td>{$sale['id']}</td>";
                echo "<td>{$sale['user_id']}</td>";
                echo "<td><strong>{$sale['user_code']}</strong></td>";
                echo "<td>\${$sale['amount']}</td>";
                echo "<td><span class='badge badge-success'>{$sale['status']}</span></td>";
                echo "<td>{$sale['created_at']}</td>";
                echo "</tr>";
            }

            echo "</tbody></table>";
        }

        // 2. 발행된 엣지 보너스 조회
        echo "<h2>💰 2. 발행된 엣지 보너스</h2>";

        $edgeBonuses = $db->select("
            SELECT
                b.id,
                b.user_id,
                u.user_id as receiver_code,
                b.from_user_id,
                fu.user_id as from_user_code,
                b.amount,
                b.package_amount,
                b.level,
                b.status,
                b.created_at
            FROM bonuses b
            JOIN users u ON b.user_id = u.id
            LEFT JOIN users fu ON b.from_user_id = fu.id
            WHERE b.bonus_type = 'edge'
            ORDER BY b.created_at DESC
            LIMIT 50
        ");

        if (empty($edgeBonuses)) {
            echo "<div class='warning-box'>⚠️ <strong>발행된 엣지 보너스가 없습니다!</strong><br>";
            echo "이것이 문제의 원인일 수 있습니다.</div>";
        } else {
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>Bonus ID</th><th>수령자 (Receiver)</th><th>발생원 (From)</th><th>금액</th><th>패키지</th><th>레벨</th><th>상태</th><th>생성일</th>";
            echo "</tr></thead><tbody>";

            foreach ($edgeBonuses as $bonus) {
                echo "<tr>";
                echo "<td>{$bonus['id']}</td>";
                echo "<td><strong>{$bonus['receiver_code']}</strong> (ID: {$bonus['user_id']})</td>";
                echo "<td><strong>{$bonus['from_user_code']}</strong> (ID: {$bonus['from_user_id']})</td>";
                echo "<td>\${$bonus['amount']}</td>";
                echo "<td>\${$bonus['package_amount']}</td>";
                echo "<td>{$bonus['level']}</td>";
                echo "<td><span class='badge badge-success'>{$bonus['status']}</span></td>";
                echo "<td>{$bonus['created_at']}</td>";
                echo "</tr>";
            }

            echo "</tbody></table>";
        }

        // 3. 발행된 매칭 보너스 조회
        echo "<h2>🎯 3. 발행된 매칭 보너스</h2>";

        $matchingBonuses = $db->select("
            SELECT
                b.id,
                b.user_id,
                u.user_id as receiver_code,
                b.from_user_id,
                fu.user_id as from_user_code,
                b.amount,
                b.package_amount,
                b.status,
                b.created_at
            FROM bonuses b
            JOIN users u ON b.user_id = u.id
            LEFT JOIN users fu ON b.from_user_id = fu.id
            WHERE b.bonus_type = 'matching'
            ORDER BY b.created_at DESC
            LIMIT 50
        ");

        if (empty($matchingBonuses)) {
            echo "<div class='warning-box'>⚠️ <strong>발행된 매칭 보너스가 없습니다!</strong><br>";
            echo "이것이 문제의 원인일 수 있습니다. 매칭 보너스는 엣지 보너스 수령자의 추천인에게 지급됩니다.</div>";
        } else {
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>Bonus ID</th><th>수령자 (Receiver)</th><th>발생원 (From)</th><th>금액</th><th>패키지</th><th>상태</th><th>생성일</th>";
            echo "</tr></thead><tbody>";

            foreach ($matchingBonuses as $bonus) {
                echo "<tr>";
                echo "<td>{$bonus['id']}</td>";
                echo "<td><strong>{$bonus['receiver_code']}</strong> (ID: {$bonus['user_id']})</td>";
                echo "<td><strong>{$bonus['from_user_code']}</strong> (ID: {$bonus['from_user_id']})</td>";
                echo "<td>\${$bonus['amount']}</td>";
                echo "<td>\${$bonus['package_amount']}</td>";
                echo "<td><span class='badge badge-success'>{$bonus['status']}</span></td>";
                echo "<td>{$bonus['created_at']}</td>";
                echo "</tr>";
            }

            echo "</tbody></table>";
        }

        // 4. Organization 테이블 구조 확인
        echo "<h2>🌳 4. Organization 테이블 데이터</h2>";

        $orgs = $db->select("
            SELECT
                o.id,
                o.user_id,
                u.user_id as user_code,
                o.parent_id,
                pu.user_id as parent_code,
                o.position,
                o.level,
                o.left_child,
                o.right_child
            FROM organization o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN users pu ON o.parent_id = pu.id
            ORDER BY o.id
            LIMIT 50
        ");

        if (empty($orgs)) {
            echo "<div class='error'>❌ <strong>Organization 테이블이 비어있습니다!</strong><br>";
            echo "이것이 엣지 보너스가 발생하지 않는 주요 원인입니다.<br>";
            echo "Organization 테이블에 회원 데이터가 없으면 엣지 보너스 계산이 불가능합니다.</div>";
        } else {
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>Org ID</th><th>User ID</th><th>User Code</th><th>Parent ID</th><th>Parent Code</th>";
            echo "<th>Position</th><th>Level</th><th>Left Child</th><th>Right Child</th>";
            echo "</tr></thead><tbody>";

            foreach ($orgs as $org) {
                $class = '';
                if (empty($org['position'])) {
                    $class = 'highlight';
                }

                echo "<tr class='$class'>";
                echo "<td>{$org['id']}</td>";
                echo "<td>{$org['user_id']}</td>";
                echo "<td><strong>{$org['user_code']}</strong></td>";
                echo "<td>" . ($org['parent_id'] ?: '-') . "</td>";
                echo "<td>" . ($org['parent_code'] ?: '-') . "</td>";
                echo "<td>";
                if (empty($org['position'])) {
                    echo "<span class='badge badge-warning'>NULL</span>";
                } else {
                    echo "<span class='badge badge-info'>{$org['position']}</span>";
                }
                echo "</td>";
                echo "<td>{$org['level']}</td>";
                echo "<td>" . ($org['left_child'] ?: '-') . "</td>";
                echo "<td>" . ($org['right_child'] ?: '-') . "</td>";
                echo "</tr>";
            }

            echo "</tbody></table>";
        }

        // 5. Users 테이블의 sponsor_id, sponsor_position 확인
        echo "<h2>👥 5. Users 테이블 - Sponsor 정보</h2>";

        $users = $db->select("
            SELECT
                id,
                user_id,
                referral_id,
                r.user_id as referral_code,
                sponsor_id,
                s.user_id as sponsor_code,
                sponsor_position,
                is_avatar,
                package_id,
                total_sales
            FROM users u
            LEFT JOIN users r ON u.referral_id = r.id
            LEFT JOIN users s ON u.sponsor_id = s.id
            WHERE u.deleted_at IS NULL
            ORDER BY u.id
            LIMIT 50
        ");

        echo "<table>";
        echo "<thead><tr>";
        echo "<th>ID</th><th>User Code</th><th>Referral ID</th><th>Referral Code</th>";
        echo "<th>Sponsor ID</th><th>Sponsor Code</th><th>Sponsor Pos</th><th>Avatar</th><th>Package</th><th>Sales</th>";
        echo "</tr></thead><tbody>";

        foreach ($users as $user) {
            $class = '';
            if ($user['sponsor_position'] === null) {
                $class = 'highlight';
            }

            echo "<tr class='$class'>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['user_id']}</strong></td>";
            echo "<td>" . ($user['referral_id'] ?: '-') . "</td>";
            echo "<td>" . ($user['referral_code'] ?: '-') . "</td>";
            echo "<td>" . ($user['sponsor_id'] ?: '-') . "</td>";
            echo "<td>" . ($user['sponsor_code'] ?: '-') . "</td>";
            echo "<td>";
            if ($user['sponsor_position'] === null) {
                echo "<span class='badge badge-warning'>NULL</span>";
            } else {
                $pos = $user['sponsor_position'] == 1 ? 'Left (1)' : 'Right (2)';
                echo "<span class='badge badge-info'>$pos</span>";
            }
            echo "</td>";
            echo "<td>" . ($user['is_avatar'] ? '✓' : '-') . "</td>";
            echo "<td>" . ($user['package_id'] ?: '-') . "</td>";
            echo "<td>\$" . ($user['total_sales'] ?: '0.00') . "</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";

        // 6. 엣지 보너스 시뮬레이션 (최근 매출 10건)
        echo "<h2>🧪 6. 엣지 보너스 발생 시뮬레이션</h2>";

        echo "<div class='section'>";
        echo "<p><strong>최근 매출 10건에 대해 엣지 보너스가 발생했어야 하는지 검증합니다.</strong></p>";

        $recentSales = $db->select("
            SELECT s.id, s.user_id, u.user_id as user_code, s.amount
            FROM sales s
            JOIN users u ON s.user_id = u.id
            WHERE s.deleted_at IS NULL
            ORDER BY s.created_at DESC
            LIMIT 10
        ");

        foreach ($recentSales as $sale) {
            echo "<div style='margin: 20px 0; padding: 15px; background: #0f172a; border-radius: 8px;'>";
            echo "<h3 style='color: #60a5fa; margin-bottom: 10px;'>매출 #{$sale['id']} - {$sale['user_code']} (\${$sale['amount']})</h3>";

            // Organization에서 이 회원 찾기
            $userOrg = $db->selectOne("
                SELECT user_id, parent_id, position
                FROM organization
                WHERE user_id = ?
            ", [$sale['user_id']]);

            if (!$userOrg) {
                echo "<div class='error' style='padding: 10px; margin: 10px 0;'>";
                echo "❌ <strong>문제 발견:</strong> organization 테이블에 회원 데이터 없음!<br>";
                echo "→ 이 회원은 엣지 보너스 계산이 불가능합니다.";
                echo "</div>";
                continue;
            }

            if (!$userOrg['parent_id']) {
                echo "<div class='warning-box' style='padding: 10px; margin: 10px 0;'>";
                echo "⚠️ 이 회원은 최상위 노드(parent_id 없음)이므로 엣지 보너스가 발생하지 않습니다.";
                echo "</div>";
                continue;
            }

            echo "<div style='margin: 10px 0;'>";
            echo "<strong>Organization 정보:</strong><br>";
            echo "- Parent ID: {$userOrg['parent_id']}<br>";
            echo "- Position: " . ($userOrg['position'] ?: '<span class="badge badge-warning">NULL</span>') . "<br>";
            echo "</div>";

            // 상위로 올라가면서 엣지 찾기
            $currentUserId = $sale['user_id'];
            $previousPosition = $userOrg['position'];
            $edgeFound = false;
            $path = [];

            for ($level = 1; $level <= 15; $level++) {
                $parentOrg = $db->selectOne("
                    SELECT o.user_id, o.parent_id, o.position, u.user_id as user_code
                    FROM organization o
                    LEFT JOIN users u ON o.user_id = u.id
                    WHERE o.user_id = (
                        SELECT parent_id FROM organization WHERE user_id = ?
                    )
                ", [$currentUserId]);

                if (!$parentOrg) {
                    break;
                }

                $path[] = [
                    'level' => $level,
                    'user_id' => $parentOrg['user_id'],
                    'user_code' => $parentOrg['user_code'],
                    'position' => $parentOrg['position'],
                    'edge' => false
                ];

                // 방향이 바뀌었는지 확인
                if ($parentOrg['position'] && $parentOrg['position'] !== $previousPosition) {
                    $edgeFound = true;
                    $path[count($path) - 1]['edge'] = true;

                    // 엣지 보너스가 실제로 지급되었는지 확인
                    $actualBonus = $db->selectOne("
                        SELECT id, amount
                        FROM bonuses
                        WHERE user_id = ? AND from_user_id = ? AND bonus_type = 'edge'
                    ", [$parentOrg['user_id'], $sale['user_id']]);

                    echo "<div class='success' style='padding: 10px; margin: 10px 0;'>";
                    echo "✅ <strong>엣지 발견!</strong><br>";
                    echo "- 레벨: {$level}<br>";
                    echo "- 수령자: {$parentOrg['user_code']} (ID: {$parentOrg['user_id']})<br>";
                    echo "- 이전 방향: {$previousPosition}<br>";
                    echo "- 현재 방향: {$parentOrg['position']}<br>";

                    if ($actualBonus) {
                        echo "<br>💰 <strong>실제 지급됨:</strong> \${$actualBonus['amount']} (Bonus ID: {$actualBonus['id']})";
                    } else {
                        echo "<br>❌ <strong>실제 지급 안 됨!</strong> (bonuses 테이블에 기록 없음)";
                    }
                    echo "</div>";

                    // 매칭 보너스 확인
                    $edgeReceiverReferral = $db->selectOne("
                        SELECT referral_id, u2.user_id as referral_code
                        FROM users u
                        LEFT JOIN users u2 ON u.referral_id = u2.id
                        WHERE u.id = ?
                    ", [$parentOrg['user_id']]);

                    if ($edgeReceiverReferral && $edgeReceiverReferral['referral_id']) {
                        $actualMatchingBonus = $db->selectOne("
                            SELECT id, amount
                            FROM bonuses
                            WHERE user_id = ? AND from_user_id = ? AND bonus_type = 'matching'
                        ", [$edgeReceiverReferral['referral_id'], $sale['user_id']]);

                        echo "<div class='info-box' style='padding: 10px; margin: 10px 0;'>";
                        echo "🎯 <strong>매칭 보너스:</strong><br>";
                        echo "- 수령자: {$edgeReceiverReferral['referral_code']} (엣지 수령자의 추천인)<br>";

                        if ($actualMatchingBonus) {
                            echo "💰 <strong>실제 지급됨:</strong> \${$actualMatchingBonus['amount']} (Bonus ID: {$actualMatchingBonus['id']})";
                        } else {
                            echo "❌ <strong>실제 지급 안 됨!</strong> (bonuses 테이블에 기록 없음)";
                        }
                        echo "</div>";
                    }

                    break;
                }

                $currentUserId = $parentOrg['user_id'];
                $previousPosition = $parentOrg['position'];
            }

            if (!$edgeFound) {
                echo "<div class='warning-box' style='padding: 10px; margin: 10px 0;'>";
                echo "⚠️ <strong>엣지가 발견되지 않았습니다.</strong><br>";
                echo "상위 15레벨까지 올라갔지만 방향이 바뀌는 지점을 찾지 못했습니다.";
                echo "</div>";
            }

            if (!empty($path)) {
                echo "<div class='tree-view'>";
                echo "<strong>상위 트리 경로:</strong>\n";
                foreach ($path as $p) {
                    $edge = $p['edge'] ? ' ← 🔥 엣지 발생!' : '';
                    $pos = $p['position'] ?: 'NULL';
                    echo "Level {$p['level']}: {$p['user_code']} (ID: {$p['user_id']}, Position: {$pos}){$edge}\n";
                }
                echo "</div>";
            }

            echo "</div>";
        }

        echo "</div>";

        // 7. 문제 진단 및 해결 방안
        echo "<h2>🔧 7. 문제 진단 및 해결 방안</h2>";

        echo "<div class='section'>";

        $issues = [];
        $orgCount = count($orgs);
        $edgeBonusCount = count($edgeBonuses);
        $matchingBonusCount = count($matchingBonuses);
        $salesCount = count($sales);

        if ($orgCount == 0) {
            $issues[] = [
                'severity' => 'critical',
                'title' => 'Organization 테이블이 비어있음',
                'description' => 'organization 테이블에 데이터가 없으면 엣지 보너스 계산이 불가능합니다.',
                'solution' => '회원 가입 시 organization 테이블에도 데이터를 삽입하도록 수정해야 합니다.'
            ];
        } else {
            // position NULL 체크
            $nullPositions = array_filter($orgs, fn($o) => empty($o['position']));
            if (count($nullPositions) > 0) {
                $issues[] = [
                    'severity' => 'warning',
                    'title' => 'Organization position이 NULL인 항목 존재',
                    'description' => count($nullPositions) . '개의 organization 레코드에서 position이 NULL입니다.',
                    'solution' => 'position 필드를 left 또는 right로 설정해야 합니다.'
                ];
            }
        }

        if ($salesCount > 0 && $edgeBonusCount == 0) {
            $issues[] = [
                'severity' => 'critical',
                'title' => '매출은 있지만 엣지 보너스가 발행되지 않음',
                'description' => "{$salesCount}건의 매출이 있지만 엣지 보너스가 하나도 발행되지 않았습니다.",
                'solution' => 'calculateEdgeBonus() 함수가 제대로 실행되지 않고 있을 가능성이 높습니다.'
            ];
        }

        if ($edgeBonusCount > 0 && $matchingBonusCount == 0) {
            $issues[] = [
                'severity' => 'warning',
                'title' => '엣지 보너스는 있지만 매칭 보너스가 없음',
                'description' => "{$edgeBonusCount}건의 엣지 보너스가 있지만 매칭 보너스가 없습니다.",
                'solution' => '엣지 보너스 수령자의 추천인이 없거나, calculateMatchingBonus() 함수에 문제가 있을 수 있습니다.'
            ];
        }

        if (empty($issues)) {
            echo "<div class='success' style='padding: 15px;'>";
            echo "✅ <strong>문제가 발견되지 않았습니다.</strong><br>";
            echo "시스템이 정상적으로 작동하고 있는 것으로 보입니다.";
            echo "</div>";
        } else {
            foreach ($issues as $issue) {
                $badgeClass = $issue['severity'] === 'critical' ? 'badge-danger' : 'badge-warning';
                $boxClass = $issue['severity'] === 'critical' ? 'error' : 'warning-box';

                echo "<div class='$boxClass' style='padding: 15px; margin: 15px 0;'>";
                echo "<span class='badge $badgeClass'>" . strtoupper($issue['severity']) . "</span>";
                echo " <strong>{$issue['title']}</strong><br><br>";
                echo "<strong>설명:</strong> {$issue['description']}<br>";
                echo "<strong>해결방안:</strong> {$issue['solution']}";
                echo "</div>";
            }
        }

        echo "</div>";

        ?>

        <div style="margin-top: 40px; padding: 20px; background: #1e293b; border-radius: 8px; text-align: center;">
            <p style="color: #94a3b8;">생성일시: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
