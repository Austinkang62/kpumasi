/**
 * Main Entry Point
 * 모든 모듈을 통합하고 초기화
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
    loadWalletAddresses,
    handleTxidInput,
    copyWalletAddress,
    closeNetworkWarning,
    selectNetwork
} from './usdtVerification.js';

import {
    checkReferralCode,
    initReferralCodeInput,
    submitSignupForm,
    collectFormData,
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

// 전역 객체로 노출 (HTML onclick 이벤트에서 사용)
window.signupApp = {
    copyUserId,
    copyToClipboard,
    goToLoginWithId,
    closeNetworkWarning,
    selectNetwork,
    showAlert,
    checkSponsorPositionAndSelect,
    selectPosition,
    copyWalletAddress
};

// DOMContentLoaded 이벤트
document.addEventListener('DOMContentLoaded', async () => {
    console.log('=== Signup App Initialized ===');

    // 파티클 생성
    createParticles();

    // 지갑 주소 로드
    try {
        await loadWalletAddresses();
        console.log('✓ 지갑 주소 로드 완료');
    } catch (error) {
        console.error('✗ 지갑 주소 로드 실패:', error);
    }

    // 추천코드 입력 초기화
    initReferralCodeInput();
    console.log('✓ 추천코드 입력 초기화');

    // 위치 박스 초기화
    initPositionBoxes();
    console.log('✓ 위치 박스 초기화');

    // TXID 입력 이벤트
    const txidInput = document.getElementById('txidInput');
    const submitButton = document.getElementById('submitButton');

    if (txidInput && submitButton) {
        txidInput.addEventListener('input', function() {
            handleTxidInput(this.value.trim(), submitButton);
        });
        console.log('✓ TXID 입력 이벤트 등록');
    }

    // 후원코드 입력 이벤트
    const sponsorCodeInput = document.getElementById('sponsorCode');
    const selectSponsorBtn = document.getElementById('selectSponsorBtn');

    if (sponsorCodeInput) {
        sponsorCodeInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.toUpperCase();

            // 후원코드 입력란에 값이 있으면 "선택" 버튼 활성화
            if (selectSponsorBtn) {
                if (e.target.value.trim().length > 0) {
                    selectSponsorBtn.disabled = false;
                    selectSponsorBtn.style.opacity = '1';
                } else {
                    selectSponsorBtn.disabled = true;
                    selectSponsorBtn.style.opacity = '0.5';
                }
            }
        });
        console.log('✓ 후원코드 입력 이벤트 등록');
    }

    // 후원 가능 회원 선택 버튼
    if (selectSponsorBtn && sponsorCodeInput) {
        selectSponsorBtn.addEventListener('click', async () => {
            const currentSponsorCode = sponsorCodeInput.value.trim().toUpperCase();

            if (!currentSponsorCode) {
                showAlert('후원코드를 입력해주세요', 'error');
                sponsorCodeInput.focus();
                return;
            }

            // 버튼 상태 변경
            selectSponsorBtn.disabled = true;
            selectSponsorBtn.textContent = '조회 중...';

            try {
                const data = await loadSponsorList(currentSponsorCode);

                if (data.success) {
                    // 리스트가 있는지 확인
                    if (!data.data.sponsor_list || data.data.sponsor_list.length === 0) {
                        showAlert('후원 가능한 자리가 없습니다', 'info');
                        selectSponsorBtn.disabled = false;
                        selectSponsorBtn.textContent = '후원목록';
                        return;
                    }

                    // 리스트 표시
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

    // 회원가입 폼 제출
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
                const data = await submitSignupForm(formData);

                if (data.success) {
                    const registrationEmail = data.data?.email || formData.email;
                    console.log(`✅ 가입신청 성공! 이메일: ${registrationEmail}`);

                    // 기존 알림 숨기기
                    document.getElementById('alertMessage').style.display = 'none';

                    // 가입신청 성공 팝업 표시
                    showPendingSuccessPopup(registrationEmail);
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

    console.log('=== Signup App Ready ===');
});
