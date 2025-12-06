# 빠른 시작 가이드 - 3가지 검증 방식

## 🎯 어떤 방식을 선택해야 할까요?

### 상황별 추천

| 상황 | 추천 방식 | 이유 |
|------|----------|------|
| **대량 가입 처리** | TRC20/BSC 즉시 검증 | 자동화로 빠른 처리 |
| **소규모 운영** | 관리자 승인 | API 비용 불필요 |
| **24/7 자동 운영** | TRC20/BSC 즉시 검증 | 관리자 개입 불필요 |
| **최고 보안 필요** | 관리자 승인 | 수동 검증으로 안전 |
| **TRON 사용자** | TRC20 즉시 검증 | 낮은 수수료 |
| **Binance 사용자** | BSC 즉시 검증 | BSC 생태계 |

## 🚀 5분 안에 시작하기

### Step 1: 검증 방식 선택

**`js/config.js`** 파일을 열어서 한 줄만 수정하세요:

```javascript
// 옵션 1: TRC20 즉시 검증 ⚡
export const VERIFICATION_MODE = 'trc20_instant';

// 옵션 2: BSC 즉시 검증 ⚡
export const VERIFICATION_MODE = 'bsc_instant';

// 옵션 3: 관리자 승인 (기본값) ⏱️
export const VERIFICATION_MODE = 'admin_approval';
```

### Step 2: API 키 설정 (즉시 검증 사용 시만)

#### TRC20 즉시 검증을 선택했다면:

1. https://www.trongrid.io/ 접속
2. 무료 API 키 발급
3. `js/config.js` 에서 API 키 입력:

```javascript
export const NETWORK_CONFIG = {
    trc20: {
        apiKey: '여기에_발급받은_API_키_입력',  // ← 이 부분만 수정
        // ... 나머지는 그대로
    }
};
```

#### BSC 즉시 검증을 선택했다면:

1. https://bscscan.com/apis 접속
2. 무료 API 키 발급
3. `js/config.js` 에서 API 키 입력:

```javascript
export const NETWORK_CONFIG = {
    bsc: {
        apiKey: '여기에_발급받은_API_키_입력',  // ← 이 부분만 수정
        // ... 나머지는 그대로
    }
};
```

#### 관리자 승인을 선택했다면:

✅ **아무것도 할 필요 없습니다!** 바로 Step 3로 이동하세요.

### Step 3: index.html에서 main-v2.js 사용

`index.html` 파일 맨 아래를 수정:

```html
<!-- 기존 (v1) -->
<script type="module" src="./js/main.js"></script>

<!-- 새 버전 (v2) - 이렇게 변경 -->
<script type="module" src="./js/main-v2.js"></script>
```

### Step 4: 테스트

1. 브라우저에서 페이지 열기
2. F12 개발자 도구 열기
3. 콘솔에서 확인:

```
=== Signup App v2 Initialized ===
📦 Loading verification module: admin_approval
✓ Verification module loaded: 관리자 승인
```

✅ **완료!** 선택한 검증 방식으로 동작합니다.

## 📋 각 방식별 상세 가이드

### 🌐 TRC20 즉시 검증

**장점:**
- ⚡ 즉시 가입 (5-10초)
- 💰 낮은 수수료
- 🤖 완전 자동화

**설정:**
```javascript
// js/config.js
export const VERIFICATION_MODE = 'trc20_instant';

export const NETWORK_CONFIG = {
    trc20: {
        enabled: true,
        apiKey: 'YOUR_TRONGRID_API_KEY' // ← API 키 입력
    }
};
```

**사용자 경험:**
1. 사용자가 TRC20으로 USDT 입금
2. TXID 입력
3. **5-10초 후 즉시 가입 완료** ✨

**필요한 것:**
- TronGrid API 키 (무료)
- TRON 네트워크 지갑 주소

### ⚡ BSC 즉시 검증

**장점:**
- ⚡ 즉시 가입 (10-20초)
- 🌍 Ethereum 생태계 호환
- 🤖 완전 자동화

