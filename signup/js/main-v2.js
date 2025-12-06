/**
 * Main Entry Point v2
 * 다중 검증 방식 지원
 */

import {
    showAlert,
    showSuccessPopup,
    showPendingSuccessPopup,
    createParticles,
    copyUserId,
    copyToClipboard,
    goToLoginWithId
} from './uiComponents.js';

import {
    checkReferralCode,
    initReferralCodeInput,
    validateForm
} from './signupForm.js';

import {
    showSponsorList,
    checkSponsorPosition,
    checkSponsorPositionAndSelect,
    selectPosition,
    loadSponsorList,
    initPositionBoxes
} from './sponsorTree.js';

import {
    getVerificationMode,
    getVerificationModule,
    getRequiredAmount,
    validateConfig
} from './config.js';

import { loadWalletAddresses } from './verification/base.js';

// 현재 사용 중인 검증 모듈
let verificationModule = null;

// 전역 객체로 노출
window.signupApp = {
    copyUserId,
    copyToClipboard,
    goToLoginWithId,
    showAlert,
    checkSponsorPositionAndSelect,
    selectPosition
};

/**
 * 검증 모듈 초기화
 */
async function initVerificationModule() {
    try {
        const mode = getVerificationMode();
        console.log(`📦 Loading verification module: ${mode}`);

        verificationModule = await getVerificationModule();

        console.log('✓ Verification module loaded:', verificationModule.VERIFICATION_NAME);

        // 모듈별 UI 업데이트
        if (verificationModule.updateWalletAddressDisplay) {
            verificationModule.updateWalletAddressDisplay();
        }

        if (verificationModule.updateNetworkInfo) {
            verificationModule.updateNetworkInfo();
        }

        return true;
    } catch (error) {
        console.error('✗ Failed to load verification module:', error);
        showAlert('검증 모듈 로드 실패: ' + error.message, 'error');
        return false;
    }
}

/**
 * 폼 데이터 수집 (검증 모드에 따라)
 */
function collectFormData() {
    const email = document.getElementById('email')?.value.trim();
    const password = document.getElementById('password')?.value;
    const passwordConfirm = document.getElementById('passwordConfirm')?.value;
    const referralCode = document.getElementById('referralCode')?.value.trim().toUpperCase();
    const sponsorCode = document.getElementById('sponsorCode')?.value.trim().toUpperCase();
    const selectedPosition = document.getElementById('selectedPosition')?.value;
    const memoInput = document.getElementById('memoInput')?.value.trim();
    const txidInput = document.getElementById('txidInput')?.value.trim();

    return {
        email,
        password,
        password_confirm: passwordConfirm,
        // 백엔드 API와 호환되도록 필드명 변경
        referral_id: referralCode,
        sponsor_id: sponsorCode || referralCode,
        sponsor_position: selectedPosition || '1',
        txid: txidInput,
        network: 'TRC20', // 설정에서 가져올 수 있음
        payment_amount: getRequiredAmount(),
        memo: memoInput || '',
        verification_mode: getVerificationMode()
    };
}

/**
 * 회원가입 제출
 */
async function submitSignup(formData) {
    try {
        console.log('=== 회원가입 제출 ===');
        console.log('검증 모드:', formData.verification_mode);

        const mode = getVerificationMode();

        // 즉시 검증 모드
        if (mode === 'trc20_instant' || mode === 'bsc_instant') {
            // 1. TXID 검증
            const verifyFunc = mode === 'trc20_instant'
                ? verificationModule.verifyTRC20Txid
                : verificationModule.verifyBSCTxid;

            const walletAddresses = await loadWalletAddresses();
            const expectedAddress = mode === 'trc20_instant'
                ? walletAddresses.trc20_usdt_address
                : walletAddresses.bsc_usdt_address;

            showAlert('TXID 검증 중...', 'info');

            const verification = await verifyFunc(
                formData.txid,
                expectedAddress,
                formData.amount
            );

            if (!verification.success) {
                showAlert(verification.error, 'error');
                return {
                    success: false,
                    message: verification.error
                };
            }

            showAlert('TXID 검증 완료! 회원가입 진행 중...', 'success');

            // 2. 검증 성공 시 회원가입 진행
            formData.verified_txid = verification.data.txid;
            formData.verified_amount = verification.data.amount;
            formData.verification_status = 'verified';
        }
        // 관리자 승인 모드
        else if (mode === 'admin_approval') {
            // 형식만 검증
            const validation = await verificationModule.verifyAdminApproval(
                formData.txid,
                formData.network
            );

            if (!validation.success) {
                showAlert(validation.error, 'error');
                return {
                    success: false,
                    message: validation.error
                };
            }

            formData.verification_status = 'pending_approval';
        }

        // 3. 서버에 가입 정보 제출
        const response = await fetch('../api/auth/signup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const responseText = await response.text();
        console.log('서버 응답:', responseText);

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON 파싱 오류:', parseError);
            throw new Error('서버 응답 형식 오류');
        }

        return data;

    } catch (error) {
        console.error('Signup error:', error);
        throw error;
    }
}

