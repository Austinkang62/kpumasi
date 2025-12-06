/**
 * TRC20 Instant Verification Module
 * TRON 블록체인 API를 통한 실시간 TXID 검증
 */

import { validateTxidFormat, validateAmount, getWalletAddresses, getExplorerUrl } from './base.js';

const NETWORK = 'TRC20';
const TRONGRID_API_KEY = 'YOUR_TRONGRID_API_KEY'; // 실제 API 키로 교체 필요

/**
 * TRC20 TXID 즉시 검증
 * @param {string} txid - 트랜잭션 ID
 * @param {string} expectedAddress - 예상 수신 주소
 * @param {number} expectedAmount - 예상 금액 (USDT)
 * @returns {Object} 검증 결과
 */
export async function verifyTRC20Txid(txid, expectedAddress, expectedAmount = 100) {
    console.log('=== TRC20 TXID 검증 시작 ===');
    console.log('TXID:', txid);
    console.log('예상 주소:', expectedAddress);
    console.log('예상 금액:', expectedAmount);

    // 1. 형식 검증
    const formatCheck = validateTxidFormat(txid, NETWORK);
    if (!formatCheck.valid) {
        return {
            success: false,
            error: formatCheck.error,
            step: 'format_validation'
        };
    }

    try {
        // 2. TronGrid API로 트랜잭션 조회
        const txInfo = await fetchTRC20Transaction(formatCheck.txid);

        if (!txInfo) {
            return {
                success: false,
                error: '트랜잭션을 찾을 수 없습니다. TXID를 확인해주세요.',
                step: 'transaction_not_found'
            };
        }

        console.log('트랜잭션 정보:', txInfo);

        // 3. 트랜잭션 상태 확인
        if (!txInfo.confirmed) {
            return {
                success: false,
                error: '트랜잭션이 아직 확인되지 않았습니다. 잠시 후 다시 시도해주세요.',
                step: 'not_confirmed',
                confirmations: txInfo.confirmations || 0
            };
        }

        // 4. USDT 전송 검증 (TRC20 USDT 컨트랙트)
        const usdtTransfer = await verifyUSDTTransfer(txInfo, expectedAddress, expectedAmount);

        if (!usdtTransfer.success) {
            return {
                success: false,
                error: usdtTransfer.error,
                step: 'usdt_verification',
                details: usdtTransfer.details
            };
        }

        // 5. 검증 성공
        return {
            success: true,
            message: 'TXID 검증이 완료되었습니다.',
            data: {
                txid: formatCheck.txid,
                network: NETWORK,
                amount: usdtTransfer.amount,
                from: usdtTransfer.from,
                to: usdtTransfer.to,
                timestamp: txInfo.timestamp,
                confirmations: txInfo.confirmations,
                explorerUrl: getExplorerUrl(formatCheck.txid, NETWORK)
            }
        };

    } catch (error) {
        console.error('TRC20 검증 오류:', error);
        return {
            success: false,
            error: '검증 중 오류가 발생했습니다: ' + error.message,
            step: 'api_error'
        };
    }
}

/**
 * TronGrid API로 트랜잭션 정보 가져오기
 */
async function fetchTRC20Transaction(txid) {
    try {
        const response = await fetch(`https://api.trongrid.io/v1/transactions/${txid}`, {
            headers: {
                'TRON-PRO-API-KEY': TRONGRID_API_KEY
            }
        });

        if (!response.ok) {
            if (response.status === 404) {
                return null; // 트랜잭션 없음
            }
            throw new Error(`TronGrid API 오류: ${response.status}`);
        }

        const data = await response.json();

        // 트랜잭션 정보 파싱
        return {
            txid: data.txID,
            confirmed: data.ret && data.ret[0] && data.ret[0].contractRet === 'SUCCESS',
            timestamp: data.block_timestamp || data.raw_data?.timestamp,
            confirmations: data.confirmations || 0,
            rawData: data
        };

    } catch (error) {
        console.error('TronGrid API 호출 실패:', error);
        throw error;
    }
}

/**
 * USDT 전송 검증 (TRC20)
 */
