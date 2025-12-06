// 전역 변수
let userData = null;

// 페이지 로드 시
window.addEventListener('load', async () => {
    await loadDashboardData();
});

// 대시보드 데이터 로드
async function loadDashboardData() {
    const sessionToken = localStorage.getItem('session_token');

    if (!sessionToken) {
        alert('로그인이 필요합니다.');
        location.href = 'login.html';
        return;
    }

    try {
        const response = await fetch(`../api/user/get-profile.php?session_token=${sessionToken}`);
        const result = await response.json();

        if (result.success) {
            userData = result.data;
            updateDashboard(userData);
            loadNotices();
            loadBonusSummary();
        } else {
            console.error('API Error:', result);
            alert('데이터 로드 실패: ' + result.message);
            if (result.message.includes('세션') || result.message.includes('인증')) {
                localStorage.removeItem('session_token');
                location.href = 'login.html';
            }
        }
    } catch (error) {
        console.error('데이터 로드 오류:', error);
        alert('데이터 로드 중 오류가 발생했습니다: ' + error.message);
    }
}

// 대시보드 업데이트
function updateDashboard(data) {
    document.getElementById('userCode').textContent = data.user_id;
    document.getElementById('referralCount').textContent = data.total_referrals || 0;
    document.getElementById('downlineCount').textContent = data.total_downline || 0;
    document.getElementById('downlineLevel').textContent = `최대 ${data.max_level || 0}단계`;
    document.getElementById('avatarCount').textContent = data.total_avatars || 0;

    const availableBonus = parseFloat(data.available_bonus || 0);
    const avatarPoints = parseFloat(data.avatar_points || 0);
    const totalBalance = availableBonus + avatarPoints;

    document.getElementById('cashBalance').textContent = `ℙ${availableBonus.toFixed(2)}`;
    document.getElementById('avatarPoints').textContent = `ℙ${avatarPoints.toFixed(2)}`;
    document.getElementById('totalBalanceDisplay').textContent = `ℙ${totalBalance.toFixed(2)}`;
    document.getElementById('totalEarningsShort').textContent = `ℙ${(parseFloat(data.total_bonus || 0) / 1000).toFixed(1)}K`;

    const referralLink = `${window.location.origin}/signup/?ref=${data.user_id}`;
    document.getElementById('referralLinkInput').value = referralLink;
}

// 공지사항 로드
async function loadNotices() {
    try {
        const response = await fetch('../api/notices/get-list.php');
        const result = await response.json();

        const noticeList = document.getElementById('noticeList');

        if (result.success && result.data && result.data.length > 0) {
            noticeList.innerHTML = result.data.slice(0, 5).map(notice => {
                const isNew = isNoticeNew(notice.created_at);
                return `
                    <div class="notice-item" onclick="showNoticeDetail(${notice.id})">
                        <div class="notice-item-content">
                            <div class="notice-item-title">${escapeHtml(notice.title)}</div>
                            <div class="notice-item-date">${formatDate(notice.created_at)}</div>
                        </div>
                        ${isNew ? '<span class="notice-badge">NEW</span>' : ''}
                    </div>
                `;
            }).join('');
        } else {
            noticeList.innerHTML = '<div class="notice-empty">등록된 공지사항이 없습니다</div>';
        }
    } catch (error) {
        console.error('공지사항 로드 오류:', error);
        document.getElementById('noticeList').innerHTML = '<div class="notice-empty">공지사항을 불러올 수 없습니다</div>';
    }
}

// 보너스 타입별 요약 로드
async function loadBonusSummary() {
    const sessionToken = localStorage.getItem('session_token');
    try {
        const response = await fetch(`../api/bonus/get-summary.php?session_token=${sessionToken}`);
        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;

            document.getElementById('referralBonusAmount').textContent =
                'ℙ' + parseFloat(data.total_referral_bonus || 0).toFixed(2);
            document.getElementById('referralBonusCount').textContent =
                (data.referral_count || 0) + '건';

            document.getElementById('edgeBonusAmount').textContent =
                'ℙ' + parseFloat(data.total_edge_bonus || 0).toFixed(2);
            document.getElementById('edgeBonusCount').textContent =
                (data.edge_count || 0) + '건';

            document.getElementById('matchingBonusAmount').textContent =
                'ℙ' + parseFloat(data.total_matching_bonus || 0).toFixed(2);
            document.getElementById('matchingBonusCount').textContent =
                (data.matching_count || 0) + '건';

            document.getElementById('rollupBonusAmount').textContent =
                'ℙ' + parseFloat(data.total_rollup_bonus || 0).toFixed(2);
            document.getElementById('rollupBonusCount').textContent =
                (data.rollup_count || 0) + '건';
        }
    } catch (error) {
        console.error('보너스 요약 로드 오류:', error);
    }
}

