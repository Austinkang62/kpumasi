# 아바타 자동 생성 Cron Job 설정 가이드

## 개요
매 5분마다 Avatar Points >= $100인 회원의 아바타를 자동 생성합니다.

---

## Cafe24 Cron 설정 방법

### 1. Cafe24 관리자 페이지 접속
1. [Cafe24 호스팅 관리](https://www.cafe24.com) 로그인
2. **나의 서비스 관리** 클릭
3. **호스팅 관리** → **부가서비스** → **예약작업(Cron)**

### 2. Cron Job 추가

#### 방법 A: 웹 URL 호출 (추천)
가장 간단한 방법입니다.

**설정값:**
```
실행 주기: */5 * * * * (매 5분마다)
실행 명령: curl -s "https://ai22.mycafe24.com/admin/cron-avatar-generator.php?key=avatar_cron_2024"
```

#### 방법 B: PHP CLI 실행
```
실행 주기: */5 * * * *
실행 명령: /usr/bin/php /ai22/www/admin/cron-avatar-generator.php >> /ai22/www/admin/logs/avatar-cron.log 2>&1
```

### 3. Cron 주기 설명
- `*/5 * * * *` = 매 5분마다 실행
- `*/10 * * * *` = 매 10분마다 실행
- `0 * * * *` = 매 시간 정각에 실행

---

## 테스트 방법

### 1. 브라우저에서 직접 테스트
```
https://ai22.mycafe24.com/admin/cron-avatar-generator.php?key=avatar_cron_2024
```

**예상 출력:**
```
[2025-11-27 16:00:00] ===== 아바타 자동 생성 Cron Job 시작 =====
[2025-11-27 16:00:00] 생성 대기 중인 회원: 1명
[2025-11-27 16:00:00] 회원 FODV4778 처리 시작 (Avatar Points: $110.95, 생성 예정: 1개)
[2025-11-27 16:00:01]   ✅ 아바타 AVA00053 생성 완료 (보너스 15건 지급)
[2025-11-27 16:00:01] 회원 FODV4778 처리 완료
[2025-11-27 16:00:01] ===== 완료 =====
[2025-11-27 16:00:01] 생성된 아바타: 1개
[2025-11-27 16:00:01] 실패: 0개
[2025-11-27 16:00:01] ===== Cron Job 종료 =====
```

### 2. SSH로 테스트 (고급)
```bash
ssh ai22@ai22.mycafe24.com
php /ai22/www/admin/cron-avatar-generator.php
```

---

## 로그 확인

### 로그 파일 위치
Cron Job 실행 로그는 다음 위치에 저장됩니다:
```
/ai22/www/admin/logs/avatar-cron.log
```

### 로그 디렉토리 생성 (처음 1회만)
```bash
mkdir -p /ai22/www/admin/logs
chmod 755 /ai22/www/admin/logs
```

### 로그 확인 방법
FTP로 접속하여 `www/admin/logs/avatar-cron.log` 파일 다운로드

---

## 보안

### API Key 변경 (선택사항)
기본 키: `avatar_cron_2024`

변경하려면 `cron-avatar-generator.php` 파일에서 수정:
```php
if (!isset($_GET['key']) || $_GET['key'] !== '새로운_키') {
```

---

## 문제 해결

### Q1. Cron이 실행되지 않습니다
**A1.** Cafe24 관리자 페이지에서 Cron 설정 확인
- 실행 주기가 올바른지 확인
- URL이 정확한지 확인 (https, key 포함)

### Q2. "Access denied" 오류
**A2.** URL에 `?key=avatar_cron_2024` 추가 확인

### Q3. 아바타가 생성되지 않습니다
**A3.** 브라우저에서 직접 테스트:
```
https://ai22.mycafe24.com/admin/cron-avatar-generator.php?key=avatar_cron_2024
```
오류 메시지 확인

### Q4. Lock timeout 오류
**A4.** 정상입니다. Cron이 다음 실행 시 재시도합니다.
또는 수동 복구 페이지 사용:
```
https://ai22.mycafe24.com/admin/avatar-auto-fix.php
```

---

## 수동 복구 (백업 방법)

Cron이 실패하거나 즉시 처리가 필요한 경우:

### 방법 1: 복구 페이지 (UI)
```
https://ai22.mycafe24.com/admin/avatar-auto-fix.php
```
- 생성 대기 회원 목록 표시
- 버튼 클릭으로 일괄 생성

### 방법 2: 간단 실행 (즉시)
```
https://ai22.mycafe24.com/admin/avatar-fix-simple.php
```
- 접속 즉시 자동 실행
- 실시간 로그 표시

---

## 운영 체크리스트

### 매일 확인
- [ ] Cron이 정상 실행되는지 확인
- [ ] 로그 파일에 오류가 없는지 확인

### 매주 확인
- [ ] 수동 복구 페이지 접속하여 누락된 아바타 확인
- [ ] Avatar Points >= $100 회원이 0명인지 확인

### 문제 발생 시
1. 브라우저에서 Cron URL 직접 실행
2. 오류 메시지 확인
3. 수동 복구 페이지로 즉시 처리

---

## 완료!

설정이 완료되면:
- ✅ 매 5분마다 자동으로 아바타 생성
- ✅ Lock 충돌 없음
- ✅ 안정적인 운영
- ✅ 수동 백업 방법 제공

**다음 단계:**
1. Cafe24에서 Cron 설정
2. 브라우저에서 테스트 실행
3. 5분 후 다시 확인하여 자동 실행 확인