async function verifyUSDTTransfer(txInfo, expectedAddress, expectedAmount) {
    const USDT_CONTRACT = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'; // TRC20 USDT 컨트랙트 주소

    try {
        const contracts = txInfo.rawData.raw_data?.contract || [];

        // TriggerSmartContract 타입 찾기
        const usdtContract = contracts.find(c =>
            c.type === 'TriggerSmartContract' &&
            c.parameter?.value?.contract_address === USDT_CONTRACT
        );

        if (!usdtContract) {
            return {
                success: false,
                error: 'USDT 전송 트랜잭션이 아닙니다.',
                details: 'TRC20 USDT 컨트랙트를 찾을 수 없습니다.'
            };
        }

        // Transfer 이벤트에서 금액 및 수신자 확인
        const transferInfo = await parseUSDTTransferEvent(txInfo.rawData);

        if (!transferInfo) {
            return {
                success: false,
                error: 'USDT 전송 정보를 파싱할 수 없습니다.'
            };
        }

        // 수신 주소 확인
        if (transferInfo.to.toLowerCase() !== expectedAddress.toLowerCase()) {
            return {
                success: false,
                error: '수신 주소가 일치하지 않습니다.',
                details: `예상: ${expectedAddress}, 실제: ${transferInfo.to}`
            };
        }

        // 금액 확인 (USDT는 6 decimals)
        const amountUSDT = transferInfo.amount / 1000000;
        const amountCheck = validateAmount(amountUSDT, expectedAmount);

        if (!amountCheck.valid) {
            return {
                success: false,
                error: amountCheck.error,
                details: `전송 금액: ${amountUSDT} USDT`
            };
        }

        return {
            success: true,
            amount: amountUSDT,
            from: transferInfo.from,
            to: transferInfo.to
        };

    } catch (error) {
        console.error('USDT 전송 검증 오류:', error);
        return {
            success: false,
            error: 'USDT 전송 검증 중 오류가 발생했습니다.',
            details: error.message
        };
    }
}

/**
 * Transfer 이벤트 파싱
 */
async function parseUSDTTransferEvent(txData) {
    try {
        // TronGrid의 이벤트 로그에서 Transfer 찾기
        const logs = txData.log || [];
        const transferLog = logs.find(log =>
            log.topics && log.topics[0] === 'ddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef' // Transfer 이벤트 시그니처
        );

        if (!transferLog) {
            // 대체 방법: contract parameter에서 data 파싱
            const contract = txData.raw_data?.contract?.[0];
            if (contract && contract.parameter?.value?.data) {
                return parseTransferData(contract.parameter.value.data);
            }
            return null;
        }

        // topics[1]: from, topics[2]: to
        const from = '0x' + transferLog.topics[1].substring(24); // 앞 24자 제거
        const to = '0x' + transferLog.topics[2].substring(24);
        const amount = parseInt(transferLog.data, 16);

        return {
            from: tronAddressFromHex(from),
            to: tronAddressFromHex(to),
            amount: amount
        };

    } catch (error) {
        console.error('Transfer 이벤트 파싱 오류:', error);
        return null;
    }
}

/**
 * contract data에서 transfer 정보 파싱
 */
function parseTransferData(data) {
    try {
        // data 형식: a9059cbb (transfer 함수 시그니처) + to address (32 bytes) + amount (32 bytes)
        if (!data || data.length < 136) {
            return null;
        }

        const to = '0x' + data.substring(32, 72);
        const amount = parseInt(data.substring(72, 136), 16);

        return {
            from: '', // data에서는 from 정보 없음
            to: tronAddressFromHex(to),
            amount: amount
        };

    } catch (error) {
        console.error('Transfer data 파싱 오류:', error);
        return null;
    }
}

/**
 * Hex 주소를 TRON Base58 주소로 변환 (간단 버전)
 */
function tronAddressFromHex(hexAddress) {
    // 실제로는 base58 인코딩 필요, 여기서는 간소화
    // 프로덕션에서는 tronweb 라이브러리 사용 권장
    return hexAddress;
}

/**
 * UI 업데이트: 지갑 주소 표시
 */
export function updateWalletAddressDisplay() {
    const walletAddressEl = document.getElementById('selectedWalletAddress');
    if (walletAddressEl) {
        const addresses = getWalletAddresses();
        walletAddressEl.textContent = addresses.trc20_usdt_address || '설정되지 않음';
    }
}

/**
 * UI 업데이트: 네트워크 정보
 */
export function updateNetworkInfo() {
    const networkLabel = document.getElementById('selectedNetworkLabel');
    if (networkLabel) {
        networkLabel.textContent = 'TRC20 (TRON Network)';
    }

    // 네트워크 안내 표시
    const networkHint = document.getElementById('networkHint');
    if (networkHint) {
        networkHint.innerHTML = `
            <div style="padding: 12px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; margin-top: 8px;">
                <strong style="color: #10B981;">✓ 자동 검증</strong><br>
                <span style="font-size: 12px; color: var(--we1-text-secondary);">
                    TXID 입력 시 TRON 블록체인에서 자동으로 검증됩니다.
                </span>
            </div>
        `;
    }
}

/**
 * 검증 모드 이름
 */
export const VERIFICATION_MODE = 'TRC20_INSTANT';
export const VERIFICATION_NAME = 'TRC20 즉시 검증';
