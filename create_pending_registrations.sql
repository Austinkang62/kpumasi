-- ============================================================================
-- PENDING_REGISTRATIONS TABLE - 회원가입 대기 테이블
-- ============================================================================
-- 관리자 승인 대기 중인 회원가입 신청 정보를 저장합니다.
-- 관리자가 승인하면 users 테이블로 이동합니다.

CREATE TABLE IF NOT EXISTS `pending_registrations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL COMMENT '이메일',
  `password` VARCHAR(255) NOT NULL COMMENT '암호화된 비밀번호',
  `txid` VARCHAR(100) NOT NULL COMMENT '트랜잭션 ID (입금 증명)',
  `network` VARCHAR(20) DEFAULT 'TRC20' COMMENT '네트워크 (TRC20, BEP20 등)',
  `payment_amount` DECIMAL(10,2) DEFAULT 100.00 COMMENT '입금 금액',
  `referral_id` VARCHAR(50) DEFAULT NULL COMMENT '추천인 코드',
  `sponsor_id` VARCHAR(50) DEFAULT NULL COMMENT '후원인 코드',
  `sponsor_position` TINYINT(1) DEFAULT NULL COMMENT '후원인 하위 포지션 (1=좌측, 2=우측)',
  `memo` TEXT DEFAULT NULL COMMENT '사용자 메모',
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' COMMENT '상태',
  `rejection_reason` VARCHAR(255) DEFAULT NULL COMMENT '거부 사유',
  `approved_by` INT(11) UNSIGNED DEFAULT NULL COMMENT '승인한 관리자 ID',
  `approved_at` DATETIME DEFAULT NULL COMMENT '승인일',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '신청일',
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `txid` (`txid`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='회원가입 대기 (관리자 승인)';

-- ============================================================================
-- 인덱스 설명
-- ============================================================================
-- email: 이메일로 빠른 검색
-- txid: TXID 중복 체크
-- status: 상태별 필터링 (pending, approved, rejected)
-- created_at: 신청일 순서 정렬
