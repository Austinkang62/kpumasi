# Signup Module - 구조화된 회원가입 시스템

## 📂 프로젝트 구조

```
www/signup/
├── index.html              # 메인 회원가입 페이지 (280줄, 기존 1982줄에서 개선)
├── signup.html             # 원본 파일 (백업용)
├── css/
│   └── signup.css          # 회원가입 전용 스타일시트
├── js/
│   ├── main.js            # 메인 진입점, 모듈 통합
│   ├── signupForm.js      # 회원가입 폼 처리 및 추천코드 검증
│   ├── usdtVerification.js # USDT 입금 검증 및 네트워크 관리
│   ├── sponsorTree.js     # 후원 트리 및 위치 선택 관리
│   └── uiComponents.js    # UI 컴포넌트 (알림, 팝업, 유틸리티)
└── README.md              # 이 파일
```

## 🎯 리팩토링 목표 및 성과

### Before (기존)
- **1개의 거대한 HTML 파일**: 1,982줄
- **모든 기능이 하나의 파일에 혼재**: HTML + CSS + JavaScript
- **유지보수 어려움**: USDT 검증 에러 발생 시 디버깅 힘듦
- **재사용 불가**: 다른 페이지에서 기능 재사용 불가능

### After (리팩토링 후)
- **280줄의 깔끔한 HTML**: 구조만 포함
- **모듈화된 JavaScript**: 기능별로 명확하게 분리
- **분리된 CSS**: 스타일 관리 용이
- **재사용 가능**: 각 모듈을 독립적으로 테스트 및 재사용 가능

## 📦 모듈 설명

### 1. `main.js` - 메인 진입점
모든 모듈을 통합하고 초기화를 담당합니다.

**주요 기능:**
- 모든 모듈 import 및 통합
- 전역 객체(`window.signupApp`) 생성
- DOMContentLoaded 이벤트 처리
- 이벤트 리스너 등록

### 2. `signupForm.js` - 회원가입 폼 처리
회원가입 폼의 검증 및 제출을 담당합니다.

**주요 함수:**
- `checkReferralCode()` - 추천코드 실시간 검증
- `validateForm()` - 폼 유효성 검증
- `collectFormData()` - 폼 데이터 수집
- `submitSignupForm()` - 서버로 데이터 전송
- `initReferralCodeInput()` - 추천코드 입력 이벤트 초기화

### 3. `usdtVerification.js` - USDT 입금 검증 ⭐
**가장 중요한 모듈 - USDT 검증 에러가 이 파일에만 집중됨**

**주요 함수:**
- `loadWalletAddresses()` - 지갑 주소 로드
- `showNetworkWarningModal()` - 네트워크 경고 모달 표시
- `closeNetworkWarning()` - 경고 모달 닫기
- `proceedWithTRC20()` - TRC20 네트워크 설정
- `copyWalletAddress()` - 지갑 주소 복사
- `handleTxidInput()` - TXID 입력 핸들링
- `verifyTxid()` - TXID 검증
- `getDepositInfo()` - 입금 정보 가져오기

**에러 해결 방법:**
- 이 파일만 수정하면 USDT 검증 관련 모든 문제 해결 가능
- 개별 테스트 가능
- 에러 로그가 명확함

### 4. `sponsorTree.js` - 후원 트리 관리
후원 시스템 및 바이너리 트리 위치 선택을 담당합니다.

**주요 함수:**
- `showSponsorList()` - 후원 가능 회원 목록 표시
- `checkSponsorPosition()` - 후원인 위치 확인
- `selectPosition()` - 바이너리 트리 위치 선택
- `loadSponsorList()` - 후원자 목록 로드
- `initPositionBoxes()` - 위치 박스 초기화

### 5. `uiComponents.js` - UI 컴포넌트
재사용 가능한 UI 요소들을 관리합니다.

**주요 함수:**
- `showAlert()` - 알림 메시지 표시
- `showSuccessPopup()` - 회원가입 성공 팝업
- `showPendingSuccessPopup()` - 가입신청 대기 팝업
- `copyUserId()` - 사용자 ID 복사
- `copyToClipboard()` - 클립보드 복사
- `goToLoginWithId()` - 로그인 페이지 이동
- `createParticles()` - 배경 파티클 생성
- `showMaintenanceMode()` - 점검 모드 표시

