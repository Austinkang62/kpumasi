<?php
/**
 * 완전 초기화 스크립트
 * Sales, Bonuses, Avatars, Transactions 삭제 및 Users 테이블 보너스/포인트 초기화
 */

require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>완전 초기화</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #f8fafc;
            margin-bottom: 20px;
        }
        .warning {
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid #ef4444;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            color: #fca5a5;
        }
        .info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid #22c55e;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            color: #4ade80;
        }
        button {
            background: #ef4444;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            margin: 10px 5px;
        }
        button:hover {
            background: #dc2626;
        }
        button.safe {
            background: #3b82f6;
        }
        button.safe:hover {
            background: #2563eb;
        }
        pre {
            background: rgba(15, 23, 42, 0.8);
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <h1>🔄 데이터베이스 완전 초기화</h1>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
        try {
            $db->beginTransaction();

            // 1. Avatars 테이블에서 아바타 삭제
            $avatarResult = $db->execute("DELETE FROM avatars");
            $avatarsDeleted = $avatarResult;

            // 2. 아바타 사용자 삭제 (is_avatar = 1)
            $avatarUsersResult = $db->execute("DELETE FROM users WHERE is_avatar = 1");
            $avatarUsersDeleted = $avatarUsersResult;

            // 3. Sales 테이블 초기화
            $salesResult = $db->execute("DELETE FROM sales");
            $salesDeleted = $salesResult;

            // 4. Bonuses 테이블 초기화
            $bonusesResult = $db->execute("DELETE FROM bonuses");
            $bonusesDeleted = $bonusesResult;

            // 5. Transactions 테이블 초기화
            $transactionsResult = $db->execute("DELETE FROM transactions");
            $transactionsDeleted = $transactionsResult;

            // 6. Users 테이블 보너스 및 포인트 초기화 (일반 회원만)
            $db->execute("
                UPDATE users
                SET
                    avatar_points = 0,
                    total_bonus = 0,
                    available_bonus = 0,
                    total_referral_bonus = 0,
                    total_edge_bonus = 0,
                    total_matching_bonus = 0,
                    total_rollup_bonus = 0,
                    avatar_count = 0,
                    total_sales = 0,
                    package_id = NULL,
                    package_date = NULL
                WHERE is_avatar = 0
            ");

            $db->commit();

            echo '<div class="success">';
            echo '<h2>✅ 초기화 완료!</h2>';
            echo '<pre>';
            echo "삭제된 아바타 레코드: $avatarsDeleted 개\n";
            echo "삭제된 아바타 계정: $avatarUsersDeleted 개\n";
            echo "삭제된 매출 내역: $salesDeleted 건\n";
            echo "삭제된 보너스 내역: $bonusesDeleted 건\n";
            echo "삭제된 트랜잭션: $transactionsDeleted 건\n";
            echo "\n일반 회원들의 보너스 및 포인트가 모두 0으로 초기화되었습니다.";
            echo '</pre>';
            echo '<p><a href="debug_avatar_issue.php"><button class="safe">디버그 페이지로 확인하기</button></a></p>';
            echo '</div>';

        } catch (Exception $e) {
            $db->rollback();
            echo '<div class="warning">';
            echo '<h2>❌ 오류 발생</h2>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '</div>';
        }

    } else {
        // 현재 데이터 상태 확인
        $salesCount = $db->selectOne("SELECT COUNT(*) as cnt FROM sales")['cnt'];
        $bonusesCount = $db->selectOne("SELECT COUNT(*) as cnt FROM bonuses")['cnt'];
        $avatarsCount = $db->selectOne("SELECT COUNT(*) as cnt FROM avatars")['cnt'];
        $avatarUsersCount = $db->selectOne("SELECT COUNT(*) as cnt FROM users WHERE is_avatar = 1")['cnt'];
        $transactionsCount = $db->selectOne("SELECT COUNT(*) as cnt FROM transactions")['cnt'];

        $usersWithPoints = $db->selectOne("
            SELECT COUNT(*) as cnt FROM users
            WHERE (avatar_points > 0 OR total_bonus > 0) AND is_avatar = 0
        ")['cnt'];

        echo '<div class="warning">';
        echo '<h2>⚠️ 경고</h2>';
        echo '<p><strong>다음 데이터가 모두 삭제됩니다:</strong></p>';
        echo '<ul>';
        echo '<li>모든 매출 내역 (Sales): <strong>' . $salesCount . '건</strong></li>';
        echo '<li>모든 보너스 내역 (Bonuses): <strong>' . $bonusesCount . '건</strong></li>';
        echo '<li>모든 아바타 (Avatars): <strong>' . $avatarsCount . '개</strong></li>';
        echo '<li>모든 아바타 계정 (Users - is_avatar=1): <strong>' . $avatarUsersCount . '개</strong></li>';
        echo '<li>모든 트랜잭션 (Transactions): <strong>' . $transactionsCount . '건</strong></li>';
        echo '</ul>';
        echo '<p><strong>다음 필드가 0으로 초기화됩니다:</strong></p>';
        echo '<ul>';
        echo '<li>일반 회원 ' . $usersWithPoints . '명의 avatar_points, total_bonus, available_bonus 등</li>';
        echo '<li>package_id, package_date도 초기화됩니다</li>';
        echo '</ul>';
        echo '<p style="color: #fca5a5; font-weight: bold;">⚠️ 이 작업은 되돌릴 수 없습니다!</p>';
        echo '</div>';

        echo '<div class="info">';
        echo '<h3>초기화 전에 확인하세요:</h3>';
        echo '<ol>';
        echo '<li>Users 테이블의 회원 정보는 유지됩니다 (삭제되지 않음)</li>';
        echo '<li>Referral 구조(추천인 관계)는 유지됩니다</li>';
        echo '<li>Organization 구조(바이너리 트리)는 유지됩니다</li>';
        echo '<li>아바타 계정(is_avatar=1)만 완전히 삭제됩니다</li>';
        echo '</ol>';
        echo '</div>';

        echo '<form method="POST">';
        echo '<input type="hidden" name="confirm_reset" value="1">';
        echo '<button type="submit">🔴 확인 - 모든 데이터 초기화 실행</button>';
        echo '<button type="button" class="safe" onclick="window.location.href=\'debug_avatar_issue.php\'">취소 - 디버그 페이지로 돌아가기</button>';
        echo '</form>';
    }
    ?>

</body>
</html>
