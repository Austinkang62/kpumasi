/**
 * Main Interactive Entry Point
 * 사용자가 UI에서 검증 방식을 선택하는 대화형 버전
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
    initVerificationSwitcher,
    getCurrentVerification,
    getVerificationModule,
    getVerificationMode,
    getSelectedNetwork
} from './verificationSwitcher.js';

import { loadWalletAddresses } from './verification/base.js';
import { copyToClipboard as copyText } from './verification/base.js';

// 전역 객체로 노출
window.signupApp = {
    copyUserId,
    copyToClipboard,
    goToLoginWithId,
    showAlert,
    checkSponsorPositionAndSelect,
    selectPosition,
    copyWalletAddress
};

/**
 * 지갑 주소 복사
 */
function copyWalletAddress(event) {
    const addressEl = document.getElementById('selectedWalletAddress');
    const address = addressEl ? addressEl.textContent.trim() : '';

    if (!address || address === '네트워크를 선택하세요' || address === '설정되지 않음') {
        showAlert('복사할 주소가 없습니다.', 'error');
        return;
    }

    const btn = event ? event.target : document.getElementById('copyWalletBtn');

    // 클립보드에 복사
    navigator.clipboard.writeText(address).then(() => {
        const originalText = btn.textContent;
        const originalBg = btn.style.background;

        btn.textContent = '✓ 복사됨!';
        btn.style.background = 'linear-gradient(135deg, #10B981, #059669)';

        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = originalBg;
        }, 2000);
    }).catch(err => {
        console.error('복사 실패:', err);
        showAlert('복사에 실패했습니다.', 'error');
    });
}

/**
 * 폼 데이터 수집
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
    const network = getSelectedNetwork() || 'TRC20';
    const mode = getVerificationMode() || 'admin_approval';

    return {
        email,
        password,
        password_confirm: passwordConfirm,
        // 백엔드 API와 호환되도록 필드명 변경
        referral_id: referralCode,
        sponsor_id: sponsorCode || referralCode,
        sponsor_position: selectedPosition || '1',
        txid: txidInput,
        network: network,
        payment_amount: 100,
        memo: memoInput || '',
        verification_mode: mode,
        // 서버 API 호환성을 위한 추가 필드
        bnb_address: txidInput,  // TXID를 임시로 사용 (서버에서 실제 사용하지 않음)
        verification_code: '000000'  // 테스트 모드용 더미 코드
    };
}

/**
 * 회원가입 제출
 */
