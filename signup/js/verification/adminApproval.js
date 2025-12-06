/**
 * Admin Approval Verification Module
 * TXID 입력 후 관리자가 수동으로 검증하는 방식
 */

import { validateTxidFormat, getWalletAddresses } from './base.js';

const NETWORK = 'TRC20'; // 기본 네트워크

/**
 * 관리자 승인 방식 TXID 검증
 * @param {string} txid - 트랜잭션 ID
 * @param {string} network - 네트워크 (TRC20, BSC 등)
 * @returns {Object} 검증 결과
 */
export async function verifyAdminApproval(txid, network = 'TRC20') {
    console.log('=== 관리자 승인 모드 ===');
    console.log('TXID:', txid);
    console.log('네트워크:', network);

    // 1. 기본 형식 검증만 수행
    const formatCheck = validateTxidFormat(txid, network);
    if (!formatCheck.valid) {
        return {
            success: false,
            error: formatCheck.error,
            step: 'format_validation'
        };
    }

    // 2. 최소 길이 검증 (간단한 검증만)
    if (txid.trim().length < 30) {
        return {
            success: false,
            error: 'TXID가 너무 짧습니다. (최소 30자)',
            step: 'length_validation'
        };
    }

    // 3. 형식만 통과하면 승인 대기 상태로 저장
    // 실제 검증은 관리자가 수행
    return {
        success: true,
        pending: true, // 승인 대기 상태
        message: 'TXID가 접수되었습니다. 관리자 확인 후 가입이 완료됩니다.',
        data: {
            txid: formatCheck.txid,
            network: network,
            status: 'pending_approval',
            submitted_at: new Date().toISOString()
        }
    };
}

/**
 * TXID 입력 유효성 간단 체크
 * @param {string} txid
 * @returns {boolean}
 */
export function isValidTxidFormat(txid) {
    if (!txid || typeof txid !== 'string') {
        return false;
    }

    const trimmed = txid.trim();

    // 최소 길이 체크
    if (trimmed.length < 30) {
        return false;
    }

    // 기본 문자열 검증 (16진수 또는 0x 시작)
    if (!/^(0x)?[0-9a-fA-F]+$/.test(trimmed)) {
        return false;
    }

    return true;
}

/**
 * UI 업데이트: 지갑 주소 표시
 */
export function updateWalletAddressDisplay(network = 'TRC20') {
    const walletAddressEl = document.getElementById('selectedWalletAddress');
    if (walletAddressEl) {
        const addresses = getWalletAddresses();
        const address = network === 'TRC20'
            ? addresses.trc20_usdt_address
            : addresses.bsc_usdt_address;
        walletAddressEl.textContent = address || '설정되지 않음';
    }
}

/**
 * UI 업데이트: 네트워크 정보
 */
export function updateNetworkInfo(network = 'TRC20') {
    const networkLabel = document.getElementById('selectedNetworkLabel');
    if (networkLabel) {
        const labels = {
            'TRC20': 'TRC20 (TRON Network)',
            'BSC': 'BSC (BNB Smart Chain)'
        };
        networkLabel.textContent = labels[network] || network;
    }

    // 관리자 승인 안내 표시
    const networkHint = document.getElementById('networkHint');
    if (networkHint) {
        networkHint.innerHTML = `
            <div style="padding: 12px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; margin-top: 8px;">
                <strong style="color: #3B82F6;">📝 관리자 승인 방식</strong><br>
                <span style="font-size: 12px; color: var(--we1-text-secondary);">
                    TXID 입력 후 관리자가 확인하여 승인합니다.<br>
                    승인 시간: 24시간 이내
                </span>
            </div>
        `;
    }
}

/**
 * 승인 대기 상태 안내 메시지
 */
export function getPendingMessage() {
    return `
        <div style="padding: 20px; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 16px;">📝</div>
            <h3 style="color: var(--we1-gold); margin-bottom: 12px;">가입 신청이 접수되었습니다</h3>
            <p style="color: var(--we1-text-secondary); line-height: 1.6;">
                입력하신 TXID를 관리자가 확인 중입니다.<br>
                승인 완료 시 이메일로 알림을 보내드립니다.
            </p>
            <div style="margin-top: 20px; padding: 14px; background: rgba(59, 130, 246, 0.1); border-radius: 8px;">
                <div style="font-size: 13px; color: #93C5FD;">
                    <strong>예상 처리 시간</strong><br>
                    <span style="font-size: 18px; font-weight: 700; color: var(--we1-gold);">24시간 이내</span>
                </div>
            </div>
        </div>
    `;
}

/**
 * 관리자 승인 프로세스 안내
 */
export function getApprovalProcessInfo() {
    return {
        title: '관리자 승인 프로세스',
        steps: [
            {
                step: 1,
                title: 'TXID 입력',
                description: '입금 완료 후 받은 트랜잭션 ID를 입력합니다.'
            },
            {
                step: 2,
                title: '관리자 확인',
                description: '관리자가 블록체인에서 입금 내역을 확인합니다.'
            },
            {
                step: 3,
                title: '승인 완료',
                description: '확인 완료 시 이메일로 회원코드가 발송됩니다.'
            }
        ],
        estimatedTime: '24시간 이내',
        benefits: [
            '수동 검증으로 높은 보안성',
            '네트워크 오류 발생 시 수동 처리 가능',
            '여러 네트워크 지원 (TRC20, BSC 등)'
        ]
    };
}

/**
 * TXID 중복 체크 (선택적)
 * 관리자 페이지에서 사용할 수 있음
 */
export async function checkTxidDuplicate(txid) {
    try {
        const response = await fetch('../../api/auth/check-txid-duplicate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ txid: txid })
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('TXID 중복 체크 오류:', error);
        return {
            success: false,
            error: '중복 체크 중 오류가 발생했습니다.'
        };
    }
}

/**
 * TXID 제출 (데이터베이스에 pending 상태로 저장)
 */
export async function submitTxidForApproval(formData) {
    try {
        console.log('=== TXID 승인 요청 제출 ===');
        console.log('제출 데이터:', formData);

        const response = await fetch('../../api/auth/signup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                ...formData,
                verification_mode: 'admin_approval',
                status: 'pending_approval'
            })
        });

        const responseText = await response.text();
        console.log('응답:', responseText);

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON 파싱 오류:', parseError);
            throw new Error('서버 응답 형식 오류');
        }

        return data;
    } catch (error) {
        console.error('TXID 제출 오류:', error);
        throw error;
    }
}

/**
 * 검증 모드 이름
 */
export const VERIFICATION_MODE = 'ADMIN_APPROVAL';
export const VERIFICATION_NAME = '관리자 승인';
