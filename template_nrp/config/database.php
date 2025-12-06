<?php
/**
 * Database Configuration
 * Cafe24 MySQL Database Connection Settings
 */

// Cafe24 데이터베이스 연결 정보
define('DB_HOST', 'localhost');            // PHP에서는 localhost 사용
define('DB_PORT', '3306');                 // DB 포트 (보통 3306)
define('DB_NAME', 'ai77');                 // 데이터베이스명
define('DB_USER', 'ai77');                 // DB 사용자명
define('DB_PASS', 'Ai0505**ftd');          // DB 비밀번호
define('DB_CHARSET', 'utf8mb4');           // 문자셋

// 데이터베이스 연결 옵션
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// 애플리케이션 설정
define('APP_NAME', 'Neural Pulse Network');
define('APP_URL', 'http://neuralwe.com');  // 실제 도메인
define('APP_ENV', 'development');  // 디버깅을 위해 임시로 development로 변경

// 세션 설정
define('SESSION_LIFETIME', 7200);  // 2시간 (초 단위)
define('SESSION_NAME', 'neural_session');

// 이메일 인증 설정
define('VERIFICATION_CODE_LIFETIME', 600);  // 10분 (초 단위)

// SMTP 설정
// Gmail SMTP를 사용하려면:
// - SMTP_HOST: 'smtp.gmail.com'
// - SMTP_PORT: 587 (TLS) 또는 465 (SSL)
// - SMTP_USER: 'your-email@gmail.com'
// - SMTP_PASS: 'your-app-password' (2단계 인증 앱 비밀번호)
// - SMTP_SECURE: 'tls' 또는 'ssl'

// Cafe24 SMTP 또는 실제 도메인 SMTP 사용
define('SMTP_HOST', 'smtp.gmail.com');       // SMTP 서버 (또는 smtp.gmail.com, smtp.naver.com 등)
define('SMTP_PORT', 587);                      // SMTP 포트 (587=TLS, 465=SSL, 25=일반)
define('SMTP_SECURE', 'tls');                  // 보안 프로토콜 ('tls', 'ssl', 또는 '')
define('SMTP_AUTH', true);                     // SMTP 인증 사용 여부
define('SMTP_USER', 'utot9780@gmail.com');   // SMTP 인증 사용자명 (이메일 주소)
define('SMTP_PASS', 'ekqkwlwtksdiuipw');    // SMTP 비밀번호 (Gmail 앱 비밀번호)
define('SMTP_FROM_EMAIL', 'utot9780@gmail.com');  // 발신자 이메일 (Gmail SMTP 사용시 SMTP_USER와 동일해야 함!)
define('SMTP_FROM_NAME', 'AI BTC BOT');   // 발신자 이름
define('SMTP_REPLY_TO', 'utot9780@gmail.com');    // 답장 받을 이메일

// 보안 설정
define('BCRYPT_COST', 12);  // 비밀번호 해싱 비용 (10-12 권장)

// 지갑 주소 설정
define('SYSTEM_DEPOSIT_WALLET_TRC20', 'TSMZdowTbRC7wMKKkEjSL3LXss9coZW16Q');  // 패키지 구매 입금용 USDT(TRC20) 주소
// 사용자의 usdt_address는 BNB Smart Chain 형식 (출금용)

// API 응답 헤더 (API 파일에서만 설정)
// header('Content-Type: application/json; charset=utf-8');
// header('Access-Control-Allow-Origin: *');  // CORS 설정 (실제 운영시 도메인 지정)
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 에러 리포팅 (디버깅용 임시 활성화)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 타임존 설정
date_default_timezone_set('Asia/Seoul');

// Database 클래스 로드
require_once __DIR__ . '/../classes/Database.php';