// DOMContentLoaded 이벤트
document.addEventListener('DOMContentLoaded', async () => {
    console.log('=== Signup App v2 Initialized ===');

    // 1. 설정 검증
    const configValid = validateConfig();
    if (!configValid.valid) {
        showAlert('설정 오류가 발생했습니다. 관리자에게 문의하세요.', 'error');
        return;
    }

    // 2. 파티클 생성
    createParticles();

    // 3. 지갑 주소 로드
    try {
        await loadWalletAddresses();
        console.log('✓ 지갑 주소 로드 완료');
    } catch (error) {
        console.error('✗ 지갑 주소 로드 실패:', error);
    }

    // 4. 검증 모듈 초기화
    const moduleLoaded = await initVerificationModule();
    if (!moduleLoaded) {
        return;
    }

    // 5. 추천코드 입력 초기화
    initReferralCodeInput();
    console.log('✓ 추천코드 입력 초기화');

    // 6. 위치 박스 초기화
    initPositionBoxes();
    console.log('✓ 위치 박스 초기화');

    // 7. TXID 입력 이벤트
    const txidInput = document.getElementById('txidInput');
    const submitButton = document.getElementById('submitButton');

    if (txidInput && submitButton) {
        txidInput.addEventListener('input', function() {
            const txidValue = this.value.trim();
            // TXID는 최소 30자 이상이어야 함
            if (txidValue.length >= 30) {
                submitButton.disabled = false;
            } else {
                submitButton.disabled = true;
            }
        });
        console.log('✓ TXID 입력 이벤트 등록');
    }

    // 8. 후원코드 입력 이벤트
    const sponsorCodeInput = document.getElementById('sponsorCode');
    const selectSponsorBtn = document.getElementById('selectSponsorBtn');

    if (sponsorCodeInput && selectSponsorBtn) {
        sponsorCodeInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase();

            if (e.target.value.trim().length > 0) {
                selectSponsorBtn.disabled = false;
                selectSponsorBtn.style.opacity = '1';
            } else {
                selectSponsorBtn.disabled = true;
                selectSponsorBtn.style.opacity = '0.5';
            }
        });
        console.log('✓ 후원코드 입력 이벤트 등록');
    }

    // 9. 후원 목록 버튼 이벤트
    if (selectSponsorBtn && sponsorCodeInput) {
        selectSponsorBtn.addEventListener('click', async () => {
            const currentSponsorCode = sponsorCodeInput.value.trim().toUpperCase();

            if (!currentSponsorCode) {
                showAlert('후원코드를 입력해주세요', 'error');
                sponsorCodeInput.focus();
                return;
            }

            selectSponsorBtn.disabled = true;
            selectSponsorBtn.textContent = '조회 중...';

            try {
                const data = await loadSponsorList(currentSponsorCode);

                if (data.success) {
                    if (!data.data.sponsor_list || data.data.sponsor_list.length === 0) {
                        showAlert('후원 가능한 자리가 없습니다', 'info');
                        selectSponsorBtn.disabled = false;
                        selectSponsorBtn.textContent = '후원목록';
                        return;
                    }

                    showSponsorList(data.data.sponsor_list);
                    selectSponsorBtn.textContent = '✓ 목록 표시';
                    selectSponsorBtn.disabled = false;
                } else {
                    showAlert(data.message || '조회 실패', 'error');
                    selectSponsorBtn.disabled = false;
                    selectSponsorBtn.textContent = '후원목록';
                }
            } catch (error) {
                console.error('Sponsor list error:', error);
                showAlert('조회 중 오류가 발생했습니다', 'error');
                selectSponsorBtn.disabled = false;
                selectSponsorBtn.textContent = '후원목록';
            }
        });
        console.log('✓ 후원 목록 버튼 이벤트 등록');
    }

    // 10. 회원가입 폼 제출
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            console.log('=== 회원가입 제출 시작 ===');

            // 유효성 검증
            if (!validateForm()) {
                return;
            }

            const button = document.getElementById('submitButton');
            button.disabled = true;
            button.textContent = '처리중...';

            try {
                // 폼 데이터 수집
                const formData = collectFormData();
                console.log('제출 데이터:', formData);

                // 서버에 전송
                const data = await submitSignup(formData);

                if (data.success) {
                    const registrationEmail = data.data?.email || formData.email;
                    console.log(`✅ 가입신청 성공! 이메일: ${registrationEmail}`);

                    // 기존 알림 숨기기
                    document.getElementById('alertMessage').style.display = 'none';

                    // 검증 모드에 따라 다른 팝업 표시
                    const mode = getVerificationMode();
                    if (mode === 'admin_approval') {
                        showPendingSuccessPopup(registrationEmail);
                    } else {
                        // 즉시 검증 모드는 바로 성공
                        showSuccessPopup(data.data.user_id, registrationEmail);
                    }
                } else {
                    showAlert(data.message || '가입신청에 실패했습니다', 'error');
                    button.textContent = '가입신청';
                    button.disabled = false;
                }
            } catch (error) {
                console.error('Signup error:', error);
                showAlert('네트워크 오류가 발생했습니다: ' + error.message, 'error');
                button.textContent = '가입신청';
                button.disabled = false;
            }
        });
        console.log('✓ 회원가입 폼 이벤트 등록');
    }

    console.log('=== Signup App v2 Ready ===');
    console.log('검증 모드:', getVerificationMode());
});
