/**
 * Sponsor Tree Module
 * 후원 트리 및 위치 선택 관리
 */

import { showAlert } from './uiComponents.js';

let positionData = null;

// 후원 가능 회원 리스트 표시
export function showSponsorList(sponsorList) {
    const container = document.getElementById('sponsorListContainer');
    const content = document.getElementById('sponsorListContent');

    if (!sponsorList || sponsorList.length === 0) {
        content.innerHTML = `
            <div style="padding: 20px; text-align: center; color: var(--we1-text-secondary);">
                후원 가능한 자리가 없습니다
            </div>
        `;
        container.style.display = 'block';
        return;
    }

    // 리스트 HTML 생성
    let html = '';
    sponsorList.forEach((item, index) => {
        const depthBadge = `<span style="display: inline-block; padding: 2px 8px; background: rgba(212, 175, 55, 0.2); border-radius: 4px; font-size: 11px; margin-right: 8px;">${item.depth}</span>`;
        const positionBadge = `<span style="display: inline-block; padding: 2px 8px; background: ${item.position === 'left' ? 'rgba(59, 130, 246, 0.2)' : 'rgba(168, 85, 247, 0.2)'}; border-radius: 4px; font-size: 11px; color: ${item.position === 'left' ? '#60a5fa' : '#c084fc'};">${item.position_text}</span>`;

        html += `
            <div class="sponsor-list-item" data-user-id="${item.user_id}" data-position="${item.position_code}"
                 style="padding: 10px 12px; margin: 6px 0; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(212, 175, 55, 0.2); border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                 onmouseover="this.style.background='rgba(212, 175, 55, 0.1)'; this.style.borderColor='var(--we1-gold)';"
                 onmouseout="this.style.background='rgba(255, 255, 255, 0.03)'; this.style.borderColor='rgba(212, 175, 55, 0.2)';">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        ${depthBadge}
                        <span style="font-family: 'Courier New', monospace; font-weight: 700; color: var(--we1-gold); font-size: 14px;">${item.user_id}</span>
                        ${positionBadge}
                    </div>
                    <div style="font-size: 11px; color: var(--we1-text-secondary);">
                        클릭하여 선택
                    </div>
                </div>
            </div>
        `;
    });

    content.innerHTML = html;
    container.style.display = 'block';

    // 클릭 이벤트 추가
    document.querySelectorAll('.sponsor-list-item').forEach(item => {
        item.addEventListener('click', () => {
            const userId = item.getAttribute('data-user-id');
            const position = item.getAttribute('data-position'); // 1=좌, 2=우

            console.log('🎯 리스트 항목 클릭:', userId, '위치:', position);

            // 후원코드 입력
            const sponsorInput = document.getElementById('sponsorCode');
            if (sponsorInput) {
                sponsorInput.value = userId;
            }

            // 위치 정보 로드 및 자동 선택
            checkSponsorPositionAndSelect(userId, position);

            // 선택된 항목 강조
            document.querySelectorAll('.sponsor-list-item').forEach(li => {
                li.style.background = 'rgba(255, 255, 255, 0.03)';
                li.style.borderColor = 'rgba(212, 175, 55, 0.2)';
            });
            item.style.background = 'rgba(212, 175, 55, 0.2)';
            item.style.borderColor = 'var(--we1-gold)';
        });
    });
}

