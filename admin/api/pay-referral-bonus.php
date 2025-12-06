<?php
/**
 * 추천 보너스 개별 지급 API
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

$userId = $_GET['user_id'] ?? '';

if (empty($userId)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    ob_end_flush();
    exit;
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    // 회원 정보 조회
    $user = $db->selectOne("
        SELECT id, user_id, referral_id, package_id
        FROM users
        WHERE id = ?
    ", [$userId]);

    if (!$user) {
        throw new Exception('회원을 찾을 수 없습니다.');
    }

    // package_id 확인
    if (!$user['package_id']) {
        throw new Exception('패키지가 등록되지 않은 회원입니다.');
    }

    // 추천인 확인
    if (!$user['referral_id']) {
        throw new Exception('추천인이 없는 회원입니다.');
    }

    // 이미 추천 보너스가 지급되었는지 확인
    $existingBonus = $db->selectOne("
        SELECT COUNT(*) as cnt
        FROM bonuses
        WHERE from_user_id = ? AND bonus_type = 'referral'
    ", [$user['id']]);

    if ($existingBonus['cnt'] > 0) {
        throw new Exception('이미 추천 보너스가 지급된 회원입니다.');
    }

    // 패키지 금액 조회
    $package = $db->selectOne("
        SELECT price FROM packages WHERE id = ?
    ", [$user['package_id']]);

    if (!$package) {
        throw new Exception('패키지 정보를 찾을 수 없습니다.');
    }

    $packageAmount = floatval($package['price']);
    $bonusAmount = $packageAmount * 0.25; // 25%
    $cashAmount = $bonusAmount * 0.65; // 65% 캐시
    $avatarPointAmount = $bonusAmount * 0.35; // 35% 아바타 포인트

    // 추천인 정보 조회
    $referrer = $db->selectOne("
        SELECT id, user_id, is_avatar, parent_account_id
        FROM users
        WHERE id = ?
    ", [$user['referral_id']]);

    if (!$referrer) {
        throw new Exception('추천인을 찾을 수 없습니다.');
    }

    // 캐시 보너스 생성
    $db->insert("
        INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
        VALUES (?, ?, 'referral', 'cash', ?, ?, ?, NOW())
    ", [
        $referrer['id'],
        $user['id'],
        $cashAmount,
        $packageAmount,
        "직접 추천 보너스 - 캐시 (65%) [수동지급]"
    ]);

    // 아바타 포인트 보너스 생성
    $db->insert("
        INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
        VALUES (?, ?, 'referral', 'avatar_point', ?, ?, ?, NOW())
    ", [
        $referrer['id'],
        $user['id'],
        $avatarPointAmount,
        $packageAmount,
        "직접 추천 보너스 - 아바타 포인트 (35%) [수동지급]"
    ]);

    // 회원 보너스 잔액 업데이트
    if ($referrer['is_avatar'] && $referrer['parent_account_id']) {
        // 아바타: 캐시 → 오너, 아바타포인트 → 본인
        $db->update("
            UPDATE users SET
                available_bonus = available_bonus + ?,
                total_bonus = total_bonus + ?,
                total_referral_bonus = total_referral_bonus + ?
            WHERE id = ?
        ", [$cashAmount, $cashAmount, $bonusAmount, $referrer['parent_account_id']]);

        $db->update("
            UPDATE users SET avatar_points = avatar_points + ? WHERE id = ?
        ", [$avatarPointAmount, $referrer['id']]);

    } else {
        // 일반 회원: 전부 본인
        $db->update("
            UPDATE users SET
                available_bonus = available_bonus + ?,
                total_bonus = total_bonus + ?,
                avatar_points = avatar_points + ?,
                total_referral_bonus = total_referral_bonus + ?
            WHERE id = ?
        ", [$cashAmount, $cashAmount + $avatarPointAmount, $avatarPointAmount, $bonusAmount, $referrer['id']]);
    }

    $db->commit();

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => '추천 보너스가 지급되었습니다.',
        'amount' => number_format($bonusAmount, 2),
        'referrer' => $referrer['user_id'],
        'cash' => number_format($cashAmount, 2),
        'avatar_point' => number_format($avatarPointAmount, 2)
    ]);
    ob_end_flush();

} catch (Exception $e) {
    $db->rollback();

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    ob_end_flush();
}
