# AI BTC BOT - 관리자 페이지 가이드

## 📋 개요

AI BTC BOT 관리자 페이지는 플랫폼 전체를 관리할 수 있는 백엔드 대시보드입니다.

## 🚀 빠른 시작

### 1. 관리자 계정 생성

데이터베이스에 관리자 계정을 생성합니다:

```bash
mysql -u ai22 -p'Ai0505**ftd' ai22 < CREATE_ADMIN.sql
```

또는 직접 SQL 실행:

```sql
UPDATE users SET is_admin = 1 WHERE user_id = 1;
```

### 2. 로그인

**로그인 정보:**
- URL: `http://yourdomain.com/admin/`
- Username: `admin`
- Password: `admin123`

⚠️ **중요:** 로그인 후 즉시 비밀번호를 변경하세요!

## 📁 파일 구조

```
/admin/
├── index.html              # 로그인 & 대시보드
├── api/                    # Backend API
│   ├── auth.php           # 인증 (로그인/로그아웃)
│   ├── stats.php          # 통계 데이터
│   ├── users.php          # 사용자 관리
│   └── withdrawals.php    # 출금 관리
├── pages/                  # 서브 페이지
│   └── withdrawals.html   # 출금 승인 페이지
├── CREATE_ADMIN.sql        # 관리자 계정 생성 SQL
└── README.md               # 이 파일
```

## 🎯 주요 기능

### 1. 대시보드 (index.html)

**실시간 통계:**
- 전체 사용자 수
- 오늘 가입자
- 활성 사용자 (최근 7일)
- 총 판매 건수
- 총 매출
- 대기 중인 출금
- 대기 중인 결제

**최근 정보:**
- 최근 가입자 10명
- 패키지별 판매 통계

### 2. 사용자 관리 (api/users.php)

**기능:**
- 사용자 목록 조회 (페이지네이션)
- 사용자 검색 (ID, 이메일, 추천코드)
- 사용자 상세 정보 조회
- 계정 활성화/비활성화
- 관리자 권한 부여/제거

**API 엔드포인트:**
```
GET  /admin/api/users.php?action=list&page=1&limit=20&search=keyword
GET  /admin/api/users.php?action=detail&user_id=123
POST /admin/api/users.php?action=toggle-status
POST /admin/api/users.php?action=toggle-admin
```

### 3. 출금 관리 (pages/withdrawals.html)

**기능:**
- 출금 요청 목록 조회
- 상태별 필터 (전체/대기/완료/거부)
- 출금 승인 (TXID 입력)
- 출금 거부 (사유 입력, 잔액 복구)

**API 엔드포인트:**
```
GET  /admin/api/withdrawals.php?action=list&status=pending
POST /admin/api/withdrawals.php?action=approve
POST /admin/api/withdrawals.php?action=reject
```

**승인 프로세스:**
1. 대기 중인 출금 확인
2. 사용자 USDT 주소로 송금
3. 블록체인 TXID 확인
4. 시스템에 TXID 입력하여 승인
5. 상태가 'completed'로 변경

**거부 프로세스:**
1. 출금 거부 사유 입력
2. 사용자 잔액 자동 복구
3. 상태가 'rejected'로 변경

## 🔒 보안

### 세션 기반 인증

- 로그인 시 PHP 세션 생성
- 모든 API는 세션 확인
- 2시간 세션 유지
- 로그아웃 시 세션 파기

### 권한 확인

```php
session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
```

### 데이터베이스 보안

- Prepared Statements (SQL Injection 방지)
- 비밀번호 bcrypt 해싱
- 트랜잭션 사용 (출금 처리)

## 📊 데이터베이스 스키마

### users 테이블에 필요한 컬럼:

```sql
is_admin TINYINT(1) DEFAULT 0  -- 관리자 여부
is_active TINYINT(1) DEFAULT 1  -- 계정 활성화 여부
```

### withdrawals 테이블 구조:

```sql
CREATE TABLE withdrawals (
    withdrawal_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    fee DECIMAL(10,2) NOT NULL,
    net_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    txid VARCHAR(255),
    processed_at TIMESTAMP NULL,
    processed_by INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (processed_by) REFERENCES users(user_id)
);
```

## 🛠️ 개발 가이드

### API 응답 형식

**성공:**
```json
{
    "success": true,
    "data": {...},
    "message": "Success message"
}
```

**실패:**
```json
{
    "success": false,
    "message": "Error message"
}
```

### 에러 처리

```php
try {
    // 코드 실행
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
```

## 📈 향후 개발 계획

### Phase 1 ✅ (완료)
- [x] 로그인/인증 시스템
- [x] 대시보드 통계
- [x] 사용자 관리 API
- [x] 출금 승인 시스템

### Phase 2 (예정)
- [ ] 결제 확인 페이지
- [ ] 판매 내역 조회
- [ ] 보너스 지급 내역
- [ ] 시스템 설정

### Phase 3 (예정)
- [ ] 실시간 알림
- [ ] 차트 및 분석
- [ ] 이메일 발송 기능
- [ ] 활동 로그

## ❓ 문제 해결

### Q: 로그인이 안 됩니다
**A:**
1. 관리자 계정이 생성되었는지 확인
2. `is_admin = 1`로 설정되었는지 확인
3. 비밀번호가 정확한지 확인

### Q: 통계가 0으로 표시됩니다
**A:**
1. 데이터베이스에 데이터가 있는지 확인
2. 브라우저 콘솔에서 API 오류 확인
3. 서버 에러 로그 확인

### Q: 출금 승인이 안 됩니다
**A:**
1. 출금 상태가 'pending'인지 확인
2. TXID를 정확히 입력했는지 확인
3. 데이터베이스 트랜잭션 오류 확인

## 📞 지원

- **문서:** 이 README 파일
- **API 문서:** 각 API 파일의 주석 참조
- **에러 로그:** 서버 PHP 에러 로그 확인

---

**최종 업데이트:** 2025-11-13
**버전:** 1.0.0