// 후원인 위치 확인
export async function checkSponsorPosition(sponsorCode) {
    const positionGroup = document.getElementById('positionGroup');
    const positionLoading = document.getElementById('positionLoading');
    const positionError = document.getElementById('positionError');
    const positionTree = document.getElementById('positionTree');

    // 초기화
    positionGroup.style.display = 'block';
    positionLoading.style.display = 'block';
    positionError.style.display = 'none';
    positionTree.style.display = 'none';
    document.getElementById('selectedPositionInfo').style.display = 'none';
    document.getElementById('selectedPosition').value = '';

    try {
        // 현재 페이지의 기준 경로 확인
        const currentPath = window.location.pathname;
        const basePath = currentPath.includes('/html/')
            ? '../api/organization/check-position.php'
            : '../api/organization/check-position.php';

        const apiUrl = `${basePath}?sponsor_id=${encodeURIComponent(sponsorCode)}`;
        console.log('현재 경로:', currentPath);
        console.log('API 호출:', apiUrl);

        const response = await fetch(apiUrl);
        console.log('응답 상태:', response.status, response.statusText);

        // 응답 텍스트를 먼저 확인
        const responseText = await response.text();
        console.log('응답 텍스트:', responseText);

        if (!response.ok) {
            positionLoading.style.display = 'none';
            positionError.innerHTML = `HTTP 오류: ${response.status} ${response.statusText}<br><small>${responseText}</small>`;
            positionError.style.display = 'block';
            return;
        }

        // JSON 파싱 시도
        let data;
        try {
            data = JSON.parse(responseText);
            console.log('응답 데이터:', data);
        } catch (parseError) {
            positionLoading.style.display = 'none';
            positionError.innerHTML = `JSON 파싱 오류<br><small>${responseText.substring(0, 200)}</small>`;
            positionError.style.display = 'block';
            return;
        }

        positionLoading.style.display = 'none';

        if (!data.success) {
            let errorMsg = data.message || '조회 실패';
            if (data.error) {
                errorMsg += `<br><small style="color: #FCA5A5;">${data.error}</small>`;
            }
            positionError.innerHTML = errorMsg;
            positionError.style.display = 'block';
            return;
        }

        // 데이터 저장
        positionData = data.data;

        // 후원인 정보 표시
        document.getElementById('sponsorName').textContent =
            `${positionData.sponsor.user_id} ${positionData.sponsor.name ? '(' + positionData.sponsor.name + ')' : ''}`;
        document.getElementById('sponsorStats').textContent =
            `레벨 ${positionData.sponsor.level} | 좌측: ${positionData.statistics.left_count}명 | 우측: ${positionData.statistics.right_count}명`;

        // 좌측 위치 상태
        const leftBox = document.getElementById('leftPosition');
        const leftStatus = leftBox.querySelector('.position-status');

        if (positionData.positions.left.available) {
            leftBox.classList.remove('occupied');
            leftBox.classList.add('available');
            leftBox.style.borderColor = 'rgba(16, 185, 129, 0.4)';
            leftBox.style.background = 'rgba(16, 185, 129, 0.05)';
            leftBox.style.cursor = 'pointer';
            leftBox.style.opacity = '1';
            leftBox.style.pointerEvents = 'auto';
            leftStatus.innerHTML = '<span style="color: #10B981;">✓ 가능</span>';
        } else {
            leftBox.classList.add('occupied');
            leftBox.classList.remove('available');
            leftBox.style.borderColor = 'rgba(239, 68, 68, 0.2)';
            leftBox.style.background = 'rgba(239, 68, 68, 0.03)';
            leftBox.style.cursor = 'not-allowed';
            leftBox.style.opacity = '0.4';
            leftBox.style.pointerEvents = 'none';
            leftStatus.innerHTML = `<span style="color: #EF4444;">✕ 사용중</span>`;
        }

        // 우측 위치 상태
        const rightBox = document.getElementById('rightPosition');
        const rightStatus = rightBox.querySelector('.position-status');

        if (positionData.positions.right.available) {
            rightBox.classList.remove('occupied');
            rightBox.classList.add('available');
            rightBox.style.borderColor = 'rgba(16, 185, 129, 0.4)';
            rightBox.style.background = 'rgba(16, 185, 129, 0.05)';
            rightBox.style.cursor = 'pointer';
            rightBox.style.opacity = '1';
            rightBox.style.pointerEvents = 'auto';
            rightStatus.innerHTML = '<span style="color: #10B981;">✓ 가능</span>';
        } else {
            rightBox.classList.add('occupied');
            rightBox.classList.remove('available');
            rightBox.style.borderColor = 'rgba(239, 68, 68, 0.2)';
            rightBox.style.background = 'rgba(239, 68, 68, 0.03)';
            rightBox.style.cursor = 'not-allowed';
            rightBox.style.opacity = '0.4';
            rightBox.style.pointerEvents = 'none';
            rightStatus.innerHTML = `<span style="color: #EF4444;">✕ 사용중</span>`;
        }

        // 트리 표시
        positionTree.style.display = 'block';

        // 자동으로 빈 자리가 하나만 있으면 선택
        if (positionData.positions.left.available && !positionData.positions.right.available) {
            selectPosition('1');
        } else if (!positionData.positions.left.available && positionData.positions.right.available) {
            selectPosition('2');
        }

        return true; // 성공

    } catch (error) {
        console.error('Check position error:', error);
        positionLoading.style.display = 'none';

        // 상세한 에러 메시지 표시
        let errorMessage = '조회 중 오류가 발생했습니다. 네트워크를 확인해주세요.';

        // 개발 모드에서 상세 에러 표시
        if (error.message) {
            errorMessage += `\n\n상세 정보: ${error.message}`;
        }

        positionError.innerHTML = errorMessage.replace(/\n/g, '<br>');
        positionError.style.display = 'block';
        return false; // 실패
    }
}

