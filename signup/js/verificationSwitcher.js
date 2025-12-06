/**
 * Verification Switcher Module
 * 사용자가 UI에서 검증 방식을 선택하고 전환할 수 있게 함
 */

import { loadWalletAddresses } from './verification/base.js';

// 현재 선택된 검증 설정
let currentVerification = {
    mode: null,           // 'trc20_instant', 'bsc_instant', 'admin_approval'
    network: null,        // 'TRC20', 'BSC'
    module: null          // 로드된 검증 모듈
};

/**
 * 네트워크 선택 (TRC20 또는 BSC)
 */
export async function selectNetwork(network) {
    console.log(`🌐 네트워크 선택: ${network}`);

    // 선택된 네트워크 저장
    currentVerification.network = network;
    document.getElementById('selectedNetwork').value = network;

    // 즉시 검증 모드로 설정
    const mode = network === 'TRC20' ? 'trc20_instant' : 'bsc_instant';
    await switchVerificationMode(mode, network);

    // UI 업데이트
    updateNetworkUI(network);
}

/**
 * 관리자 승인 방식으로 전환
 */
export async function switchToAdminApproval() {
    console.log('📝 관리자 승인 방식으로 전환');

    // 네트워크가 선택되지 않았으면 TRC20을 기본으로
    const network = currentVerification.network || 'TRC20';

    await switchVerificationMode('admin_approval', network);
    updateAdminApprovalUI(network);
}

/**
 * 검증 모드 전환
 */
async function switchVerificationMode(mode, network) {
    console.log(`🔄 검증 모드 전환: ${mode} (${network})`);

    currentVerification.mode = mode;
    document.getElementById('verificationMode').value = mode;

    // 해당 검증 모듈 로드 (캐시 busting 적용)
    const cacheBuster = '?v=20251124-8';
    try {
        switch (mode) {
            case 'trc20_instant':
                currentVerification.module = await import('./verification/trc20.js' + cacheBuster);
                break;

            case 'bsc_instant':
                currentVerification.module = await import('./verification/bsc.js' + cacheBuster);
                break;

            case 'admin_approval':
                currentVerification.module = await import('./verification/adminApproval.js' + cacheBuster);
                break;

            default:
                throw new Error(`Unknown verification mode: ${mode}`);
        }

        console.log('✓ 검증 모듈 로드 완료:', currentVerification.module.VERIFICATION_NAME);
        return true;

    } catch (error) {
        console.error('✗ 검증 모듈 로드 실패:', error);
        return false;
    }
}

/**
 * 네트워크 선택 UI 업데이트 (즉시 검증)
 */
function updateNetworkUI(network) {
    // 버튼 스타일 업데이트
    const trc20Btn = document.getElementById('selectTRC20Btn');
    const bscBtn = document.getElementById('selectBSCBtn');

    // 모든 버튼 초기화
    [trc20Btn, bscBtn].forEach(btn => {
        btn.style.borderColor = 'rgba(212, 175, 55, 0.3)';
        btn.style.background = 'rgba(255, 255, 255, 0.05)';
        btn.style.transform = 'scale(1)';
    });

    // 선택된 버튼 강조
    const selectedBtn = network === 'TRC20' ? trc20Btn : bscBtn;
    selectedBtn.style.borderColor = 'var(--we1-gold)';
    selectedBtn.style.background = 'rgba(212, 175, 55, 0.15)';
    selectedBtn.style.transform = 'scale(1.05)';
    selectedBtn.style.boxShadow = '0 0 20px rgba(212, 175, 55, 0.3)';

    // 즉시 검증 안내 표시
    const verificationInfo = document.getElementById('verificationMethodInfo');
    verificationInfo.style.display = 'block';

    // 지갑 주소 섹션 표시
    showWalletAddressSection(network);

    // TXID 입력 섹션 표시
    const txidSection = document.getElementById('txidInputSection');
    txidSection.style.display = 'block';

    // TXID 힌트 업데이트
    const txidHint = document.getElementById('txidHint');
    txidHint.innerHTML = `
        <strong style="color: var(--we1-gold);">⚡ 즉시 검증</strong><br>
        TXID 입력 시 블록체인에서 자동으로 검증되어 즉시 가입이 완료됩니다.
    `;

    // 안내 사항 표시
    showDepositNotice(network, 'instant');

    // 메모 입력란 숨김 (즉시 검증 모드에서는 불필요)
    const memoSection = document.getElementById('memoSection');
    if (memoSection) {
        memoSection.style.display = 'none';
    }
}

