<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>캐시 클리어</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #667eea;
            margin-top: 0;
            text-align: center;
        }
        .result {
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 16px;
            line-height: 1.6;
        }
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
        }
        .info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            font-weight: bold;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }
        .info-box strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 서버 캐시 클리어</h1>

<?php
$cleared = [];
$warnings = [];

// OpCache 클리어
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        $cleared[] = 'OpCache';
    } else {
        $warnings[] = 'OpCache를 클리어하지 못했습니다.';
    }
} else {
    $warnings[] = 'OpCache가 활성화되어 있지 않습니다.';
}

// APCu 클리어
if (function_exists('apcu_clear_cache')) {
    if (apcu_clear_cache()) {
        $cleared[] = 'APCu Cache';
    } else {
        $warnings[] = 'APCu Cache를 클리어하지 못했습니다.';
    }
} else {
    $warnings[] = 'APCu가 활성화되어 있지 않습니다.';
}

// Realpath Cache 클리어
if (function_exists('clearstatcache')) {
    clearstatcache(true);
    $cleared[] = 'Stat Cache';
}

// 세션 클리어 (선택적)
if (isset($_GET['clear_session']) && $_GET['clear_session'] === '1') {
    session_start();
    session_destroy();
    $cleared[] = 'Session Data';
}

// 결과 출력
if (!empty($cleared)) {
    echo '<div class="result success">';
    echo '<strong>✅ 캐시 클리어 성공!</strong><br><br>';
    echo '다음 캐시가 클리어되었습니다:<br>';
    echo '<ul>';
    foreach ($cleared as $cache) {
        echo "<li>{$cache}</li>";
    }
    echo '</ul>';
    echo '</div>';
}

if (!empty($warnings)) {
    echo '<div class="result warning">';
    echo '<strong>⚠️ 경고</strong><br><br>';
    foreach ($warnings as $warning) {
        echo "• {$warning}<br>";
    }
    echo '</div>';
}

// 추가 정보
echo '<div class="result info">';
echo '<strong>📌 다음 단계:</strong><br><br>';
echo '1. <strong>브라우저 캐시도 클리어하세요:</strong><br>';
echo '   • Chrome/Edge: Ctrl + Shift + Delete<br>';
echo '   • Firefox: Ctrl + Shift + Delete<br>';
echo '   • Safari: Cmd + Option + E<br><br>';
echo '2. <strong>강제 새로고침:</strong><br>';
echo '   • Windows: Ctrl + F5<br>';
echo '   • Mac: Cmd + Shift + R<br><br>';
echo '3. <strong>시크릿 모드로 테스트</strong> (권장)';
echo '</div>';

// 캐시 정보
echo '<div class="info-box">';
echo '<strong>현재 PHP 설정:</strong><br>';
echo '• OpCache 활성화: ' . (function_exists('opcache_reset') ? '✅ Yes' : '❌ No') . '<br>';
echo '• APCu 활성화: ' . (function_exists('apcu_clear_cache') ? '✅ Yes' : '❌ No') . '<br>';
echo '• PHP 버전: ' . phpversion() . '<br>';
echo '• 서버 시간: ' . date('Y-m-d H:i:s') . '<br>';
echo '</div>';
?>

        <button onclick="location.reload()">🔄 다시 클리어</button>
        <button onclick="window.location.href='?clear_session=1'" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); margin-top: 10px;">
            🗑️ 세션까지 클리어
        </button>

        <div class="info-box" style="margin-top: 20px; text-align: center;">
            <small>
                이 페이지는 작업 완료 후 삭제하거나 접근을 차단하는 것이 좋습니다.<br>
                <a href="CACHE_CLEAR_INSTRUCTIONS.md" style="color: #667eea;">📖 자세한 가이드 보기</a>
            </small>
        </div>
    </div>
</body>
</html>
