<?php
/**
 * 아바타 자동 생성 - 간단 버전
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance();

// ===== 필요한 함수들 =====

function findSpilloverPosition($db, $referrerId) {
    $referrer = $db->selectOne("SELECT id, user_id FROM users WHERE id = ?", [$referrerId]);
    if (!$referrer) return null;

    $queue = [['id' => $referrer['id'], 'user_id' => $referrer['user_id']]];
    $visited = [];

    while (count($queue) > 0) {
        $current = array_shift($queue);
        $currentId = $current['id'];

        if (in_array($currentId, $visited)) continue;
        $visited[] = $currentId;

        $children = $db->select("
            SELECT id, user_id, sponsor_position
            FROM users
            WHERE sponsor_id = ?
            ORDER BY sponsor_position
        ", [$current['user_id']]);

        $hasLeft = false;
        $hasRight = false;
        $leftChild = null;
        $rightChild = null;

        foreach ($children as $child) {
            if ($child['sponsor_position'] == 1) {
                $hasLeft = true;
                $leftChild = $child;
            } elseif ($child['sponsor_position'] == 2) {
                $hasRight = true;
                $rightChild = $child;
            }
        }

        if (!$hasLeft) return ['parent_id' => $currentId, 'position' => 1];
        if (!$hasRight) return ['parent_id' => $currentId, 'position' => 2];

        if ($leftChild) $queue[] = ['id' => $leftChild['id'], 'user_id' => $leftChild['user_id']];
        if ($rightChild) $queue[] = ['id' => $rightChild['id'], 'user_id' => $rightChild['user_id']];
    }

    return null;
}

function createAvatarAccount($db, $parentUserId, $packageId = 2) {
    $parent = $db->selectOne("SELECT id, user_id, name, avatar_count FROM users WHERE id = ?", [$parentUserId]);
    if (!$parent) throw new Exception("부모 회원을 찾을 수 없습니다.");

    $totalAvatarsResult = $db->selectOne("SELECT COUNT(*) as total FROM users WHERE name LIKE 'Avatar %' AND deleted_at IS NULL");
    $totalAvatars = intval($totalAvatarsResult['total']) + 1;
    $avatarUserId = 'AVA' . str_pad($totalAvatars, 5, '0', STR_PAD_LEFT);

    $attempts = 0;
    while ($attempts < 100) {
        $exists = $db->selectOne("SELECT id FROM users WHERE user_id = ?", [$avatarUserId]);
        if (!$exists) break;
        $totalAvatars++;
        $avatarUserId = 'AVA' . str_pad($totalAvatars, 5, '0', STR_PAD_LEFT);
        $attempts++;
    }

    if ($attempts >= 100) throw new Exception("아바타 ID 생성 실패");

    $spilloverPos = findSpilloverPosition($db, $parentUserId);
    if (!$spilloverPos) {
        $spilloverPos = ['parent_id' => $parentUserId, 'position' => 1];
    }

    $sponsorInternalId = $spilloverPos['parent_id'];
    $position = $spilloverPos['position'];

    $sponsor = $db->selectOne("SELECT user_id FROM users WHERE id = ?", [$sponsorInternalId]);
    if (!$sponsor) throw new Exception("스폰서를 찾을 수 없습니다.");
    $sponsorUserId = $sponsor['user_id'];

    $parentAccountId = $parentUserId;

    $db->execute("
        INSERT INTO users (
            user_id, password, name, email,
            is_avatar, referral_id, sponsor_id, sponsor_position, package_id,
            parent_account_id,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, NOW(), NOW())
    ", [
        $avatarUserId,
        password_hash('avatar_' . time(), PASSWORD_BCRYPT),
        'Avatar ' . $totalAvatars,
        $avatarUserId . '@avatar.local',
        $parentUserId,
        $sponsorUserId,
        $position,
        $packageId,
        $parentAccountId
    ]);

    $avatarInternalId = $db->lastInsertId();

    $db->execute("
        INSERT INTO avatars (parent_user_id, avatar_user_id, trigger_amount, package_id, status, created_at)
        VALUES (?, ?, 100.00, ?, 'active', NOW())
    ", [$parentUserId, $avatarInternalId, $packageId]);

    $db->execute("UPDATE users SET avatar_count = avatar_count + 1 WHERE id = ?", [$parentUserId]);

    return ['avatar_user_id' => $avatarUserId, 'avatar_internal_id' => $avatarInternalId];
}

function calculateAndDistributeBonuses($db, $userInternalId, $saleId, $amount, $packageId) {
    $bonusCount = 0;
    $totalBonusAmount = 0;

    $referralResult = calculateReferralBonus($db, $userInternalId, $saleId, $amount, $packageId);
    $bonusCount += $referralResult['count'];
    $totalBonusAmount += $referralResult['amount'];

    $edgeResult = calculateEdgeBonus($db, $userInternalId, $saleId, $amount, $packageId);
    $bonusCount += $edgeResult['count'];
    $totalBonusAmount += $edgeResult['amount'];
    $edgeReceiverId = $edgeResult['receiver_id'] ?? null;

    $matchingResult = calculateMatchingBonus($db, $userInternalId, $saleId, $amount, $packageId, $edgeReceiverId);
    $bonusCount += $matchingResult['count'];
    $totalBonusAmount += $matchingResult['amount'];

    $rollupResult = calculateRollupBonus($db, $userInternalId, $saleId, $amount, $packageId);
    $bonusCount += $rollupResult['count'];
    $totalBonusAmount += $rollupResult['amount'];

    return ['count' => $bonusCount, 'total_amount' => $totalBonusAmount];
}

function calculateReferralBonus($db, $userInternalId, $saleId, $amount, $packageId) {
    $count = 0;
    $totalAmount = 0;

    $referrer = $db->selectOne("SELECT referral_id FROM users WHERE id = ?", [$userInternalId]);
    if ($referrer && $referrer['referral_id']) {
        $referrerId = $referrer['referral_id'];
        $receiver = $db->selectOne("SELECT id, is_avatar, referral_id FROM users WHERE id = ?", [$referrerId]);

        $bonusAmount = $amount * 0.25;
        $cashBonus = $bonusAmount * 0.65;
        $avatarPoints = $bonusAmount * 0.35;

        $db->execute("INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at) VALUES (?, ?, 'referral', 'cash', ?, ?, 1, 'paid', NOW())", [$referrerId, $userInternalId, $cashBonus, $amount]);
        $db->execute("INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at) VALUES (?, ?, 'referral', 'avatar_point', ?, ?, 1, 'paid', NOW())", [$referrerId, $userInternalId, $avatarPoints, $amount]);

        if ($receiver && $receiver['is_avatar']) {
            $parentId = $receiver['referral_id'];
            $db->execute("UPDATE users SET total_bonus = total_bonus + ?, available_bonus = available_bonus + ?, total_referral_bonus = total_referral_bonus + ? WHERE id = ?", [$cashBonus, $cashBonus, $cashBonus, $parentId]);
            $db->execute("UPDATE users SET avatar_points = avatar_points + ? WHERE id = ?", [$avatarPoints, $referrerId]);
        } else {
            $db->execute("UPDATE users SET total_bonus = total_bonus + ?, available_bonus = available_bonus + ?, avatar_points = avatar_points + ?, total_referral_bonus = total_referral_bonus + ? WHERE id = ?", [$bonusAmount, $cashBonus, $avatarPoints, $bonusAmount, $referrerId]);
        }

        $count = 1;
        $totalAmount = $bonusAmount;
    }

    return ['count' => $count, 'amount' => $totalAmount];
}

function calculateEdgeBonus($db, $userInternalId, $saleId, $amount, $packageId) {
    $count = 0;
    $totalAmount = 0;

    $newUser = $db->selectOne("SELECT id, user_id, sponsor_id, sponsor_position FROM users WHERE id = ?", [$userInternalId]);
    if (!$newUser || !$newUser['sponsor_position']) {
        return ['count' => $count, 'amount' => $totalAmount, 'receiver_id' => null];
    }

    $bonusAmount = $amount * 0.25;
    $cashBonus = $bonusAmount * 0.65;
    $avatarPoints = $bonusAmount * 0.35;

    $currentUserId = $newUser['user_id'];
    $lastPosition = intval($newUser['sponsor_position']);
    $level = 0;
    $edgeReceiverId = null;

    while (true) {
        $parent = $db->selectOne("SELECT id, user_id, sponsor_id, sponsor_position FROM users WHERE user_id = ?", [$currentUserId]);
        if (!$parent || !$parent['sponsor_id']) break;

        $level++;
        $currentPosition = intval($parent['sponsor_position']);

        if ($level > 1 && $currentPosition !== $lastPosition) {
            $edgeReceiver = $db->selectOne("SELECT id, user_id FROM users WHERE user_id = ?", [$parent['sponsor_id']]);

            if ($edgeReceiver) {
                $edgeReceiverId = $edgeReceiver['id'];

                $db->execute("INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at) VALUES (?, ?, 'edge', 'cash', ?, ?, ?, 'paid', NOW())", [$edgeReceiverId, $userInternalId, $cashBonus, $amount, $level]);
                $db->execute("INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at) VALUES (?, ?, 'edge', 'avatar_point', ?, ?, ?, 'paid', NOW())", [$edgeReceiverId, $userInternalId, $avatarPoints, $amount, $level]);

                $receiver = $db->selectOne("SELECT id, is_avatar, referral_id FROM users WHERE id = ?", [$edgeReceiverId]);

                if ($receiver && $receiver['is_avatar']) {
                    $parentId = $receiver['referral_id'];
                    $db->execute("UPDATE users SET total_bonus = total_bonus + ?, available_bonus = available_bonus + ?, total_edge_bonus = total_edge_bonus + ? WHERE id = ?", [$cashBonus, $cashBonus, $cashBonus, $parentId]);
                    $db->execute("UPDATE users SET avatar_points = avatar_points + ? WHERE id = ?", [$avatarPoints, $edgeReceiverId]);
                } else {
                    $db->execute("UPDATE users SET total_bonus = total_bonus + ?, available_bonus = available_bonus + ?, avatar_points = avatar_points + ?, total_edge_bonus = total_edge_bonus + ? WHERE id = ?", [$bonusAmount, $cashBonus, $avatarPoints, $bonusAmount, $edgeReceiverId]);
                }

                $count = 1;
                $totalAmount = $bonusAmount;

                return ['count' => $count, 'amount' => $totalAmount, 'receiver_id' => $edgeReceiverId];
            }
            break;
        }

        $lastPosition = $currentPosition;
        $currentUserId = $parent['sponsor_id'];
    }

    return ['count' => $count, 'amount' => $totalAmount, 'receiver_id' => null];
}

function calculateMatchingBonus($db, $userInternalId, $saleId, $amount, $packageId, $edgeReceiverId = null) {
    $count = 0;
    $totalAmount = 0;

    if (!$edgeReceiverId) return ['count' => $count, 'amount' => $totalAmount];

    $bonusAmount = $amount * 0.25;
    $cashBonus = $bonusAmount * 0.65;
    $avatarPoints = $bonusAmount * 0.35;

    $edgeReceiver = $db->selectOne("SELECT id, referral_id FROM users WHERE id = ?", [$edgeReceiverId]);
    if (!$edgeReceiver || !$edgeReceiver['referral_id']) return ['count' => $count, 'amount' => $totalAmount];

    $matchingReceiverId = $edgeReceiver['referral_id'];

    $db->execute("INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at) VALUES (?, ?, 'matching', 'cash', ?, ?, 1, 'paid', NOW())", [$matchingReceiverId, $userInternalId, $cashBonus, $amount]);
    $db->execute("INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at) VALUES (?, ?, 'matching', 'avatar_point', ?, ?, 1, 'paid', NOW())", [$matchingReceiverId, $userInternalId, $avatarPoints, $amount]);

    $receiver = $db->selectOne("SELECT id, is_avatar, referral_id FROM users WHERE id = ?", [$matchingReceiverId]);

    if ($receiver && $receiver['is_avatar']) {
        $parentId = $receiver['referral_id'];
        $db->execute("UPDATE users SET total_bonus = total_bonus + ?, available_bonus = available_bonus + ?, total_matching_bonus = total_matching_bonus + ? WHERE id = ?", [$cashBonus, $cashBonus, $cashBonus, $parentId]);
        $db->execute("UPDATE users SET avatar_points = avatar_points + ? WHERE id = ?", [$avatarPoints, $matchingReceiverId]);
    } else {
        $db->execute("UPDATE users SET total_bonus = total_bonus + ?, available_bonus = available_bonus + ?, avatar_points = avatar_points + ?, total_matching_bonus = total_matching_bonus + ? WHERE id = ?", [$bonusAmount, $cashBonus, $avatarPoints, $bonusAmount, $matchingReceiverId]);
    }

    $count = 1;
    $totalAmount = $bonusAmount;

    return ['count' => $count, 'amount' => $totalAmount];
}

function calculateRollupBonus($db, $userInternalId, $saleId, $amount, $packageId) {
    $count = 0;
    $totalAmount = 0;
    $bonusPerLevel = 1.00;
    $currentUserId = $userInternalId;
    $maxPossibleLevel = 25;

    for ($level = 1; $level <= $maxPossibleLevel; $level++) {
        $parent = $db->selectOne("SELECT referral_id FROM users WHERE id = ?", [$currentUserId]);
        if (!$parent || !$parent['referral_id']) break;

        $parentId = $parent['referral_id'];

        $directReferralCount = $db->selectOne("SELECT COUNT(*) as cnt FROM users WHERE referral_id = ?", [$parentId]);
        $referralCount = (int)($directReferralCount['cnt'] ?? 0);

        $maxLevelForUser = 0;
        if ($referralCount >= 7) $maxLevelForUser = 25;
        elseif ($referralCount == 6) $maxLevelForUser = 22;
        elseif ($referralCount == 5) $maxLevelForUser = 20;
        elseif ($referralCount == 4) $maxLevelForUser = 18;
        elseif ($referralCount == 3) $maxLevelForUser = 16;
        elseif ($referralCount == 2) $maxLevelForUser = 14;
        elseif ($referralCount == 1) $maxLevelForUser = 12;
        elseif ($referralCount == 0) $maxLevelForUser = 10;

        if ($level > $maxLevelForUser) {
            $currentUserId = $parentId;
            continue;
        }

        $bonusAmount = $bonusPerLevel;
        $receiver = $db->selectOne("SELECT id, is_avatar, referral_id FROM users WHERE id = ?", [$parentId]);

        $cashBonus = $bonusAmount * 0.65;
        $avatarPoints = $bonusAmount * 0.35;

        $db->execute("INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at) VALUES (?, ?, 'rollup', 'cash', ?, ?, ?, 'paid', NOW())", [$parentId, $userInternalId, $cashBonus, $amount, $level]);
        $db->execute("INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at) VALUES (?, ?, 'rollup', 'avatar_point', ?, ?, ?, 'paid', NOW())", [$parentId, $userInternalId, $avatarPoints, $amount, $level]);

        if ($receiver && $receiver['is_avatar']) {
            $ownerId = $receiver['referral_id'];
            $db->execute("UPDATE users SET total_bonus = total_bonus + ?, available_bonus = available_bonus + ?, total_rollup_bonus = total_rollup_bonus + ? WHERE id = ?", [$cashBonus, $cashBonus, $cashBonus, $ownerId]);
            $db->execute("UPDATE users SET avatar_points = avatar_points + ? WHERE id = ?", [$avatarPoints, $parentId]);
        } else {
            $db->execute("UPDATE users SET total_bonus = total_bonus + ?, available_bonus = available_bonus + ?, avatar_points = avatar_points + ?, total_rollup_bonus = total_rollup_bonus + ? WHERE id = ?", [$bonusAmount, $cashBonus, $avatarPoints, $bonusAmount, $parentId]);
        }

        $count++;
        $totalAmount += $bonusAmount;
        $currentUserId = $parentId;
    }

    return ['count' => $count, 'amount' => $totalAmount];
}

// ===== 실행 =====

// 타임아웃 방지
set_time_limit(300); // 5분
ini_set('max_execution_time', 300);

// 출력 버퍼 플러시 설정
ob_implicit_flush(true);
ob_end_flush();

echo '<h1>아바타 자동 생성 실행</h1>';
echo '<pre>';
flush();

// Avatar Points >= $100인 회원 조회
$eligibleUsers = $db->select("
    SELECT id, user_id, name, avatar_points
    FROM users
    WHERE avatar_points >= 100.00
      AND deleted_at IS NULL
      AND is_avatar = 0
    ORDER BY avatar_points DESC
");

if (empty($eligibleUsers)) {
    echo "생성 대기 중인 아바타가 없습니다.\n";
    exit;
}

echo "생성 대기 중인 회원: " . count($eligibleUsers) . "명\n\n";
flush();

$totalCreated = 0;

foreach ($eligibleUsers as $user) {
    $userId = $user['id'];
    $userIdStr = $user['user_id'];
    $avatarPoints = floatval($user['avatar_points']);
    $avatarCount = floor($avatarPoints / 100);

    echo "회원 {$userIdStr} 처리 시작 (Avatar Points: \${$avatarPoints}, 생성 예정: {$avatarCount}개)\n";
    flush();

    for ($i = 0; $i < $avatarCount; $i++) {
        try {
            $db->beginTransaction();

            // 1. 아바타 계정 생성
            $avatarInfo = createAvatarAccount($db, $userId, 2);

            // 2. 아바타 포인트 $100 차감
            $db->execute("UPDATE users SET avatar_points = avatar_points - 100.00 WHERE id = ?", [$userId]);

            // 3. 아바타의 $100 매출 자동 처리
            $salesDateTime = date('Y-m-d H:i:s');
            $db->execute("
                INSERT INTO sales (user_id, package_id, amount, payment_method, txid, status, confirmed_at, created_at)
                VALUES (?, 2, 100.00, 'AVATAR_AUTO', 'AVATAR_FIX', 'completed', ?, ?)
            ", [$avatarInfo['avatar_internal_id'], $salesDateTime, $salesDateTime]);

            $saleId = $db->lastInsertId();

            $db->execute("
                UPDATE users
                SET package_id = 2, package_date = ?, total_sales = 100.00, updated_at = NOW()
                WHERE id = ?
            ", [$salesDateTime, $avatarInfo['avatar_internal_id']]);

            // 4. 보너스 계산 및 지급
            $bonusResult = calculateAndDistributeBonuses($db, $avatarInfo['avatar_internal_id'], $saleId, 100.00, 2);

            $db->execute("
                INSERT INTO transactions (user_id, type, amount, currency, reference_id, description, created_at)
                VALUES (?, 'avatar_purchase', 100.00, 'AVATAR_POINTS', ?, 'Avatar 자동 복구 생성', ?)
            ", [$avatarInfo['avatar_internal_id'], $saleId, $salesDateTime]);

            $db->commit();

            $totalCreated++;
            echo "  ✅ 아바타 {$avatarInfo['avatar_user_id']} 생성 완료 (보너스 {$bonusResult['count']}건 지급)\n";
            flush();

        } catch (Exception $e) {
            $db->rollback();
            echo "  ❌ 아바타 생성 실패: " . $e->getMessage() . "\n";
            flush();
        }
    }

    echo "회원 {$userIdStr} 처리 완료\n\n";
    flush();
}

echo "\n===== 완료 =====\n";
echo "생성된 아바타: {$totalCreated}개\n";
echo '</pre>';
?>
