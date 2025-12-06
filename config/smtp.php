<?php
/**
 * SMTP 설정
 * 이메일 발송을 위한 SMTP 서버 정보
 */

return [
    // SMTP 서버 정보
    'host' => 'smtp.gmail.com',  // Gmail SMTP 서버
    'port' => 587,               // TLS 포트 (또는 465 for SSL)
    'secure' => 'tls',           // 'tls' 또는 'ssl'

    // 인증 정보
    'username' => 'your-email@gmail.com',  // 발신 이메일
    'password' => 'your-app-password',     // 앱 비밀번호 (Gmail 2단계 인증 시)

    // 발신자 정보
    'from_email' => 'noreply@k-pumasi.com',
    'from_name' => 'K-Pumasi',

    // 개발 모드
    'debug_mode' => true,  // true: 콘솔에만 출력, false: 실제 이메일 발송
];

/*
 * Gmail 사용 시 설정 방법:
 * 1. Google 계정 > 보안 > 2단계 인증 활성화
 * 2. 앱 비밀번호 생성 (https://myaccount.google.com/apppasswords)
 * 3. 생성된 16자리 비밀번호를 위의 'password'에 입력
 *
 * 다른 SMTP 서버 예시:
 * - Naver: smtp.naver.com (465/SSL)
 * - Daum: smtp.daum.net (465/SSL)
 * - AWS SES: email-smtp.us-east-1.amazonaws.com (587/TLS)
 */
