<?php
/**
 * 아바타 자동 생성 복구 도구
 * Avatar Points >= $100인데 생성되지 않은 아바타를 자동으로 생성
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

// sales-input.php에서 필요한 함수만 복사 (세션 체크 없이)
// 나중에 include하지 않고 여기 직접 정의

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance();

// ===== 필요한 함수들 (sales-input.php에서 복사) =====

/**
 * 스필오버 위치 찾기 (Binary Tree)
 */
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

        if (!$hasLeft) {
            return ['parent_id' => $currentId, 'position' => 1];
        }
        if (!$hasRight) {
            return ['parent_id' => $currentId, 'position' => 2];
        }

        if ($leftChild) $queue[] = ['id' => $leftChild['id'], 'user_id' => $leftChild['user_id']];
        if ($rightChild) $queue[] = ['id' => $rightChild['id'], 'user_id' => $rightChild['user_id']];
    }

    return null;
}

/**
 * 아바타 계정 생성
 */
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

    // parent_account_id는 INT이므로 부모의 id (내부 ID)를 사용
    $parentAccountId = $parentUserId;  // 이미 id (INT)

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

/**
 * 보너스 계산 및 분배
 */
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

// POST 요청 처리
$action = $_POST['action'] ?? '';
$autoFix = ($action === 'auto_fix');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>아바타 자동 생성 복구</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Malgun Gothic', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 20px;
            line-height: 1.6;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #60a5fa; margin-bottom: 20px; font-size: 28px; }
        h2 { color: #34d399; margin: 30px 0 15px 0; font-size: 20px; border-bottom: 2px solid #34d399; padding-bottom: 5px; }
        h3 { color: #fbbf24; margin: 20px 0 10px 0; font-size: 18px; }

        .info {
            background: rgba(59, 130, 246, 0.1);
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            color: #93c5fd;
        }
        .success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            color: #6ee7b7;
        }
        .warning {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid #ef4444;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            color: #fca5a5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: #1e293b;
            border-radius: 8px;
            overflow: hidden;
        }
        th {
            background: #334155;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #94a3b8;
            border-bottom: 2px solid #475569;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #334155;
        }
        tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin: 5px;
        }
        button:hover { background: #2563eb; }
        button.danger {
            background: #ef4444;
        }
        button.danger:hover {
            background: #dc2626;
        }
        button.success {
            background: #10b981;
        }
        button.success:hover {
            background: #059669;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }
        .badge-warning {
            background: #ef4444;
            color: white;
        }
        .badge-success {
            background: #10b981;
            color: white;
        }

        .log-entry {
            background: #1e293b;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .log-entry.error {
            border-left-color: #ef4444;
        }
        .log-entry.success {
            border-left-color: #10b981;
        }

        pre {
            background: #0f172a;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            color: #cbd5e1;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 아바타 자동 생성 복구 도구</h1>

        <?php
        // ===== STEP 1: Avatar Points >= $100인데 생성 안 된 회원 조회 =====

        echo '<h2>📊 생성 대기 중인 회원 (Avatar Points >= $100)</h2>';

        $eligibleUsers = $db->select("
            SELECT
                id,
                user_id,
                name,
                avatar_points,
                available_bonus,
                total_bonus,
                avatar_count,
                created_at
            FROM users
            WHERE avatar_points >= 100.00
              AND deleted_at IS NULL
              AND is_avatar = 0
            ORDER BY avatar_points DESC
        ");

        if (empty($eligibleUsers)) {
            echo '<div class="success">';
            echo '<p>✅ 모든 회원의 아바타가 정상적으로 생성되었습니다.</p>';
            echo '<p>Avatar Points가 $100 이상인 회원이 없습니다.</p>';
            echo '</div>';
        } else {
            echo '<div class="warning">';
            echo '<p><strong>⚠️ ' . count($eligibleUsers) . '명의 회원이 아바타 생성 조건을 충족했지만 생성되지 않았습니다.</strong></p>';
            echo '<p>아래 버튼을 클릭하여 자동으로 복구할 수 있습니다.</p>';
            echo '</div>';

            // 총 생성 가능한 아바타 수 계산
            $totalAvatarsToCreate = 0;
            $totalPointsToDeduct = 0;

            foreach ($eligibleUsers as $user) {
                $avatarPoints = floatval($user['avatar_points']);
                $count = floor($avatarPoints / 100);
                $totalAvatarsToCreate += $count;
                $totalPointsToDeduct += ($count * 100);
            }

            echo '<div class="info">';
            echo '<h3>📈 예상 복구 결과</h3>';
            echo '<p><strong>생성될 아바타 수:</strong> ' . $totalAvatarsToCreate . '개</p>';
            echo '<p><strong>차감될 Avatar Points:</strong> $' . number_format($totalPointsToDeduct, 2) . '</p>';
            echo '</div>';

            echo '<table>';
            echo '<tr>';
            echo '<th>회원 ID</th>';
            echo '<th>이름</th>';
            echo '<th>Avatar Points</th>';
            echo '<th>생성 가능 수</th>';
            echo '<th>남을 포인트</th>';
            echo '<th>현재 보유 아바타</th>';
            echo '<th>가입일</th>';
            echo '</tr>';

            foreach ($eligibleUsers as $user) {
                $avatarPoints = floatval($user['avatar_points']);
                $avatarCount = floor($avatarPoints / 100);
                $remaining = $avatarPoints - ($avatarCount * 100);

                echo '<tr>';
                echo '<td>' . htmlspecialchars($user['user_id']) . '</td>';
                echo '<td>' . htmlspecialchars($user['name']) . '</td>';
                echo '<td>$' . number_format($user['avatar_points'], 2) . ' <span class="badge badge-warning">생성 대기</span></td>';
                echo '<td><strong>' . $avatarCount . '개</strong></td>';
                echo '<td>$' . number_format($remaining, 2) . '</td>';
                echo '<td>' . $user['avatar_count'] . '개</td>';
                echo '<td>' . date('Y-m-d', strtotime($user['created_at'])) . '</td>';
                echo '</tr>';
            }
            echo '</table>';

            // 자동 복구 버튼
            echo '<form method="POST" style="margin-top: 30px;">';
            echo '<input type="hidden" name="action" value="auto_fix">';
            echo '<button type="submit" class="success" onclick="return confirm(\'정말로 ' . $totalAvatarsToCreate . '개의 아바타를 자동 생성하시겠습니까?\');">🚀 자동 복구 실행 (' . $totalAvatarsToCreate . '개 생성)</button>';
            echo '<button type="button" onclick="location.href=\'' . $_SERVER['PHP_SELF'] . '\'">새로고침</button>';
            echo '</form>';
        }

        // ===== STEP 2: 자동 복구 실행 =====

        if ($autoFix && !empty($eligibleUsers)) {
            echo '<h2>🔄 자동 복구 실행 중...</h2>';

            $successCount = 0;
            $failCount = 0;
            $totalCreated = 0;
            $logs = [];

            foreach ($eligibleUsers as $user) {
                $userId = $user['id'];
                $userIdStr = $user['user_id'];
                $avatarPoints = floatval($user['avatar_points']);
                $avatarCount = floor($avatarPoints / 100);

                $logs[] = [
                    'type' => 'info',
                    'message' => "회원 {$userIdStr} 처리 시작 (Avatar Points: \${$avatarPoints}, 생성 예정: {$avatarCount}개)"
                ];

                $createdForThisUser = 0;

                for ($i = 0; $i < $avatarCount; $i++) {
                    try {
                        $db->beginTransaction();

                        // 1. 아바타 계정 생성
                        $avatarInfo = createAvatarAccount($db, $userId, 2);

                        // 2. 아바타 포인트 $100 차감
                        $db->execute("
                            UPDATE users SET avatar_points = avatar_points - 100.00 WHERE id = ?
                        ", [$userId]);

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
                        $bonusResult = calculateAndDistributeBonuses(
                            $db,
                            $avatarInfo['avatar_internal_id'],
                            $saleId,
                            100.00,
                            2
                        );

                        $db->execute("
                            INSERT INTO transactions (user_id, type, amount, currency, reference_id, description, created_at)
                            VALUES (?, 'avatar_purchase', 100.00, 'AVATAR_POINTS', ?, 'Avatar 자동 복구 생성', ?)
                        ", [$avatarInfo['avatar_internal_id'], $saleId, $salesDateTime]);

                        $db->commit();

                        $createdForThisUser++;
                        $totalCreated++;

                        $logs[] = [
                            'type' => 'success',
                            'message' => "✅ 아바타 {$avatarInfo['avatar_user_id']} 생성 완료 (보너스 {$bonusResult['count']}건 지급)"
                        ];

                    } catch (Exception $e) {
                        $db->rollback();
                        $failCount++;

                        $logs[] = [
                            'type' => 'error',
                            'message' => "❌ 아바타 생성 실패: " . $e->getMessage() . " (파일: " . $e->getFile() . ", 라인: " . $e->getLine() . ")"
                        ];

                        error_log("Avatar auto-fix error for user {$userIdStr}: " . $e->getMessage());
                        continue;
                    }
                }

                if ($createdForThisUser > 0) {
                    $successCount++;
                    $logs[] = [
                        'type' => 'success',
                        'message' => "회원 {$userIdStr} 처리 완료: {$createdForThisUser}개 아바타 생성"
                    ];
                } else {
                    $logs[] = [
                        'type' => 'warning',
                        'message' => "회원 {$userIdStr} 처리 완료: 생성된 아바타 없음"
                    ];
                }
            }

            // 복구 결과
            echo '<div class="success">';
            echo '<h3>✅ 자동 복구 완료</h3>';
            echo '<p><strong>처리된 회원:</strong> ' . count($eligibleUsers) . '명</p>';
            echo '<p><strong>성공:</strong> ' . $successCount . '명</p>';
            echo '<p><strong>실패:</strong> ' . $failCount . '명</p>';
            echo '<p><strong>생성된 아바타:</strong> ' . $totalCreated . '개</p>';
            echo '</div>';

            // 상세 로그
            echo '<h2>📋 실행 로그</h2>';

            foreach ($logs as $log) {
                $class = $log['type'];
                echo '<div class="log-entry ' . $class . '">';
                echo htmlspecialchars($log['message']);
                echo '</div>';
            }

            echo '<div style="margin-top: 30px;">';
            echo '<button onclick="location.href=\'' . $_SERVER['PHP_SELF'] . '\'">다시 확인</button>';
            echo '<a href="/admin/pages/users.html" style="color: #60a5fa; margin-left: 15px;">회원 목록으로</a>';
            echo '</div>';
        }

        // ===== STEP 3: 시스템 통계 =====

        if (!$autoFix) {
            echo '<h2>📈 시스템 통계</h2>';

            $stats = $db->selectOne("
                SELECT
                    COUNT(*) as total_users,
                    SUM(CASE WHEN avatar_points >= 100 THEN 1 ELSE 0 END) as users_with_100plus,
                    SUM(avatar_points) as total_avatar_points,
                    SUM(avatar_count) as total_avatars_created,
                    AVG(avatar_points) as avg_avatar_points
                FROM users
                WHERE deleted_at IS NULL AND is_avatar = 0
            ");

            echo '<table>';
            echo '<tr><th>항목</th><th>값</th></tr>';
            echo '<tr><td>전체 회원 수</td><td>' . number_format($stats['total_users']) . '명</td></tr>';
            echo '<tr><td>Avatar Points >= $100 회원</td><td><strong>' . number_format($stats['users_with_100plus']) . '명</strong></td></tr>';
            echo '<tr><td>전체 Avatar Points</td><td>$' . number_format($stats['total_avatar_points'], 2) . '</td></tr>';
            echo '<tr><td>생성된 아바타 수</td><td>' . number_format($stats['total_avatars_created']) . '개</td></tr>';
            echo '<tr><td>평균 Avatar Points</td><td>$' . number_format($stats['avg_avatar_points'], 2) . '</td></tr>';
            echo '</table>';

            // 아바타 생성 이력 (최근 10건)
            echo '<h3>최근 생성된 아바타 (10건)</h3>';

            $recentAvatars = $db->select("
                SELECT
                    u.user_id,
                    u.name,
                    u.referral_id,
                    u.total_sales,
                    u.created_at,
                    parent.user_id as parent_user_id,
                    parent.name as parent_name
                FROM users u
                LEFT JOIN users parent ON u.referral_id = parent.id
                WHERE u.is_avatar = 1
                  AND u.deleted_at IS NULL
                ORDER BY u.created_at DESC
                LIMIT 10
            ");

            if (empty($recentAvatars)) {
                echo '<p style="color: #94a3b8;">아직 생성된 아바타가 없습니다.</p>';
            } else {
                echo '<table>';
                echo '<tr>';
                echo '<th>아바타 ID</th>';
                echo '<th>이름</th>';
                echo '<th>소유자</th>';
                echo '<th>매출</th>';
                echo '<th>생성일</th>';
                echo '</tr>';

                foreach ($recentAvatars as $avatar) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($avatar['user_id']) . ' <span class="badge badge-success">AVATAR</span></td>';
                    echo '<td>' . htmlspecialchars($avatar['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($avatar['parent_user_id'] ?? 'N/A') . ' (' . htmlspecialchars($avatar['parent_name'] ?? 'N/A') . ')</td>';
                    echo '<td>$' . number_format($avatar['total_sales'], 2) . '</td>';
                    echo '<td>' . date('Y-m-d H:i', strtotime($avatar['created_at'])) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
        ?>

    </div>
</body>
</html>
