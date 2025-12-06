-- --------------------------------------------------------
-- K-Pumasi Database Schema
-- Premium Network Platform - 바이너리 시스템
-- --------------------------------------------------------

-- 호스트: ai22.mycafe24.com
-- 서버: MariaDB 10.1.13
-- 문자셋: UTF-8
-- 프로젝트: K-Pumasi (추천기준 바이너리 네트워크 플랫폼)
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- --------------------------------------------------------
-- 1. 회원 관리 테이블
-- --------------------------------------------------------

-- 회원 정보 (메인 테이블)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL COMMENT '사용자 아이디',
  `password` varchar(255) NOT NULL COMMENT '암호화된 비밀번호 (bcrypt)',
  `name` varchar(100) NOT NULL COMMENT '이름',
  `email` varchar(100) NOT NULL COMMENT '이메일',
  `phone` varchar(20) DEFAULT NULL COMMENT '전화번호',
  `usdt_address` varchar(100) DEFAULT NULL COMMENT 'USDT 입금 주소 (TRC20)',
  `bnb_address` varchar(100) DEFAULT NULL COMMENT 'BNB 출금 주소 (Smart Chain)',

  -- 조직도 관계
  `referral_id` int(11) unsigned DEFAULT NULL COMMENT '추천인 ID (직접 추천)',
  `sponsor_id` varchar(50) DEFAULT NULL COMMENT '후원인 user_id (바이너리 트리)',
  `sponsor_position` tinyint(1) DEFAULT NULL COMMENT '후원인 하위 위치: 1=좌측, 2=우측',

  -- 패키지 정보
  `package_id` tinyint(1) DEFAULT '0' COMMENT '패키지: 0=없음, 1=$50, 2=$100',
  `package_date` datetime DEFAULT NULL COMMENT '패키지 구매일',

  -- 인증 및 권한
  `email_verified` tinyint(1) DEFAULT '0' COMMENT '이메일 인증 여부',
  `verification_code` varchar(10) DEFAULT NULL COMMENT '인증 코드',
  `role` enum('user','admin','super') DEFAULT 'user' COMMENT '권한',
  `status` enum('active','inactive','suspended') DEFAULT 'active' COMMENT '상태',

  -- 아바타 시스템
  `is_avatar` tinyint(1) DEFAULT '0' COMMENT '아바타 계정 여부',
  `parent_user_id` int(11) unsigned DEFAULT NULL COMMENT '아바타의 부모 계정 ID (구버전)',
  `parent_account_id` int(11) unsigned DEFAULT NULL COMMENT '아바타 소유자 ID (신버전)',
  `avatar_count` int(11) DEFAULT '0' COMMENT '생성된 아바타 수',
  `avatar_points` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '아바타 포인트 (보너스의 35%)',

  -- 재무 정보
  `total_bonus` decimal(15,2) DEFAULT '0.00' COMMENT '누적 보너스 (전체)',
  `available_bonus` decimal(15,2) DEFAULT '0.00' COMMENT '출금 가능 보너스 (캐시 65%)',
  `total_sales` decimal(15,2) DEFAULT '0.00' COMMENT '누적 매출',
  `total_withdrawn` decimal(15,2) DEFAULT '0.00' COMMENT '총 출금액',
  `debt_amount` decimal(15,2) DEFAULT '0.00' COMMENT '외상 금액 (출금 시 차감)',
  `credit_sale_amount` decimal(10,2) DEFAULT '0.00' COMMENT '외상매출 금액',
  `withdrawal_hold` tinyint(1) DEFAULT '0' COMMENT '출금홀딩 (0=해제, 1=홀딩)',
  `last_withdrawal_date` datetime DEFAULT NULL COMMENT '마지막 출금일',

  -- 보너스 타입별 누적
  `total_referral_bonus` decimal(15,2) DEFAULT '0.00' COMMENT '누적 추천 보너스',
  `total_edge_bonus` decimal(15,2) DEFAULT '0.00' COMMENT '누적 엣지 보너스',
  `total_matching_bonus` decimal(15,2) DEFAULT '0.00' COMMENT '누적 매칭 보너스',
  `total_rollup_bonus` decimal(15,2) DEFAULT '0.00' COMMENT '누적 롤업 보너스',

  -- 추천 정보
  `direct_referrals` int(11) DEFAULT '0' COMMENT '직접 추천 수',

  -- 계정 그룹 (다계정 시스템)
  `is_main_account` tinyint(1) DEFAULT '1' COMMENT '메인 계정 여부 (1=메인, 0=서브)',
  `account_group` varchar(8) DEFAULT NULL COMMENT '계정 그룹 코드 (메인계정의 user_id)',
  `account_count` tinyint(2) DEFAULT '1' COMMENT '등록한 총 계정 개수 (1~7)',

  -- 시스템 정보
  `memo` text COMMENT '관리자 메모',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '가입일',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
  `deleted_at` datetime DEFAULT NULL COMMENT '삭제 일시 (소프트 삭제)',
  `deleted_by` varchar(50) DEFAULT NULL COMMENT '삭제한 관리자',
  `delete_reason` text COMMENT '삭제 사유',

  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `referral_id` (`referral_id`),
  KEY `sponsor_id` (`sponsor_id`),
  KEY `sponsor_position` (`sponsor_position`),
  KEY `package_id` (`package_id`),
  KEY `parent_user_id` (`parent_user_id`),
  KEY `status` (`status`),
  KEY `is_avatar` (`is_avatar`),
  KEY `idx_parent_account` (`parent_account_id`),
  KEY `idx_account_group` (`account_group`),
  KEY `idx_is_main` (`is_main_account`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_withdrawal_hold` (`withdrawal_hold`),
  KEY `idx_credit_sale` (`credit_sale_amount`),
  KEY `idx_debt_amount` (`debt_amount`),
  CONSTRAINT `fk_parent_account` FOREIGN KEY (`parent_account_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='회원 정보';

-- --------------------------------------------------------
-- 2. 보너스 시스템 테이블
-- --------------------------------------------------------

-- 보너스 지급 내역 (4가지 보너스)
CREATE TABLE IF NOT EXISTS `bonuses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '보너스 받는 사람 ID',
  `from_user_id` int(11) unsigned NOT NULL COMMENT '보너스 발생시킨 사람 ID (신규 가입자)',
  `bonus_type` enum('direct','binary','avatar','referral','edge','matching','rollup') DEFAULT 'binary' COMMENT '보너스 타입',
  `payment_type` enum('cash','avatar_point') DEFAULT 'cash' COMMENT '지급 타입: cash(65%), avatar_point(35%)',
  `amount` decimal(10,2) NOT NULL COMMENT '보너스 금액',
  `package_amount` decimal(10,2) NOT NULL COMMENT '신규 회원 패키지 금액',
  `level` tinyint(2) DEFAULT NULL COMMENT '롤업 레벨 (1-25), 다른 보너스는 NULL',
  `edge_position` enum('left','right') DEFAULT NULL COMMENT '엣지 보너스 위치 (좌/우)',
  `related_bonus_id` int(11) unsigned DEFAULT NULL COMMENT '연관 보너스 ID (매칭 보너스용)',
  `status` enum('pending','paid','cancelled') DEFAULT 'paid' COMMENT '상태',
  `description` varchar(255) DEFAULT NULL COMMENT '보너스 설명',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '지급일',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `from_user_id` (`from_user_id`),
  KEY `bonus_type` (`bonus_type`),
  KEY `payment_type` (`payment_type`),
  KEY `level` (`level`),
  KEY `created_at` (`created_at`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='보너스 지급 내역 (4가지 보너스: 추천, 엣지, 매칭, 롤업)';

-- 보너스 집계 테이블
CREATE TABLE IF NOT EXISTS `bonus_summary` (
  `user_id` int(11) unsigned NOT NULL COMMENT '회원 ID',
  `total_referral_bonus` decimal(15,2) DEFAULT '0.00' COMMENT '누적 추천 보너스',
  `total_edge_bonus` decimal(15,2) DEFAULT '0.00' COMMENT '누적 엣지 보너스',
  `total_matching_bonus` decimal(15,2) DEFAULT '0.00' COMMENT '누적 매칭 보너스',
  `total_rollup_bonus` decimal(15,2) DEFAULT '0.00' COMMENT '누적 롤업 보너스',
  `referral_count` int(11) DEFAULT '0' COMMENT '추천 보너스 건수',
  `edge_count` int(11) DEFAULT '0' COMMENT '엣지 보너스 건수',
  `matching_count` int(11) DEFAULT '0' COMMENT '매칭 보너스 건수',
  `rollup_count` int(11) DEFAULT '0' COMMENT '롤업 보너스 건수',
  `last_bonus_at` timestamp NULL DEFAULT NULL COMMENT '마지막 보너스 수령일',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '마지막 업데이트',
  PRIMARY KEY (`user_id`),
  KEY `last_bonus_at` (`last_bonus_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='보너스 집계 테이블';

-- --------------------------------------------------------
-- 3. 조직도 테이블
-- --------------------------------------------------------

-- Binary Tree 조직도
CREATE TABLE IF NOT EXISTS `organization` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '회원 ID',
  `parent_id` int(11) unsigned DEFAULT NULL COMMENT '부모 노드 ID',
  `left_child` int(11) unsigned DEFAULT NULL COMMENT '왼쪽 자식 ID',
  `right_child` int(11) unsigned DEFAULT NULL COMMENT '오른쪽 자식 ID',
  `position` enum('left','right','root') DEFAULT 'root' COMMENT '부모의 어느 쪽 자식인지',
  `level` int(11) DEFAULT '1' COMMENT '조직도 레벨 (1=루트)',
  `left_count` int(11) DEFAULT '0' COMMENT '왼쪽 하위 멤버 수',
  `right_count` int(11) DEFAULT '0' COMMENT '오른쪽 하위 멤버 수',
  `total_downline` int(11) DEFAULT '0' COMMENT '전체 하위 멤버 수',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `parent_id` (`parent_id`),
  KEY `left_child` (`left_child`),
  KEY `right_child` (`right_child`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Binary Tree 조직도';

-- --------------------------------------------------------
-- 4. 아바타 시스템
-- --------------------------------------------------------

-- 아바타 계정
CREATE TABLE IF NOT EXISTS `avatars` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_user_id` int(11) unsigned NOT NULL COMMENT '부모 계정 ID (아바타 소유자)',
  `avatar_user_id` int(11) unsigned NOT NULL COMMENT '아바타 계정 ID',
  `trigger_amount` decimal(15,2) NOT NULL COMMENT '트리거 금액 (아바타 생성 시점 APT 100)',
  `package_id` tinyint(1) unsigned NOT NULL COMMENT '패키지 ID',
  `total_earned` decimal(15,2) DEFAULT '0.00' COMMENT '누적 수익',
  `btc_accumulated` decimal(15,8) DEFAULT '0.00000000' COMMENT '누적 BTC',
  `status` enum('active','completed','inactive') DEFAULT 'active' COMMENT '상태',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
  PRIMARY KEY (`id`),
  KEY `parent_user_id` (`parent_user_id`),
  KEY `avatar_user_id` (`avatar_user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='아바타 계정 관리';

-- --------------------------------------------------------
-- 5. 매출/패키지 관리
-- --------------------------------------------------------

-- 패키지 정보
CREATE TABLE IF NOT EXISTS `packages` (
  `id` tinyint(1) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '패키지명',
  `price` decimal(10,2) NOT NULL COMMENT '가격 (USDT)',
  `commission_level_1_5` decimal(10,2) NOT NULL COMMENT '레벨 1-5 커미션',
  `commission_level_6_15` decimal(10,2) NOT NULL COMMENT '레벨 6-15 커미션',
  `avatar_trigger` decimal(10,2) NOT NULL COMMENT '아바타 생성 기준액',
  `avatar_multiplier` tinyint(1) NOT NULL COMMENT '생성되는 아바타 수',
  `max_earning` decimal(15,2) NOT NULL COMMENT '최대 수익',
  `status` enum('active','inactive') DEFAULT 'active' COMMENT '상태',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='패키지 정보';

-- 패키지 구매 내역
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '구매자 ID',
  `package_id` tinyint(1) unsigned NOT NULL COMMENT '패키지 ID',
  `amount` decimal(10,2) NOT NULL COMMENT '구매 금액',
  `payment_method` varchar(20) DEFAULT 'USDT_TRC20' COMMENT '결제 수단',
  `payment_address` varchar(100) DEFAULT NULL COMMENT '입금 주소',
  `txid` varchar(100) DEFAULT NULL COMMENT '트랜잭션 ID',
  `status` enum('pending','confirmed','completed','failed','cancelled') DEFAULT 'pending' COMMENT '상태',
  `is_upgrade` tinyint(1) DEFAULT '0' COMMENT '업그레이드 여부 ($50+$50=$100)',
  `confirmed_at` datetime DEFAULT NULL COMMENT '확인 시간',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '구매 시간',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `package_id` (`package_id`),
  KEY `status` (`status`),
  KEY `txid` (`txid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='패키지 구매 내역';

-- --------------------------------------------------------
-- 6. 출금 시스템
-- --------------------------------------------------------

-- 출금 내역
CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '출금 신청자 ID',
  `amount` decimal(15,2) NOT NULL COMMENT '출금 금액',
  `fee` decimal(15,2) DEFAULT '0.00' COMMENT '수수료',
  `net_amount` decimal(15,2) NOT NULL COMMENT '실 출금액 (금액-수수료)',
  `currency` varchar(10) DEFAULT 'USDT' COMMENT '출금 통화',
  `withdrawal_address` varchar(100) NOT NULL COMMENT '출금 주소',
  `network` varchar(20) DEFAULT 'BNB_SMART_CHAIN' COMMENT '네트워크',
  `status` enum('pending','approved','processing','completed','rejected','cancelled') DEFAULT 'pending' COMMENT '상태',
  `txid` varchar(100) DEFAULT NULL COMMENT '출금 트랜잭션 ID',
  `admin_note` text COMMENT '관리자 메모',
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '신청일',
  `approved_at` datetime DEFAULT NULL COMMENT '승인일',
  `completed_at` datetime DEFAULT NULL COMMENT '완료일',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `requested_at` (`requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='출금 내역';

-- --------------------------------------------------------
-- 7. 관리자 시스템
-- --------------------------------------------------------

-- 관리자 계정
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '관리자 아이디',
  `password` varchar(255) NOT NULL COMMENT '비밀번호 (bcrypt)',
  `email` varchar(100) NOT NULL COMMENT '이메일',
  `full_name` varchar(100) DEFAULT NULL COMMENT '관리자 이름',
  `role` enum('super_admin','admin','manager') DEFAULT 'admin' COMMENT '권한 레벨',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '활성화 여부',
  `last_login` timestamp NULL DEFAULT NULL COMMENT '마지막 로그인',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 admin_id',
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='관리자 계정 테이블';

-- 관리자 세션
CREATE TABLE IF NOT EXISTS `admin_sessions` (
  `session_id` varchar(255) NOT NULL,
  `admin_id` int(11) NOT NULL COMMENT '관리자 ID',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP 주소',
  `user_agent` text COMMENT '브라우저 정보',
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '마지막 활동',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '만료 시간',
  PRIMARY KEY (`session_id`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='관리자 세션';

-- 관리자 활동 로그
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) unsigned NOT NULL COMMENT '관리자 ID',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '액션',
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '대상 타입',
  `target_id` int(11) unsigned DEFAULT NULL COMMENT '대상 ID',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '상세 설명',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP 주소',
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User Agent',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '시간',
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='관리자 활동 로그';

-- --------------------------------------------------------
-- 8. 회원가입 및 인증
-- --------------------------------------------------------

-- 회원가입 승인 대기
CREATE TABLE IF NOT EXISTS `pending_registrations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL COMMENT '이메일',
  `password` varchar(255) NOT NULL COMMENT '암호화된 비밀번호',
  `txid` varchar(100) NOT NULL COMMENT 'USDT 입금 트랜잭션 ID',
  `network` varchar(20) DEFAULT 'TRC20' COMMENT '네트워크 (TRC20, BSC 등)',
  `payment_amount` decimal(10,2) DEFAULT '100.00' COMMENT '입금 금액',
  `referral_id` varchar(50) DEFAULT NULL COMMENT '추천인 코드',
  `sponsor_id` varchar(50) DEFAULT NULL COMMENT '후원인 코드',
  `sponsor_position` tinyint(1) DEFAULT NULL COMMENT '후원 위치 (1=좌측, 2=우측)',
  `status` enum('pending','approved','rejected') DEFAULT 'pending' COMMENT '승인 상태',
  `admin_note` text COMMENT '관리자 메모',
  `approved_by` varchar(50) DEFAULT NULL COMMENT '승인/거절한 관리자',
  `approved_at` datetime DEFAULT NULL COMMENT '승인/거절 일시',
  `created_user_id` varchar(50) DEFAULT NULL COMMENT '승인 후 생성된 회원코드',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '신청일',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
  `memo` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `txid` (`txid`),
  KEY `email` (`email`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  KEY `idx_status_created` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='회원가입 승인 대기';

-- 이메일 인증
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '이메일',
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '인증 코드',
  `expires_at` datetime NOT NULL COMMENT '만료 시간',
  `verified` tinyint(1) DEFAULT '0' COMMENT '인증 여부',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `code` (`code`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='이메일 인증';

-- --------------------------------------------------------
-- 9. 세션 및 트랜잭션
-- --------------------------------------------------------

-- 세션 관리 (사용자)
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '사용자 ID',
  `token` varchar(255) NOT NULL COMMENT '세션 토큰',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP 주소',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'User Agent',
  `last_activity` datetime DEFAULT NULL COMMENT '마지막 활동',
  `expires_at` datetime NOT NULL COMMENT '만료 시간',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='세션 관리';

-- 거래 로그
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '사용자 ID',
  `type` enum('purchase','bonus','withdrawal','avatar_generation','refund') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '거래 타입',
  `amount` decimal(15,2) NOT NULL COMMENT '금액',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'USDT' COMMENT '통화',
  `reference_id` int(11) unsigned DEFAULT NULL COMMENT '참조 ID (sale_id, withdrawal_id 등)',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '설명',
  `balance_before` decimal(15,2) DEFAULT '0.00' COMMENT '거래 전 잔액',
  `balance_after` decimal(15,2) DEFAULT '0.00' COMMENT '거래 후 잔액',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '거래 시간',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='거래 로그';

-- --------------------------------------------------------
-- 10. 기타 시스템
-- --------------------------------------------------------

-- 시스템 설정
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '설정 키',
  `setting_value` text COLLATE utf8mb4_unicode_ci COMMENT '설정 값',
  `setting_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'string' COMMENT '데이터 타입',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '설명',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 설정';

-- 공지사항
CREATE TABLE IF NOT EXISTS `notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_important` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 결제/입금 내역
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL COMMENT '회원 ID (회원가입 전이면 NULL)',
  `txid` varchar(100) NOT NULL COMMENT '트랜잭션 ID',
  `network` enum('BSC','TRC20') NOT NULL COMMENT '네트워크',
  `amount` decimal(15,2) NOT NULL COMMENT '입금액 (USDT)',
  `to_address` varchar(100) NOT NULL COMMENT '입금 주소',
  `status` enum('pending','confirmed','failed') DEFAULT 'confirmed' COMMENT '상태',
  `purpose` enum('signup','package','other') DEFAULT 'signup' COMMENT '용도',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
  PRIMARY KEY (`id`),
  UNIQUE KEY `txid` (`txid`),
  KEY `user_id` (`user_id`),
  KEY `network` (`network`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='결제/입금 내역';

-- 로얄코드 관리
CREATE TABLE IF NOT EXISTS `royal_codes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(8) NOT NULL COMMENT '로얄코드 (예: AAAA1111)',
  `pattern_type` varchar(50) NOT NULL COMMENT '패턴 타입 (예: letter_same+number_same)',
  `status` enum('available','assigned','reserved') DEFAULT 'available' COMMENT '상태',
  `assigned_user_id` int(11) unsigned DEFAULT NULL COMMENT '배정된 사용자 ID',
  `assigned_by` int(11) unsigned DEFAULT NULL COMMENT '배정한 관리자 ID',
  `assigned_at` datetime DEFAULT NULL COMMENT '배정 일시',
  `reserved_note` text COMMENT '예약 메모',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `status` (`status`),
  KEY `pattern_type` (`pattern_type`),
  KEY `assigned_user_id` (`assigned_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='로얄코드 관리';

-- --------------------------------------------------------
-- 11. VIEW 정의
-- --------------------------------------------------------

-- 활성 회원 뷰 (삭제되지 않은 회원)
CREATE OR REPLACE VIEW `active_users_view` AS
SELECT * FROM `users` WHERE `deleted_at` IS NULL;

-- 삭제된 회원 뷰
CREATE OR REPLACE VIEW `deleted_users_view` AS
SELECT
  `id`, `user_id`, `email`, `name`, `phone`,
  `referral_id`, `sponsor_id`, `package_id`,
  `total_bonus`, `total_sales`, `created_at`,
  `deleted_at`, `deleted_by`, `delete_reason`
FROM `users`
WHERE `deleted_at` IS NOT NULL
ORDER BY `deleted_at` DESC;

-- 보너스 요약 뷰
CREATE OR REPLACE VIEW `v_bonus_summary` AS
SELECT
  `user_id`,
  `total_referral_bonus`,
  `total_edge_bonus`,
  `total_matching_bonus`,
  `total_rollup_bonus`,
  `referral_count`,
  `edge_count`,
  `matching_count`,
  `rollup_count`,
  (`total_referral_bonus` + `total_edge_bonus` + `total_matching_bonus` + `total_rollup_bonus`) AS `total_bonus`,
  `last_bonus_at`
FROM `bonus_summary`;

-- 로얄코드 뷰
CREATE OR REPLACE VIEW `view_royal_codes` AS
SELECT
  rc.`id`, rc.`code`, rc.`pattern_type`, rc.`status`,
  u.`user_id` AS `assigned_user_code`,
  u.`name` AS `assigned_user_name`,
  u.`email` AS `assigned_user_email`,
  admin.`user_id` AS `assigned_by_code`,
  admin.`name` AS `assigned_by_name`,
  rc.`assigned_at`, rc.`reserved_note`, rc.`created_at`
FROM `royal_codes` rc
LEFT JOIN `users` u ON rc.`assigned_user_id` = u.`id`
LEFT JOIN `users` admin ON rc.`assigned_by` = admin.`id`;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
