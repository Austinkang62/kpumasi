<?php
/**
 * pumasi 아이디를 pumasiai로 변경
 * 8자리 규칙 준수를 위한 변경 스크립트
 */

require_once __DIR__ . '/../config/database.php';

echo "=================================================\n";
echo "pumasi → pumasiai 변경 스크립트\n";
echo "=================================================\n\n";

try {
    $db = Database::getInstance();

    // 1. pumasi 계정 확인
    echo "1. pumasi 계정 확인 중...\n";
    $user = $db->selectOne("SELECT * FROM users WHERE user_id = 'pumasi'");

    if (!$user) {
        echo "❌ pumasi 계정을 찾을 수 없습니다.\n";
        exit;
    }

    echo "✅ pumasi 계정 발견:\n";
    echo "   - ID: {$user['id']}\n";
    echo "   - 이름: {$user['name']}\n";
    echo "   - 이메일: {$user['email']}\n";
    echo "   - 패키지: {$user['package_id']}\n\n";

    // 2. pumasiai 아이디가 이미 존재하는지 확인
    echo "2. pumasiai 아이디 중복 확인...\n";
    $existingPumasiai = $db->selectOne("SELECT * FROM users WHERE user_id = 'pumasiai'");

    if ($existingPumasiai) {
        echo "❌ pumasiai 아이디가 이미 존재합니다!\n";
        echo "   기존 계정을 먼저 처리해야 합니다.\n";
        exit;
    }
    echo "✅ pumasiai 아이디 사용 가능\n\n";

    // 3. sponsor_id로 pumasi를 참조하는 계정 확인
    echo "3. pumasi를 sponsor로 가진 계정 확인...\n";
    $sponsoredUsers = $db->select("SELECT user_id, name FROM users WHERE sponsor_id = 'pumasi'");

    if (count($sponsoredUsers) > 0) {
        echo "✅ pumasi를 sponsor로 가진 계정: " . count($sponsoredUsers) . "개\n";
        foreach ($sponsoredUsers as $su) {
            echo "   - {$su['user_id']} ({$su['name']})\n";
        }
    } else {
        echo "   pumasi를 sponsor로 가진 계정이 없습니다.\n";
    }
    echo "\n";

    // 4. 변경 확인
    echo "=================================================\n";
    echo "변경 예정 내용:\n";
    echo "=================================================\n";
    echo "1. users.user_id: 'pumasi' → 'pumasiai'\n";
    if (count($sponsoredUsers) > 0) {
        echo "2. users.sponsor_id: 'pumasi' → 'pumasiai' (" . count($sponsoredUsers) . "개 레코드)\n";
    }
    echo "\n⚠️  주의: 이 작업은 되돌릴 수 없습니다!\n";
    echo "=================================================\n\n";

    // 5. 사용자 확인 (CLI 환경)
    if (php_sapi_name() === 'cli') {
        echo "계속하시겠습니까? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);

        if (strtolower($confirmation) !== 'yes') {
            echo "\n❌ 작업이 취소되었습니다.\n";
            exit;
        }
    } else {
        // 웹 환경에서는 GET 파라미터로 확인
        if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
            echo "<h2>변경 확인</h2>";
            echo "<p>위 내용을 확인하고 <a href='?confirm=yes' style='color: red; font-weight: bold;'>여기를 클릭</a>하여 변경을 진행하세요.</p>";
            exit;
        }
    }

    echo "\n6. 변경 작업 시작...\n\n";

    // 트랜잭션 시작
    $db->beginTransaction();

    try {
        // 6-1. users.user_id 변경
        echo "   6-1. users.user_id 변경 중...\n";
        $affected1 = $db->execute("UPDATE users SET user_id = 'pumasiai' WHERE user_id = 'pumasi'");
        echo "   ✅ {$affected1}개 레코드 변경됨\n\n";

        // 6-2. users.sponsor_id 변경
        if (count($sponsoredUsers) > 0) {
            echo "   6-2. users.sponsor_id 변경 중...\n";
            $affected2 = $db->execute("UPDATE users SET sponsor_id = 'pumasiai' WHERE sponsor_id = 'pumasi'");
            echo "   ✅ {$affected2}개 레코드 변경됨\n\n";
        }

        // 커밋
        $db->commit();

        echo "=================================================\n";
        echo "✅ 변경 완료!\n";
        echo "=================================================\n\n";

        // 7. 결과 확인
        echo "7. 변경 결과 확인...\n";
        $updatedUser = $db->selectOne("SELECT user_id, name, email FROM users WHERE user_id = 'pumasiai'");

        if ($updatedUser) {
            echo "✅ pumasiai 계정 확인:\n";
            echo "   - user_id: {$updatedUser['user_id']}\n";
            echo "   - 이름: {$updatedUser['name']}\n";
            echo "   - 이메일: {$updatedUser['email']}\n\n";
        }

        $oldUser = $db->selectOne("SELECT user_id FROM users WHERE user_id = 'pumasi'");
        if (!$oldUser) {
            echo "✅ pumasi 계정이 더 이상 존재하지 않습니다.\n\n";
        }

        if (count($sponsoredUsers) > 0) {
            $updatedSponsors = $db->select("SELECT user_id, sponsor_id FROM users WHERE sponsor_id = 'pumasiai'");
            echo "✅ sponsor_id가 pumasiai로 변경된 계정: " . count($updatedSponsors) . "개\n\n";
        }

        echo "=================================================\n";
        echo "🎉 모든 작업이 성공적으로 완료되었습니다!\n";
        echo "=================================================\n";

    } catch (Exception $e) {
        // 롤백
        $db->rollback();
        echo "\n❌ 오류 발생! 모든 변경사항이 롤백되었습니다.\n";
        echo "오류 메시지: " . $e->getMessage() . "\n";
        throw $e;
    }

} catch (Exception $e) {
    echo "\n❌ 스크립트 실행 오류:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
