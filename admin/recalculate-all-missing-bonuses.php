<?php
/**
 * 누락된 엣지/매칭 보너스 일괄 재계산
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance();

echo "<html><head><meta charset='utf-8'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #0f172a; color: #e2e8f0; }
h2 { color: #60a5fa; }
.success { color: #10b981; }
.error { color: #ef4444; }
.info { background: #1e3a8a; padding: 10px; margin: 10px 0; border-left: 4px solid #3b82f6; }
.warning { background: #78350f; padding: 10px; margin: 10px 0; border-left: 4px solid #f59e0b; color: #fbbf24; }
.log { background: #1e293b; padding: 15px; margin: 10px 0; border: 1px solid #334155; max-height: 600px; overflow-y: auto; }
pre { margin: 5px 0; }
</style></head><body>";

echo "<h2>🔄 누락된 보너스 일괄 재계산</h2>";

// missing-bonuses.json 파일 확인
$jsonFile = __DIR__ . '/missing-bonuses.json';

if (!file_exists($jsonFile)) {
    echo "<div class='warning'>";
    echo "⚠️ missing-bonuses.json 파일을 찾을 수 없습니다.<br>";
    echo "먼저 <a href='check-all-avatars-bonus.php' style='color: #60a5fa;'>전수조사</a>를 실행해주세요.";
    echo "</div>";
    echo "</body></html>";
    exit;
}

$missingList = json_decode(file_get_contents($jsonFile), true);

if (empty($missingList)) {
    echo "<div class='info'>";
    echo "✅ 누락된 보너스가 없습니다!";
    echo "</div>";
    echo "</body></html>";
    exit;
}

echo "<div class='info'>";
echo "누락된 계정 수: <strong>" . count($missingList) . "</strong>개<br>";
echo "처리 시작...<br>";
echo "</div>";

echo "<div class='log'>";

$packageAmount = 100.00;
$bonusAmount = $packageAmount * 0.25; // $25
$cashAmount = $bonusAmount * 0.65; // $16.25
$avatarPointAmount = $bonusAmount * 0.35; // $8.75

$successCount = 0;
$errorCount = 0;
$edgeCreated = 0;
$matchingCreated = 0;

foreach ($missingList as $item) {
    try {
        $db->beginTransaction();

        $userId = $item['user_id'];
        $userIdNum = $item['id'];

        echo "<pre><strong>━━━ {$userId} ━━━</strong></pre>";

        // 사용자 정보 조회
        $user = $db->selectOne("SELECT id, user_id, sponsor_position FROM users WHERE id = ?", [$userIdNum]);
        if (!$user) {
            throw new Exception("사용자를 찾을 수 없습니다: {$userId}");
        }

        $direction = $user['sponsor_position'] == 1 ? 'left' : 'right';

        // 엣지 보너스 생성
        if ($item['missing_edge'] && $item['edge_receiver_id']) {
            echo "<pre>  엣지 보너스 생성: {$item['edge_receiver']} ← {$userId}</pre>";

            // 캐시 보너스
            $db->insert("
                INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, edge_position, description, created_at)
                VALUES (?, ?, 'edge', 'cash', ?, ?, ?, ?, NOW())
            ", [
                $item['edge_receiver_id'],
                $userIdNum,
                $cashAmount,
                $packageAmount,
                $direction,
                "바이너리 엣지 보너스 - 캐시 ({$direction}, 65%) [일괄재계산]"
            ]);

            // 아바타 포인트 보너스
            $db->insert("
                INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, edge_position, description, created_at)
                VALUES (?, ?, 'edge', 'avatar_point', ?, ?, ?, ?, NOW())
            ", [
                $item['edge_receiver_id'],
                $userIdNum,
                $avatarPointAmount,
                $packageAmount,
                $direction,
                "바이너리 엣지 보너스 - 아바타 포인트 ({$direction}, 35%) [일괄재계산]"
            ]);

            // 수령자 업데이트
            $receiver = $db->selectOne("SELECT id, is_avatar, parent_account_id FROM users WHERE id = ?", [$item['edge_receiver_id']]);

            if ($receiver['is_avatar'] && $receiver['parent_account_id']) {
                // 아바타: 캐시→오너, 포인트→본인
                $db->update("
                    UPDATE users SET
                        available_bonus = available_bonus + ?,
                        total_bonus = total_bonus + ?,
                        total_edge_bonus = total_edge_bonus + ?
                    WHERE id = ?
                ", [$cashAmount, $cashAmount, $bonusAmount, $receiver['parent_account_id']]);

                $db->update("
                    UPDATE users SET avatar_points = avatar_points + ? WHERE id = ?
                ", [$avatarPointAmount, $item['edge_receiver_id']]);
            } else {
                // 일반 회원
                $db->update("
                    UPDATE users SET
                        available_bonus = available_bonus + ?,
                        total_bonus = total_bonus + ?,
                        avatar_points = avatar_points + ?,
                        total_edge_bonus = total_edge_bonus + ?
                    WHERE id = ?
                ", [$cashAmount, $cashAmount + $avatarPointAmount, $avatarPointAmount, $bonusAmount, $item['edge_receiver_id']]);
            }

            echo "<pre>  ✅ 엣지 \$25.00 지급 완료</pre>";
            $edgeCreated++;
        }

        // 매칭 보너스 생성
        if ($item['missing_matching'] && $item['matching_receiver_id']) {
            echo "<pre>  매칭 보너스 생성: {$item['matching_receiver']} ← {$userId}</pre>";

            // 캐시 보너스
            $db->insert("
                INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
                VALUES (?, ?, 'matching', 'cash', ?, ?, ?, NOW())
            ", [
                $item['matching_receiver_id'],
                $userIdNum,
                $cashAmount,
                $packageAmount,
                "추천매칭 보너스 - 캐시 (65%) [일괄재계산]"
            ]);

            // 아바타 포인트 보너스
            $db->insert("
                INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
                VALUES (?, ?, 'matching', 'avatar_point', ?, ?, ?, NOW())
            ", [
                $item['matching_receiver_id'],
                $userIdNum,
                $avatarPointAmount,
                $packageAmount,
                "추천매칭 보너스 - 아바타 포인트 (35%) [일괄재계산]"
            ]);

            // 수령자 업데이트
            $matchingRcv = $db->selectOne("SELECT id, is_avatar, parent_account_id FROM users WHERE id = ?", [$item['matching_receiver_id']]);

            if ($matchingRcv['is_avatar'] && $matchingRcv['parent_account_id']) {
                // 아바타
                $db->update("
                    UPDATE users SET
                        available_bonus = available_bonus + ?,
                        total_bonus = total_bonus + ?,
                        total_matching_bonus = total_matching_bonus + ?
                    WHERE id = ?
                ", [$cashAmount, $cashAmount, $bonusAmount, $matchingRcv['parent_account_id']]);

                $db->update("
                    UPDATE users SET avatar_points = avatar_points + ? WHERE id = ?
                ", [$avatarPointAmount, $item['matching_receiver_id']]);
            } else {
                // 일반 회원
                $db->update("
                    UPDATE users SET
                        available_bonus = available_bonus + ?,
                        total_bonus = total_bonus + ?,
                        avatar_points = avatar_points + ?,
                        total_matching_bonus = total_matching_bonus + ?
                    WHERE id = ?
                ", [$cashAmount, $cashAmount + $avatarPointAmount, $avatarPointAmount, $bonusAmount, $item['matching_receiver_id']]);
            }

            echo "<pre>  ✅ 매칭 \$25.00 지급 완료</pre>";
            $matchingCreated++;
        }

        $db->commit();
        echo "<pre class='success'>✅ {$userId} 처리 완료</pre>";
        $successCount++;

    } catch (Exception $e) {
        $db->rollback();
        echo "<pre class='error'>❌ {$userId} 오류: " . $e->getMessage() . "</pre>";
        $errorCount++;
    }
}

echo "</div>";

echo "<div class='info'>";
echo "<h3>📊 처리 결과</h3>";
echo "총 처리: <strong>" . count($missingList) . "</strong>개<br>";
echo "성공: <strong class='success'>{$successCount}</strong>개<br>";
echo "실패: <strong class='error'>{$errorCount}</strong>개<br>";
echo "엣지 보너스 생성: <strong class='success'>{$edgeCreated}</strong>건<br>";
echo "매칭 보너스 생성: <strong class='success'>{$matchingCreated}</strong>건<br>";
echo "총 지급액: <strong class='success'>\$" . number_format(($edgeCreated + $matchingCreated) * 25, 2) . "</strong><br>";
echo "</div>";

if ($successCount > 0) {
    echo "<div class='info'>";
    echo "✅ 재계산 완료!<br>";
    echo "<a href='check-all-avatars-bonus.php' style='color: #60a5fa;'>← 전수조사 다시 실행하여 확인</a>";
    echo "</div>";
}

echo "</body></html>";
