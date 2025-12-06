<?php
/**
 * 에러 로그 표시 페이지
 * http://localhost/show_error.php
 */

// 에러 표시 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP 설정 정보</h1>";

// Apache 에러 로그 경로
echo "<h2>에러 로그 경로:</h2>";
echo "<pre>";
echo "error_log: " . ini_get('error_log') . "\n";
echo "</pre>";

// 최근 에러 로그 읽기
$errorLogPath = ini_get('error_log');
if (!$errorLogPath) {
    // 기본 경로들 시도
    $possiblePaths = [
        'C:/xampp/apache/logs/error.log',
        'C:/wamp64/logs/apache_error.log',
        '/var/log/apache2/error.log',
        '/var/log/httpd/error_log'
    ];

    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $errorLogPath = $path;
            break;
        }
    }
}

echo "<h2>최근 에러 로그 (마지막 50줄):</h2>";
echo "<pre style='background:#f5f5f5; padding:20px; max-height:600px; overflow-y:scroll;'>";

if ($errorLogPath && file_exists($errorLogPath)) {
    $lines = file($errorLogPath);
    $lastLines = array_slice($lines, -50);
    echo htmlspecialchars(implode('', $lastLines));
} else {
    echo "에러 로그 파일을 찾을 수 없습니다.\n";
    echo "수동으로 확인하세요:\n";
    echo "- C:/xampp/apache/logs/error.log\n";
    echo "- C:/wamp64/logs/apache_error.log\n";
}

echo "</pre>";

// User.php 로드 테스트
echo "<h2>User.php 로드 테스트:</h2>";
echo "<pre>";
try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/classes/Database.php';
    require_once __DIR__ . '/classes/User.php';
    echo "✅ User.php 로드 성공\n";
} catch (Exception $e) {
    echo "❌ User.php 로드 실패:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
echo "</pre>";

// BonusDistributor.php 로드 테스트
echo "<h2>BonusDistributor.php 로드 테스트:</h2>";
echo "<pre>";
try {
    require_once __DIR__ . '/classes/BonusDistributor.php';
    echo "✅ BonusDistributor.php 로드 성공\n";
} catch (Exception $e) {
    echo "❌ BonusDistributor.php 로드 실패:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
echo "</pre>";
?>