async function submitSignup(formData) {
    try {
        console.log('=== 회원가입 제출 ===');
        console.log('검증 모드:', formData.verification_mode);
        console.log('네트워크:', formData.network);

        const mode = formData.verification_mode;
        const verificationModule = getVerificationModule();

        if (!verificationModule) {
            showAlert('검증 모듈이 로드되지 않았습니다. 네트워크를 선택해주세요.', 'error');
            return { success: false, message: '검증 모듈 없음' };
        }

        // 즉시 검증 모드
        if (mode === 'trc20_instant' || mode === 'bsc_instant') {
            console.log('⚡ 즉시 검증 시작...');

            // TXID 검증
            const verifyFunc = mode === 'trc20_instant'
                ? verificationModule.verifyTRC20Txid
                : verificationModule.verifyBSCTxid;

            const walletAddresses = await loadWalletAddresses();
            const expectedAddress = mode === 'trc20_instant'
                ? walletAddresses.trc20_usdt_address
                : walletAddresses.bsc_usdt_address;

            showAlert('TXID 검증 중입니다. 잠시만 기다려주세요...', 'info');

            const verification = await verifyFunc(
                formData.txid,
                expectedAddress,
                formData.amount
            );

            if (!verification.success) {
                // 검증 실패 시 관리자 승인으로 전환 제안
                const retry = confirm(
                    `즉시 검증 실패: ${verification.error}\n\n` +
                    `관리자 승인 방식으로 진행하시겠습니까?`
                );

                if (retry) {
                    // 관리자 승인으로 전환
                    formData.verification_mode = 'admin_approval';
                    formData.verification_status = 'pending_approval';
                    console.log('📝 관리자 승인 모드로 전환');
                } else {
                    return {
                        success: false,
                        message: verification.error
                    };
                }
            } else {
                // 검증 성공
                showAlert('✅ TXID 검증 완료! 회원가입 진행 중...', 'success');
                formData.verified_txid = verification.data.txid;
                formData.verified_amount = verification.data.amount;
                formData.verified_network = formData.network;  // 검증된 네트워크
                formData.verification_status = 'verified';
            }
        }
        // 관리자 승인 모드
        else if (mode === 'admin_approval') {
            console.log('📝 관리자 승인 모드');

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

        // 서버에 가입 정보 제출
        console.log('📤 서버에 데이터 제출...');

        // 검증 모드에 따라 다른 API 엔드포인트 호출
        const apiEndpoint = (mode === 'admin_approval' || formData.verification_status === 'pending_approval')
            ? '../api/auth/register-pending.php'  // 관리자 승인
            : '../api/auth/register.php';         // 즉시 검증

        console.log('📍 API 엔드포인트:', apiEndpoint);

        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const responseText = await response.text();
        console.log('📥 서버 응답 (원본):', responseText);
        console.log('📊 HTTP 상태:', response.status, response.statusText);

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ JSON 파싱 오류:', parseError);
            console.error('📄 응답 내용:', responseText.substring(0, 500));

            // 사용자에게 자세한 에러 표시
            throw new Error(
                `서버 응답 형식 오류\n\n` +
                `응답 내용: ${responseText.substring(0, 200)}${responseText.length > 200 ? '...' : ''}\n\n` +
                `개발자 도구 콘솔을 확인하세요.`
            );
        }

        console.log('✅ 파싱된 데이터:', data);
        return data;

    } catch (error) {
        console.error('Signup error:', error);
        throw error;
    }
}

// DOMContentLoaded 이벤트
document.addEventListener('DOMContentLoaded', async () => {
    console.log('=== Signup App (Interactive) Initialized ===');

    // 1. 파티클 생성
    createParticles();

    // 2. 지갑 주소 로드
    try {
        await loadWalletAddresses();
        console.log('✓ 지갑 주소 로드 완료');
    } catch (error) {
        console.error('✗ 지갑 주소 로드 실패:', error);
    }

    // 3. 검증 방식 전환기 초기화
    initVerificationSwitcher();

    // 4. 추천코드 입력 초기화
    initReferralCodeInput();
    console.log('✓ 추천코드 입력 초기화');

    // 5. 위치 박스 초기화
    initPositionBoxes();
    console.log('✓ 위치 박스 초기화');

    // 6. TXID 입력 이벤트
    const txidInput = document.getElementById('txidInput');
    const submitButton = document.getElementById('submitButton');

    if (txidInput && submitButton) {
        txidInput.addEventListener('input', function() {
            const txidValue = this.value.trim();
            const network = getSelectedNetwork();

            // 네트워크가 선택되고 TXID가 최소 길이 이상이면 활성화
            if (network && txidValue.length >= 30) {
                submitButton.disabled = false;
            } else {
                submitButton.disabled = true;
            }
        });
        console.log('✓ TXID 입력 이벤트 등록');
    }

    // 7. 후원코드 입력 이벤트
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

    // 8. 후원 목록 버튼 이벤트
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

    // 9. 회원가입 폼 제출
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            console.log('=== 회원가입 제출 시작 ===');

            // 네트워크 선택 확인
            const network = getSelectedNetwork();
            if (!network) {
                showAlert('네트워크를 선택해주세요 (TRC20 또는 BSC)', 'error');
                return;
            }

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
                    const mode = formData.verification_mode;
                    if (mode === 'admin_approval' || formData.verification_status === 'pending_approval') {
                        showPendingSuccessPopup(registrationEmail);
                    } else {
                        // 즉시 검증 성공
                        const userId = data.data?.user_id || data.data?.id;
                        showSuccessPopup(userId, registrationEmail);
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

    console.log('=== Signup App (Interactive) Ready ===');
});