/**
 * 관리자 승인 UI 업데이트
 */
function updateAdminApprovalUI(network) {
    // 네트워크 버튼 - 선택된 버튼은 액티브 상태 유지
    const trc20Btn = document.getElementById('selectTRC20Btn');
    const bscBtn = document.getElementById('selectBSCBtn');

    // 모든 버튼 초기화
    [trc20Btn, bscBtn].forEach(btn => {
        btn.style.borderColor = 'rgba(212, 175, 55, 0.3)';
        btn.style.background = 'rgba(255, 255, 255, 0.05)';
        btn.style.opacity = '0.5';
        btn.style.transform = 'scale(1)';
        btn.style.boxShadow = 'none';
    });

    // 선택된 네트워크 버튼은 액티브 상태 유지
    const selectedBtn = network === 'TRC20' ? trc20Btn : bscBtn;
    selectedBtn.style.borderColor = 'var(--we1-gold)';
    selectedBtn.style.background = 'rgba(212, 175, 55, 0.15)';
    selectedBtn.style.opacity = '1';
    selectedBtn.style.transform = 'scale(1.05)';
    selectedBtn.style.boxShadow = '0 0 20px rgba(212, 175, 55, 0.3)';

    // 검증 방식 안내 변경
    const verificationInfo = document.getElementById('verificationMethodInfo');
    verificationInfo.style.background = 'rgba(59, 130, 246, 0.1)';
    verificationInfo.style.borderColor = 'rgba(59, 130, 246, 0.3)';
    verificationInfo.innerHTML = `
        <div style="font-size: 12px; color: #93C5FD; line-height: 1.6;">
            <strong>📝 관리자 승인 모드</strong><br>
            TXID 입력 후 관리자가 확인하여 승인합니다. (24시간 이내)
        </div>
    `;
    verificationInfo.style.display = 'block';

    // 지갑 주소 섹션 표시
    showWalletAddressSection(network);

    // TXID 입력 섹션 표시
    const txidSection = document.getElementById('txidInputSection');
    txidSection.style.display = 'block';

    // TXID 힌트 업데이트
    const txidHint = document.getElementById('txidHint');
    txidHint.innerHTML = `
        <strong style="color: #3B82F6;">📝 관리자 승인</strong><br>
        입금 후 TXID를 입력하세요. 관리자 확인 후 가입이 완료됩니다. (24시간 이내)
    `;

    // 안내 사항 표시
    showDepositNotice(network, 'admin');

    // 메모 입력란 표시 (관리자 승인 모드에서 필요)
    const memoSection = document.getElementById('memoSection');
    if (memoSection) {
        memoSection.style.display = 'block';
    }

    // 가입신청 버튼 활성화 여부 확인
    const txidInput = document.getElementById('txidInput');
    const submitButton = document.getElementById('submitButton');
    if (txidInput && submitButton) {
        const txidValue = txidInput.value.trim();
        // TXID가 입력되어 있으면 버튼 활성화
        if (txidValue.length >= 30) {
            submitButton.disabled = false;
            console.log('✅ 관리자 승인 모드: 가입신청 버튼 활성화');
        }
    }
}

/**
 * 지갑 주소 섹션 표시
 */
async function showWalletAddressSection(network) {
    const section = document.getElementById('walletAddressSection');
    const label = document.getElementById('selectedNetworkLabel');
    const addressEl = document.getElementById('selectedWalletAddress');
    const copyBtn = document.getElementById('copyWalletBtn');

    // 섹션 표시
    section.style.display = 'block';

    // 라벨 업데이트
    const networkNames = {
        'TRC20': 'TRC20 (TRON Network) 입금 주소',
        'BSC': 'BSC (BNB Smart Chain) 입금 주소'
    };
    label.textContent = networkNames[network] || '입금 주소';

    // 지갑 주소 로드
    try {
        const walletAddresses = await loadWalletAddresses();
        const address = network === 'TRC20'
            ? walletAddresses.trc20_usdt_address
            : walletAddresses.bsc_usdt_address;

        addressEl.textContent = address || '설정되지 않음';

        // 주소가 있으면 복사 버튼 표시 및 재설정
        if (address) {
            // 복사 버튼을 완전히 새로 만들어서 교체
            const oldBtn = document.getElementById('copyWalletBtn');
            const newBtn = document.createElement('button');
            newBtn.type = 'button';
            newBtn.id = 'copyWalletBtn';
            newBtn.textContent = '📋 복사';
            newBtn.style.cssText = oldBtn.style.cssText;

            // 클릭 이벤트 추가
            newBtn.onclick = function() {
                console.log('🖱️ 복사 버튼 클릭!');
                const addr = addressEl.textContent.trim();
                console.log('주소:', addr);

                if (!addr || addr === '설정되지 않음' || addr === '주소 로드 실패') {
                    alert('복사할 주소가 없습니다.');
                    return;
                }

                navigator.clipboard.writeText(addr).then(() => {
                    console.log('✅ 복사 성공!');
                    const orig = newBtn.textContent;
                    newBtn.textContent = '✓ 복사됨!';
                    newBtn.style.background = '#10b981';

                    setTimeout(() => {
                        newBtn.textContent = orig;
                        newBtn.style.background = '#d4af37';
                    }, 2000);
                }).catch(err => {
                    console.error('❌ 복사 실패:', err);
                    alert('복사 실패: ' + err.message);
                });
            };

            // 기존 버튼 교체
            oldBtn.parentNode.replaceChild(newBtn, oldBtn);
            newBtn.style.display = 'block';
            console.log('✅ 복사 버튼 재생성 완료');
        }
    } catch (error) {
        console.error('지갑 주소 로드 오류:', error);
        addressEl.textContent = '주소 로드 실패';
    }
}

