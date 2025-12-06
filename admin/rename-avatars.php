<?php
/**
 * 기존 아바타 계정 이름 변경 스크립트
 * TWMN8579_avatar_1 형식 -> AVA00001 형식으로 변경
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "<h1>아바타 계정 이름 변경</h1>\n";
echo "<pre>\n";

try {
    $db = Database::getInstance();

    // 1. 기존 아바타 조회 (이름이 'Avatar'로 시작하는 모든 계정)
    $avatars = $db->select("
        SELECT id, user_id, name, parent_account_id, created_at
        FROM users
        WHERE name LIKE 'Avatar %'
        AND (deleted_at IS NULL OR deleted_at = '')
        ORDER BY created_at ASC
    ");

    echo "총 " . count($avatars) . "개의 아바타를 찾았습니다.\n\n";

    if (count($avatars) === 0) {
        echo "변경할 아바타가 없습니다.\n";
        exit;
    }

    // 2. 사용자 확인
    echo "====================================\n";
    echo "다음 아바타들의 user_id를 변경합니다:\n";
    echo "====================================\n";
    foreach ($avatars as $i => $avatar) {
        $newUserId = 'AVA' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
        echo sprintf("%d. %s -> %s (이름: %s)\n",
            $i + 1,
            $avatar['user_id'],
            $newUserId,
            $avatar['name']
        );
    }
    echo "====================================\n\n";

    // 3. 실행 확인
    echo "위 변경사항을 적용하시겠습니까?\n";
    echo "계속하려면 'yes'를 입력하세요: ";

    // CLI 모드 체크
    if (php_sapi_name() === 'cli') {
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $confirm = trim($line);
        fclose($handle);
    } else {
        // 웹 모드: URL 파라미터로 확인
        $confirm = $_GET['confirm'] ?? '';
        if ($confirm !== 'yes') {
            echo "\n\n";
            echo "<a href='?confirm=yes' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>";
            echo "예, 변경하겠습니다 (클릭)";
            echo "</a>\n";
            echo "</pre>";
            exit;
        }
    }

    if ($confirm !== 'yes') {
        echo "\n작업이 취소되었습니다.\n";
        exit;
    }

    echo "\n\n변경 작업을 시작합니다...\n\n";

    // 4. 트랜잭션 시작
    $db->beginTransaction();

    try {
        $updated = 0;
        $errors = [];

        foreach ($avatars as $i => $avatar) {
            $oldUserId = $avatar['user_id'];
            $newUserId = 'AVA' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
            $newName = 'Avatar ' . ($i + 1);
            $newEmail = $newUserId . '@avatar.local';

            echo "처리 중: {$oldUserId} -> {$newUserId} ... ";

            try {
                // users 테이블 업데이트
                $db->execute("
                    UPDATE users
                    SET user_id = ?, name = ?, email = ?
                    WHERE id = ?
                ", [$newUserId, $newName, $newEmail, $avatar['id']]);

                // 다른 테이블에서 이 아바타를 참조하는 레코드 업데이트

                // 1) users 테이블의 sponsor_id 업데이트
                $db->execute("
                    UPDATE users
                    SET sponsor_id = ?
                    WHERE sponsor_id = ?
                ", [$newUserId, $oldUserId]);

                // 2) users 테이블의 parent_account_id 업데이트
                $db->execute("
                    UPDATE users
                    SET parent_account_id = ?
                    WHERE parent_account_id = ?
                ", [$newUserId, $oldUserId]);

                // 3) bonuses 테이블 업데이트 (있다면)
                $db->execute("
                    UPDATE bonuses
                    SET to_user_id = ?
                    WHERE to_user_id = ?
                ", [$newUserId, $oldUserId]);

                $db->execute("
                    UPDATE bonuses
                    SET from_user_id = ?
                    WHERE from_user_id = ?
                ", [$newUserId, $oldUserId]);

                $updated++;
                echo "완료\n";

            } catch (Exception $e) {
                $errors[] = "ERROR: {$oldUserId} - " . $e->getMessage();
                echo "실패: " . $e->getMessage() . "\n";
            }
        }

        // 5. 커밋
        $db->commit();

        echo "\n====================================\n";
        echo "변경 완료!\n";
        echo "====================================\n";
        echo "총 {$updated}개의 아바타가 변경되었습니다.\n";

        if (count($errors) > 0) {
            echo "\n에러 발생:\n";
            foreach ($errors as $error) {
                echo "  - {$error}\n";
            }
        }

        echo "\n\n최종 확인:\n";
        $finalCheck = $db->select("
            SELECT user_id, name, parent_account_id, created_at
            FROM users
            WHERE name LIKE 'Avatar %'
            AND (deleted_at IS NULL OR deleted_at = '')
            ORDER BY created_at ASC
            LIMIT 10
        ");

        echo "처음 10개 아바타:\n";
        foreach ($finalCheck as $ava) {
            echo sprintf("  - %s (%s) - 부모: %s\n",
                $ava['user_id'],
                $ava['name'],
                $ava['parent_account_id'] ?: '없음'
            );
        }

    } catch (Exception $e) {
        $db->rollback();
        echo "\n\n롤백되었습니다.\n";
        echo "에러: " . $e->getMessage() . "\n";
        throw $e;
    }

} catch (Exception $e) {
    echo "\n\n치명적 에러:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n</pre>";
?>
