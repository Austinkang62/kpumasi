<?php
/**
 * 대시보드 데이터 검증 스크립트
 * 표시되는 통계와 실제 DB 데이터 비교
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "<h1>대시보드 데이터 검증</h1>\n";
echo "<pre>\n";

// 검증할 user_id 입력
$targetUserId = $_GET['user_id'] ?? '';

if (empty($targetUserId)) {
    echo "사용법: verify-dashboard-data.php?user_id=TWMN8579\n\n";
    echo "검증할 회원의 user_id를 입력하세요.\n";
    echo "</pre>";
    exit;
}

try {
    $db = Database::getInstance();

    // 사용자 조회
    $user = $db->selectOne("
        SELECT * FROM users WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = '')
    ", [$targetUserId]);

    if (!$user) {
        echo "❌ 회원을 찾을 수 없습니다: {$targetUserId}\n";
        exit;
    }

    echo "====================================\n";
    echo "회원 정보: {$user['user_id']} ({$user['name']})\n";
    echo "====================================\n\n";

    // 1. 추천인 수 (직접 추천)
    $referralCount = $db->selectOne("
        SELECT COUNT(*) as cnt
        FROM users
        WHERE referral_id = ?
        AND (deleted_at IS NULL OR deleted_at = '')
    ", [$user['id']]);

    echo "👥 추천인 수 (직접 추천)\n";
    echo "- DB 조회: {$referralCount['cnt']}명\n";
    echo "- users.direct_referrals: {$user['direct_referrals']}\n";
    echo ($referralCount['cnt'] == $user['direct_referrals'] ? "✅ 일치\n" : "⚠️ 불일치\n");
    echo "\n";

    // 2. 조직도 (산하 회원) - sponsor_id 기준
    function countDownlineRecursive($db, $sponsorId, &$maxDepth, $currentDepth = 1) {
        $children = $db->select("
            SELECT user_id FROM users
            WHERE sponsor_id = ?
            AND (deleted_at IS NULL OR deleted_at = '')
        ", [$sponsorId]);

        $count = count($children);

        if ($count > 0 && $currentDepth > $maxDepth) {
            $maxDepth = $currentDepth;
        }

        foreach ($children as $child) {
            $count += countDownlineRecursive($db, $child['user_id'], $maxDepth, $currentDepth + 1);
        }

        return $count;
    }

    $maxDepth = 0;
    $totalDownline = countDownlineRecursive($db, $user['user_id'], $maxDepth);

    echo "🌳 조직도 (산하 회원)\n";
    echo "- 총 산하 회원: {$totalDownline}명\n";
    echo "- 최대 단계: {$maxDepth}단계\n";
    echo "\n";

    // 3. 아바타 수
    $avatarCount = $db->selectOne("
        SELECT COUNT(*) as cnt
        FROM users
        WHERE name LIKE 'Avatar%'
        AND parent_user_id = ?
        AND (deleted_at IS NULL OR deleted_at = '')
    ", [$user['id']]);

    echo "🤖 아바타 수\n";
    echo "- DB 조회 (parent_user_id): {$avatarCount['cnt']}개\n";
    echo "- users.avatar_count: {$user['avatar_count']}\n";
    echo ($avatarCount['cnt'] == $user['avatar_count'] ? "✅ 일치\n" : "⚠️ 불일치\n");
    echo "\n";

    // 4. 보너스 통계
    $bonusStats = $db->selectOne("
        SELECT
            COALESCE(SUM(CASE WHEN bonus_type = 'referral' THEN amount ELSE 0 END), 0) as referral_bonus,
            COALESCE(SUM(CASE WHEN bonus_type = 'edge' THEN amount ELSE 0 END), 0) as edge_bonus,
            COALESCE(SUM(CASE WHEN bonus_type = 'matching' THEN amount ELSE 0 END), 0) as matching_bonus,
            COALESCE(SUM(CASE WHEN bonus_type = 'rollup' THEN amount ELSE 0 END), 0) as rollup_bonus,
            COALESCE(COUNT(CASE WHEN bonus_type = 'referral' THEN 1 END), 0) as referral_count,
            COALESCE(COUNT(CASE WHEN bonus_type = 'edge' THEN 1 END), 0) as edge_count,
            COALESCE(COUNT(CASE WHEN bonus_type = 'matching' THEN 1 END), 0) as matching_count,
            COALESCE(COUNT(CASE WHEN bonus_type = 'rollup' THEN 1 END), 0) as rollup_count
        FROM bonuses
        WHERE user_id = ?
        AND status = 'paid'
    ", [$user['id']]);

    $totalBonus = $bonusStats['referral_bonus'] + $bonusStats['edge_bonus'] +
                  $bonusStats['matching_bonus'] + $bonusStats['rollup_bonus'];

    echo "💎 보너스 통계\n";
    echo "- 👥 추천 보너스: ℙ{$bonusStats['referral_bonus']} ({$bonusStats['referral_count']}건)\n";
    echo "- ⚡ 엣지 보너스: ℙ{$bonusStats['edge_bonus']} ({$bonusStats['edge_count']}건)\n";
    echo "- 🤝 매칭 보너스: ℙ{$bonusStats['matching_bonus']} ({$bonusStats['matching_count']}건)\n";
    echo "- 📊 롤업 보너스: ℙ{$bonusStats['rollup_bonus']} ({$bonusStats['rollup_count']}건)\n";
    echo "- 💰 총 보너스: ℙ{$totalBonus}\n";
    echo "- users.total_bonus: ℙ{$user['total_bonus']}\n";
    echo ($totalBonus == $user['total_bonus'] ? "✅ 일치\n" : "⚠️ 불일치\n");
    echo "\n";

    // 5. 잔액
    $avatarPoints = $user['avatar_points'] ?? 0;
    $totalBalance = $user['available_bonus'] + $avatarPoints;

    echo "💵 잔액\n";
    echo "- 캐시 (available_bonus): ℙ{$user['available_bonus']}\n";
    echo "- 포인트 (avatar_points): ℙ{$avatarPoints}\n";
    echo "- 합계: ℙ{$totalBalance}\n";
    echo "\n";

    // 6. 출금
    $withdrawalStats = $db->selectOne("
        SELECT
            COALESCE(SUM(net_amount), 0) as total_withdrawn,
            COUNT(*) as withdrawal_count
        FROM withdrawals
        WHERE user_id = ?
        AND status = 'completed'
    ", [$user['id']]);

    echo "💸 출금\n";
    echo "- DB 조회: ℙ{$withdrawalStats['total_withdrawn']} ({$withdrawalStats['withdrawal_count']}건)\n";
    echo "- users.total_withdrawn: ℙ{$user['total_withdrawn']}\n";
    echo ($withdrawalStats['total_withdrawn'] == $user['total_withdrawn'] ? "✅ 일치\n" : "⚠️ 불일치\n");
    echo "\n";

    echo "====================================\n";
    echo "검증 완료\n";
    echo "====================================\n";

} catch (Exception $e) {
    echo "\n\n치명적 에러:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n</pre>";
?>
