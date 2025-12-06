# 🔄 캐시 클리어 가이드

## 이메일 중복 오류 해결 방법

이메일 중복 체크 로직을 제거했지만, 브라우저나 서버 캐시로 인해 여전히 오류가 발생할 수 있습니다.

---

## 📋 해결 방법

### 1️⃣ **사용자 측 - 브라우저 캐시 강제 새로고침**

#### Chrome/Edge/Brave
```
Ctrl + Shift + Delete (Windows/Linux)
Cmd + Shift + Delete (Mac)
```
또는
```
Ctrl + F5 (Windows/Linux)
Cmd + Shift + R (Mac)
```

#### Firefox
```
Ctrl + Shift + Delete (Windows/Linux)
Cmd + Shift + Delete (Mac)
```

#### Safari
```
Cmd + Option + E (캐시 비우기)
Cmd + R (새로고침)
```

---

### 2️⃣ **개발자 도구에서 캐시 비활성화**

1. F12 키 눌러 개발자 도구 열기
2. Network 탭 선택
3. "Disable cache" 체크박스 선택
4. 페이지 새로고침 (F5)

---

### 3️⃣ **시크릿/프라이빗 모드로 테스트**

```
Ctrl + Shift + N (Chrome/Edge - Windows)
Ctrl + Shift + P (Firefox - Windows)
Cmd + Shift + N (Chrome/Edge - Mac)
Cmd + Shift + P (Firefox - Mac)
```

---

### 4️⃣ **서버 측 - PHP OpCache 클리어**

서버에서 PHP 파일 캐시가 있을 수 있습니다.

#### 방법 1: 웹에서 클리어
`clear_cache.php` 파일을 만들어 브라우저에서 실행:

```php
<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OpCache cleared!";
} else {
    echo "⚠️ OpCache not available";
}

// APCu 캐시도 클리어
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "<br>✅ APCu cache cleared!";
}
?>
```

#### 방법 2: 서버 재시작
```bash
# Apache
sudo service apache2 restart

# Nginx + PHP-FPM
sudo service php-fpm restart
sudo service nginx restart
```

---

### 5️⃣ **파일 버전 관리로 캐시 우회**

HTML 파일에 버전 파라미터 추가:

**Before:**
```html
<link rel="stylesheet" href="../css/signup-v2.css">
<script src="../js/signup-v2.js"></script>
```

**After:**
```html
<link rel="stylesheet" href="../css/signup-v2.css?v=20251118">
<script src="../js/signup-v2.js?v=20251118"></script>
```

---

## 🔍 캐시 문제인지 확인하는 방법

### 브라우저 개발자 도구로 확인

1. F12 키로 개발자 도구 열기
2. Network 탭 선택
3. 페이지 새로고침 (F5)
4. `register-pending.php` 요청 찾기
5. Response 탭에서 실제 응답 확인

**캐시된 응답:**
```
Status: 304 Not Modified
```

**새로운 응답:**
```
Status: 200 OK
```

---

## ✅ 수정 내용 확인 방법

### API 직접 테스트

```bash
# 동일 이메일로 두 번 테스트
curl -X POST http://localhost/api/auth/register-pending.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Test1234!@#",
    "txid": "test_txid_12345678901234567890123456789012345678901234567890"
  }'
```

**예상 결과 (수정 후):**
- 첫 번째: ✅ 성공
- 두 번째: ❌ TXID 중복 오류 (이메일은 통과!)

**이전 결과 (수정 전):**
- 첫 번째: ✅ 성공
- 두 번째: ❌ 이메일 중복 오류

---

## 🛠️ 완전 초기화 방법 (최후의 수단)

모든 캐시를 완전히 제거:

```bash
# 1. 브라우저 완전 종료
# 2. 브라우저 캐시 폴더 삭제

# Windows - Chrome
%LOCALAPPDATA%\Google\Chrome\User Data\Default\Cache

# Windows - Firefox
%APPDATA%\Mozilla\Firefox\Profiles\

# Mac - Chrome
~/Library/Caches/Google/Chrome/

# 3. 브라우저 재시작
```

---

## 📝 수정 완료 확인 체크리스트

- [ ] register-pending.php 파일 수정 완료
- [ ] 브라우저 강제 새로고침 (Ctrl+F5)
- [ ] 시크릿 모드로 테스트
- [ ] 개발자 도구에서 API 응답 확인
- [ ] 동일 이메일로 2개 계정 생성 테스트 성공

---

## 🎯 최종 확인

성공적으로 수정되었다면:

```
✅ 동일 이메일로 여러 계정 생성 가능
✅ "이미 사용 중인 이메일입니다" 오류 발생 안 함
✅ TXID는 여전히 중복 체크됨
```

---

## 📞 문제 지속 시

1. **파일 수정 확인:**
   `/api/auth/register-pending.php` 파일의 99-126줄이 주석 처리되었는지 확인

2. **서버 재시작:**
   웹 서버 또는 PHP 프로세스 재시작

3. **다른 브라우저로 테스트:**
   Chrome, Firefox, Safari 등 다른 브라우저에서 테스트

4. **타임스탬프 확인:**
   파일 수정 시간이 최신인지 확인
   ```bash
   ls -l /mnt/c/app/pum/www/api/auth/register-pending.php
   ```
