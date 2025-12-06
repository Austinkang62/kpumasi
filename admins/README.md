# Super Admin Panel - K-Pumasi

## 📋 개요

**Super Admin Panel**은 K-Pumasi 플랫폼의 최상위 관리 시스템입니다. 일반 관리자(`/admin`)와 달리, Super Admin만 접근할 수 있으며 시스템 전체와 관리자 계정을 관리할 수 있습니다.

## 🔐 접근 권한

### 역할 구분

| 역할 | 접근 경로 | 권한 |
|------|----------|------|
| **Super Admin** | `/admins/` | 모든 시스템 관리 + 관리자 계정 관리 |
| **Admin** | `/admin/` | 일반 관리자 기능 (사용자, 출금, 통계 등) |
| **Manager** | `/admin/` | 제한된 관리자 기능 |

### 인증 시스템

- **세션 기반**: `super_admin_logged_in` 세션으로 관리
- **별도 인증**: 일반 관리자와 완전히 분리된 세션 관리
- **권한 체크**: `includes/auth_check.php`로 모든 페이지 보호
- **자동 리다이렉트**: 권한 없으면 로그인 페이지로 이동

## 📁 폴더 구조

```
/admins/
├── login.php                    # Super Admin 로그인 페이지
├── index.php                    # Super Admin 대시보드
├── api/                         # Backend API
│   ├── auth.php                 # Super Admin 인증 (로그인/로그아웃)
│   ├── stats.php                # 통계 데이터
│   ├── admin-manage.php         # 관리자 계정 관리 (CRUD)
│   └── activity-logs.php        # 활동 로그 조회
├── pages/                       # 서브 페이지
│   ├── admin-list.php           # 관리자 목록 & 관리
│   ├── system-settings.php      # 시스템 설정 (미구현)
│   └── activity-logs.php        # 활동 로그 페이지 (미구현)
├── includes/                    # 공통 컴포넌트
│   ├── auth_check.php           # Super Admin 권한 체크 미들웨어
│   └── sidebar.php              # 사이드바 컴포넌트
├── assets/                      # 정적 파일
│   ├── css/
│   │   └── main.css            # 메인 스타일시트
│   └── js/
│       └── main.js             # 메인 JavaScript (미구현)
└── README.md                    # 이 파일
```

## 🚀 시작하기

### 1. Super Admin 계정 생성

데이터베이스에서 기존 관리자를 Super Admin으로 승격:

```sql
-- 기존 관리자를 Super Admin으로 변경
UPDATE admins
SET role = 'super_admin'
WHERE username = 'admin1';  -- 또는 원하는 username
```

또는 새로운 Super Admin 생성:

```sql
-- 비밀번호: superadmin123
INSERT INTO admins (username, password, email, full_name, role, is_active, created_at)
VALUES (
    'superadmin',
    '$2b$12$LQv3c1yduii3TXfAaHfNWeu0qwlxN5.8d3Qw0Z3e1uQ8xM8xM8xM8',  -- bcrypt hash
    'superadmin@kpumasi.com',
    'Super Administrator',
    'super_admin',
    1,
    NOW()
);
```

### 2. 로그인

1. 브라우저에서 `/admins/login.php` 접속
2. Super Admin 계정으로 로그인
3. 자동으로 대시보드로 이동

### 3. 권한 확인

- ✅ `role = 'super_admin'`인 계정만 접근 가능
- ❌ 일반 `admin` 또는 `manager`는 접근 거부
- 🔗 일반 관리자는 `/admin/` 페이지 이용

## 🎯 주요 기능

### 1. 대시보드 (index.php)

**통계 카드:**
- 전체 관리자 수
- Super Admin 수
- 활성 관리자 수
- 오늘 로그인 수

**시스템 상태:**
- 전체 사용자 수
- 총 매출
- 대기 출금
- 데이터베이스 상태

**최근 활동:**
- 관리자 로그인/로그아웃
- 계정 생성/수정/삭제
- 설정 변경 등

### 2. 관리자 계정 관리 (pages/admin-list.php)

**기능:**
- ➕ 새 관리자 추가
- 📝 관리자 정보 수정
- 🔄 계정 활성화/비활성화
- 🔍 관리자 검색
- 📊 관리자 목록 조회

**추가 가능한 역할:**
- `super_admin`: 모든 권한
- `admin`: 일반 관리자 권한
- `manager`: 제한된 관리자 권한

### 3. 활동 로그 (미구현)

**로깅 내용:**
- 로그인/로그아웃
- 관리자 계정 생성/수정/삭제
- 시스템 설정 변경
- IP 주소 및 User Agent 기록

### 4. 시스템 설정 (미구현)

**향후 구현 예정:**
- 플랫폼 설정
- 지갑 주소 관리
- 수수료 설정
- 이메일 템플릿 관리

## 🔒 보안 기능

### 세션 관리

- **세션 ID**: 64자 랜덤 문자열
- **만료 시간**: `SESSION_LIFETIME` (기본 2시간)
- **활동 추적**: 마지막 활동 시간 자동 업데이트
- **자동 로그아웃**: 세션 만료 시 자동 로그인 페이지 이동

### 권한 체크

```php
// 모든 Super Admin 페이지에 포함
require_once __DIR__ . '/includes/auth_check.php';

// $currentSuperAdmin 변수 자동 생성:
// - admin_id
// - username
// - email
// - role
```

