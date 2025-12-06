<?php
/**
 * 자동 아바타 생성 실행 스크립트
 * 백그라운드에서 실행됨 (사용자 대기 없음)
 */

// 보안 키 체크
if (!isset($_GET['key']) || $_GET['key'] !== 'auto_trigger_2024') {
    http_response_code(403);
    die('Access denied');
}

error_reporting(E_ALL);
ini_set('display_errors', 0); // 백그라운드 실행이므로 화면 출력 안 함
set_time_limit(300);

// Lock 파일
$lockFile = __DIR__ . '/.avatar_lock';

// 실행 완료 시 Lock 파일 삭제
register_shutdown_function(function() use ($lockFile) {
    @unlink($lockFile);
});

try {
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance();

    // Lock 타임아웃 증가
    try {
        $db->execute("SET SESSION innodb_lock_wait_timeout = 10");
        $db->execute("SET SESSION lock_wait_timeout = 10");
    } catch (Exception $e) {
        // 설정 실패해도 계속 진행
    }

    // ===== 함수들 =====

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
        $parent = $db->selectOne("SELECT id, user_id, name, avatar_count, is_avatar, parent_account_id FROM users WHERE id = ?", [$parentUserId]);
        if (!$parent) throw new Exception("부모 회원을 찾을 수 없습니다.");

        // 오너 결정: 부모가 아바타이면 부모의 오너를 승계, 일반 회원이면 부모가 오너
        if ($parent['is_avatar'] && $parent['parent_account_id']) {
            // 부모가 아바타인 경우: 부모의 오너를 승계 (최상위 실제 회원)
            $parentAccountId = $parent['parent_account_id'];
        } else {
            // 부모가 일반 회원인 경우: 부모가 오너
            $parentAccountId = $parentUserId;
        }

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

        // Referral
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
            $bonusCount++;
        }

        // Edge, Matching, Rollup은 간소화를 위해 생략 (필요시 추가)

        return ['count' => $bonusCount];
    }

    // ===== 실행 =====

    $users = $db->select("
        SELECT id, user_id, avatar_points, is_avatar, parent_account_id
        FROM users
        WHERE avatar_points >= 100.00
          AND deleted_at IS NULL
          -- is_avatar 조건 제거: 아바타도 다음 세대 아바타 생성 가능
        ORDER BY avatar_points DESC
        LIMIT 10
    ");

    if (empty($users)) {
        exit; // 대기 없음
    }

    $totalCreated = 0;

    foreach ($users as $user) {
        $userId = $user['id'];
        $avatarPoints = floatval($user['avatar_points']);
        $avatarCount = floor($avatarPoints / 100);

        for ($i = 0; $i < $avatarCount; $i++) {
            try {
                $db->beginTransaction();

                $avatarInfo = createAvatarAccount($db, $userId, 2);
                $db->execute("UPDATE users SET avatar_points = avatar_points - 100.00 WHERE id = ?", [$userId]);

                $salesDateTime = date('Y-m-d H:i:s');
                $db->execute("
                    INSERT INTO sales (user_id, package_id, amount, payment_method, txid, status, confirmed_at, created_at)
                    VALUES (?, 2, 100.00, 'AVATAR_AUTO', 'AVATAR_AUTO', 'completed', ?, ?)
                ", [$avatarInfo['avatar_internal_id'], $salesDateTime, $salesDateTime]);

                $saleId = $db->lastInsertId();

                $db->execute("
                    UPDATE users
                    SET package_id = 2, package_date = ?, total_sales = 100.00, updated_at = NOW()
                    WHERE id = ?
                ", [$salesDateTime, $avatarInfo['avatar_internal_id']]);

                $bonusResult = calculateAndDistributeBonuses($db, $avatarInfo['avatar_internal_id'], $saleId, 100.00, 2);

                $db->execute("
                    INSERT INTO transactions (user_id, type, amount, currency, reference_id, description, created_at)
                    VALUES (?, 'avatar_purchase', 100.00, 'AVATAR_POINTS', ?, 'Avatar 자동생성', ?)
                ", [$avatarInfo['avatar_internal_id'], $saleId, $salesDateTime]);

                $db->commit();
                $totalCreated++;

            } catch (Exception $e) {
                $db->rollback();
                // 실패해도 계속 진행
                continue;
            }
        }
    }

} catch (Exception $e) {
    // 에러 발생해도 조용히 종료
}

// Lock 파일 삭제는 shutdown function에서 자동 처리됨
?>
