<?php
/**
 * AVA00054의 엣지/매칭 보너스 재계산 및 지급
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/BonusDistributor.php';

header('Content-Type: text/plain; charset=utf-8');

$db = Database::getInstance();

echo "=== AVA00054 보너스 재계산 ===\n\n";

// AVA00054 정보 조회
$user = $db->selectOne("
    SELECT id, user_id, referral_id, sponsor_id, sponsor_position, created_at
    FROM users
    WHERE user_id = 'AVA00054'
");

if (!$user) {
    echo "❌ AVA00054를 찾을 수 없습니다.\n";
    exit;
}

echo "회원 정보:\n";
echo "  ID: {$user['id']}\n";
echo "  user_id: {$user['user_id']}\n";
echo "  referral_id: {$user['referral_id']}\n";
echo "  sponsor_id: {$user['sponsor_id']}\n";
echo "  sponsor_position: {$user['sponsor_position']}\n";
echo "  등록일: {$user['created_at']}\n\n";

// 기존 보너스 확인
echo "기존 발생 보너스:\n";
$existingBonuses = $db->select("
    SELECT bonus_type, payment_type, SUM(amount) as total
    FROM bonuses
    WHERE from_user_id = ?
    GROUP BY bonus_type, payment_type
", [$user['id']]);

foreach ($existingBonuses as $b) {
    echo "  {$b['bonus_type']} ({$b['payment_type']}): \${$b['total']}\n";
}
echo "\n";

// 엣지 보너스 수동 계산
echo "엣지 보너스 수동 계산:\n";

$packageAmount = 100.00;
$bonusDistributor = new BonusDistributor();

try {
    $db->beginTransaction();

    // 엣지 보너스 재계산 (private 메서드이므로 직접 로직 구현)
    $newUserId = $user['id'];
    $sponsorCode = $user['sponsor_id'];

    $bonusAmount = $packageAmount * 0.25; // $25
    $cashAmount = $bonusAmount * 0.65; // $16.25
    $avatarPointAmount = $bonusAmount * 0.35; // $8.75

    // 방향 전환 지점 찾기
    $currentUserCode = $user['sponsor_id']; // AVA00039
    $lastPosition = intval($user['sponsor_position']); // 1
    $edgeReceiver = null;

    echo "  시작: AVA00054 위치={$lastPosition}\n";

    $level = 1;
    while ($currentUserCode && $level <= 20) {
        $currentNode = $db->selectOne("
            SELECT id, user_id, sponsor_id, sponsor_position
            FROM users
            WHERE user_id = ?
        ", [$currentUserCode]);

        if (!$currentNode || !$currentNode['sponsor_position'] || !$currentNode['sponsor_id']) {
            echo "  레벨 {$level}: {$currentUserCode} - 중단\n";
            break;
        }

        $currentPosition = intval($currentNode['sponsor_position']);
        echo "  레벨 {$level}: {$currentNode['user_id']} 위치={$currentPosition}";

        if ($currentPosition !== $lastPosition) {
            echo " ⭐ 방향 전환!\n";
            $edgeReceiver = $db->selectOne("
                SELECT id, user_id
                FROM users
                WHERE user_id = ?
            ", [$currentNode['sponsor_id']]);
            break;
        }

        echo "\n";
        $currentUserCode = $currentNode['sponsor_id'];
        $lastPosition = $currentPosition;
        $level++;
    }

    if (!$edgeReceiver) {
        echo "\n❌ 엣지 수령자를 찾을 수 없습니다.\n";
        $db->rollback();
        exit;
    }

    echo "\n✅ 엣지 수령자: {$edgeReceiver['user_id']} (ID: {$edgeReceiver['id']})\n\n";

    // 기존 엣지 보너스 확인
    $existingEdge = $db->selectOne("
        SELECT COUNT(*) as cnt
        FROM bonuses
        WHERE from_user_id = ? AND bonus_type = 'edge'
    ", [$user['id']]);

    if ($existingEdge['cnt'] > 0) {
        echo "⚠️  이미 엣지 보너스가 존재합니다. 삭제 후 재생성하시겠습니까?\n";
        echo "   (이 스크립트는 자동으로 진행하지 않습니다. 수동으로 삭제 후 다시 실행하세요.)\n";
        $db->rollback();
        exit;
    }

    // 엣지 보너스 생성
    $direction = $user['sponsor_position'] == 1 ? 'left' : 'right';

    // 캐시 보너스
    $cashBonusId = $db->insert("
        INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, edge_position, description, created_at)
        VALUES (?, ?, 'edge', 'cash', ?, ?, ?, ?, NOW())
    ", [
        $edgeReceiver['id'],
        $user['id'],
        $cashAmount,
        $packageAmount,
        $direction,
        "바이너리 엣지 보너스 - 캐시 ({$direction}, 65%) [재계산]"
    ]);

    // 아바타 포인트 보너스
    $avatarBonusId = $db->insert("
        INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, edge_position, description, created_at)
        VALUES (?, ?, 'edge', 'avatar_point', ?, ?, ?, ?, NOW())
    ", [
        $edgeReceiver['id'],
        $user['id'],
        $avatarPointAmount,
        $packageAmount,
        $direction,
        "바이너리 엣지 보너스 - 아바타 포인트 ({$direction}, 35%) [재계산]"
    ]);

    // 수령자 보너스 업데이트
    $receiver = $db->selectOne("SELECT id, is_avatar, parent_account_id FROM users WHERE id = ?", [$edgeReceiver['id']]);

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
        ", [$avatarPointAmount, $edgeReceiver['id']]);

        echo "✅ 엣지 보너스 지급 완료 (아바타):\n";
        echo "   캐시 \${$cashAmount} → 오너 (ID: {$receiver['parent_account_id']})\n";
        echo "   포인트 \${$avatarPointAmount} → 아바타 {$edgeReceiver['user_id']}\n";
    } else {
        // 일반 회원: 전부 본인
        $db->update("
            UPDATE users SET
                available_bonus = available_bonus + ?,
                total_bonus = total_bonus + ?,
                avatar_points = avatar_points + ?,
                total_edge_bonus = total_edge_bonus + ?
            WHERE id = ?
        ", [$cashAmount, $cashAmount + $avatarPointAmount, $avatarPointAmount, $bonusAmount, $edgeReceiver['id']]);

        echo "✅ 엣지 보너스 지급 완료 (일반 회원):\n";
        echo "   총 \${$bonusAmount} → {$edgeReceiver['user_id']}\n";
    }

    // 매칭 보너스도 계산
    echo "\n매칭 보너스 계산:\n";

    $matchingReceiver = null;
    if ($edgeReceiver && $edgeReceiver['id']) {
        $edgeUser = $db->selectOne("SELECT referral_id FROM users WHERE id = ?", [$edgeReceiver['id']]);
        if ($edgeUser && $edgeUser['referral_id']) {
            $matchingReceiver = $db->selectOne("
                SELECT id, user_id FROM users WHERE id = ?
            ", [$edgeUser['referral_id']]);
        }
    }

    if (!$matchingReceiver) {
        echo "  ❌ 매칭 수령자 없음 (엣지 수령자에게 추천인이 없음)\n";
    } else {
        echo "  ✅ 매칭 수령자: {$matchingReceiver['user_id']}\n";

        // 매칭 보너스 생성
        $matchingCashBonusId = $db->insert("
            INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
            VALUES (?, ?, 'matching', 'cash', ?, ?, ?, NOW())
        ", [
            $matchingReceiver['id'],
            $user['id'],
            $cashAmount,
            $packageAmount,
            "추천매칭 보너스 - 캐시 (65%) [재계산]"
        ]);

        $matchingAvatarBonusId = $db->insert("
            INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
            VALUES (?, ?, 'matching', 'avatar_point', ?, ?, ?, NOW())
        ", [
            $matchingReceiver['id'],
            $user['id'],
            $avatarPointAmount,
            $packageAmount,
            "추천매칭 보너스 - 아바타 포인트 (35%) [재계산]"
        ]);

        // 수령자 업데이트
        $matchingRcv = $db->selectOne("SELECT id, is_avatar, parent_account_id FROM users WHERE id = ?", [$matchingReceiver['id']]);

        if ($matchingRcv['is_avatar'] && $matchingRcv['parent_account_id']) {
            $db->update("
                UPDATE users SET
                    available_bonus = available_bonus + ?,
                    total_bonus = total_bonus + ?,
                    total_matching_bonus = total_matching_bonus + ?
                WHERE id = ?
            ", [$cashAmount, $cashAmount, $bonusAmount, $matchingRcv['parent_account_id']]);

            $db->update("
                UPDATE users SET avatar_points = avatar_points + ? WHERE id = ?
            ", [$avatarPointAmount, $matchingReceiver['id']]);

            echo "  ✅ 매칭 보너스 지급 완료 (아바타)\n";
        } else {
            $db->update("
                UPDATE users SET
                    available_bonus = available_bonus + ?,
                    total_bonus = total_bonus + ?,
                    avatar_points = avatar_points + ?,
                    total_matching_bonus = total_matching_bonus + ?
                WHERE id = ?
            ", [$cashAmount, $cashAmount + $avatarPointAmount, $avatarPointAmount, $bonusAmount, $matchingReceiver['id']]);

            echo "  ✅ 매칭 보너스 지급 완료 (일반 회원)\n";
        }
    }

    $db->commit();
    echo "\n✅ 재계산 완료!\n";

} catch (Exception $e) {
    $db->rollback();
    echo "\n❌ 오류 발생: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
