<?php
/**
 * 아바타 parent_account_id 설정 스크립트
 * avatars 테이블의 parent_user_id를 기반으로 users 테이블의 parent_account_id 설정
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "<h1>아바타 Parent Account ID 설정</h1>\n";
echo "<pre>\n";

try {
    $db = Database::getInstance();

    // 1. avatars 테이블이 있는지 확인
    $tablesResult = $db->select("SHOW TABLES LIKE 'avatars'");

    if (empty($tablesResult)) {
        echo "❌ avatars 테이블이 존재하지 않습니다.\n";
        echo "\n대체 방법: referral_id를 기반으로 parent_account_id 설정\n\n";

        // referral_id를 기반으로 설정
        $avatars = $db->select("
            SELECT
                id,
                user_id,
                name,
                referral_id,
                parent_account_id
            FROM users
            WHERE name LIKE 'Avatar %'
            AND (deleted_at IS NULL OR deleted_at = '')
            ORDER BY created_at ASC
        ");

        echo "총 " . count($avatars) . "개의 아바타를 찾았습니다.\n\n";

        if (count($avatars) === 0) {
            echo "처리할 아바타가 없습니다.\n";
            exit;
        }

        echo "====================================\n";
        echo "다음 아바타들의 parent_account_id를 설정합니다:\n";
        echo "====================================\n";

        $needsUpdate = [];
        foreach ($avatars as $avatar) {
            if (empty($avatar['parent_account_id']) && !empty($avatar['referral_id'])) {
                // referral_id(내부 ID)로 부모 user_id 조회
                $parent = $db->selectOne("
                    SELECT user_id, name
                    FROM users
                    WHERE id = ?
                ", [$avatar['referral_id']]);

                if ($parent) {
                    $needsUpdate[] = [
                        'avatar' => $avatar,
                        'parent_user_id' => $parent['user_id'],
                        'parent_name' => $parent['name']
                    ];

                    echo sprintf("- %s (referral_id: %d) -> parent: %s (%s)\n",
                        $avatar['user_id'],
                        $avatar['referral_id'],
                        $parent['user_id'],
                        $parent['name']
                    );
                }
            }
        }

        echo "====================================\n";
        echo "업데이트 대상: " . count($needsUpdate) . "개\n\n";

        if (count($needsUpdate) === 0) {
            echo "✅ 모든 아바타의 parent_account_id가 이미 설정되어 있습니다.\n";
            exit;
        }

        // 확인
        echo "위 변경사항을 적용하시겠습니까?\n";

        if (php_sapi_name() !== 'cli') {
            $confirm = $_GET['confirm'] ?? '';
            if ($confirm !== 'yes') {
                echo "\n\n";
                echo "<a href='?confirm=yes' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>";
                echo "예, 설정하겠습니다 (클릭)";
                echo "</a>\n";
                echo "</pre>";
                exit;
            }
        }

        echo "\n\n업데이트를 시작합니다...\n\n";

        $db->beginTransaction();

        try {
            $updated = 0;

            foreach ($needsUpdate as $item) {
                $avatarId = $item['avatar']['id'];
                $parentUserId = $item['parent_user_id'];

                echo "처리 중: {$item['avatar']['user_id']} -> parent: {$parentUserId} ... ";

                $db->execute("
                    UPDATE users
                    SET parent_account_id = ?
                    WHERE id = ?
                ", [$parentUserId, $avatarId]);

                $updated++;
                echo "완료\n";
            }

            $db->commit();

            echo "\n====================================\n";
            echo "✅ 완료!\n";
            echo "====================================\n";
            echo "총 {$updated}개의 아바타 parent_account_id가 설정되었습니다.\n";

        } catch (Exception $e) {
            $db->rollback();
            echo "\n❌ 롤백되었습니다.\n";
            echo "에러: " . $e->getMessage() . "\n";
            throw $e;
        }

    } else {
        // avatars 테이블 기반 설정
        echo "✅ avatars 테이블을 찾았습니다.\n\n";

        // avatars 테이블에서 parent-child 관계 조회
        $avatarRelations = $db->select("
            SELECT
                a.parent_user_id,
                a.avatar_user_id,
                p.user_id as parent_user_id_str,
                c.user_id as child_user_id_str,
                p.name as parent_name,
                c.name as child_name
            FROM avatars a
            LEFT JOIN users p ON a.parent_user_id = p.id
            LEFT JOIN users c ON a.avatar_user_id = c.id
            WHERE a.status = 'active'
            ORDER BY a.created_at ASC
        ");

        echo "총 " . count($avatarRelations) . "개의 아바타 관계를 찾았습니다.\n\n";

        if (count($avatarRelations) === 0) {
            echo "처리할 아바타 관계가 없습니다.\n";
            exit;
        }

        echo "====================================\n";
        echo "다음 아바타들의 parent_account_id를 설정합니다:\n";
        echo "====================================\n";

        foreach ($avatarRelations as $rel) {
            echo sprintf("- 자식: %s (%s) -> 부모: %s (%s)\n",
                $rel['child_user_id_str'],
                $rel['child_name'],
                $rel['parent_user_id_str'],
                $rel['parent_name']
            );
        }

        echo "====================================\n\n";

        // 확인
        echo "위 변경사항을 적용하시겠습니까?\n";

        if (php_sapi_name() !== 'cli') {
            $confirm = $_GET['confirm'] ?? '';
            if ($confirm !== 'yes') {
                echo "\n\n";
                echo "<a href='?confirm=yes' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>";
                echo "예, 설정하겠습니다 (클릭)";
                echo "</a>\n";
                echo "</pre>";
                exit;
            }
        }

        echo "\n\n업데이트를 시작합니다...\n\n";

        $db->beginTransaction();

        try {
            $updated = 0;

            foreach ($avatarRelations as $rel) {
                $childUserId = $rel['child_user_id_str'];
                $parentInternalId = $rel['parent_user_id']; // 내부 ID (INT)
                $parentUserIdStr = $rel['parent_user_id_str'];

                if (empty($childUserId) || empty($parentInternalId)) {
                    echo "스킵: child={$childUserId}, parent_id={$parentInternalId} (NULL 값)\n";
                    continue;
                }

                echo "처리 중: {$childUserId} -> parent: {$parentUserIdStr} (ID: {$parentInternalId}) ... ";

                // parent_account_id에 내부 ID (INT) 설정
                $db->execute("
                    UPDATE users
                    SET parent_account_id = ?
                    WHERE user_id = ?
                ", [$parentInternalId, $childUserId]);

                $updated++;
                echo "완료\n";
            }

            $db->commit();

            echo "\n====================================\n";
            echo "✅ 완료!\n";
            echo "====================================\n";
            echo "총 {$updated}개의 아바타 parent_account_id가 설정되었습니다.\n";

        } catch (Exception $e) {
            $db->rollback();
            echo "\n❌ 롤백되었습니다.\n";
            echo "에러: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    // 최종 확인
    echo "\n\n최종 확인 (처음 10개 아바타):\n";
    echo "====================================\n";

    $finalCheck = $db->select("
        SELECT user_id, name, parent_account_id, created_at
        FROM users
        WHERE name LIKE 'Avatar %'
        AND (deleted_at IS NULL OR deleted_at = '')
        ORDER BY created_at ASC
        LIMIT 10
    ");

    foreach ($finalCheck as $ava) {
        echo sprintf("- %s (%s) - 부모: %s\n",
            $ava['user_id'],
            $ava['name'],
            $ava['parent_account_id'] ?: '없음 (루트 아바타)'
        );
    }

    echo "====================================\n";

} catch (Exception $e) {
    echo "\n\n치명적 에러:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n</pre>";
?>
