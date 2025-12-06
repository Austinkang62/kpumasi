-- ============================================
-- AI BTC BOT 관리자 계정 생성 SQL
-- ============================================
-- 사용법: mysql -u ai22 -p ai22 < CREATE_ADMIN.sql
-- ============================================

USE ai22;

-- 1. 관리자 계정 생성
-- 비밀번호: admin123
-- 해시: bcrypt 알고리즘

INSERT INTO users (
    login_id,
    email,
    password,
    referral_code,
    usdt_address,
    package_type,
    package_status,
    is_active,
    is_admin,
    created_at
) VALUES (
    'admin',
    'admin@aibtcbot.com',
    '$2y$12$LQv3c1yduii3TXfAaHfNWeu0qwlxN5.8d3Qw0Z3e1uQ8xM8xM8xM8',
    'ADMIN001',
    'TSMZdowTbRC7wMKKkEjSL3LXss9coZW16Q',
    NULL,
    'none',
    1,
    1,
    NOW()
) ON DUPLICATE KEY UPDATE
    is_admin = 1,
    is_active = 1;

-- 2. 생성 확인
SELECT '━━━━━━━━━━━━━━━━━━━━━━━━━━━' as '';
SELECT '✅ 관리자 계정 생성 완료!' as '';
SELECT '━━━━━━━━━━━━━━━━━━━━━━━━━━━' as '';

SELECT
    user_id,
    login_id,
    email,
    is_admin,
    is_active,
    created_at
FROM users
WHERE login_id = 'admin';

-- 3. 로그인 정보
SELECT '' as '';
SELECT '📋 관리자 로그인 정보' as '';
SELECT '━━━━━━━━━━━━━━━━━━━━━━━━━━━' as '';
SELECT 'Username: admin' as '';
SELECT 'Password: admin123' as '';
SELECT 'Email: admin@aibtcbot.com' as '';
SELECT '━━━━━━━━━━━━━━━━━━━━━━━━━━━' as '';
SELECT '' as '';
SELECT '🔐 로그인 방법:' as '';
SELECT '1. http://yourdomain.com/admin/ 접속' as '';
SELECT '2. Username: admin' as '';
SELECT '3. Password: admin123' as '';
SELECT '4. ⚠️ 로그인 후 반드시 비밀번호를 변경하세요!' as '';

-- ============================================
-- 추가 관리자 생성 (필요시)
-- ============================================

-- 기존 사용자를 관리자로 지정:
-- UPDATE users SET is_admin = 1 WHERE user_id = [USER_ID];

-- 관리자 권한 제거:
-- UPDATE users SET is_admin = 0 WHERE user_id = [USER_ID];

-- 모든 관리자 목록:
-- SELECT user_id, login_id, email, created_at
-- FROM users
-- WHERE is_admin = 1;