## 🚀 사용 방법

### 1. 페이지 접속
```
http://yourdomain.com/signup/index.html
```

### 2. USDT 검증 에러 디버깅
USDT 관련 에러가 발생하면:

1. `js/usdtVerification.js` 파일을 엽니다
2. 해당 함수를 찾습니다 (예: `verifyTxid()`)
3. 수정합니다
4. 브라우저를 새로고침하여 테스트합니다

**예시:**
```javascript
// js/usdtVerification.js

// TXID 검증 로직 수정
export async function verifyTxid(txid, network) {
    try {
        // 여기에 새로운 검증 로직 추가
        if (!txid || txid.length < 30) {
            throw new Error('유효하지 않은 TXID 형식입니다.');
        }

        // 서버 API 호출 추가 가능
        // const response = await fetch('...');

        return { success: true, txid };
    } catch (error) {
        console.error('TXID 검증 오류:', error);
        return { success: false, message: error.message };
    }
}
```

### 3. 새 기능 추가
새로운 기능을 추가하려면:

1. 적절한 모듈 파일을 선택하거나 새 모듈을 생성합니다
2. 함수를 `export`로 내보냅니다
3. `main.js`에서 import하고 필요시 `window.signupApp`에 추가합니다

**예시:**
```javascript
// js/newFeature.js
export function myNewFunction() {
    console.log('새 기능!');
}

// js/main.js
import { myNewFunction } from './newFeature.js';

window.signupApp = {
    ...
    myNewFunction
};
```

## 🔧 개발 팁

### 디버깅
브라우저 콘솔에서 전역 객체 접근:
```javascript
// 전역 객체 확인
console.log(window.signupApp);

// 함수 직접 호출
window.signupApp.showAlert('테스트', 'success');
```

### 에러 추적
모든 모듈에서 `console.log`를 사용하여 상세한 로그를 제공합니다:
- `main.js`: `=== Signup App Initialized ===`
- `usdtVerification.js`: `지갑 주소 로드 오류:`, `TXID 검증 오류:`
- `sponsorTree.js`: `후원 리스트 API 호출:`, `위치 정보 로드 실패`
- `signupForm.js`: `가입신청 시작`, `서버 응답`

### 모듈 테스트
각 모듈을 독립적으로 테스트할 수 있습니다:

```javascript
// 브라우저 콘솔에서
import { verifyTxid } from './js/usdtVerification.js';
const result = await verifyTxid('test-txid-12345678901234567890123456', 'TRC20');
console.log(result);
```

## 📊 성능 개선

### 파일 크기
- **HTML**: 1,982줄 → 280줄 (85% 감소)
- **JavaScript**: 모듈화로 캐싱 효율 증가
- **CSS**: 별도 파일로 분리하여 재사용 가능

### 로딩 속도
- ES6 모듈 사용으로 브라우저 최적화
- 필요한 모듈만 로드
- 코드 스플리팅 가능

### 유지보수성
- 기능별 파일 분리로 코드 찾기 쉬움
- 독립적 테스트 가능
- 버전 관리 용이 (Git diff가 명확함)

## 🐛 트러블슈팅

### Q: USDT 검증 에러가 발생합니다
**A:** `js/usdtVerification.js` 파일의 관련 함수를 확인하세요. 브라우저 콘솔에 상세한 에러 로그가 표시됩니다.

### Q: 모듈을 찾을 수 없다는 에러가 발생합니다
**A:** 파일 경로를 확인하세요. 모든 경로는 상대 경로로 작성되어 있습니다. 또한 서버에서 제공되어야 합니다 (file:// 프로토콜에서는 ES6 모듈이 작동하지 않습니다).

### Q: onclick 이벤트가 작동하지 않습니다
**A:** `window.signupApp` 객체에 함수가 export되어 있는지 `main.js`를 확인하세요.

## 📝 향후 개선 사항

- [ ] TypeScript 마이그레이션
- [ ] 단위 테스트 추가 (Jest)
- [ ] 에러 바운더리 구현
- [ ] TXID 검증 API 서버 연동 강화
- [ ] 오프라인 모드 지원

## 📞 지원

문제가 발생하거나 질문이 있으시면 이슈를 생성해주세요.

---

**버전**: 2.0.0
**최종 업데이트**: 2025-11-24
**작성자**: Claude Code
