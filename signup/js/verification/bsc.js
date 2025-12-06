/**
 * BSC Instant Verification Module
 * BNB Smart Chain API를 통한 실시간 TXID 검증
 */

import { validateTxidFormat, validateAmount, getWalletAddresses, getExplorerUrl } from './base.js';

const NETWORK = 'BSC';
const BSCSCAN_API_KEY = 'IUSI785IDCRYTZMRGA86BXG3U2CZAIG6VC'; // BscScan API (logs용)
const BSC_RPC_URL = 'https://bsc-dataseed.binance.org/'; // Public RPC (트랜잭션 조회용)

/**
 * BSC TXID 즉시 검증
 * @param {string} txid - 트랜잭션 ID (0x...)
 * @param {string} expectedAddress - 예상 수신 주소
 * @param {number} expectedAmount - 예상 금액 (USDT)
 * @returns {Object} 검증 결과
 */
export async function verifyBSCTxid(txid, expectedAddress, expectedAmount = 100) {
    console.log('=== BSC TXID 검증 시작 ===');
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
        // 2. BscScan API로 트랜잭션 조회
        const txInfo = await fetchBSCTransaction(formatCheck.txid);

        if (!txInfo) {
            return {
                success: false,
                error: '트랜잭션을 찾을 수 없습니다. TXID를 확인해주세요.',
                step: 'transaction_not_found'
            };
        }

        console.log('트랜잭션 정보:', txInfo);

        // 3. 트랜잭션 상태 확인
        if (txInfo.isError === '1') {
            return {
                success: false,
                error: '실패한 트랜잭션입니다.',
                step: 'transaction_failed'
            };
        }

        if (!txInfo.confirmations || txInfo.confirmations < 1) {
            return {
                success: false,
                error: '트랜잭션이 아직 확인되지 않았습니다. 잠시 후 다시 시도해주세요.',
                step: 'not_confirmed',
                confirmations: txInfo.confirmations || 0
            };
        }

        // 4. USDT 전송 검증 (BEP20 USDT) - receipt를 함께 전달
        const usdtTransfer = await verifyUSDTTransfer(formatCheck.txid, expectedAddress, expectedAmount, txInfo);

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
                blockNumber: txInfo.blockNumber,
                explorerUrl: getExplorerUrl(formatCheck.txid, NETWORK)
            }
        };

    } catch (error) {
        console.error('BSC 검증 오류:', error);
        return {
            success: false,
            error: '검증 중 오류가 발생했습니다: ' + error.message,
            step: 'api_error'
        };
    }
}

/**
 * JSON-RPC 호출 헬퍼 함수
 */
async function rpcCall(method, params) {
    const response = await fetch(BSC_RPC_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            jsonrpc: '2.0',
            id: 1,
            method: method,
            params: params
        })
    });

    if (!response.ok) {
        throw new Error(`RPC 오류: ${response.status}`);
    }

    const data = await response.json();

    if (data.error) {
        throw new Error(`RPC 에러: ${data.error.message}`);
    }

    return data.result;
}

/**
 * BSC RPC로 트랜잭션 정보 가져오기
 */
async function fetchBSCTransaction(txid) {
    try {
        // ✅ BSC Public RPC 사용 (무료, API 키 불필요)

        // 1. 트랜잭션 정보 가져오기
        const txData = await rpcCall('eth_getTransactionByHash', [txid]);

        if (!txData) {
            return null; // 트랜잭션 없음
        }

        // 2. 트랜잭션 receipt 가져오기 (상태 확인용)
        const receipt = await rpcCall('eth_getTransactionReceipt', [txid]);

        console.log('📦 Receipt 객체:', receipt);
        console.log('📦 Receipt blockNumber:', receipt?.blockNumber);

        // confirmations 계산
        let confirmations = 0;

        if (receipt?.blockNumber) {
            try {
                // 현재 블록 번호 가져오기 - RPC
                const currentBlockHex = await rpcCall('eth_blockNumber', []);

                if (currentBlockHex) {
                    const currentBlock = parseInt(currentBlockHex, 16);
                    const txBlock = parseInt(receipt.blockNumber, 16);
                    confirmations = currentBlock - txBlock;
                    console.log(`BSC Confirmations: ${confirmations} (현재: ${currentBlock}, TX: ${txBlock})`);
                } else {
                    // API 오류 시 기본값: 블록에 포함되었으면 최소 1 confirmation
                    confirmations = 1;
                    console.warn('BSC block number 조회 실패, 기본값 1 사용');
                }
            } catch (blockError) {
                console.error('BSC block 조회 오류:', blockError);
                // 블록 번호가 있으면 최소 1 confirmation으로 처리
                confirmations = 1;
            }
        } else {
            // 아직 블록에 포함되지 않음
            confirmations = 0;
            console.warn('BSC 트랜잭션이 아직 블록에 포함되지 않음');
        }

        return {
            txid: txData.hash,
            from: txData.from,
            to: txData.to,
            blockNumber: receipt?.blockNumber || null,
            timestamp: Date.now() / 1000, // RPC는 timestamp 제공 안 함
            confirmations: confirmations,
            isError: receipt?.status === '0x0' ? '1' : '0', // 0x0 = 실패, 0x1 = 성공
            rawData: {
                tx: txData,
                receipt: receipt
            }
        };

    } catch (error) {
        console.error('BscScan API 호출 실패:', error);
        throw error;
    }
}