// 후원 위치 확인 후 자동 선택 (리스트에서 선택한 경우)
export async function checkSponsorPositionAndSelect(userId, targetPosition) {
    console.log('📍 checkSponsorPositionAndSelect 호출:', userId, targetPosition);

    // 먼저 위치 정보 로드
    const success = await checkSponsorPosition(userId);

    console.log('📍 checkSponsorPosition 결과:', success);

    if (success) {
        // 위치 정보가 로드되면 해당 위치 자동 선택
        console.log('✅ 위치 자동 선택:', targetPosition === '1' ? '좌측' : '우측');
        setTimeout(() => {
            selectPosition(targetPosition);
        }, 300);
    } else {
        console.error('❌ 위치 정보 로드 실패');
    }
}

// 위치 선택
export function selectPosition(position) {
    // 모든 박스에서 선택 해제
    document.querySelectorAll('.position-box').forEach(box => {
        box.style.transform = 'scale(1)';
        box.style.boxShadow = 'none';
    });

    // 선택된 박스 강조
    const selectedBox = position === '1' ?
        document.getElementById('leftPosition') :
        document.getElementById('rightPosition');

    if (selectedBox.classList.contains('occupied')) {
        return; // 이미 차있는 자리는 선택 불가
    }

    selectedBox.style.transform = 'scale(1.05)';
    selectedBox.style.boxShadow = '0 0 20px rgba(212, 175, 55, 0.5)';
    selectedBox.style.borderColor = 'var(--we1-gold)';

    // Hidden input 업데이트
    document.getElementById('selectedPosition').value = position;

    // 선택 정보 표시 - 간소화
    const positionText = position === '1' ? '좌측 선택됨' : '우측 선택됨';
    document.getElementById('selectedPositionText').textContent = positionText;
    document.getElementById('selectedPositionInfo').style.display = 'block';
}

// 후원자 목록 로드
export async function loadSponsorList(referralCode) {
    try {
        const currentPath = window.location.pathname;
        const basePath = currentPath.includes('/html/')
            ? '../api/auth/get-sponsor-list.php'
            : '../api/auth/get-sponsor-list.php';

        const apiUrl = `${basePath}?referral_id=${encodeURIComponent(referralCode)}`;
        console.log('후원 리스트 API 호출:', apiUrl);

        const response = await fetch(apiUrl);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log('후원 리스트 응답:', data);

        return data;
    } catch (error) {
        console.error('Sponsor list error:', error);
        throw error;
    }
}

// 위치 박스 클릭 이벤트 초기화
export function initPositionBoxes() {
    document.querySelectorAll('.position-box').forEach(box => {
        box.addEventListener('click', () => {
            const position = box.getAttribute('data-position');
            if (!box.classList.contains('occupied')) {
                selectPosition(position);
            }
        });
    });
}
