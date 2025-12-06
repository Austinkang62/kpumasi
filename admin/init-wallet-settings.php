<?php
/**
 * 지갑 설정 초기화 스크립트
 * settings 테이블에 지갑 주소 설정 추가
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

echo "<h1>지갑 설정 초기화</h1>\n";
echo "<pre>\n";

try {
    $db = Database::getInstance();

    echo "지갑 설정을 초기화합니다...\n\n";

    // settings 테이블 확인
    $tableCheck = $db->select("SHOW TABLES LIKE 'settings'");
    if (empty($tableCheck)) {
        echo "❌ settings 테이블이 없습니다.\n";
        echo "먼저 settings 테이블을 생성해야 합니다.\n";
        exit;
    }

    echo "✅ settings 테이블 확인\n\n";

    // 현재 지갑 설정 확인
    $existingSettings = $db->select("
        SELECT setting_key, setting_value
        FROM settings
        WHERE setting_key IN ('bsc_usdt_address', 'trc20_usdt_address')
    ");

    $existing = [];
    foreach ($existingSettings as $row) {
        $existing[$row['setting_key']] = $row['setting_value'];
    }

    echo "====================================\n";
    echo "현재 설정:\n";
    echo "====================================\n";
    echo "BSC USDT 주소: " . ($existing['bsc_usdt_address'] ?? '(없음)') . "\n";
    echo "TRC20 USDT 주소: " . ($existing['trc20_usdt_address'] ?? '(없음)') . "\n";
    echo "====================================\n\n";

    // 기본값 설정
    $defaultSettings = [
        'bsc_usdt_address' => '0x6d466363f42a6a3c51e2cf7ec4656ab2a83fc4c9',  // 사용자 제공 주소
        'trc20_usdt_address' => 'THafBjzPmaPQdQcLS1Fmp6sh7dYgYY1bq4'  // 기본값
    ];

    echo "다음 기본값으로 설정합니다:\n";
    echo "BSC USDT 주소: " . $defaultSettings['bsc_usdt_address'] . "\n";
    echo "TRC20 USDT 주소: " . $defaultSettings['trc20_usdt_address'] . "\n\n";

    // 확인
    if (php_sapi_name() !== 'cli') {
        $confirm = $_GET['confirm'] ?? '';
        if ($confirm !== 'yes') {
            echo "<a href='?confirm=yes' style='background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>";
            echo "예, 초기화하겠습니다 (클릭)";
            echo "</a>\n";
            echo "</pre>";
            exit;
        }
    }

    echo "초기화를 시작합니다...\n\n";

    $db->beginTransaction();

    try {
        // BSC USDT 주소 설정
        $db->execute("
            INSERT INTO settings (setting_key, setting_value, setting_type, description, created_at, updated_at)
            VALUES ('bsc_usdt_address', ?, 'string', 'BSC USDT 지갑 주소', NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                setting_value = ?,
                updated_at = NOW()
        ", [$defaultSettings['bsc_usdt_address'], $defaultSettings['bsc_usdt_address']]);

        echo "✅ BSC USDT 주소 설정: " . $defaultSettings['bsc_usdt_address'] . "\n";

        // TRC20 USDT 주소 설정
        $db->execute("
            INSERT INTO settings (setting_key, setting_value, setting_type, description, created_at, updated_at)
            VALUES ('trc20_usdt_address', ?, 'string', 'TRC20 USDT 지갑 주소', NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                setting_value = ?,
                updated_at = NOW()
        ", [$defaultSettings['trc20_usdt_address'], $defaultSettings['trc20_usdt_address']]);

        echo "✅ TRC20 USDT 주소 설정: " . $defaultSettings['trc20_usdt_address'] . "\n";

        $db->commit();

        echo "\n====================================\n";
        echo "✅ 완료!\n";
        echo "====================================\n";

        // 최종 확인
        $finalSettings = $db->select("
            SELECT setting_key, setting_value, updated_at
            FROM settings
            WHERE setting_key IN ('bsc_usdt_address', 'trc20_usdt_address')
        ");

        echo "\n최종 설정:\n";
        foreach ($finalSettings as $row) {
            echo sprintf("- %s: %s (업데이트: %s)\n",
                $row['setting_key'],
                $row['setting_value'],
                $row['updated_at']
            );
        }

    } catch (Exception $e) {
        $db->rollback();
        echo "\n❌ 롤백되었습니다.\n";
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
