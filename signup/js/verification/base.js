/**
 * Base Verification Module
 * 모든 검증 방식의 공통 로직
 */

// 지갑 주소 저장
let walletAddresses = {
    trc20_usdt_address: '',
    bsc_usdt_address: ''
};

/**
 * 지갑 주소 로드 (공통)
 */
export async function loadWalletAddresses() {
    try {
        const response = await fetch('../../api/settings/get-wallet.php');
        const data = await response.json();

        if (data.success) {
            walletAddresses = data.data;
            return walletAddresses;
        } else {
            throw new Error('지갑 주소 로드 실패');
        }
    } catch (error) {
        console.error('지갑 주소 로드 오류:', error);
        throw error;
    }
}

/**
 * 지갑 주소 가져오기
 */
export function getWalletAddresses() {
    return walletAddresses;
}

/**
 * TXID 형식 기본 검증 (공통)
 */
export function validateTxidFormat(txid, network) {
    if (!txid || typeof txid !== 'string') {
        return {
            valid: false,
            error: 'TXID가 입력되지 않았습니다.'
        };
    }

    const trimmedTxid = txid.trim();

    // TRC20 (TRON) - 64자 16진수
    if (network === 'TRC20') {
        if (trimmedTxid.length !== 64) {
            return {
                valid: false,
                error: 'TRC20 TXID는 64자여야 합니다.'
            };
        }
        if (!/^[0-9a-fA-F]{64}$/.test(trimmedTxid)) {
            return {
                valid: false,
                error: 'TRC20 TXID는 16진수(0-9, a-f)만 포함해야 합니다.'
            };
        }
    }

    // BSC (BNB Smart Chain) - 0x + 64자 16진수
    if (network === 'BSC') {
        if (!trimmedTxid.startsWith('0x')) {
            return {
                valid: false,
                error: 'BSC TXID는 0x로 시작해야 합니다.'
            };
        }
        if (trimmedTxid.length !== 66) { // 0x + 64자
            return {
                valid: false,
                error: 'BSC TXID는 66자(0x 포함)여야 합니다.'
            };
        }
        if (!/^0x[0-9a-fA-F]{64}$/.test(trimmedTxid)) {
            return {
                valid: false,
                error: 'BSC TXID 형식이 올바르지 않습니다.'
            };
        }
    }

    return {
        valid: true,
        txid: trimmedTxid
    };
}

/**
 * 클립보드에 텍스트 복사 (공통)
 */
export async function copyToClipboard(text, buttonElement = null) {
    try {
        await navigator.clipboard.writeText(text);

        if (buttonElement) {
            const originalText = buttonElement.textContent;
            const originalBg = buttonElement.style.background;

            buttonElement.textContent = '✓ 복사됨!';
            buttonElement.style.background = 'linear-gradient(135deg, #10B981, #059669)';

            setTimeout(() => {
                buttonElement.textContent = originalText;
                buttonElement.style.background = originalBg;
            }, 2000);
        }

        return true;
    } catch (err) {
        console.error('복사 실패:', err);
        return false;
    }
}

/**
 * 금액 검증 (공통)
 */
export function validateAmount(amount, expectedAmount = 100) {
    const numAmount = parseFloat(amount);

    if (isNaN(numAmount)) {
        return {
            valid: false,
            error: '유효하지 않은 금액입니다.'
        };
    }

    // 정확히 $100만 허용 (±$0.10 오차 허용)
    const tolerance = 0.10;
    const difference = Math.abs(numAmount - expectedAmount);

    if (difference > tolerance) {
        const minAmount = (expectedAmount - tolerance).toFixed(2);
        const maxAmount = (expectedAmount + tolerance).toFixed(2);
        return {
            valid: false,
            error: `정확히 ${expectedAmount} USDT만 입금 가능합니다. (허용 범위: ${minAmount} ~ ${maxAmount} USDT)\n입금액: ${numAmount.toFixed(2)} USDT`
        };
    }

    return {
        valid: true,
        amount: numAmount
    };
}

/**
 * 네트워크 표시 이름 가져오기
 */
export function getNetworkDisplayName(network) {
    const names = {
        'TRC20': 'TRC20 (TRON Network)',
        'BSC': 'BSC (BNB Smart Chain)',
        'ERC20': 'ERC20 (Ethereum)'
    };
    return names[network] || network;
}

/**
 * 네트워크별 탐색기 URL 생성
 */
export function getExplorerUrl(txid, network) {
    const explorers = {
        'TRC20': `https://tronscan.org/#/transaction/${txid}`,
        'BSC': `https://bscscan.com/tx/${txid}`,
        'ERC20': `https://etherscan.io/tx/${txid}`
    };
    return explorers[network] || '#';
}
