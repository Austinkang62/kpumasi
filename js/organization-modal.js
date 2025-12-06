/**
 * Organization Modal - 회원 정보 모달 기능
 * 각 회원 아바타를 클릭하면 상세 정보를 표시하는 모달
 */

// 전역 변수
let currentModalUserId = null;
let currentModalUserName = null;
let adminSessionData = null;

// 회원 정보 모달 표시
window.showUserProfile = function(nodeId) {
    console.log('showUserProfile called with nodeId:', nodeId);

    // 전역 함수와 변수 확인
    if (typeof window.findNodeById !== 'function') {
        console.error('❌ findNodeById function not found');
        return;
    }
    if (typeof window.originalTreeData === 'undefined') {
        console.error('❌ originalTreeData not found');
        return;
    }
    if (typeof window.countTeamMembers !== 'function') {
        console.error('❌ countTeamMembers function not found');
        return;
    }

    console.log('✅ All dependencies found');

    // 노드 데이터 찾기
    const node = window.findNodeById(window.originalTreeData, nodeId);
    if (!node) {
        console.error('Node not found:', nodeId);
        return;
    }

    // 모달 요소들
    const modal = document.getElementById('userProfileModal');
    const modalAvatar = document.getElementById('modalAvatar');
    const modalUserId = document.getElementById('modalUserId');
    const modalUserLevel = document.getElementById('modalUserLevel');
    const modalJoinDate = document.getElementById('modalJoinDate');
    const modalLevel = document.getElementById('modalLevel');
    const modalDirectReferrals = document.getElementById('modalDirectReferrals');
    const modalTotalTeam = document.getElementById('modalTotalTeam');
    const modalDirectSales = document.getElementById('modalDirectSales');
    const modalTeamSales = document.getElementById('modalTeamSales');
    const modalTotalEarnings = document.getElementById('modalTotalEarnings');
    const modalSignupBtn = document.getElementById('modalSignupBtn');
    const modalPurchaseBtn = document.getElementById('modalPurchaseBtn');
    const modalEditBtn = document.getElementById('modalEditBtn');

    // 데이터 계산
    const joinDate = node.joined_date ? new Date(node.joined_date).toLocaleDateString('ko-KR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }) : '-';
    // referral_org 기반 직접 추천인 수 (API에서 제공)
    const directReferrals = node.direct_referrals_count || 0;
    const totalTeam = window.countTeamMembers(node);
    const directReferralsSales = node.direct_referrals_sales || 0;
    const teamSales = node.team_sales || 0;
    const totalEarnings = node.total_earnings || 0;

    console.log('[Modal Debug] Node data:', {
        user_id: node.user_id,
        total_earnings: totalEarnings,
        direct_referrals_sales: directReferralsSales,
        team_sales: teamSales
    });

    // 레벨 계산 (트리 깊이)
    function getNodeDepth(searchNode, currentNode, depth = 0) {
        if (currentNode.id === searchNode.id) return depth;
        if (currentNode.children) {
            for (let child of currentNode.children) {
                const result = getNodeDepth(searchNode, child, depth + 1);
                if (result !== -1) return result;
            }
        }
        return -1;
    }
    const nodeDepth = getNodeDepth(node, window.originalTreeData);

    // 나의 단계까지의 모든 팀원 수 계산 (1단계부터 maxDepth단계까지)
    function countTeamUpToLevel(node, maxDepth, currentDepth = 0) {
        if (currentDepth > maxDepth) {
            return 0;
        }
        let count = 0;
        if (currentDepth > 0) { // 자기 자신은 제외, 1단계부터 카운트
            count = 1;
        }
        if (node.children && currentDepth < maxDepth) {
            for (let child of node.children) {
                count += countTeamUpToLevel(child, maxDepth, currentDepth + 1);
            }
        }
        return count;
    }

    // 나의 단계 계산 (직접 추천 수에 따라)
    let myLevel = 0;
    if (directReferrals === 1) {
        myLevel = 5;
    } else if (directReferrals === 2) {
        myLevel = 10;
    } else if (directReferrals >= 3) {
        myLevel = 15;
    }

    // 나의 팀원 수 (1단계부터 myLevel단계까지의 모든 팀원)
    const currentLevelTeam = myLevel > 0 ? countTeamUpToLevel(node, myLevel, 0) : 0;

    // 나의 추천 매출합 계산 (직접 추천한 회원들의 패키지 금액)
    let myDirectSales = 0;
    if (node.children) {
        for (let child of node.children) {
            const packageAmount = child.package_code === 'PKG_100' ? 100 :
                                 child.package_code === 'PKG_50' ? 50 : 0;
            myDirectSales += packageAmount;
        }
    }

    // 나의 팀원 매출합 계산 (1단계부터 myLevel단계까지)
    function calculateTeamSalesUpToLevel(node, maxDepth, currentDepth = 0) {
        if (currentDepth > maxDepth) {
            return 0;
        }
        let sales = 0;
        if (currentDepth > 0) { // 자기 자신 제외
            // 각 노드의 패키지 금액을 매출로 계산
            const packageAmount = node.package_code === 'PKG_100' ? 100 :
                                 node.package_code === 'PKG_50' ? 50 : 0;
            sales = packageAmount;
        }
        if (node.children && currentDepth < maxDepth) {
            for (let child of node.children) {
                sales += calculateTeamSalesUpToLevel(child, maxDepth, currentDepth + 1);
            }
        }
        return sales;
    }

    const myTeamSales = myLevel > 0 ? calculateTeamSalesUpToLevel(node, myLevel, 0) : 0;

    // 아바타 이모지 설정
    const avatarEmojis = ['👑', '💼', '👤', '👥', '🔷', '🔸', '⭐', '✨'];
    const emoji = avatarEmojis[Math.min(nodeDepth, avatarEmojis.length - 1)];

    // 매출액에 따른 아바타 클래스
    let avatarClass = 'no-sales';
    if (totalEarnings >= 100) {
        avatarClass = 'sales-100';
    } else if (totalEarnings >= 50) {
        avatarClass = 'sales-50';
    } else if (totalEarnings > 0) {
        avatarClass = 'has-avatar';
    }

    // 모달 데이터 설정
    modalAvatar.textContent = emoji;
    modalAvatar.className = `modal-avatar ${avatarClass}`;
    modalUserId.textContent = node.user_id || '아이디없음';
    modalUserLevel.textContent = `Level ${nodeDepth}`;

    // 프로그레스 바 및 회차 표시 업데이트
    updateModalProgressBar(totalEarnings, node.package_code);
    updateCycleIndicators(totalEarnings, node.package_code);

    modalDirectReferrals.textContent = directReferrals;

    // 나의 단계와 현재 단계 팀원 수 설정
    const modalMyLevel = document.getElementById('modalMyLevel');
    const modalCurrentLevelTeam = document.getElementById('modalCurrentLevelTeam');
    if (modalMyLevel) modalMyLevel.textContent = myLevel;
    if (modalCurrentLevelTeam) modalCurrentLevelTeam.textContent = currentLevelTeam;

    // 수익 정보 업데이트
    const modalAvatarTotal = document.getElementById('modalAvatarTotal');
    const modalBonusTotal = document.getElementById('modalBonusTotal');
    const modalWithdrawalTotal = document.getElementById('modalWithdrawalTotal');
    const modalCurrentBalance = document.getElementById('modalCurrentBalance');

    // 총액 (프로그레스 바 위의 금액)
    const totalAmount = totalEarnings;

    // 패키지별 1회차 금액 (100%)
    let packageAmount = 0;
    if (node.package_code === 'PKG_50') {
        packageAmount = 50;
    } else if (node.package_code === 'PKG_100') {
        packageAmount = 100;
    }

    // 회차 계산 (0부터 시작)
    let currentCycle = 0;
    if (packageAmount > 0) {
        const cycleAmount = packageAmount * 3; // 300% = 1회차
        currentCycle = Math.floor(totalAmount / cycleAmount);
    }

    // 아바타 합계 = 회차 * 100%
    const avatarTotal = currentCycle * packageAmount;

    // 보너스 합계 = 총액 - 아바타 합계
    const bonusTotal = totalAmount - avatarTotal;

    // 출금 합계 (추후 API에서 받을 예정)
    const withdrawalTotal = 0;

    // 현재 잔액 = 보너스 합계 - 출금 합계
    const currentBalance = bonusTotal - withdrawalTotal;

    if (modalAvatarTotal) modalAvatarTotal.textContent = `$${avatarTotal.toFixed(2)}`;
    if (modalBonusTotal) modalBonusTotal.textContent = `$${bonusTotal.toFixed(2)}`;
    if (modalWithdrawalTotal) modalWithdrawalTotal.textContent = `$${withdrawalTotal.toFixed(2)}`;
    if (modalCurrentBalance) modalCurrentBalance.textContent = `$${currentBalance.toFixed(2)}`;

    // 아바타 구매 알림 표시 (1회차 이상 완료 시)
    const avatarAlert = document.getElementById('avatarPurchaseAlert');
    if (avatarAlert && currentCycle >= 1) {
        avatarAlert.style.display = 'flex';

        // 아바타 구매 버튼 클릭 이벤트
        const avatarButton = document.getElementById('goToAvatarPurchase');
        if (avatarButton) {
            avatarButton.onclick = function() {
                alert('🎭 아바타 구매 준비중입니다.');
            };
        }
    } else if (avatarAlert) {
        avatarAlert.style.display = 'none';
    }

    // 버튼 URL 설정
    // 관리자 모드 체크 (iframe인지 확인)
    const isAdminMode = window.self !== window.top;

    // 관리자 모드면 간편 페이지로 이동
    const signupPage = isAdminMode ? 'signup_adm.html' : 'signup.html';
    const purchasePage = 'purchase.html';  // 통합: 항상 purchase.html 사용

    // 현재 로그인한 사용자의 user_id 가져오기
    // 우선순위: 1) 관리자 세션, 2) 조직도 루트 노드
    let currentLoginId = '';
    if (window.adminSessionData && window.adminSessionData.user_id) {
        currentLoginId = window.adminSessionData.user_id;
    } else if (window.originalTreeData && window.originalTreeData.user_id) {
        currentLoginId = window.originalTreeData.user_id;
    }

    modalSignupBtn.href = `${signupPage}?referral_id=${node.user_id}`;
    modalPurchaseBtn.href = `${purchasePage}?user_id=${node.user_id}&memo=${currentLoginId}`;
    modalPurchaseBtn.target = '_blank';  // 새 탭에서 열기
    modalEditBtn.href = `edit-profile.html?user_id=${node.user_id}`;

    // 현재 모달의 사용자 정보 저장 (삭제 시 사용)
    currentModalUserId = node.id;
    currentModalUserName = node.user_id;

    // 디버깅: 관리자 정보 확인
    console.log('[Modal] Delete button check:', {
        isAdminMode: isAdminMode,
        hasAdminSessionData: !!window.adminSessionData,
        adminRole: window.adminSessionData ? window.adminSessionData.role : 'none'
    });

    // 관리자 삭제 버튼 표시 여부
    const deleteSection = document.getElementById('adminDeleteSection');
    if (deleteSection) {
        // Super Admin인 경우에만 삭제 버튼 표시 (role: 'super' 또는 'super_admin')
        // window.adminSessionData를 사용 (전역 변수)
        const isSuperAdmin = window.adminSessionData &&
                            (window.adminSessionData.role === 'super' ||
                             window.adminSessionData.role === 'super_admin');

        if (isAdminMode && isSuperAdmin) {
            console.log('[Modal] ✅ Showing delete button');
            deleteSection.style.display = 'block';
        } else {
            console.log('[Modal] ❌ Hiding delete button');
            deleteSection.style.display = 'none';
        }
    } else {
        console.log('[Modal] ⚠️ Delete section not found');
    }

    // 모달 표시
    modal.classList.add('active');
};

// 회원 정보 모달 닫기
window.closeUserProfile = function() {
    const modal = document.getElementById('userProfileModal');
    modal.classList.remove('active');
    currentModalUserId = null;
    currentModalUserName = null;
};

// 회차 표시 업데이트 (다이아몬드 마커)
function updateCycleIndicators(balance, packageId) {
    const diamondMarker = document.getElementById('modalDiamondMarker');
    const cycleNumber = document.getElementById('modalCycleNumber');

    if (!diamondMarker || !cycleNumber) return;

    // 패키지별 1회차 금액
    let cycleAmount = 0;
    if (packageId === 'PKG_50') {
        cycleAmount = 150; // $50 패키지: 300% = $150
    } else if (packageId === 'PKG_100') {
        cycleAmount = 300; // $100 패키지: 300% = $300
    } else {
        // 패키지 없으면 회차 표시 안함
        diamondMarker.style.display = 'none';
        return;
    }

    // 현재 회차 계산 (0부터 시작)
    const currentCycle = Math.floor(balance / cycleAmount);
    const balanceInCurrentCycle = balance % cycleAmount;

    // 프로그레스 바 위치에 다이아몬드 배치 (0-100%)
    const position = (balanceInCurrentCycle / cycleAmount) * 100;

    diamondMarker.style.display = 'block';
    diamondMarker.style.left = `${position}%`;
    cycleNumber.textContent = currentCycle;
}

// 모달 프로그레스 바 업데이트
function updateModalProgressBar(balance, packageId) {
    const container = document.getElementById('modalBonusProgress');
    const amountDisplay = document.getElementById('modalBonusAmount');
    const titleElement = document.getElementById('modalBonusTitle');

    if (!container || !amountDisplay) {
        return;
    }

    container.style.display = 'block';

    // 패키지 구매 여부 확인
    const hasPackage = packageId === 'PKG_50' || packageId === 'PKG_100';

    // 패키지별 구간 설정
    let packageAmount, zone1End, zone2End, zone3End;
    if (packageId === 'PKG_50') {
        packageAmount = 50;
        zone1End = 50;    // 0-50 (100%)
        zone2End = 100;   // 50-100 (200%)
        zone3End = 150;   // 100-150 (300%)
    } else if (packageId === 'PKG_100') {
        packageAmount = 100;
        zone1End = 100;   // 0-100 (100%)
        zone2End = 200;   // 100-200 (200%)
        zone3End = 300;   // 100-300 (300%)
    } else {
        // 패키지 없음 - 기본값
        packageAmount = 50;
        zone1End = 50;
        zone2End = 100;
        zone3End = 150;
    }

    // 회차 계산
    const cycleAmount = zone3End; // 1회차 = 300%
    const currentCycle = Math.floor(balance / cycleAmount);
    const balanceInCurrentCycle = balance % cycleAmount; // 현재 회차 내에서의 잔액

    // 제목 업데이트
    if (hasPackage && titleElement) {
        titleElement.textContent = `💰 패키지 $${packageAmount} 보너스 상태`;
    } else if (titleElement) {
        titleElement.textContent = '💰 보너스 진행 상태';
    }

    // 패키지가 없으면 금액 표시 숨김
    if (!hasPackage) {
        amountDisplay.style.display = 'none';
    } else {
        amountDisplay.style.display = 'block';
        amountDisplay.textContent = `$${balance.toFixed(2)}`;
    }

    // 구간별 색상 변경 (현재 회차 기준)
    if (hasPackage) {
        if (balanceInCurrentCycle < zone2End) {
            // 0-200% 구간: 출금 가능 (녹색)
            amountDisplay.style.background = 'linear-gradient(135deg, #00ff88, #00ffff)';
        } else {
            // 200-300% 구간: 아바타 구역 - 출금 금지 (빨강)
            amountDisplay.style.background = 'linear-gradient(135deg, #ff4444, #ff00ff)';
        }
        amountDisplay.style.webkitBackgroundClip = 'text';
        amountDisplay.style.webkitTextFillColor = 'transparent';
    }
}

// 매출 삭제 함수 (관리자 전용)
window.deleteSalesFromModal = async function() {
    if (!currentModalUserId || !currentModalUserName) {
        alert('삭제할 사용자 정보를 찾을 수 없습니다.');
        return;
    }

    // window.adminSessionData 사용 (role: 'super' 또는 'super_admin')
    const isSuperAdmin = window.adminSessionData &&
                        (window.adminSessionData.role === 'super' ||
                         window.adminSessionData.role === 'super_admin');

    if (!isSuperAdmin) {
        alert('Super Admin 권한이 필요합니다.');
        return;
    }

    // 확인 대화상자
    const confirmMsg = `"${currentModalUserName}" 회원의 모든 매출을 삭제하시겠습니까?\n\n⚠️ 주의사항:\n• 이 작업은 되돌릴 수 없습니다!\n• 매출로 인해 발생한 모든 보너스가 역산 처리됩니다\n• 보너스를 받은 회원들의 수익이 차감됩니다\n• 음수가 되는 경우 0으로 처리됩니다`;

    if (!confirm(confirmMsg)) {
        return;
    }

    // 2차 확인
    const finalConfirm = prompt(`정말 삭제하시려면 "DELETE"를 입력하세요:`);

    if (finalConfirm !== 'DELETE') {
        alert('삭제가 취소되었습니다.');
        return;
    }

    try {
        // 관리자 토큰 가져오기
        const adminToken = localStorage.getItem('admin_token');

        if (!adminToken) {
            alert('관리자 세션이 만료되었습니다. 다시 로그인해주세요.');
            return;
        }

        // API 호출
        const response = await fetch('../api/admin/delete-sales.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: adminToken,
                user_id: currentModalUserId
            })
        });

        const result = await response.json();

        if (result.success) {
            let message = `✓ "${currentModalUserName}" 회원의 매출이 삭제되었습니다.\n\n`;
            message += `• 삭제된 매출 건수: ${result.data.deleted_sales_count}건\n`;
            message += `• 총 매출액: $${result.data.total_amount.toFixed(2)}\n`;
            message += `• 영향받은 회원 수: ${result.data.affected_users}명`;

            alert(message);

            // 모달 닫기
            window.closeUserProfile();

            // 조직도 새로고침
            if (window.location.reload) {
                window.location.reload();
            }
        } else {
            alert('❌ 삭제 실패: ' + (result.message || '알 수 없는 오류'));
        }
    } catch (error) {
        console.error('Delete sales error:', error);
        alert('❌ 매출 삭제 중 오류가 발생했습니다: ' + error.message);
    }
};

