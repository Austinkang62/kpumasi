<?php
/**
 * 모든 아바타 계정의 엣지/매칭 보너스 누락 확인
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance();

echo "<html><head><meta charset='utf-8'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #0f172a; color: #e2e8f0; }
h2 { color: #60a5fa; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; background: #1e293b; }
th, td { border: 1px solid #334155; padding: 8px; text-align: left; }
th { background: #334155; color: #f1f5f9; font-weight: bold; }
.ok { color: #10b981; }
.missing { color: #ef4444; font-weight: bold; }
.warning { color: #f59e0b; }
.info { background: #1e3a8a; padding: 10px; margin: 10px 0; border-left: 4px solid #3b82f6; }
</style></head><body>";

echo "<h2>🔍 아바타 계정 엣지/매칭 보너스 전수조사</h2>";

// 모든 아바타 조회
$avatars = $db->select("
    SELECT id, user_id, sponsor_id, sponsor_position, referral_id, created_at
    FROM users
    WHERE is_avatar = 1
    ORDER BY created_at ASC
");

echo "<div class='info'>총 아바타 계정: <strong>" . count($avatars) . "</strong>개</div>";

$missingEdgeCount = 0;
$missingMatchingCount = 0;
$missingList = [];

echo "<table>";
echo "<tr>
    <th>No</th>
    <th>아바타 ID</th>
    <th>후원인</th>
    <th>위치</th>
    <th>추천</th>
    <th>엣지</th>
    <th>매칭</th>
    <th>롤업</th>
    <th>엣지 조건</th>
    <th>엣지 수령자</th>
    <th>매칭 수령자</th>
    <th>상태</th>
</tr>";

foreach ($avatars as $idx => $avatar) {
    $no = $idx + 1;

    // 발생한 보너스 조회
    $bonuses = $db->select("
        SELECT bonus_type, SUM(amount) as total
        FROM bonuses
        WHERE from_user_id = ?
        GROUP BY bonus_type
    ", [$avatar['id']]);

    $bonusMap = [
        'referral' => 0,
        'edge' => 0,
        'matching' => 0,
        'rollup' => 0
    ];

    foreach ($bonuses as $b) {
        $bonusMap[$b['bonus_type']] = floatval($b['total']);
    }

    // 엣지 조건 확인
    $edgeCondition = '없음';
    $edgeReceiver = null;
    $matchingReceiver = null;
    $shouldHaveEdge = false;
    $shouldHaveMatching = false;

    if ($avatar['sponsor_id'] && $avatar['sponsor_position']) {
        // 엣지 조건 체크
        $currentUserCode = $avatar['sponsor_id'];
        $lastPosition = intval($avatar['sponsor_position']);
        $level = 1;

        while ($currentUserCode && $level <= 20) {
            $currentNode = $db->selectOne("
                SELECT id, user_id, sponsor_id, sponsor_position
                FROM users
                WHERE user_id = ?
            ", [$currentUserCode]);

            if (!$currentNode || !$currentNode['sponsor_position'] || !$currentNode['sponsor_id']) {
                break;
            }

            $currentPosition = intval($currentNode['sponsor_position']);

            // 방향 전환 확인
            if ($currentPosition !== $lastPosition) {
                $shouldHaveEdge = true;
                $edgeCondition = "레벨{$level} 전환";

                // 엣지 수령자
                $edgeReceiver = $db->selectOne("
                    SELECT id, user_id
                    FROM users
                    WHERE user_id = ?
                ", [$currentNode['sponsor_id']]);

                // 매칭 수령자 (엣지 수령자의 추천인)
                if ($edgeReceiver) {
                    $edgeUserInfo = $db->selectOne("
                        SELECT referral_id FROM users WHERE id = ?
                    ", [$edgeReceiver['id']]);

                    if ($edgeUserInfo && $edgeUserInfo['referral_id']) {
                        $shouldHaveMatching = true;
                        $matchingReceiver = $db->selectOne("
                            SELECT id, user_id FROM users WHERE id = ?
                        ", [$edgeUserInfo['referral_id']]);
                    }
                }

                break;
            }

            $currentUserCode = $currentNode['sponsor_id'];
            $lastPosition = $currentPosition;
            $level++;
        }
    }

    // 상태 판단
    $status = '<span class="ok">✅ 정상</span>';
    $isMissing = false;

    if ($shouldHaveEdge && $bonusMap['edge'] == 0) {
        $status = '<span class="missing">❌ 엣지 누락</span>';
        $missingEdgeCount++;
        $isMissing = true;
    }

    if ($shouldHaveMatching && $bonusMap['matching'] == 0) {
        if ($isMissing) {
            $status = '<span class="missing">❌ 엣지+매칭 누락</span>';
        } else {
            $status = '<span class="missing">❌ 매칭 누락</span>';
        }
        $missingMatchingCount++;
        $isMissing = true;
    }

    if ($isMissing) {
        $missingList[] = [
            'user_id' => $avatar['user_id'],
            'id' => $avatar['id'],
            'edge_receiver' => $edgeReceiver ? $edgeReceiver['user_id'] : null,
            'edge_receiver_id' => $edgeReceiver ? $edgeReceiver['id'] : null,
            'matching_receiver' => $matchingReceiver ? $matchingReceiver['user_id'] : null,
            'matching_receiver_id' => $matchingReceiver ? $matchingReceiver['id'] : null,
            'missing_edge' => $shouldHaveEdge && $bonusMap['edge'] == 0,
            'missing_matching' => $shouldHaveMatching && $bonusMap['matching'] == 0
        ];
    }

    $posText = $avatar['sponsor_position'] == 1 ? '좌(1)' : ($avatar['sponsor_position'] == 2 ? '우(2)' : '-');

    echo "<tr>";
    echo "<td>{$no}</td>";
    echo "<td><strong>{$avatar['user_id']}</strong></td>";
    echo "<td>{$avatar['sponsor_id']}</td>";
    echo "<td>{$posText}</td>";
    echo "<td>\$" . number_format($bonusMap['referral'], 2) . "</td>";
    echo "<td" . ($shouldHaveEdge && $bonusMap['edge'] == 0 ? " class='missing'" : "") . ">\$" . number_format($bonusMap['edge'], 2) . "</td>";
    echo "<td" . ($shouldHaveMatching && $bonusMap['matching'] == 0 ? " class='missing'" : "") . ">\$" . number_format($bonusMap['matching'], 2) . "</td>";
    echo "<td>\$" . number_format($bonusMap['rollup'], 2) . "</td>";
    echo "<td>{$edgeCondition}</td>";
    echo "<td>" . ($edgeReceiver ? $edgeReceiver['user_id'] : '-') . "</td>";
    echo "<td>" . ($matchingReceiver ? $matchingReceiver['user_id'] : '-') . "</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<div class='info'>";
echo "<h3>📊 요약</h3>";
echo "총 아바타: <strong>" . count($avatars) . "</strong>개<br>";
echo "엣지 누락: <strong class='missing'>{$missingEdgeCount}</strong>개<br>";
echo "매칭 누락: <strong class='missing'>{$missingMatchingCount}</strong>개<br>";
echo "누락 계정: <strong class='missing'>" . count($missingList) . "</strong>개<br>";
echo "</div>";

// 누락 목록 저장 (재계산 스크립트용)
if (!empty($missingList)) {
    file_put_contents(
        __DIR__ . '/missing-bonuses.json',
        json_encode($missingList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    echo "<div class='info'>";
    echo "<h3>💾 누락 목록 저장됨</h3>";
    echo "파일: <code>admin/missing-bonuses.json</code><br>";
    echo "다음 단계: <a href='recalculate-all-missing-bonuses.php' style='color: #60a5fa;'>누락된 보너스 일괄 재계산 실행 →</a>";
    echo "</div>";
}

echo "</body></html>";
