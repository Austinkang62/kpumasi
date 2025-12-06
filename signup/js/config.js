/**
 * Signup Configuration
 * 회원가입 시스템 설정
 */

/**
 * 검증 모드 선택
 *
 * 사용 가능한 모드:
 * - 'trc20_instant': TRC20 즉시 검증 (TRON 블록체인 API 실시간 검증)
 * - 'bsc_instant': BSC 즉시 검증 (BNB Smart Chain API 실시간 검증)
 * - 'admin_approval': 관리자 승인 (TXID 입력 후 관리자가 수동 검증)
 */
export const VERIFICATION_MODE = 'admin_approval'; // 기본값: 관리자 승인

/**
 * 네트워크 설정
 */
export const NETWORK_CONFIG = {
    // TRC20 설정
    trc20: {
        enabled: true,
        name: 'TRC20 (TRON Network)',
        icon: '🌐',
        contractAddress: 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', // USDT TRC20
        decimals: 6,
        explorerUrl: 'https://tronscan.org/#/transaction/',
        apiKey: 'YOUR_TRONGRID_API_KEY' // TronGrid API 키
    },

    // BSC 설정
    bsc: {
        enabled: true,
        name: 'BSC (BNB Smart Chain)',
        icon: '⚡',
        contractAddress: '0x55d398326f99059fF775485246999027B3197955', // USDT BEP20
        decimals: 18,
        explorerUrl: 'https://bscscan.com/tx/',
        apiKey: 'YOUR_BSCSCAN_API_KEY' // BscScan API 키
    }
};

/**
 * 입금 금액 설정
 */
export const DEPOSIT_CONFIG = {
    // 필수 입금 금액 (USDT)
    requiredAmount: 100,

    // 금액 허용 오차 (%)
    tolerance: 0, // 0 = 정확한 금액만 허용, 5 = ±5% 허용

    // 최소 컨펌 수 (블록체인 확인 횟수)
    minConfirmations: {
        trc20: 1,   // TRC20는 1컨펌 권장
        bsc: 12     // BSC는 12컨펌 권장
    }
};

/**
 * UI 설정
 */
export const UI_CONFIG = {
    // 검증 중 표시할 메시지
    messages: {
        verifying: '검증 중입니다...',
        success: '검증이 완료되었습니다!',
        pending: '승인 대기 중입니다.',
        error: '검증에 실패했습니다.'
    },

    // 로딩 시간 (밀리초)
    loadingTimeout: 30000, // 30초

    // 자동 재시도
    autoRetry: {
        enabled: true,
        maxAttempts: 3,
        delay: 5000 // 5초
    }
};

/**
 * 관리자 승인 설정
 */
export const ADMIN_APPROVAL_CONFIG = {
    // 승인 예상 시간 (시간)
    estimatedApprovalTime: 24,

    // 자동 알림 발송
    sendNotifications: true,

    // 승인 대기 중 재제출 허용
    allowResubmit: false,

    // TXID 중복 체크
    checkDuplicate: true
};

/**
 * 개발 모드 설정
 */
export const DEV_CONFIG = {
    // 개발 모드 활성화
    enabled: false,

    // 콘솔 로그 표시
    verbose: true,

    // 테스트 TXID 허용
    allowTestTxid: false,

    // 테스트 TXID 목록
    testTxids: [
        '1234567890123456789012345678901234567890123456789012345678901234' // TRC20 테스트
    ]
};

/**
 * 현재 설정된 검증 모드 가져오기
 */
export function getVerificationMode() {
    return VERIFICATION_MODE;
}

/**
 * 검증 모드에 따른 모듈 가져오기
 */
export async function getVerificationModule() {
    const cacheBuster = '?v=20251124-8';
    switch (VERIFICATION_MODE) {
        case 'trc20_instant':
            return await import('./verification/trc20.js' + cacheBuster);

        case 'bsc_instant':
            return await import('./verification/bsc.js' + cacheBuster);

        case 'admin_approval':
            return await import('./verification/adminApproval.js' + cacheBuster);

        default:
            console.warn(`Unknown verification mode: ${VERIFICATION_MODE}, using admin_approval`);
            return await import('./verification/adminApproval.js' + cacheBuster);
    }
}

/**
 * 네트워크 설정 가져오기
 */
export function getNetworkConfig(network) {
    const networkKey = network.toLowerCase();
    return NETWORK_CONFIG[networkKey] || null;
}

/**
 * 필수 입금 금액 가져오기
 */
export function getRequiredAmount() {
    return DEPOSIT_CONFIG.requiredAmount;
}

/**
 * 설정 검증
 */
export function validateConfig() {
    const errors = [];

    // 검증 모드 확인
    const validModes = ['trc20_instant', 'bsc_instant', 'admin_approval'];
    if (!validModes.includes(VERIFICATION_MODE)) {
        errors.push(`Invalid VERIFICATION_MODE: ${VERIFICATION_MODE}`);
    }

    // 즉시 검증 모드에서 API 키 확인
    if (VERIFICATION_MODE === 'trc20_instant' && !NETWORK_CONFIG.trc20.apiKey) {
        errors.push('TronGrid API key is required for trc20_instant mode');
    }

    if (VERIFICATION_MODE === 'bsc_instant' && !NETWORK_CONFIG.bsc.apiKey) {
        errors.push('BscScan API key is required for bsc_instant mode');
    }

    // 입금 금액 확인
    if (DEPOSIT_CONFIG.requiredAmount <= 0) {
        errors.push('Required deposit amount must be greater than 0');
    }

    if (errors.length > 0) {
        console.error('Configuration errors:', errors);
        return {
            valid: false,
            errors: errors
        };
    }

    return {
        valid: true,
        mode: VERIFICATION_MODE
    };
}

/**
 * 설정 초기화 및 검증
 */
export function initConfig() {
    console.log('=== Signup Configuration ===');
    console.log('Verification Mode:', VERIFICATION_MODE);
    console.log('Required Amount:', DEPOSIT_CONFIG.requiredAmount, 'USDT');
    console.log('Dev Mode:', DEV_CONFIG.enabled);

    const validation = validateConfig();

    if (!validation.valid) {
        console.error('Configuration validation failed!');
        console.error('Errors:', validation.errors);
        return false;
    }

    console.log('✓ Configuration validated successfully');
    return true;
}

// 자동 초기화
if (typeof window !== 'undefined') {
    initConfig();
}