// ========================================
// 사용자 상세 정보
// ========================================
async function showUserDetail() {
    if (!userData) {
        alert('사용자 정보를 불러오는 중입니다.');
        return;
    }

    const content = `
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">회원 코드</div>
                <div class="detail-value">${userData.user_id}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">이름</div>
                <div class="detail-value">${userData.name || '-'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">이메일</div>
                <div class="detail-value">${userData.email || '-'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">전화번호</div>
                <div class="detail-value">${userData.phone || '-'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">패키지</div>
                <div class="detail-value">${userData.package_name || '미구매'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">상태</div>
                <div class="detail-value">${userData.status || '-'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">추천인 코드</div>
                <div class="detail-value">${userData.referral_code || '-'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">스폰서 코드</div>
                <div class="detail-value">${userData.sponsor_code || '-'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">스폰서 위치</div>
                <div class="detail-value">${userData.sponsor_position == 1 ? '왼쪽' : userData.sponsor_position == 2 ? '오른쪽' : '-'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">가입일</div>
                <div class="detail-value">${userData.created_at || '-'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">아바타 여부</div>
                <div class="detail-value">${userData.is_avatar ? '예 🤖' : '아니오'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">BSC 지갑주소</div>
                <div class="detail-value" style="font-size: 11px; word-break: break-all;">${userData.bnb_address || '-'}</div>
            </div>
        </div>

        <div class="info-box" style="margin-top: 20px;">
            <strong>📊 통계 요약</strong><br>
            • 총 수익: <span class="highlight">ℙ${parseFloat(userData.total_bonus || 0).toFixed(2)}</span><br>
            • 출금 가능: <span class="highlight">ℙ${parseFloat(userData.available_bonus || 0).toFixed(2)}</span><br>
            • 아바타 포인트: <span class="highlight">ℙ${parseFloat(userData.avatar_points || 0).toFixed(2)}</span><br>
            • 직접 추천: <span class="highlight">${userData.total_referrals || 0}명</span><br>
            • 전체 조직: <span class="highlight">${userData.total_downline || 0}명</span>
        </div>
    `;

    document.getElementById('userDetailContent').innerHTML = content;
    openModal('modalUserDetail');
}