**설정:**
```javascript
// js/config.js
export const VERIFICATION_MODE = 'bsc_instant';

export const NETWORK_CONFIG = {
    bsc: {
        enabled: true,
        apiKey: 'YOUR_BSCSCAN_API_KEY' // ← API 키 입력
    }
};
```

**사용자 경험:**
1. 사용자가 BSC로 USDT 입금
2. TXID 입력
3. **10-20초 후 즉시 가입 완료** ✨

**필요한 것:**
- BscScan API 키 (무료)
- BSC 네트워크 지갑 주소

### 📝 관리자 승인

**장점:**
- ✅ API 키 불필요
- 🔒 최고 보안
- 💰 완전 무료

**설정:**
```javascript
// js/config.js
export const VERIFICATION_MODE = 'admin_approval';

// API 키 필요 없음!
```

**사용자 경험:**
1. 사용자가 USDT 입금
2. TXID 입력
3. **24시간 이내 관리자 승인 후 가입 완료** ⏱️

**필요한 것:**
- 관리자 승인 페이지 (별도 구현 필요)

## 🔄 방식 전환하기

### 관리자 승인 → TRC20 즉시 검증

```javascript
// Before
export const VERIFICATION_MODE = 'admin_approval';

// After
export const VERIFICATION_MODE = 'trc20_instant';

// API 키 추가
export const NETWORK_CONFIG = {
    trc20: {
        apiKey: 'YOUR_API_KEY'
    }
};
```

### TRC20 → BSC 전환

```javascript
// Before
export const VERIFICATION_MODE = 'trc20_instant';

// After
export const VERIFICATION_MODE = 'bsc_instant';

// 지갑 주소도 BSC 주소로 변경 필요!
```

## 🧪 테스트 모드

개발 중에는 테스트 모드를 활성화하세요:

```javascript
// js/config.js
export const DEV_CONFIG = {
    enabled: true,          // 개발 모드 활성화
    verbose: true,          // 상세 로그 표시
    allowTestTxid: true,    // 테스트 TXID 허용
};
```

## ⚙️ 고급 설정

### 입금 금액 변경

```javascript
// js/config.js
export const DEPOSIT_CONFIG = {
    requiredAmount: 100,    // 기본: $100
    // requiredAmount: 50,  // 변경: $50
};
```

### 컨펌 수 조정

```javascript
// js/config.js
export const DEPOSIT_CONFIG = {
    minConfirmations: {
        trc20: 1,   // TRC20: 1컨펌 (약 3초)
        bsc: 12     // BSC: 12컨펌 (약 36초)
        // bsc: 1   // 빠른 테스트용: 1컨펌
    }
};
```

## 🐛 문제 해결

### "검증 모듈 로드 실패" 에러
**원인**: config.js 설정 오류
**해결**:
1. `VERIFICATION_MODE` 철자 확인
2. API 키 확인 (즉시 검증 사용 시)
3. 브라우저 콘솔 에러 확인

### "TXID 검증 실패" 에러
**원인**: TXID 형식 오류 또는 API 오류
**해결**:
1. TXID 형식 확인:
   - TRC20: 64자 16진수
   - BSC: 0x + 64자 (총 66자)
2. 블록체인 탐색기에서 TXID 확인
3. API 키 유효성 확인

### 즉시 검증이 느림
**원인**: 컨펌 대기
**해결**:
```javascript
// 컨펌 수 줄이기 (테스트용)
minConfirmations: {
    trc20: 0,  // 즉시 (권장하지 않음)
    bsc: 1     // 1컨펌 (약 3초)
}
```

## 📞 지원

- **설정 예시**: `js/config.js` 파일의 주석 참고
- **상세 문서**: `README-v2.md`
- **검증 모듈**: `js/verification/` 폴더

---

**팁**: 처음에는 **관리자 승인** 방식으로 시작한 후, 운영이 안정되면 **즉시 검증**으로 전환하는 것을 추천합니다! 🚀