/**
 * 입금 안내 사항 표시
 */
function showDepositNotice(network, mode) {
    const notice = document.getElementById('depositNotice');
    const content = document.getElementById('depositNoticeContent');

    notice.style.display = 'block';

    const networkName = network === 'TRC20' ? 'TRC20 (TRON)' : 'BSC (BNB Smart Chain)';

    if (mode === 'instant') {
        // 즉시 검증 안내
        notice.style.background = 'rgba(16, 185, 129, 0.1)';
        notice.style.borderColor = 'rgba(16, 185, 129, 0.3)';
        content.innerHTML = `
            <strong style="color: #10B981; display: block; margin-bottom: 8px;">⚡ 즉시 검증 모드</strong>
            <span style="color: var(--we1-text-secondary);">
                • 반드시 <strong style="color: var(--we1-gold);">${networkName}</strong> 네트워크로만 입금하세요<br>
                • TXID 입력 시 블록체인에서 자동으로 검증됩니다<br>
                • 검증 완료 후 <strong>즉시 가입</strong>이 완료됩니다<br>
                • 잘못된 네트워크로 입금 시 복구가 불가능합니다
            </span>
        `;
    } else {
        // 관리자 승인 안내
        notice.style.background = 'rgba(59, 130, 246, 0.1)';
        notice.style.borderColor = 'rgba(59, 130, 246, 0.3)';
        content.innerHTML = `
            <strong style="color: #3B82F6; display: block; margin-bottom: 8px;">📝 관리자 승인 모드</strong>
            <span style="color: var(--we1-text-secondary);">
                • <strong style="color: var(--we1-gold);">${networkName}</strong> 네트워크로 입금하세요<br>
                • TXID 입력 후 관리자가 확인합니다<br>
                • 승인 완료 시 이메일로 회원코드가 발송됩니다<br>
                • 예상 처리 시간: <strong style="color: var(--we1-gold);">24시간 이내</strong>
            </span>
        `;
    }
}

/**
 * 현재 검증 설정 가져오기
 */
export function getCurrentVerification() {
    return currentVerification;
}

/**
 * 검증 모듈 가져오기
 */
export function getVerificationModule() {
    return currentVerification.module;
}

/**
 * 검증 모드 가져오기
 */
export function getVerificationMode() {
    return currentVerification.mode;
}

/**
 * 네트워크 가져오기
 */
export function getSelectedNetwork() {
    return currentVerification.network;
}

/**
 * 초기화
 */
export function initVerificationSwitcher() {
    console.log('🎛️ Verification Switcher 초기화');

    // TRC20 버튼 이벤트
    const trc20Btn = document.getElementById('selectTRC20Btn');
    if (trc20Btn) {
        trc20Btn.addEventListener('click', () => selectNetwork('TRC20'));
    }

    // BSC 버튼 이벤트
    const bscBtn = document.getElementById('selectBSCBtn');
    if (bscBtn) {
        bscBtn.addEventListener('click', () => selectNetwork('BSC'));
    }

    // 관리자 승인 전환 버튼
    const switchBtn = document.getElementById('switchToAdminApprovalBtn');
    if (switchBtn) {
        switchBtn.addEventListener('click', () => switchToAdminApproval());
    }

    console.log('✓ Verification Switcher 초기화 완료');
}