// 회원 삭제 함수 (관리자 전용)
window.deleteUserFromModal = async function() {
    if (!currentModalUserId || !currentModalUserName) {
        alert('삭제할 사용자 정보를 찾을 수 없습니다.');
        return;
    }

    // window.adminSessionData 사용 (role: 'super' 또는 'super_admin')
    const isSuperAdmin = window.adminSessionData &&
                        (window.adminSessionData.role === 'super' ||
                         window.adminSessionData.role === 'super_admin');

    if (!isSuperAdmin) {
        alert('Super Admin 권한이 필요합니다.');
        return;
    }

    // 확인 대화상자
    const confirmMsg = `정말로 "${currentModalUserName}" 회원을 삭제하시겠습니까?\n\n⚠️ 이 작업은 되돌릴 수 없습니다!\n⚠️ 모든 관련 데이터(세션, 통계, 추천 관계)가 함께 삭제됩니다.`;

    if (!confirm(confirmMsg)) {
        return;
    }

    // 2차 확인
    const finalConfirm = prompt(`정말 삭제하시려면 회원 ID "${currentModalUserName}"를 입력하세요:`);

    if (finalConfirm !== currentModalUserName) {
        alert('회원 ID가 일치하지 않습니다. 삭제가 취소되었습니다.');
        return;
    }

    try {
        // 관리자 토큰 가져오기
        const adminToken = localStorage.getItem('admin_token');

        if (!adminToken) {
            alert('관리자 세션이 만료되었습니다. 다시 로그인해주세요.');
            return;
        }

        // API 호출
        const response = await fetch('../api/admin/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: adminToken,
                action: 'delete',
                user_id: currentModalUserId
            })
        });

        const result = await response.json();

        if (result.success) {
            alert(`✓ "${currentModalUserName}" 회원이 삭제되었습니다.`);

            // 모달 닫기
            window.closeUserProfile();

            // 조직도 새로고침
            if (window.location.reload) {
                window.location.reload();
            }
        } else {
            alert('❌ 삭제 실패: ' + (result.message || '알 수 없는 오류'));
        }
    } catch (error) {
        console.error('Delete user error:', error);
        alert('❌ 삭제 중 오류가 발생했습니다: ' + error.message);
    }
};

// 모달 이벤트 리스너 초기화
function initializeModal() {
    console.log('🔧 Initializing modal...');

    const modal = document.getElementById('userProfileModal');
    const modalClose = document.getElementById('modalClose');

    if (!modal || !modalClose) {
        console.warn('⚠️ Modal elements not found');
        return;
    }

    console.log('✅ Modal initialized successfully');

    // 닫기 버튼
    modalClose.addEventListener('click', window.closeUserProfile);

    // 모달 배경 클릭 시 닫기
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            window.closeUserProfile();
        }
    });

    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            window.closeUserProfile();
        }
    });
}

// DOMContentLoaded 이벤트에서 모달 초기화
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeModal);
} else {
    initializeModal();
}