### 활동 로그

모든 중요한 작업은 `admin_logs` 테이블에 기록:

```sql
CREATE TABLE admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 📊 API 엔드포인트

### 인증 API (api/auth.php)

```
POST /admins/api/auth.php?action=login
POST /admins/api/auth.php?action=logout
GET  /admins/api/auth.php?action=check
```

### 통계 API (api/stats.php)

```
GET /admins/api/stats.php
```

**응답:**
```json
{
  "success": true,
  "data": {
    "total_admins": 5,
    "super_admins": 2,
    "active_admins": 4,
    "today_logins": 3,
    "total_users": 150,
    "total_sales": "5000.00",
    "pending_withdrawals": 2
  }
}
```

### 관리자 관리 API (api/admin-manage.php)

```
GET  /admins/api/admin-manage.php?action=list&limit=20&offset=0&search=keyword
GET  /admins/api/admin-manage.php?action=detail&admin_id=1
POST /admins/api/admin-manage.php?action=create
POST /admins/api/admin-manage.php?action=update
POST /admins/api/admin-manage.php?action=toggle-status
POST /admins/api/admin-manage.php?action=delete
```

**새 관리자 생성 예시:**
```json
{
  "username": "newadmin",
  "password": "SecurePass123",
  "email": "newadmin@kpumasi.com",
  "full_name": "New Admin",
  "role": "admin"
}
```

### 활동 로그 API (api/activity-logs.php)

```
GET /admins/api/activity-logs.php?limit=20&offset=0
```

## 🎨 디자인 시스템

### 색상 팔레트

```css
--primary: #667eea;        /* 메인 보라색 */
--primary-dark: #764ba2;   /* 어두운 보라색 */
--success: #43e97b;        /* 성공 (초록) */
--danger: #f5576c;         /* 위험 (빨강) */
--warning: #f59e0b;        /* 경고 (주황) */
--info: #4facfe;           /* 정보 (파랑) */
```

### 컴포넌트

- **사이드바**: 고정 위치, 280px 너비
- **통계 카드**: 그리드 레이아웃, 호버 애니메이션
- **데이터 테이블**: 반응형, 스트라이프 효과
- **모달**: 중앙 정렬, 반투명 배경
- **배지**: 역할별 그라데이션 색상

## 🔄 일반 관리자 페이지와 차이점

| 기능 | Super Admin (`/admins/`) | Admin (`/admin/`) |
|------|--------------------------|-------------------|
| **접근 권한** | `super_admin`만 | `admin`, `manager` |
| **관리자 계정 관리** | ✅ 가능 | ❌ 불가능 |
| **시스템 설정** | ✅ 가능 | ❌ 불가능 |
| **사용자 관리** | 🔗 `/admin/`으로 이동 | ✅ 가능 |
| **출금 승인** | 🔗 `/admin/`으로 이동 | ✅ 가능 |
| **통계 조회** | ✅ 모든 통계 | ✅ 기본 통계 |
| **세션 관리** | 별도 세션 | 별도 세션 |

## 🛠️ 개발 가이드

### 새 페이지 추가

1. **권한 체크 포함:**
```php
<?php
require_once __DIR__ . '/../includes/auth_check.php';
?>
```

2. **사이드바 포함:**
```php
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
```

3. **CSS 로드:**
```html
<link rel="stylesheet" href="../assets/css/main.css">
```

### 활동 로그 추가

```php
$db->execute(
    "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
     VALUES (?, ?, ?, ?, ?)",
    [
        $_SESSION['super_admin_id'],
        'your_action_name',
        json_encode(['key' => 'value']),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]
);
```

## 📝 TODO / 향후 개발

### Phase 1 ✅ (완료)
- [x] Super Admin 로그인 시스템
- [x] 권한 기반 접근 제어
- [x] 대시보드 통계
- [x] 관리자 계정 CRUD
- [x] 활동 로그 API

### Phase 2 (진행 중)
- [ ] 활동 로그 페이지 구현
- [ ] 시스템 설정 페이지
- [ ] 비밀번호 변경 기능
- [ ] 관리자 상세 보기 모달

### Phase 3 (예정)
- [ ] 실시간 알림 시스템
- [ ] 차트 및 분석 대시보드
- [ ] 이메일 발송 기능
- [ ] 백업 및 복원 기능
- [ ] API 사용량 모니터링

## ❓ 문제 해결

### Q: Super Admin 로그인이 안 됩니다
**A:**
1. 데이터베이스에서 `role = 'super_admin'` 확인
2. `is_active = 1` 확인
3. 비밀번호가 bcrypt로 해시되었는지 확인

### Q: 일반 관리자가 접근하려고 합니다
**A:**
자동으로 403 에러 페이지가 표시되며 `/admin/`으로 안내됩니다.

### Q: 세션이 자주 만료됩니다
**A:**
`config/database.php`에서 `SESSION_LIFETIME` 값을 증가시키세요 (초 단위).

### Q: 관리자를 삭제할 수 없습니다
**A:**
자기 자신은 삭제할 수 없습니다. 다른 Super Admin 계정으로 로그인하세요.

## 📞 지원

- **문서**: 이 README 파일
- **API 문서**: 각 API 파일의 주석 참조
- **일반 관리자 문서**: `/admin/README.md`

---

**최종 업데이트:** 2025-12-02
**버전:** 1.0.0
**개발자:** K-Pumasi Development Team