/**
 * USDT 전송 검증 (BEP20) - receipt의 logs 직접 파싱
 */
async function verifyUSDTTransfer(txid, expectedAddress, expectedAmount, txInfo) {
    const USDT_CONTRACT = '0x55d398326f99059fF775485246999027B3197955'; // BSC USDT 컨트랙트 주소
    const TRANSFER_EVENT_SIGNATURE = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef'; // Transfer(address,address,uint256)

    try {
        // receipt에서 logs 가져오기
        const logs = txInfo.rawData.receipt?.logs || [];

        if (logs.length === 0) {
            return {
                success: false,
                error: 'USDT 전송 트랜잭션이 아닙니다.',
                details: '트랜잭션 로그가 없습니다.'
            };
        }

        // USDT Transfer 이벤트 찾기
        const transferLog = logs.find(log =>
            log.address.toLowerCase() === USDT_CONTRACT.toLowerCase() &&
            log.topics[0] === TRANSFER_EVENT_SIGNATURE
        );

        if (!transferLog) {
            return {
                success: false,
                error: 'USDT 전송 트랜잭션이 아닙니다.',
                details: 'BEP20 USDT Transfer 이벤트를 찾을 수 없습니다.'
            };
        }

        // Transfer 이벤트 파싱
        // topics[1]: from (indexed), topics[2]: to (indexed)
        const from = '0x' + transferLog.topics[1].substring(26); // 0x + 앞 24자 제거
        const to = '0x' + transferLog.topics[2].substring(26);
        const amount = parseInt(transferLog.data, 16);

        console.log('💰 USDT Transfer:', { from, to, amount });

        // 수신 주소 확인
        if (to.toLowerCase() !== expectedAddress.toLowerCase()) {
            return {
                success: false,
                error: '수신 주소가 일치하지 않습니다.',
                details: `예상: ${expectedAddress}, 실제: ${to}`
            };
        }

        // 금액 확인 (USDT는 18 decimals on BSC)
        const amountUSDT = amount / (10 ** 18);
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
            from: from,
            to: to
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
 * UI 업데이트: 지갑 주소 표시
 */
export function updateWalletAddressDisplay() {
    const walletAddressEl = document.getElementById('selectedWalletAddress');
    if (walletAddressEl) {
        const addresses = getWalletAddresses();
        walletAddressEl.textContent = addresses.bsc_usdt_address || '설정되지 않음';
    }
}

/**
 * UI 업데이트: 네트워크 정보
 */
export function updateNetworkInfo() {
    const networkLabel = document.getElementById('selectedNetworkLabel');
    if (networkLabel) {
        networkLabel.textContent = 'BSC (BNB Smart Chain)';
    }

    // 네트워크 안내 표시
    const networkHint = document.getElementById('networkHint');
    if (networkHint) {
        networkHint.innerHTML = `
            <div style="padding: 12px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; margin-top: 8px;">
                <strong style="color: #F59E0B;">⚡ 자동 검증</strong><br>
                <span style="font-size: 12px; color: var(--we1-text-secondary);">
                    TXID 입력 시 BNB Smart Chain에서 자동으로 검증됩니다.
                </span>
            </div>
        `;
    }
}

/**
 * 검증 모드 이름
 */
export const VERIFICATION_MODE = 'BSC_INSTANT';
export const VERIFICATION_NAME = 'BSC 즉시 검증';
