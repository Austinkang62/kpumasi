/**
 * USDT Verification Module
 * USDT 입금 검증 및 네트워크 관련 기능
 */

// 전역 변수
let walletAddresses = { bsc_usdt_address: '', trc20_usdt_address: '' };

// 지갑 주소 로드
export async function loadWalletAddresses() {
    try {
        const response = await fetch('../api/settings/get-wallet.php');
        const data = await response.json();

        if (data.success) {
            walletAddresses = data.data;
            // 로드 후 즉시 화면에 표시
            const walletAddressEl = document.getElementById('selectedWalletAddress');
            if (walletAddressEl) {
                walletAddressEl.textContent = walletAddresses.trc20_usdt_address || '설정되지 않음';
            }
            return walletAddresses;
        } else {
            throw new Error('지갑 주소 로드 실패');
        }
    } catch (error) {
        console.error('지갑 주소 로드 오류:', error);
        const walletAddressEl = document.getElementById('selectedWalletAddress');
        if (walletAddressEl) {
            walletAddressEl.textContent = '주소 로드 실패';
        }
        throw error;
    }
}

// 지갑 주소 가져오기
export function getWalletAddresses() {
    return walletAddresses;
}

// 네트워크 선택
export function selectNetwork(network) {
    // 디자인된 경고 팝업 표시
    showNetworkWarningModal();
}

// 네트워크 경고 모달 표시
export function showNetworkWarningModal() {
    // 모달 오버레이 생성
    const overlay = document.createElement('div');
    overlay.id = 'networkWarningOverlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(5px);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    `;

    // 모달 컨텐츠
    overlay.innerHTML = `
        <div style="
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            border: 3px solid #ef4444;
            border-radius: 24px;
            padding: 30px 20px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(239, 68, 68, 0.4);
            animation: scaleIn 0.3s ease;
            text-align: center;
        ">
            <!-- 경고 아이콘 -->
            <div style="font-size: 60px; margin-bottom: 15px; animation: pulse 1.5s infinite;">⚠️</div>

            <!-- 제목 -->
            <h2 style="
                font-size: 22px;
                font-weight: 800;
                color: #ef4444;
                margin-bottom: 15px;
                font-family: 'Inter', sans-serif;
            ">중요 경고</h2>

            <!-- 메인 메시지 -->
            <div style="
                background: rgba(239, 68, 68, 0.15);
                border: 2px solid rgba(239, 68, 68, 0.3);
                border-radius: 12px;
                padding: 15px;
                margin-bottom: 15px;
            ">
                <div style="font-size: 16px; font-weight: 700; color: #fbbf24; margin-bottom: 8px;">
                    🌐 TRC20 (TRON) 네트워크 전용
                </div>
                <div style="font-size: 13px; color: var(--we1-text-primary); line-height: 1.6;">
                    반드시 <strong style="color: #4ade80;">TRC20 네트워크</strong>로만 입금하세요!
                </div>
            </div>

            <!-- 경고 항목 -->
            <div style="text-align: left; margin-bottom: 20px;">
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 10px;
                    background: rgba(239, 68, 68, 0.1);
                    border-radius: 8px;
                    margin-bottom: 8px;
                ">
                    <span style="font-size: 20px; flex-shrink: 0;">❌</span>
                    <span style="font-size: 14px; font-weight: 600; color: #f87171;">BSC (BNB Smart Chain) 아닙니다!</span>
                </div>
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 10px;
                    background: rgba(239, 68, 68, 0.1);
                    border-radius: 8px;
                    margin-bottom: 8px;
                ">
                    <span style="font-size: 20px; flex-shrink: 0;">❌</span>
                    <span style="font-size: 14px; font-weight: 600; color: #f87171;">ERC20 (Ethereum) 아닙니다!</span>
                </div>
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 10px;
                    background: rgba(239, 68, 68, 0.1);
                    border-radius: 8px;
                ">
                    <span style="font-size: 20px; flex-shrink: 0;">💀</span>
                    <span style="font-size: 14px; font-weight: 600; color: #f87171;">잘못된 네트워크 = 복구 불가!</span>
                </div>
            </div>

            <!-- 버튼 -->
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button onclick="window.signupApp.closeNetworkWarning(true)" style="
                    width: 100%;
                    padding: 14px;
                    background: linear-gradient(135deg, #10b981, #059669);
                    color: white;
                    border: none;
                    border-radius: 12px;
                    font-size: 15px;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.3s;
                    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
                ">TRC20 확인, 진행</button>
                <button onclick="window.signupApp.closeNetworkWarning(false)" style="
                    width: 100%;
                    padding: 14px;
                    background: #4b5563;
                    color: white;
                    border: none;
                    border-radius: 12px;
                    font-size: 15px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s;
                ">취소</button>
            </div>
        </div>

        <style>
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes scaleIn {
                from { transform: scale(0.8); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
        </style>
    `;

    document.body.appendChild(overlay);
}