// ========================================
// 잔액 상세
// ========================================
async function showBalanceDetail() {
    const sessionToken = localStorage.getItem('session_token');

    try {
        const response = await fetch(`../api/dashboard/get-balance-detail.php?session_token=${sessionToken}`);
        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;

            let content = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">총 수익 (누적)</div>
                        <div class="detail-value">ℙ${parseFloat(data.total_bonus || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">캐시 (USDT)</div>
                        <div class="detail-value">ℙ${parseFloat(data.available_bonus || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">아바타 포인트</div>
                        <div class="detail-value">ℙ${parseFloat(data.avatar_points || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">총 출금액</div>
                        <div class="detail-value">ℙ${parseFloat(data.total_withdrawn || 0).toFixed(2)}</div>
                    </div>
                </div>

                <div class="info-box">
                    <strong>💡 잔액 구성</strong><br>
                    • <strong>캐시 (USDT)</strong>: 보너스 수익의 65%, 출금 가능<br>
                    • <strong>아바타 포인트</strong>: 보너스 수익의 35%, 100 APT 도달 시 자동으로 아바타 생성
                </div>

                <h3 style="color: #d4af37; margin: 25px 0 15px 0;">📊 보너스 타입별 내역</h3>
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>보너스 타입</th>
                            <th>건수</th>
                            <th>총액</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr onclick="showBonusTypeDetail('referral')" style="cursor: pointer;">
                            <td>👥 추천 보너스</td>
                            <td>${data.referral_count || 0}건</td>
                            <td>ℙ${parseFloat(data.referral_total || 0).toFixed(2)}</td>
                        </tr>
                        <tr onclick="showBonusTypeDetail('edge')" style="cursor: pointer;">
                            <td>⚡ 엣지 보너스</td>
                            <td>${data.edge_count || 0}건</td>
                            <td>ℙ${parseFloat(data.edge_total || 0).toFixed(2)}</td>
                        </tr>
                        <tr onclick="showBonusTypeDetail('matching')" style="cursor: pointer;">
                            <td>🤝 매칭 보너스</td>
                            <td>${data.matching_count || 0}건</td>
                            <td>ℙ${parseFloat(data.matching_total || 0).toFixed(2)}</td>
                        </tr>
                        <tr onclick="showBonusTypeDetail('rollup')" style="cursor: pointer;">
                            <td>📊 롤업 보너스</td>
                            <td>${data.rollup_count || 0}건</td>
                            <td>ℙ${parseFloat(data.rollup_total || 0).toFixed(2)}</td>
                        </tr>
                    </tbody>
                </table>
            `;

            document.getElementById('balanceDetailContent').innerHTML = content;
            openModal('modalBalanceDetail');
        }
    } catch (error) {
        console.error('잔액 상세 로드 오류:', error);
        alert('잔액 상세 정보를 불러올 수 없습니다.');
    }
}

// ========================================
// 캐시 상세
// ========================================
async function showCashDetail() {
    const sessionToken = localStorage.getItem('session_token');

    try {
        const response = await fetch(`../api/dashboard/get-cash-history.php?session_token=${sessionToken}`);
        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;

            let content = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">현재 캐시</div>
                        <div class="detail-value">ℙ${parseFloat(data.current_cash || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">누적 캐시 수익</div>
                        <div class="detail-value">ℙ${parseFloat(data.total_cash_earned || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">총 출금액</div>
                        <div class="detail-value">ℙ${parseFloat(data.total_withdrawn || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">출금 가능액</div>
                        <div class="detail-value">ℙ${parseFloat(data.withdrawable || 0).toFixed(2)}</div>
                    </div>
                </div>

                <div class="info-box">
                    <strong>💵 캐시 (USDT) 안내</strong><br>
                    • 모든 보너스의 <strong>65%</strong>는 캐시로 지급됩니다<br>
                    • 캐시는 출금 신청을 통해 USDT로 인출 가능합니다<br>
                    • 최소 출금액: <span class="highlight">ℙ10.00</span>
                </div>

                <h3 style="color: #d4af37; margin: 25px 0 15px 0;">💸 최근 출금 내역</h3>
            `;

            if (data.recent_withdrawals && data.recent_withdrawals.length > 0) {
                content += `
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th>신청일</th>
                                <th>금액</th>
                                <th>상태</th>
                                <th>처리일</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.recent_withdrawals.forEach(w => {
                    const statusColors = {
                        'pending': '#ff9800',
                        'approved': '#2196f3',
                        'processing': '#9c27b0',
                        'completed': '#4caf50',
                        'rejected': '#f44336'
                    };
                    content += `
                        <tr>
                            <td>${w.created_at}</td>
                            <td>ℙ${parseFloat(w.amount).toFixed(2)}</td>
                            <td><span style="color: ${statusColors[w.status]}">${w.status_name}</span></td>
                            <td>${w.processed_at || '-'}</td>
                        </tr>
                    `;
                });

                content += `
                        </tbody>
                    </table>
                `;
            } else {
                content += '<p style="text-align: center; color: rgba(255,255,255,0.5); padding: 20px;">출금 내역이 없습니다</p>';
            }

            document.getElementById('cashDetailContent').innerHTML = content;
            openModal('modalCashDetail');
        }
    } catch (error) {
        console.error('캐시 상세 로드 오류:', error);
        alert('캐시 상세 정보를 불러올 수 없습니다.');
    }
}

// ========================================
// 포인트 상세
// ========================================
async function showPointsDetail() {
    const sessionToken = localStorage.getItem('session_token');

    try {
        const response = await fetch(`../api/dashboard/get-points-history.php?session_token=${sessionToken}`);
        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;

            let content = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">현재 포인트</div>
                        <div class="detail-value">ℙ${parseFloat(data.current_points || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">누적 포인트 획득</div>
                        <div class="detail-value">ℙ${parseFloat(data.total_points_earned || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">아바타 생성 사용</div>
                        <div class="detail-value">ℙ${parseFloat(data.used_for_avatars || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">생성된 아바타 수</div>
                        <div class="detail-value">${data.total_avatars || 0}개</div>
                    </div>
                </div>

                <div class="info-box">
                    <strong>🤖 아바타 포인트 (APT) 안내</strong><br>
                    • 모든 보너스의 <strong>35%</strong>는 아바타 포인트로 지급됩니다<br>
                    • 포인트가 <strong>100 APT</strong>에 도달하면 자동으로 아바타가 생성됩니다<br>
                    • 아바타는 무한 생성 가능하며, 패키지 패턴을 그대로 복제합니다<br>
                    • 아바타의 캐시 수익은 <strong>65%</strong>가 소유자에게 지급됩니다
                </div>

                <div class="warning-box">
                    ⚠️ <strong>다음 아바타 생성까지</strong><br>
                    현재: <span class="highlight">${parseFloat(data.current_points || 0).toFixed(2)} APT</span> / 필요: <span class="highlight">100 APT</span><br>
                    남은 APT: <span class="highlight">${(100 - parseFloat(data.current_points || 0)).toFixed(2)} APT</span>
                </div>
            `;

            if (data.recent_avatars && data.recent_avatars.length > 0) {
                content += `
                    <h3 style="color: #d4af37; margin: 25px 0 15px 0;">🤖 최근 생성된 아바타</h3>
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th>아바타 코드</th>
                                <th>생성일</th>
                                <th>누적 수익</th>
                                <th>내 수익 (65%)</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.recent_avatars.forEach(a => {
                    const myEarnings = parseFloat(a.total_bonus || 0) * 0.65;
                    content += `
                        <tr>
                            <td>${a.avatar_code}</td>
                            <td>${a.created_at}</td>
                            <td>ℙ${parseFloat(a.total_bonus || 0).toFixed(2)}</td>
                            <td>ℙ${myEarnings.toFixed(2)}</td>
                        </tr>
                    `;
                });

                content += `
                        </tbody>
                    </table>
                `;
            }

            document.getElementById('pointsDetailContent').innerHTML = content;
            openModal('modalPointsDetail');
        }
    } catch (error) {
        console.error('포인트 상세 로드 오류:', error);
        alert('포인트 상세 정보를 불러올 수 없습니다.');
    }
}

// ========================================
// 보너스 타입별 상세
// ========================================
async function showBonusTypeDetail(bonusType) {
    const sessionToken = localStorage.getItem('session_token');

    const bonusNames = {
        'referral': '👥 추천 보너스',
        'edge': '⚡ 엣지 보너스',
        'matching': '🤝 매칭 보너스',
        'rollup': '📊 롤업 보너스'
    };

    const bonusDescriptions = {
        'referral': '직접 추천한 회원이 패키지 구매 시 25% 지급',
        'edge': '이진 트리에서 방향 전환 발생 시 25% 지급',
        'matching': '엣지 보너스를 받은 회원의 추천인에게 25% 지급',
        'rollup': '스폰서 라인 최대 25단계까지 레벨당 ℙ1 지급'
    };

    document.getElementById('bonusTypeTitle').textContent = bonusNames[bonusType];

    try {
        const response = await fetch(`../api/dashboard/get-bonus-type-detail.php?session_token=${sessionToken}&bonus_type=${bonusType}`);
        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;

            let content = `
                <div class="info-box">
                    <strong>${bonusNames[bonusType]}</strong><br>
                    ${bonusDescriptions[bonusType]}
                </div>

                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">총 건수</div>
                        <div class="detail-value">${data.total_count || 0}건</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">총 금액</div>
                        <div class="detail-value">ℙ${parseFloat(data.total_amount || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">캐시 (65%)</div>
                        <div class="detail-value">ℙ${(parseFloat(data.total_amount || 0) * 0.65).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">포인트 (35%)</div>
                        <div class="detail-value">ℙ${(parseFloat(data.total_amount || 0) * 0.35).toFixed(2)}</div>
                    </div>
                </div>
            `;

            if (data.bonuses && data.bonuses.length > 0) {
                content += `
                    <h3 style="color: #d4af37; margin: 25px 0 15px 0;">📋 최근 발생 내역 (최근 50건)</h3>
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th>날짜</th>
                                <th>발생처</th>
                                ${bonusType === 'rollup' ? '<th>레벨</th>' : ''}
                                <th>금액</th>
                                <th>캐시</th>
                                <th>포인트</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.bonuses.forEach(b => {
                    const cash = parseFloat(b.amount) * 0.65;
                    const points = parseFloat(b.amount) * 0.35;
                    content += `
                        <tr>
                            <td>${b.created_at}</td>
                            <td>${b.from_user || '-'}</td>
                            ${bonusType === 'rollup' ? `<td>${b.level || '-'}단계</td>` : ''}
                            <td>ℙ${parseFloat(b.amount).toFixed(2)}</td>
                            <td>ℙ${cash.toFixed(2)}</td>
                            <td>ℙ${points.toFixed(2)}</td>
                        </tr>
                    `;
                });

                content += `
                        </tbody>
                    </table>
                `;
            } else {
                content += '<p style="text-align: center; color: rgba(255,255,255,0.5); padding: 20px;">아직 발생한 보너스가 없습니다</p>';
            }

            document.getElementById('bonusTypeContent').innerHTML = content;
            openModal('modalBonusTypeDetail');
        }
    } catch (error) {
        console.error('보너스 타입 상세 로드 오류:', error);
        alert('보너스 상세 정보를 불러올 수 없습니다.');
    }
}

// ========================================
// 추천인 리스트
// ========================================
async function showReferralList() {
    openModal('modalReferralList');

    const sessionToken = localStorage.getItem('session_token');

    try {
        const response = await fetch(`../api/dashboard/get-referrals.php?session_token=${sessionToken}`);
        const result = await response.json();

        if (result.success) {
            const content = document.getElementById('referralListContent');

            if (!result.data.referrals || result.data.referrals.length === 0) {
                content.innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.5);">아직 추천인이 없습니다.</p>';
                return;
            }

            content.innerHTML = result.data.referrals.map(ref => `
                <div class="list-item" onclick="showReferralDetail('${ref.user_id}')">
                    <div class="list-item-header">
                        <div class="list-item-title">${ref.user_id}</div>
                        <div class="list-item-badge">${ref.package_name || '미구매'}</div>
                    </div>
                    <div class="list-item-info">
                        <div>📅 가입일: ${ref.created_at}</div>
                        <div>💰 총 수익: ℙ${parseFloat(ref.total_bonus || 0).toFixed(2)}</div>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('추천인 리스트 로드 오류:', error);
    }
}

// ========================================
// 추천인 상세 정보
// ========================================
async function showReferralDetail(userId) {
    closeModal('modalReferralList');
    openModal('modalReferralDetail');

    const sessionToken = localStorage.getItem('session_token');

    try {
        const response = await fetch(`../api/dashboard/get-referral-detail.php?session_token=${sessionToken}&user_id=${userId}`);
        const result = await response.json();

        if (result.success) {
            const ref = result.data.user_info;
            const content = document.getElementById('referralDetailContent');

            content.innerHTML = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">회원 코드</div>
                        <div class="detail-value">${ref.user_id}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">이름</div>
                        <div class="detail-value">${ref.name || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">이메일</div>
                        <div class="detail-value">${ref.email || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">패키지</div>
                        <div class="detail-value">${ref.package_name || '미구매'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">가입일</div>
                        <div class="detail-value">${ref.created_at}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">총 수익</div>
                        <div class="detail-value">ℙ${parseFloat(ref.total_bonus || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">출금 가능</div>
                        <div class="detail-value">ℙ${parseFloat(ref.available_bonus || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">아바타 포인트</div>
                        <div class="detail-value">ℙ${parseFloat(ref.avatar_points || 0).toFixed(2)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">하위 조직</div>
                        <div class="detail-value">${ref.downline_count || 0}명</div>
                    </div>
                </div>

                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="closeModal('modalReferralDetail'); showReferralList();">
                        ← 목록으로
                    </button>
                </div>
            `;
        }
    } catch (error) {
        console.error('추천인 상세 로드 오류:', error);
    }
}

// ========================================
// 아바타 리스트
// ========================================
async function showAvatarList() {
    openModal('modalAvatarList');

    const sessionToken = localStorage.getItem('session_token');

    try {
        const response = await fetch(`../api/dashboard/get-avatars.php?session_token=${sessionToken}`);
        const result = await response.json();

        if (result.success) {
            const content = document.getElementById('avatarListContent');

            if (!result.data.avatars || result.data.avatars.length === 0) {
                content.innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.5);">아직 아바타가 생성되지 않았습니다.</p>';
                return;
            }

            content.innerHTML = result.data.avatars.map(avatar => {
                const ownerEarnings = parseFloat(avatar.total_bonus || 0) * 0.65;
                return `
                    <div class="list-item">
                        <div class="list-item-header">
                            <div class="list-item-title">🤖 ${avatar.user_id}</div>
                            <div class="list-item-badge">활성</div>
                        </div>
                        <div class="list-item-info">
                            <div>📅 생성일: ${avatar.created_date}</div>
                            <div>💰 누적 수익: ℙ${parseFloat(avatar.total_bonus || 0).toFixed(2)}</div>
                            <div>💵 내 수익 (65%): ℙ${ownerEarnings.toFixed(2)}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    } catch (error) {
        console.error('아바타 리스트 로드 오류:', error);
    }
}

// ========================================
// 수익 상세 내역
// ========================================
async function showEarningsDetail() {
    openModal('modalEarnings');

    const sessionToken = localStorage.getItem('session_token');

    try {
        const response = await fetch(`../api/dashboard/get-earnings-detail.php?session_token=${sessionToken}`);
        const result = await response.json();

        if (result.success) {
            const content = document.getElementById('earningsContent');

            if (!result.data.bonuses || result.data.bonuses.length === 0) {
                content.innerHTML = '<p style="text-align:center;color:rgba(255,255,255,0.5);">수익 내역이 없습니다.</p>';
                return;
            }

            content.innerHTML = `
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>날짜</th>
                            <th>보너스 타입</th>
                            <th>금액</th>
                            <th>발생처</th>
                            <th>레벨</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${result.data.bonuses.map(bonus => `
                            <tr>
                                <td>${bonus.created_date}</td>
                                <td>${bonus.bonus_type_name}</td>
                                <td><span style="color: #4caf50; font-weight: 600;">+ℙ${parseFloat(bonus.amount).toFixed(2)}</span></td>
                                <td>${bonus.from_user_code || '-'}</td>
                                <td>${bonus.level || '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }
    } catch (error) {
        console.error('수익 내역 로드 오류:', error);
    }
}

// ========================================
// 공지사항 상세
// ========================================
async function showNoticeDetail(noticeId) {
    try {
        const response = await fetch(`../api/notices/get-detail.php?id=${noticeId}`);
        const result = await response.json();

        if (result.success && result.data) {
            const notice = result.data;

            document.getElementById('noticeDetailContent').innerHTML = `
                <div style="margin-bottom: 20px;">
                    <h3 style="color: #d4af37; margin-bottom: 10px;">${escapeHtml(notice.title)}</h3>
                    <div style="color: rgba(255,255,255,0.6); font-size: 13px; margin-bottom: 20px;">
                        ${formatDate(notice.created_at)}
                    </div>
                    <div style="color: #fff; line-height: 1.8; white-space: pre-wrap;">
                        ${escapeHtml(notice.content)}
                    </div>
                </div>
            `;

            openModal('modalNoticeDetail');
        } else {
            alert('공지사항을 불러올 수 없습니다.');
        }
    } catch (error) {
        console.error('공지사항 상세 로드 오류:', error);
        alert('공지사항을 불러올 수 없습니다.');
    }
}

// ========================================
// 유틸리티 함수
// ========================================
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));

    if (days === 0) {
        const hours = Math.floor(diff / (1000 * 60 * 60));
        if (hours === 0) {
            const minutes = Math.floor(diff / (1000 * 60));
            return `${minutes}분 전`;
        }
        return `${hours}시간 전`;
    } else if (days < 7) {
        return `${days}일 전`;
    }

    return date.toLocaleDateString('ko-KR');
}

function isNoticeNew(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    return days < 3;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function toggleMenu() {
    const menu = document.getElementById('dropdownMenu');
    menu.classList.toggle('active');
}

// 메뉴 외부 클릭 시 닫기
document.addEventListener('click', (e) => {
    const menu = document.getElementById('dropdownMenu');
    const menuBtn = document.querySelector('.menu-btn');

    if (menu && menuBtn && !menu.contains(e.target) && !menuBtn.contains(e.target)) {
        menu.classList.remove('active');
    }
});

// ========================================
// 기타 함수
// ========================================
function showOrganization() {
    location.href = 'organization.html';
}

function showWithdrawalForm() {
    location.href = 'withdrawal.html';
}

function showReferralLink() {
    openModal('modalReferralLink');
}

function showBonusHistory() {
    showEarningsDetail();
}

function copyReferralLink() {
    const input = document.getElementById('referralLinkInput');
    input.select();
    document.execCommand('copy');
    alert('추천 링크가 복사되었습니다!');
}

function goToSignup() {
    if (userData && userData.user_id) {
        const signupUrl = `../signup/?ref=${userData.user_id}`;
        window.open(signupUrl, '_blank');
    } else {
        window.open('../signup/', '_blank');
    }
}

function showProfileEdit() {
    alert('정보 변경 기능은 추후 구현 예정입니다.');
}

function logout() {
    if (confirm('로그아웃 하시겠습니까?')) {
        localStorage.removeItem('session_token');
        location.href = 'login.html';
    }
}
