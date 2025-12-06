/**
 * Signup Form Module
 * 회원가입 폼 처리 및 추천코드 검증
 */

import { showAlert, showSuccessPopup, showPendingSuccessPopup } from './uiComponents.js';
import { getDepositInfo } from './usdtVerification.js';

let referralCheckTimeout = null;

// 추천코드 검증 함수
export async function checkReferralCode(referralCodeInput) {
    // readOnly 필드는 수정할 수 없음
    if (referralCodeInput.readOnly) {
        return;
    }

    const referralCode = referralCodeInput.value.trim().toUpperCase();

    if (referralCode === '') {
        // 빈 값이면 에러 표시 (필수이므로)
        const resultDiv = document.getElementById('referralResult');
        resultDiv.style.display = 'block';
        resultDiv.style.color = '#EF4444';
        resultDiv.innerHTML = '❌ 추천코드는 필수 입력사항입니다';
        referralCodeInput.style.borderColor = '#EF4444';
        return;
    }

    if (referralCode.length !== 8) {
        // 8자리가 아니면 에러 표시
        const resultDiv = document.getElementById('referralResult');
        resultDiv.style.display = 'block';
        resultDiv.style.color = '#EF4444';
        resultDiv.innerHTML = '❌ 추천코드는 8자리여야 합니다';
        referralCodeInput.style.borderColor = '#EF4444';
        return;
    }

    // 대문자로 변환 후 저장
    referralCodeInput.value = referralCode;

    try {
        // 현재 페이지의 기준 경로 확인
        const currentPath = window.location.pathname;
        const basePath = currentPath.includes('/html/')
            ? '../api/auth/check-referral.php'
            : '../api/auth/check-referral.php';

        const apiUrl = `${basePath}?code=${encodeURIComponent(referralCode)}`;
        console.log('추천코드 API 호출:', apiUrl);

        const response = await fetch(apiUrl);
        const data = await response.json();

        const resultDiv = document.getElementById('referralResult');

        if (data.success) {
            // 성공 - 추천인 정보 표시
            resultDiv.innerHTML = `
                <div style="padding: 12px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; color: #6EE7B7;">
                    <strong>✅ ${data.message}</strong><br>
                    <small style="color: var(--we1-text-secondary); margin-top: 6px; display: block;">
                        회원코드: ${data.data.user_id} ${data.data.name ? '(' + data.data.name + ')' : ''}
                    </small>
                </div>
            `;
            resultDiv.style.display = 'block';
            referralCodeInput.style.borderColor = '#10B981';

            // 후원코드 입력란에 추천코드 자동 기입
            const sponsorCodeInput = document.getElementById('sponsorCode');
            if (sponsorCodeInput) {
                sponsorCodeInput.value = referralCode;
            }

            // "선택" 버튼 활성화
            const selectSponsorBtn = document.getElementById('selectSponsorBtn');
            if (selectSponsorBtn) {
                selectSponsorBtn.disabled = false;
                selectSponsorBtn.style.opacity = '1';
            }

            return true;
        } else {
            // 실패
            resultDiv.innerHTML = `
                <div style="padding: 12px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; color: #FCA5A5;">
                    <strong>❌ ${data.message}</strong>
                </div>
            `;
            resultDiv.style.display = 'block';
            referralCodeInput.style.borderColor = '#EF4444';
            return false;
        }
    } catch (error) {
        console.error('Check referral error:', error);
        const resultDiv = document.getElementById('referralResult');
        resultDiv.innerHTML = `
            <div style="padding: 12px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; color: #FCA5A5;">
                <strong>❌ 조회 중 오류가 발생했습니다</strong>
            </div>
        `;
        resultDiv.style.display = 'block';
        return false;
    }
}

// 추천코드 입력 핸들러 초기화
export function initReferralCodeInput() {
    const referralCodeInput = document.getElementById('referralCode');
    if (!referralCodeInput) return;

    // 입력 시 자동 검증 (debounce 적용)
    referralCodeInput.addEventListener('input', (e) => {
        // 대문자 변환
        e.target.value = e.target.value.toUpperCase();

        // 이전 타이머 취소
        if (referralCheckTimeout) {
            clearTimeout(referralCheckTimeout);
        }

        // 0.5초 후 검증
        referralCheckTimeout = setTimeout(() => {
            checkReferralCode(referralCodeInput);
        }, 500);
    });
}