// 경고 모달 닫기
export function closeNetworkWarning(confirmed) {
    const overlay = document.getElementById('networkWarningOverlay');
    if (overlay) {
        overlay.remove();
    }

    if (confirmed) {
        // 확인 시 네트워크 설정 진행
        proceedWithTRC20();
    }
}

// TRC20 네트워크 설정 진행
export function proceedWithTRC20() {
    const trc20Btn = document.getElementById('selectTRC20Btn');
    const networkInput = document.getElementById('selectedNetwork');
    const walletStep = document.getElementById('walletAddressStep');
    const txidStep = document.getElementById('txidVerifyStep');
    const networkLabel = document.getElementById('selectedNetworkLabel');
    const walletAddressEl = document.getElementById('selectedWalletAddress');

    // TRC20 선택 처리
    if (trc20Btn) trc20Btn.classList.add('selected');

    // 네트워크 값 설정
    if (networkInput) networkInput.value = 'TRC20';

    // 레이블 업데이트
    if (networkLabel) networkLabel.textContent = 'TRC20 (TRON)';

    // 지갑 주소 표시
    if (walletAddressEl) {
        walletAddressEl.textContent = walletAddresses.trc20_usdt_address || '설정되지 않음';
    }

    // 다음 단계 표시
    if (walletStep) walletStep.style.display = 'block';
    if (txidStep) txidStep.style.display = 'block';
}

// 지갑 주소 복사
export function copyWalletAddress() {
    const walletAddressEl = document.getElementById('selectedWalletAddress');
    const walletAddress = walletAddressEl ? walletAddressEl.textContent : '';

    if (!walletAddress || walletAddress === '설정되지 않음' || walletAddress === '주소 로드 실패') {
        alert('복사할 주소가 없습니다.');
        return;
    }

    navigator.clipboard.writeText(walletAddress).then(() => {
        const btn = event.target;
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
        alert('주소 복사에 실패했습니다. 직접 복사해주세요.');
    });
}

// TXID 입력 핸들러
export function handleTxidInput(txidValue, submitButton) {
    // TXID는 최소 30자 이상이어야 함
    if (txidValue.length >= 30) {
        if (submitButton) submitButton.disabled = false;
    } else {
        if (submitButton) submitButton.disabled = true;
    }
}

// TXID 검증 (필요시 서버 검증 추가)
export async function verifyTxid(txid, network) {
    try {
        // 클라이언트 측 기본 검증
        if (!txid || txid.length < 30) {
            throw new Error('유효하지 않은 TXID 형식입니다.');
        }

        // 여기에 서버 측 검증 API 호출 추가 가능
        // const response = await fetch('../api/verify-txid.php', {
        //     method: 'POST',
        //     headers: { 'Content-Type': 'application/json' },
        //     body: JSON.stringify({ txid, network })
        // });
        // const data = await response.json();
        // return data;

        // 현재는 기본 검증만 수행
        return {
            success: true,
            message: 'TXID 형식이 유효합니다.',
            txid: txid
        };
    } catch (error) {
        console.error('TXID 검증 오류:', error);
        return {
            success: false,
            message: error.message || 'TXID 검증에 실패했습니다.'
        };
    }
}

// USDT 입금 정보 가져오기
export function getDepositInfo() {
    const txidInput = document.getElementById('txidInput');
    const networkInput = document.getElementById('selectedNetwork');

    return {
        txid: txidInput ? txidInput.value.trim() : '',
        network: networkInput ? networkInput.value : 'TRC20',
        amount: 100 // 고정 금액
    };
}
