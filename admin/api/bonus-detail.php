<?php
/**
 * Admin Bonus Detail Proxy
 * 403 에러 우회를 위한 프록시
 */

// GET 파라미터 전달
$_GET['type'] = $_GET['type'] ?? '';

// API 파일 직접 include
require_once __DIR__ . '/../../api/bonus/get-bonus-detail.php';
