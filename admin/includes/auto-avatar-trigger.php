<?php
/**
 * 자동 아바타 생성 트리거
 * Admin 페이지 로드 시 자동으로 포함됨
 * 마지막 실행 후 4시간 경과 시 백그라운드로 아바타 생성
 */

// 이미 실행 중이면 중복 실행 방지
if (defined('AUTO_AVATAR_TRIGGER_LOADED')) {
    return;
}
define('AUTO_AVATAR_TRIGGER_LOADED', true);

// 마지막 실행 시간 파일
$lastRunFile = __DIR__ . '/../.avatar_last_run';
$lockFile = __DIR__ . '/../.avatar_lock';

// Lock 파일이 있으면 이미 실행 중
if (file_exists($lockFile)) {
    // Lock 파일이 30분 이상 오래되었으면 삭제 (비정상 종료 대비)
    if (time() - filemtime($lockFile) > 1800) {
        @unlink($lockFile);
    } else {
        return; // 실행 중이므로 종료
    }
}

// 마지막 실행 시간 확인
$lastRun = 0;
if (file_exists($lastRunFile)) {
    $lastRun = (int)file_get_contents($lastRunFile);
}

$currentTime = time();
$timeSinceLastRun = $currentTime - $lastRun;
$fourHours = 4 * 60 * 60; // 4시간 = 14400초

// 4시간 경과했는지 확인
if ($timeSinceLastRun < $fourHours) {
    return; // 아직 4시간 안 지남
}

// 백그라운드로 실행 (사용자 대기 없음)
$url = 'https://' . $_SERVER['HTTP_HOST'] . '/admin/avatar-auto-execute.php?key=auto_trigger_2024';

// Lock 파일 생성
file_put_contents($lockFile, $currentTime);

// cURL로 백그라운드 실행 (비동기)
if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); // 1초만 대기
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    @curl_exec($ch);
    curl_close($ch);
} else {
    // cURL 없으면 file_get_contents로 실행
    $context = stream_context_create([
        'http' => [
            'timeout' => 1,
            'ignore_errors' => true
        ]
    ]);
    @file_get_contents($url, false, $context);
}

// 마지막 실행 시간 업데이트
file_put_contents($lastRunFile, $currentTime);

// Lock 파일은 실행 스크립트에서 삭제됨
?>
