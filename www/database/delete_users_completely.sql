-- 회원 완전 삭제 스크립트
-- 사용자: BYDY7570, WRGP3948, JNRT1315, LMVK8706

-- 1. 세션 삭제
DELETE FROM sessions WHERE user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');

-- 2. 이메일 인증 삭제
DELETE FROM email_verifications WHERE user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');

-- 3. 거래 내역 삭제
DELETE FROM transactions WHERE user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');

-- 4. 아바타 삭제
DELETE FROM avatars WHERE owner_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');

-- 5. 출금 내역 삭제
DELETE FROM withdrawals WHERE user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');

-- 6. 보너스 내역 삭제 (받은 보너스)
DELETE FROM bonuses WHERE user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');

-- 7. 보너스 내역 삭제 (발생시킨 보너스)
DELETE FROM bonuses WHERE from_user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');

-- 8. 판매 내역 삭제
DELETE FROM sales WHERE user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');

-- 9. 조직도에서 삭제
DELETE FROM organization WHERE user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');

-- 10. 관리자 로그 삭제 (선택사항 - 로그를 남기려면 주석 처리)
DELETE FROM admin_logs WHERE related_user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');

-- 11. 최종적으로 users 테이블에서 삭제
DELETE FROM users WHERE user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');

-- 삭제 확인
SELECT '삭제 완료' AS status;
SELECT 'users 테이블 확인:' AS info, COUNT(*) AS count FROM users WHERE user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');
SELECT 'organization 테이블 확인:' AS info, COUNT(*) AS count FROM organization WHERE user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');
SELECT 'bonuses 테이블 확인:' AS info, COUNT(*) AS count FROM bonuses WHERE user_id IN ('BYDY7570', 'WRGP3948', 'JNRT1315', 'LMVK8706');
