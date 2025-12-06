<?php
/**
 * OPcache 초기화
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP Cache 초기화 ===\n\n";

// OPcache 초기화
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✅ OPcache가 초기화되었습니다.\n";
    } else {
        echo "❌ OPcache 초기화 실패\n";
    }
} else {
    echo "ℹ️  OPcache가 활성화되어 있지 않습니다.\n";
}

echo "\n";

// 현재 OPcache 상태
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "OPcache 상태:\n";
    echo "- 활성화: " . ($status['opcache_enabled'] ? 'YES' : 'NO') . "\n";
    echo "- 캐시된 스크립트: " . ($status['opcache_statistics']['num_cached_scripts'] ?? 0) . "\n";
}

echo "\n=== 완료 ===\n";
echo "이제 조직도 페이지를 새로고침하세요.\n";