// 회원가입 폼 제출
export async function submitSignupForm(formData) {
    try {
        console.log('===== 가입신청 시작 =====');
        console.log('제출 데이터:', formData);

        // 현재 페이지의 기준 경로 확인
        const currentPath = window.location.pathname;
        const basePath = currentPath.includes('/html/')
            ? '../api/auth/signup.php'
            : '../api/auth/signup.php';

        console.log('API 경로:', basePath);

        const response = await fetch(basePath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        console.log('응답 상태:', response.status, response.statusText);

        // 응답 텍스트 먼저 확인
        const responseText = await response.text();
        console.log('응답 텍스트:', responseText.substring(0, 500));

        // JSON 파싱 시도
        let data;
        try {
            data = JSON.parse(responseText);
            console.log('파싱된 응답:', data);
        } catch (parseError) {
            console.error('JSON 파싱 에러:', parseError);
            console.error('전체 응답:', responseText);
            throw new Error('서버 응답이 올바른 JSON 형식이 아닙니다: ' + responseText.substring(0, 200));
        }

        // 서버 응답 로그
        console.log('===== 서버 응답 =====');
        console.log('성공 여부:', data.success);
        console.log('메시지:', data.message);
        console.log('응답 데이터:', data.data);
        console.log('===================');

        return data;
    } catch (error) {
        console.error('Signup error:', error);
        throw error;
    }
}

// 폼 데이터 수집
export function collectFormData() {
    const email = document.getElementById('email')?.value.trim();
    const password = document.getElementById('password')?.value;
    const passwordConfirm = document.getElementById('passwordConfirm')?.value;
    const referralCode = document.getElementById('referralCode')?.value.trim().toUpperCase();
    const sponsorCode = document.getElementById('sponsorCode')?.value.trim().toUpperCase();
    const selectedPosition = document.getElementById('selectedPosition')?.value;
    const memoInput = document.getElementById('memoInput')?.value.trim();

    // USDT 입금 정보
    const depositInfo = getDepositInfo();

    return {
        email,
        password,
        password_confirm: passwordConfirm,
        // 백엔드 API와 호환되도록 필드명 변경
        referral_id: referralCode,
        sponsor_id: sponsorCode || referralCode, // 후원코드 없으면 추천코드 사용
        sponsor_position: selectedPosition || '1', // 기본값 1 (좌측)
        txid: depositInfo.txid,
        network: depositInfo.network,
        payment_amount: depositInfo.amount,
        memo: memoInput || ''
    };
}

// 폼 유효성 검증
export function validateForm() {
    const email = document.getElementById('email')?.value.trim();
    const password = document.getElementById('password')?.value;
    const passwordConfirm = document.getElementById('passwordConfirm')?.value;
    const referralCode = document.getElementById('referralCode')?.value.trim();
    const txidInput = document.getElementById('txidInput')?.value.trim();

    // 이메일 검증
    if (!email) {
        showAlert('이메일을 입력해주세요', 'error');
        return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showAlert('올바른 이메일 형식이 아닙니다', 'error');
        return false;
    }

    // 비밀번호 검증
    if (!password || password.length < 8) {
        showAlert('비밀번호는 8자 이상이어야 합니다', 'error');
        return false;
    }

    if (password !== passwordConfirm) {
        showAlert('비밀번호가 일치하지 않습니다', 'error');
        return false;
    }

    // 추천코드 검증
    if (!referralCode || referralCode.length !== 8) {
        showAlert('추천코드를 정확히 입력해주세요 (8자리)', 'error');
        return false;
    }

    // TXID 검증
    if (!txidInput || txidInput.length < 30) {
        showAlert('TXID를 입력해주세요 (최소 30자)', 'error');
        return false;
    }

    return true;
}
