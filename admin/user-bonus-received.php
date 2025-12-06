<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>받은 보너스 - K-Pumasi Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            padding: 20px;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-button:hover {
            background: rgba(59, 130, 246, 0.3);
            transform: translateX(-5px);
        }

        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }

        .search-container {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .search-container input {
            padding: 8px 12px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 8px;
            color: #f8fafc;
            font-size: 0.85em;
            width: 180px;
            transition: all 0.3s;
        }

        .search-container input:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-container button {
            padding: 8px 12px;
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
        }

        .search-container button:hover {
            background: rgba(59, 130, 246, 0.3);
            transform: translateY(-2px);
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .nav-button {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(30, 41, 59, 0.5);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9em;
            font-weight: 500;
        }

        .nav-button:hover {
            background: rgba(30, 41, 59, 0.8);
            color: #e2e8f0;
            transform: translateY(-2px);
        }

        .nav-button.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #ffffff;
            border-color: #3b82f6;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .nav-button.active::before {
            content: "● ";
            color: #ffffff;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .loading {
            text-align: center;
            color: #94a3b8;
            padding: 40px;
            font-size: 1.1em;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
        }

        /* 회원 현황판 */
        .member-dashboard {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .member-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(148, 163, 184, 0.2);
        }

        .member-dashboard-title {
            font-size: 1.8em;
            font-weight: 700;
            color: #f8fafc;
        }

        .member-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .member-info-card {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 12px;
            padding: 20px;
        }

        .member-info-card h3 {
            color: #94a3b8;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.05);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #94a3b8;
            font-size: 0.9em;
        }

        .info-value {
            color: #f8fafc;
            font-weight: 600;
        }

        .info-value.highlight {
            color: #10b981;
        }

        .member-details-section {
            margin-top: 30px;
        }

        .detail-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(148, 163, 184, 0.1);
        }

        .detail-tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-weight: 600;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
            font-size: 0.95em;
        }

        .detail-tab:hover {
            color: #60a5fa;
        }

        .detail-tab.active {
            color: #60a5fa;
            border-bottom-color: #60a5fa;
        }

        .detail-tab-content {
            display: none;
        }

        .detail-tab-content.active {
            display: block;
        }

        .bonus-list-item {
            background: rgba(15, 23, 42, 0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .bonus-list-item:hover {
            background: rgba(59, 130, 246, 0.05);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .bonus-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .bonus-type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .bonus-type-badge.referral { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .bonus-type-badge.edge { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .bonus-type-badge.matching { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .bonus-type-badge.rollup { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }

        /* 보너스 타입 버튼 스타일 */
        .bonus-type-btn {
            width: 100%;
            background: transparent;
            border: 2px solid rgba(148, 163, 184, 0.3);
            color: inherit;
            font-size: 0.85em;
            margin-bottom: 8px;
            cursor: pointer;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .bonus-type-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: currentColor;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .bonus-type-btn.active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(16, 185, 129, 0.2));
            border-color: currentColor;
            border-width: 3px;
            box-shadow: 0 0 20px currentColor;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <div style="display: flex; gap: 10px;">
                <a href="users-manage.html" class="back-button" title="회원 목록">👥</a>
                <a href="index.php" class="back-button" title="대시보드">🏠</a>
                <a href="bonus-history.html" class="back-button" title="보너스 히스토리">📊</a>
            </div>
            <div class="search-container">
                <input type="text" id="userSearchInput" placeholder="회원 ID 검색..." onkeypress="if(event.key==='Enter') searchUser()" />
                <button onclick="searchUser()">🔍</button>
            </div>
            <div class="nav-buttons">
                <a href="#" id="nav_user_detail" class="nav-button">회원</a>
                <a href="#" id="nav_bonus_received" class="nav-button active">받은 보너스</a>
                <a href="#" id="nav_bonus_given" class="nav-button">발생한 보너스</a>
            </div>
        </div>

        <div id="loading" class="loading">회원 정보를 불러오는 중...</div>
        <div id="error" class="error-message" style="display: none;"></div>

        <div id="content" style="display: none;">
            <div class="member-dashboard" id="memberDashboard">
                <!-- 회원 정보가 여기에 표시됩니다 -->
            </div>
        </div>
    </div>

    <script>
        // 회원 검색
        function searchUser() {
            const userId = document.getElementById('userSearchInput').value.trim();
            if (!userId) {
                alert('회원 ID를 입력하세요.');
                return;
            }
            window.location.href = `user-bonus-received.php?user_id=${encodeURIComponent(userId)}`;
        }

        // 조직도 표시
        async function showOrgChart(bonusType) {
            const orgChartArea = document.getElementById('orgChartArea');
            if (!orgChartArea) return;

            const data = window.currentMemberData;
            if (!data) return;

            // 모든 버튼의 active 클래스 제거
            document.querySelectorAll('.bonus-type-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // 현재 선택된 버튼에 active 클래스 추가
            const currentBtn = document.querySelector(`.bonus-type-btn[data-type="${bonusType}"]`);
            if (currentBtn) {
                currentBtn.classList.add('active');
            }

            // 타입별 색상
            const typeColors = {
                'referral': '#60a5fa',
                'edge': '#10b981',
                'matching': '#fbbf24',
                'rollup': '#a78bfa'
            };

            const color = typeColors[bonusType];

            if (bonusType === 'referral') {
                renderReferralOrgChart(orgChartArea, data, color);
            } else if (bonusType === 'edge') {
                await renderEdgeOrgChart(orgChartArea, data, color);
            } else if (bonusType === 'matching') {
                await renderMatchingOrgChart(orgChartArea, data, color);
            } else if (bonusType === 'rollup') {
                renderRollupOrgChart(orgChartArea, data, color);
            } else {
                orgChartArea.innerHTML = `
                    <div style="color: ${color}; font-size: 1.2em; font-weight: 600;">
                        ${bonusType.toUpperCase()} 조직도 (개발 중)
                    </div>
                `;
            }
        }

        // REFERRAL 조직도 렌더링
        function renderReferralOrgChart(container, data, color) {
            const m = data.member;
            let referrals = data.referrals || [];

            // 날짜 순서대로 정렬 (오래된 순)
            referrals = referrals.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

            // 내가 받은 referral 보너스에서 각 회원이 보너스를 발생시켰는지 확인
            const receivedBonuses = data.received_bonuses.filter(b => b.bonus_type === 'referral');
            const bonusReceivedFrom = new Set(receivedBonuses.map(b => b.giver_code));

            // 계단식 오프셋 계산 (우측 넘침 방지)
            const containerWidth = container.clientWidth || 1100; // 기본값
            const nodeWidth = 250; // 노드 예상 너비
            const padding = 40; // 좌우 패딩
            const availableWidth = containerWidth - padding * 2 - nodeWidth;

            // 추천 회원이 2명 이상일 때만 계단 간격 계산 (미세한 들여쓰기)
            let stepSize = 8; // 기본 간격 (기존의 10%)
            if (referrals.length > 1) {
                const totalSteps = referrals.length;
                const maxOffset = availableWidth;
                stepSize = Math.min(8, Math.floor(maxOffset / totalSteps));
                stepSize = Math.max(3, stepSize); // 최소 3px 보장
            }

            let html = `
                <div style="width: 100%; position: relative; padding: 20px;">
                    <h3 style="color: ${color}; margin-bottom: 30px; font-size: 1.3em;">REFERRAL 조직도</h3>

                    <!-- 본인 (좌측 상단) -->
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div style="background: rgba(59, 130, 246, 0.2); border: 2px solid ${color}; border-radius: 10px; padding: 12px 25px; width: fit-content;">
                            <div style="color: ${color}; font-weight: 700; font-size: 1em;">${m.user_id}</div>
                            <div style="color: #94a3b8; font-size: 0.8em; margin-top: 3px;">${m.name || '-'}</div>
                        </div>
            `;

            if (referrals.length === 0) {
                html += `
                    <div style="color: #64748b; font-size: 0.9em; margin-top: 20px;">추천한 회원이 없습니다</div>
                `;
            } else {
                // 추천한 회원들 (계단식 배치 - 동적 간격)
                referrals.forEach((ref, index) => {
                    const hasBonus = bonusReceivedFrom.has(ref.user_id);
                    const borderColor = hasBonus ? '#10b981' : '#64748b';
                    const bgColor = hasBonus ? 'rgba(16, 185, 129, 0.1)' : 'rgba(100, 116, 139, 0.1)';
                    const statusColor = hasBonus ? '#10b981' : '#94a3b8';

                    // 일련번호
                    const sequenceNumber = index + 1;

                    // 날짜 포맷 (MM.DD)
                    const date = new Date(ref.created_at);
                    const dateStr = `${String(date.getMonth() + 1).padStart(2, '0')}.${String(date.getDate()).padStart(2, '0')}`;

                    // 계단식 오프셋 (첫 노드는 루트와 같은 위치, 이후 공백 증가)
                    const marginLeft = index * stepSize;

                    html += `
                        <!-- 추천 회원 (계단식) -->
                        <div style="background: ${bgColor}; border: 2px solid ${borderColor}; border-radius: 10px; padding: 10px 20px; width: fit-content; margin-left: ${marginLeft}px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="color: ${statusColor}; font-size: 1em; font-weight: 600;">${sequenceNumber}</span>
                                <span onclick="location.href='user-bonus-received.php?user_id=${encodeURIComponent(ref.user_id)}'" style="color: #f8fafc; font-weight: 600; font-size: 0.95em; cursor: pointer; text-decoration: underline;" onmouseover="this.style.color='#60a5fa'" onmouseout="this.style.color='#f8fafc'">${ref.user_id}</span>
                                <span style="color: ${statusColor}; font-size: 0.8em;">${dateStr}</span>
                            </div>
                        </div>
                    `;
                });
            }

            html += `
                    </div>
                </div>
            `;

            container.style.display = 'block';
            container.style.alignItems = 'flex-start';
            container.style.justifyContent = 'flex-start';
            container.innerHTML = html;
        }

        // 체인 조회 함수 (좌측/우측)
        async function fetchChain(userId, direction = 'right') {
            try {
                const response = await fetch(`/admin/api/get-right-chain.php?user_id=${userId}&direction=${direction}&_=${Date.now()}`);
                const result = await response.json();
                return result.success ? result.chain : [];
            } catch (error) {
                console.error(`${direction === 'left' ? '좌측' : '우측'} 체인 조회 실패:`, error);
                return [];
            }
        }

        // EDGE 조직도 렌더링
        async function renderEdgeOrgChart(container, data, color) {
            const m = data.member;
            const sponsored = data.sponsored || [];

            // 엣지 보너스에서 대상자 정보 수집
            const receivedEdgeBonuses = data.received_bonuses.filter(b => b.bonus_type === 'edge');

            // 디버깅: 엣지 보너스 상세 정보
            console.log('=== EDGE 조직도 디버깅 ===');
            console.log('1. 받은 엣지 보너스 전체:', receivedEdgeBonuses);
            console.log('2. sponsored 배열:', sponsored);
            if (receivedEdgeBonuses.length > 0) {
                console.log('3. 첫 번째 엣지 보너스:', receivedEdgeBonuses[0]);
                console.log('   - giver_code:', receivedEdgeBonuses[0].giver_code);
                console.log('   - edge_position:', receivedEdgeBonuses[0].edge_position);
            }

            // 엣지 발생자별로 정보 그룹화 (edge_position 포함)
            const edgeGiversMap = {};
            receivedEdgeBonuses.forEach(b => {
                const giverId = b.giver_code;
                const edgePosition = b.edge_position; // 'left' or 'right'

                if (!edgeGiversMap[giverId]) {
                    edgeGiversMap[giverId] = {
                        user_id: giverId,
                        name: b.giver_name,
                        created_at: b.created_at,
                        total: 0,
                        edge_position: edgePosition
                    };
                }
                edgeGiversMap[giverId].total += parseFloat(b.amount || 0);
            });

            // edge_position으로 좌측/우측 레그 분류
            let leftEdgeTargets = [];
            let rightEdgeTargets = [];

            console.log('4. edgeGiversMap:', edgeGiversMap);

            Object.values(edgeGiversMap).forEach(giver => {
                console.log(`   처리 중: ${giver.user_id}, edge_position: ${giver.edge_position}`);

                if (giver.edge_position === 'left') {
                    console.log(`     → 좌측 레그 (edge_position=left)`);
                    leftEdgeTargets.push(giver);
                } else if (giver.edge_position === 'right') {
                    console.log(`     → 우측 레그 (edge_position=right)`);
                    rightEdgeTargets.push(giver);
                } else {
                    // edge_position이 없는 경우: sponsored 배열에서 찾기 (폴백)
                    const sponsoredUser = sponsored.find(s => s.user_id === giver.user_id);
                    console.log(`     edge_position=null, sponsored 검색:`, sponsoredUser);

                    if (sponsoredUser) {
                        if (sponsoredUser.sponsor_position === 1) {
                            console.log(`     → 좌측 레그 (fallback, sponsor_position=1)`);
                            leftEdgeTargets.push(giver);
                        } else if (sponsoredUser.sponsor_position === 2) {
                            console.log(`     → 우측 레그 (fallback, sponsor_position=2)`);
                            rightEdgeTargets.push(giver);
                        }
                    } else {
                        console.log(`     → 좌측 레그 (기본값, sponsored 배열에 없음)`);
                        // 위치 불명: 좌측에 추가
                        leftEdgeTargets.push(giver);
                    }
                }
            });

            console.log('5. 최종 분류 결과:');
            console.log('   - 좌측 레그:', leftEdgeTargets.map(t => t.user_id));
            console.log('   - 우측 레그:', rightEdgeTargets.map(t => t.user_id));
            console.log('======================');

            // 날짜 순서대로 정렬 (오래된 순)
            leftEdgeTargets = leftEdgeTargets.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            rightEdgeTargets = rightEdgeTargets.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

            // 실제 직접 하위 찾기 (레이블용)
            console.log('6. sponsored 배열 상세:', sponsored);
            const leftDirectChild = sponsored.find(s => s.sponsor_position === 1);
            const rightDirectChild = sponsored.find(s => s.sponsor_position === 2);
            console.log('7. leftDirectChild:', leftDirectChild);
            console.log('8. rightDirectChild:', rightDirectChild);
            const leftLabel = leftDirectChild ? leftDirectChild.user_id : '';
            const rightLabel = rightDirectChild ? rightDirectChild.user_id : '';

            // 좌측 직접 하위의 우측 체인 가져오기
            let leftRightChain = [];
            if (leftDirectChild) {
                console.log('9. 좌측 체인 조회 시작:', leftDirectChild.user_id);
                leftRightChain = await fetchChain(leftDirectChild.user_id, 'right');
                console.log('10. leftRightChain 결과:', leftRightChain);
                console.log('   - length:', leftRightChain.length);
                if (leftRightChain.length > 0) {
                    console.log('   - 첫 번째:', leftRightChain[0].user_id, 'right_children:', leftRightChain[0].right_children);
                }
            }

            // 우측 직접 하위의 좌측 엣지 레그와 우측 체인 가져오기
            let rightLeftChain = [];
            let rightRightChain = [];
            if (rightDirectChild) {
                console.log('11. 우측 레그의 좌측 엣지 레그 조회 시작:', rightDirectChild.user_id);
                rightLeftChain = await fetchChain(rightDirectChild.user_id, 'left');
                console.log('12. rightLeftChain 결과:', rightLeftChain);
                console.log('   - length:', rightLeftChain.length);
                if (rightLeftChain.length > 0) {
                    console.log('   - 첫 번째:', rightLeftChain[0].user_id, 'left_children:', rightLeftChain[0].left_children);
                }

                console.log('13. 우측 체인 조회 시작:', rightDirectChild.user_id);
                rightRightChain = await fetchChain(rightDirectChild.user_id, 'right');
                console.log('14. rightRightChain 결과:', rightRightChain);
                console.log('   - length:', rightRightChain.length);
                if (rightRightChain.length > 0) {
                    console.log('   - 첫 번째:', rightRightChain[0].user_id, 'right_children:', rightRightChain[0].right_children);
                }
            }

            let html = `
                <div style="width: 100%; position: relative; padding: 20px;">
                    <h3 style="color: ${color}; margin-bottom: 30px; font-size: 1.3em;">EDGE 조직도</h3>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 80px;">
                        <!-- 좌측 레그 -->
                        <div style="display: flex; flex-direction: column; gap: 20px; align-items: flex-start;">
                            <!-- 레그 라벨 -->
                            <div style="background: rgba(16, 185, 129, 0.15); border: 2px solid ${color}; border-radius: 10px; padding: 12px 25px; width: fit-content;">
                                <div style="color: ${color}; font-weight: 700; font-size: 1em;">좌측 ${leftLabel}</div>
                            </div>

                            <!-- 체인 멤버들 -->
                            <div style="display: flex; flex-direction: column; gap: 15px; margin-left: 40px;">
            `;

            // 좌측 엣지 대상자 표시 제거 (체인만 표시)

            // 좌측 하위의 우측 체인을 엣지 대상자 스타일로 표시
            if (leftRightChain.length > 1) {
                leftRightChain.forEach((node, index) => {
                    // 첫 번째는 직접 하위 자신이므로 건너뜀
                    if (index === 0) return;
                    const borderColor = '#10b981';
                    const bgColor = 'rgba(16, 185, 129, 0.1)';
                    const statusColor = '#10b981';

                    // 일련번호 (1부터 시작)
                    const sequenceNumber = index;

                    // 날짜 포맷 (MM.DD)
                    const date = new Date(node.created_at);
                    const dateStr = `${String(date.getMonth() + 1).padStart(2, '0')}.${String(date.getDate()).padStart(2, '0')}`;

                    const marginLeft = 0;
                    const positionLabel = 'L';
                    const positionColor = '#10b981';

                    html += `
                        <div style="display: flex; align-items: center; gap: 10px; margin-left: ${marginLeft}px;">
                            <span style="color: ${color}; font-size: 1.2em;">↘</span>
                            <div style="background: ${bgColor}; border: 2px solid ${borderColor}; border-radius: 10px; padding: 10px 20px; width: fit-content;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: ${statusColor}; font-size: 1em; font-weight: 600;">${sequenceNumber}</span>
                                    <span onclick="location.href='user-bonus-received.php?user_id=${encodeURIComponent(node.user_id)}'" style="color: #f8fafc; font-weight: 600; font-size: 0.95em; cursor: pointer; text-decoration: underline;" onmouseover="this.style.color='#10b981'" onmouseout="this.style.color='#f8fafc'">${node.user_id}</span>
                                    <span style="color: ${positionColor}; font-size: 0.75em; background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px; font-weight: 700;">${positionLabel}</span>
                                    <span style="color: ${statusColor}; font-size: 0.8em;">${dateStr}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            html += `
                            </div>
                        </div>

                        <!-- 우측 레그 -->
                        <div style="display: flex; flex-direction: column; gap: 20px; align-items: flex-end;">
                            <!-- 레그 라벨 -->
                            <div style="background: rgba(16, 185, 129, 0.15); border: 2px solid ${color}; border-radius: 10px; padding: 12px 25px; width: fit-content;">
                                <div style="color: ${color}; font-weight: 700; font-size: 1em;">우측 ${rightLabel}</div>
                            </div>

                            <!-- 체인 멤버들 -->
                            <div style="display: flex; flex-direction: column; gap: 15px; margin-right: 40px; align-items: flex-end;">
            `;

            // 우측 엣지 대상자 표시 제거 (체인만 표시)

            // 우측 하위의 좌측 엣지 레그를 엣지 대상자 스타일로 표시
            if (rightLeftChain.length > 1) {
                rightLeftChain.forEach((node, index) => {
                    // 첫 번째는 직접 하위 자신이므로 건너뜀
                    if (index === 0) return;
                    const borderColor = '#10b981';
                    const bgColor = 'rgba(16, 185, 129, 0.1)';
                    const statusColor = '#10b981';

                    // 일련번호 (1부터 시작)
                    const sequenceNumber = index;

                    // 날짜 포맷 (MM.DD)
                    const date = new Date(node.created_at);
                    const dateStr = `${String(date.getMonth() + 1).padStart(2, '0')}.${String(date.getDate()).padStart(2, '0')}`;

                    const marginRight = 0;
                    const positionLabel = 'R';
                    const positionColor = '#10b981';

                    html += `
                        <div style="display: flex; align-items: center; gap: 10px; margin-right: ${marginRight}px;">
                            <div style="background: ${bgColor}; border: 2px solid ${borderColor}; border-radius: 10px; padding: 10px 20px; width: fit-content;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="color: ${statusColor}; font-size: 1em; font-weight: 600;">${sequenceNumber}</span>
                                    <span onclick="location.href='user-bonus-received.php?user_id=${encodeURIComponent(node.user_id)}'" style="color: #f8fafc; font-weight: 600; font-size: 0.95em; cursor: pointer; text-decoration: underline;" onmouseover="this.style.color='#10b981'" onmouseout="this.style.color='#f8fafc'">${node.user_id}</span>
                                    <span style="color: ${positionColor}; font-size: 0.75em; background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px; font-weight: 700;">${positionLabel}</span>
                                    <span style="color: ${statusColor}; font-size: 0.8em;">${dateStr}</span>
                                </div>
                            </div>
                            <span style="color: ${color}; font-size: 1.2em;">↙</span>
                        </div>
                    `;
                });
            }

            // 우측 하위의 우측 체인 표시 제거 (좌측 엣지 레그만 표시)

            html += `
                            </div>
                        </div>
                    </div>
                </div>
            `;

            container.style.display = 'block';
            container.style.alignItems = 'flex-start';
            container.style.justifyContent = 'flex-start';
            container.innerHTML = html;
        }

        // MATCHING 조직도 렌더링
        async function renderMatchingOrgChart(container, data, color) {
            const m = data.member;
            let referrals = data.referrals || [];

            // 엣지 보너스와 매칭 보너스 수집
            const receivedEdgeBonuses = data.received_bonuses.filter(b => b.bonus_type === 'edge');
            const receivedMatchingBonuses = data.received_bonuses.filter(b => b.bonus_type === 'matching');

            // 날짜 순서대로 정렬 (오래된 순)
            referrals = referrals.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

            // 디버깅: 매칭 보너스 확인
            console.log('=== MATCHING 조직도 디버깅 ===');
            console.log('전체 매칭 보너스:', receivedMatchingBonuses);
            console.log('전체 엣지 보너스:', receivedEdgeBonuses);
            console.log('매칭 보너스 샘플 (edge_position 확인):', receivedMatchingBonuses.slice(0, 3).map(b => ({
                id: b.id,
                from_user_id: b.from_user_id,
                giver_code: b.giver_code,
                edge_position: b.edge_position,
                description: b.description
            })));
            console.log('엣지 보너스 샘플 (edge_position 확인):', receivedEdgeBonuses.slice(0, 3).map(b => ({
                id: b.id,
                from_user_id: b.from_user_id,
                giver_code: b.giver_code,
                edge_position: b.edge_position,
                description: b.description
            })));

            // sponsored 배열에서 sponsor_position으로 좌/우 추천인 매핑
            const sponsored = data.sponsored || [];
            const leftSponsor = sponsored.find(s => s.sponsor_position === 1);
            const rightSponsor = sponsored.find(s => s.sponsor_position === 2);

            console.log('좌측 스폰서:', leftSponsor);
            console.log('우측 스폰서:', rightSponsor);

            // 엣지 보너스의 from_user_id → edge_position 매핑 생성
            const fromUserIdToPosition = {};
            receivedEdgeBonuses.forEach(b => {
                if (b.from_user_id && b.edge_position) {
                    fromUserIdToPosition[b.from_user_id] = b.edge_position;
                }
            });

            console.log('from_user_id → edge_position 매핑:', fromUserIdToPosition);

            // 각 추천인별로 엣지 발생 건수와 매칭 받은 개수 계산 (비동기)
            const matchingTargetsPromises = referrals.map(async ref => {
                try {
                    // 이 추천인이 받은 엣지 보너스를 API로 조회
                    const response = await fetch(`api/member-financial.php?action=detail&user_id=${encodeURIComponent(ref.user_id)}`);
                    const result = await response.json();

                    if (!result.success) {
                        return {
                            user_id: ref.user_id,
                            name: ref.name,
                            created_at: ref.created_at,
                            edge_count: 0,
                            matching_count: 0,
                            is_match: true
                        };
                    }

                    // 추천인이 받은 엣지 보너스 개수 (from_user_id 기준 중복 제거)
                    const edgeBonuses = result.received_bonuses.filter(b => b.bonus_type === 'edge');
                    // from_user_id 기준으로 유니크하게 카운트 (cash + avatar_point 합쳐서 1개로)
                    const uniqueEdgeFromUsers = [...new Set(edgeBonuses.map(b => b.from_user_id))];
                    const edgeCount = uniqueEdgeFromUsers.length;

                    // 내가 받은 매칭 보너스 중 추천인이 엣지를 받은 구매자와 동일한 from_user_id 카운트
                    // (추천인이 엣지를 받은 구매자 = 내가 매칭을 받은 구매자)
                    const myMatchingFromSameBuyers = receivedMatchingBonuses.filter(b =>
                        uniqueEdgeFromUsers.includes(b.from_user_id)
                    );
                    const uniqueMatchingFromUsers = [...new Set(myMatchingFromSameBuyers.map(b => b.from_user_id))];
                    const matchingCount = uniqueMatchingFromUsers.length;

                    console.log(`추천인 ${ref.user_id} 검증:`, {
                        edgeCount: edgeCount,
                        matchingCount: matchingCount,
                        isMatch: edgeCount === matchingCount,
                        uniqueEdgeFromUsers: uniqueEdgeFromUsers,
                        uniqueMatchingFromUsers: uniqueMatchingFromUsers,
                        edgeBonusDetails: edgeBonuses.map(b => ({id: b.id, from: b.from_user_id, type: b.payment_type, amount: b.amount})),
                        matchingBonusDetails: myMatchingFromSameBuyers.map(b => ({id: b.id, from: b.from_user_id, giver: b.giver_code, type: b.payment_type, amount: b.amount}))
                    });

                    return {
                        user_id: ref.user_id,
                        name: ref.name,
                        created_at: ref.created_at,
                        edge_count: edgeCount,
                        matching_count: matchingCount,
                        is_match: edgeCount === matchingCount
                    };
                } catch (error) {
                    console.error(`추천인 ${ref.user_id} 조회 실패:`, error);
                    return {
                        user_id: ref.user_id,
                        name: ref.name,
                        created_at: ref.created_at,
                        edge_count: 0,
                        matching_count: 0,
                        is_match: true
                    };
                }
            });

            // 모든 비동기 작업 완료 대기
            const matchingTargets = await Promise.all(matchingTargetsPromises);

            // 전체 합계 계산
            const totalEdgeCount = matchingTargets.reduce((sum, t) => sum + t.edge_count, 0);
            const totalMatchingCount = matchingTargets.reduce((sum, t) => sum + t.matching_count, 0);
            const isTotalMatch = totalEdgeCount === totalMatchingCount;

            // 계단식 오프셋 계산 (레퍼럴 조직도와 동일)
            const containerWidth = container.clientWidth || 1100;
            const nodeWidth = 250;
            const padding = 40;
            const availableWidth = containerWidth - padding * 2 - nodeWidth;

            let stepSize = 8;
            if (matchingTargets.length > 1) {
                const totalSteps = matchingTargets.length;
                const maxOffset = availableWidth;
                stepSize = Math.min(8, Math.floor(maxOffset / totalSteps));
                stepSize = Math.max(3, stepSize);
            }

            let html = `
                <div style="width: 100%; position: relative; padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h3 style="color: ${color}; margin: 0; font-size: 1.3em;">MATCHING 조직도</h3>
                        <div style="background: ${isTotalMatch ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)'}; border: 2px solid ${isTotalMatch ? '#10b981' : '#ef4444'}; border-radius: 10px; padding: 10px 20px;">
                            <span style="color: ${isTotalMatch ? '#10b981' : '#ef4444'}; font-weight: 700; font-size: 1em;">
                                전체 엣지:${totalEdgeCount} / 매칭:${totalMatchingCount} ${isTotalMatch ? '✓ 정상' : '⚠️ 불일치'}
                            </span>
                        </div>
                    </div>

                    <!-- 본인 (좌측 상단) -->
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div style="background: rgba(251, 191, 36, 0.2); border: 2px solid ${color}; border-radius: 10px; padding: 12px 25px; width: fit-content;">
                            <div style="color: ${color}; font-weight: 700; font-size: 1em;">${m.user_id}</div>
                            <div style="color: #94a3b8; font-size: 0.8em; margin-top: 3px;">${m.name || '-'}</div>
                        </div>
            `;

            if (matchingTargets.length === 0) {
                html += `
                    <div style="color: #64748b; font-size: 0.9em; margin-top: 20px;">추천한 회원이 없습니다</div>
                `;
            } else {
                // 모든 추천인 표시 (레퍼럴 조직도 스타일)
                matchingTargets.forEach((target, index) => {
                    // 엣지와 매칭 개수가 일치하면 녹색, 불일치하면 빨간색
                    const isMatch = target.is_match && target.edge_count > 0;
                    const hasIssue = !target.is_match && (target.edge_count > 0 || target.matching_count > 0);

                    const borderColor = isMatch ? '#10b981' : (hasIssue ? '#ef4444' : '#64748b');
                    const bgColor = isMatch ? 'rgba(16, 185, 129, 0.1)' : (hasIssue ? 'rgba(239, 68, 68, 0.1)' : 'rgba(100, 116, 139, 0.1)');
                    const statusColor = isMatch ? '#10b981' : (hasIssue ? '#ef4444' : '#94a3b8');

                    // 일련번호
                    const sequenceNumber = index + 1;

                    // 날짜 포맷 (MM.DD)
                    const date = new Date(target.created_at);
                    const dateStr = `${String(date.getMonth() + 1).padStart(2, '0')}.${String(date.getDate()).padStart(2, '0')}`;

                    // 계단식 오프셋
                    const marginLeft = index * stepSize;

                    html += `
                        <!-- 추천 회원 (계단식) -->
                        <div style="background: ${bgColor}; border: 2px solid ${borderColor}; border-radius: 10px; padding: 10px 20px; width: fit-content; margin-left: ${marginLeft}px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="color: ${statusColor}; font-size: 1em; font-weight: 600;">${sequenceNumber}</span>
                                <span onclick="location.href='user-bonus-received.php?user_id=${encodeURIComponent(target.user_id)}'" style="color: #f8fafc; font-weight: 600; font-size: 0.95em; cursor: pointer; text-decoration: underline;" onmouseover="this.style.color='${isMatch ? '#10b981' : (hasIssue ? '#ef4444' : '#60a5fa')}'" onmouseout="this.style.color='#f8fafc'">${target.user_id}</span>
                                <span style="color: ${statusColor}; font-size: 0.8em; background: rgba(0,0,0,0.2); padding: 3px 8px; border-radius: 4px; font-weight: 500;">엣지:${target.edge_count} / 매칭:${target.matching_count}${hasIssue ? ' ⚠️' : ''}</span>
                                <span style="color: ${statusColor}; font-size: 0.8em;">날짜 ${dateStr}</span>
                            </div>
                        </div>
                    `;
                });
            }

            html += `
                    </div>
                </div>
            `;

            container.style.display = 'block';
            container.style.alignItems = 'flex-start';
            container.style.justifyContent = 'flex-start';
            container.innerHTML = html;
        }

        // ROLLUP 조직도 렌더링
        function renderRollupOrgChart(container, data, color) {
            const m = data.member;

            // 롤업 보너스 수집
            const receivedRollupBonuses = data.received_bonuses.filter(b => b.bonus_type === 'rollup');

            // 레벨별로 그룹화
            const levelMap = {};
            receivedRollupBonuses.forEach(b => {
                const level = b.level || 0;
                if (!levelMap[level]) {
                    levelMap[level] = {
                        level: level,
                        givers: [],
                        total: 0,
                        count: 0
                    };
                }

                // 발생자 추가 (중복 제거)
                const giverId = b.giver_code;
                if (!levelMap[level].givers.find(g => g.user_id === giverId)) {
                    levelMap[level].givers.push({
                        user_id: giverId,
                        name: b.giver_name
                    });
                }

                levelMap[level].total += parseFloat(b.amount || 0);
                levelMap[level].count += 1;
            });

            // 레벨별로 정렬 (높은 레벨부터 낮은 레벨로 - 맨 아래가 높은 레벨)
            const levels = Object.values(levelMap).sort((a, b) => b.level - a.level);

            // 최대 25단계까지만 표시
            const displayLevels = levels.slice(0, 25);

            let html = `
                <div style="width: 100%; position: relative; padding: 20px;">
                    <h3 style="color: ${color}; margin-bottom: 30px; font-size: 1.3em;">ROLLUP 조직도 (최대 25단계)</h3>

                    <div style="display: flex; flex-direction: column-reverse; align-items: center; gap: 15px;">
            `;

            if (displayLevels.length === 0) {
                html += `
                    <div style="color: #64748b; font-size: 0.9em; margin: 20px 0;">롤업 보너스가 없습니다</div>
                `;
            } else {
                // 레벨별로 표시 (아래에서 위로)
                displayLevels.forEach((levelData, index) => {
                    const isDeepLevel = levelData.level >= 10;
                    const bgColor = isDeepLevel ? 'rgba(139, 92, 246, 0.15)' : 'rgba(139, 92, 246, 0.1)';
                    const borderColor = isDeepLevel ? '#a78bfa' : 'rgba(167, 139, 250, 0.5)';

                    // 발생자 목록 (최대 5명만 표시)
                    const displayGivers = levelData.givers.slice(0, 5);
                    const moreCount = levelData.givers.length - displayGivers.length;
                    const giversText = displayGivers.map(g => g.user_id).join(', ') + (moreCount > 0 ? ` 외 ${moreCount}명` : '');

                    html += `
                        <!-- 연결선 -->
                        ${index < displayLevels.length - 1 ? `
                            <div style="width: 2px; height: 20px; background: linear-gradient(to top, ${color}, transparent); opacity: 0.5;"></div>
                        ` : ''}

                        <!-- 레벨 노드 -->
                        <div style="background: ${bgColor}; border: 2px solid ${borderColor}; border-radius: 12px; padding: 15px 30px; min-width: 500px; max-width: 700px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <span style="color: ${color}; font-weight: 700; font-size: 1.1em;">레벨 ${levelData.level}</span>
                                    <span style="color: #94a3b8; font-size: 0.85em;">${levelData.count}건</span>
                                </div>
                                <span style="color: #a78bfa; font-weight: 700; font-size: 1.2em;">$${levelData.total.toFixed(2)}</span>
                            </div>
                            <div style="color: #94a3b8; font-size: 0.8em; margin-top: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${giversText}">
                                ${giversText}
                            </div>
                        </div>
                    `;
                });

                // 화살표 (위쪽으로)
                html += `
                    <div style="width: 2px; height: 30px; background: linear-gradient(to top, ${color}, transparent); opacity: 0.5;"></div>
                    <div style="color: ${color}; font-size: 2em; line-height: 1;">↑</div>
                `;
            }

            // 본인 (맨 위)
            html += `
                    <div style="background: rgba(139, 92, 246, 0.2); border: 3px solid ${color}; border-radius: 12px; padding: 15px 30px; min-width: 300px;">
                        <div style="text-align: center;">
                            <div style="color: ${color}; font-weight: 700; font-size: 1.2em;">${m.user_id}</div>
                            <div style="color: #94a3b8; font-size: 0.85em; margin-top: 5px;">${m.name || '-'}</div>
                        </div>
                    </div>
                </div>
            </div>
            `;

            container.style.display = 'block';
            container.style.alignItems = 'center';
            container.style.justifyContent = 'center';
            container.innerHTML = html;
        }

        // ============================================
        // 보너스 검증 함수들
        // ============================================

        // 레퍼럴 보너스 검증
        async function verifyReferralBonuses(data) {
            const m = data.member;
            const referrals = data.referrals || [];
            const receivedReferralBonuses = data.received_bonuses.filter(b => b.bonus_type === 'referral');

            const results = {
                type: 'referral',
                expectedCount: 0,
                actualCount: receivedReferralBonuses.length,
                expectedAmount: 0,
                actualAmount: receivedReferralBonuses.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0),
                missingBonuses: [],
                details: []
            };

            // 각 추천인의 매출 확인
            for (const ref of referrals) {
                try {
                    // 추천인의 매출 정보 조회
                    const response = await fetch(`api/member-financial.php?action=detail&user_id=${encodeURIComponent(ref.user_id)}`);
                    const refData = await response.json();

                    if (refData.success && refData.sales && refData.sales.length > 0) {
                        const totalSales = refData.sales.reduce((sum, s) => sum + parseFloat(s.amount || 0), 0);
                        const expectedBonus = totalSales * 0.25; // 25% 레퍼럴 보너스

                        // 이 추천인으로부터 받은 보너스 확인
                        const bonusesFromThisRef = receivedReferralBonuses.filter(b => b.giver_code === ref.user_id);
                        const actualBonus = bonusesFromThisRef.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0);

                        results.expectedCount += refData.sales.length;
                        results.expectedAmount += expectedBonus;

                        let status = 'OK';
                        let wrongReceiver = null;

                        // 보너스를 받지 못한 경우 상세 확인
                        if (Math.abs(expectedBonus - actualBonus) > 0.01) {
                            // 추천인의 발생 보너스 확인
                            const givenBonuses = refData.given_bonuses.filter(b =>
                                b.bonus_type === 'referral' &&
                                b.from_user_id === refData.member.id
                            );

                            if (givenBonuses.length === 0) {
                                status = 'MISSING_NOT_GENERATED'; // 누락/미발생
                            } else {
                                // 다른 회원에게 지급되었는지 확인
                                const givenToOthers = givenBonuses.filter(b => b.receiver_code !== m.user_id);
                                if (givenToOthers.length > 0) {
                                    status = 'MISSING_WRONG_RECEIVER'; // 누락/다른 ID 발생
                                    wrongReceiver = givenToOthers.map(b => b.receiver_code).join(', ');
                                } else {
                                    status = 'MISSING_NOT_GENERATED';
                                }
                            }

                            results.missingBonuses.push({
                                user_id: ref.user_id,
                                expected: expectedBonus,
                                actual: actualBonus,
                                diff: expectedBonus - actualBonus,
                                status: status,
                                wrongReceiver: wrongReceiver
                            });
                        }

                        results.details.push({
                            user_id: ref.user_id,
                            name: ref.name,
                            salesCount: refData.sales.length,
                            totalSales: totalSales,
                            expectedBonus: expectedBonus,
                            actualBonus: actualBonus,
                            bonusCount: bonusesFromThisRef.length,
                            status: status,
                            wrongReceiver: wrongReceiver
                        });
                    }
                } catch (error) {
                    console.error(`Error fetching data for ${ref.user_id}:`, error);
                }
            }

            return results;
        }

        // 엣지 보너스 검증
        async function verifyEdgeBonuses(data) {
            const m = data.member;
            const sponsored = data.sponsored || [];
            const receivedEdgeBonuses = data.received_bonuses.filter(b => b.bonus_type === 'edge');

            const results = {
                type: 'edge',
                expectedCount: 0,
                actualCount: receivedEdgeBonuses.length,
                expectedAmount: 0,
                actualAmount: receivedEdgeBonuses.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0),
                missingBonuses: [],
                details: []
            };

            // 좌측/우측 레그로 분류
            const leftLeg = sponsored.filter(s => s.sponsor_position === 1);
            const rightLeg = sponsored.filter(s => s.sponsor_position === 2);

            // 각 스폰서 회원의 하위 매출 확인 (간소화: 직접 매출만 체크)
            for (const sponsor of sponsored) {
                try {
                    const response = await fetch(`api/member-financial.php?action=detail&user_id=${encodeURIComponent(sponsor.user_id)}`);
                    const sponsorData = await response.json();

                    if (sponsorData.success && sponsorData.sales && sponsorData.sales.length > 0) {
                        const totalSales = sponsorData.sales.reduce((sum, s) => sum + parseFloat(s.amount || 0), 0);
                        const expectedBonus = totalSales * 0.25; // 25% 엣지 보너스

                        const bonusesFromThisSponsor = receivedEdgeBonuses.filter(b => b.giver_code === sponsor.user_id);
                        const actualBonus = bonusesFromThisSponsor.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0);

                        results.expectedCount += sponsorData.sales.length;
                        results.expectedAmount += expectedBonus;

                        let status = 'OK';
                        let wrongReceiver = null;

                        // 보너스를 받지 못한 경우 상세 확인
                        if (Math.abs(expectedBonus - actualBonus) > 0.01) {
                            // 스폰서 회원의 발생 보너스 확인
                            const givenBonuses = sponsorData.given_bonuses.filter(b =>
                                b.bonus_type === 'edge' &&
                                b.from_user_id === sponsorData.member.id
                            );

                            if (givenBonuses.length === 0) {
                                status = 'MISSING_NOT_GENERATED'; // 누락/미발생
                            } else {
                                // 다른 회원에게 지급되었는지 확인
                                const givenToOthers = givenBonuses.filter(b => b.receiver_code !== m.user_id);
                                if (givenToOthers.length > 0) {
                                    status = 'MISSING_WRONG_RECEIVER'; // 누락/다른 ID 발생
                                    wrongReceiver = givenToOthers.map(b => b.receiver_code).join(', ');
                                } else {
                                    status = 'MISSING_NOT_GENERATED';
                                }
                            }

                            results.missingBonuses.push({
                                user_id: sponsor.user_id,
                                expected: expectedBonus,
                                actual: actualBonus,
                                diff: expectedBonus - actualBonus,
                                status: status,
                                wrongReceiver: wrongReceiver
                            });
                        }

                        results.details.push({
                            user_id: sponsor.user_id,
                            name: sponsor.name,
                            position: sponsor.sponsor_position === 1 ? '좌측' : '우측',
                            salesCount: sponsorData.sales.length,
                            totalSales: totalSales,
                            expectedBonus: expectedBonus,
                            actualBonus: actualBonus,
                            bonusCount: bonusesFromThisSponsor.length,
                            status: status,
                            wrongReceiver: wrongReceiver
                        });
                    }
                } catch (error) {
                    console.error(`Error fetching data for ${sponsor.user_id}:`, error);
                }
            }

            return results;
        }

        // 매칭 보너스 검증
        async function verifyMatchingBonuses(data) {
            const m = data.member;
            const referrals = data.referrals || [];
            const receivedMatchingBonuses = data.received_bonuses.filter(b => b.bonus_type === 'matching');
            const receivedEdgeBonuses = data.received_bonuses.filter(b => b.bonus_type === 'edge');

            const results = {
                type: 'matching',
                expectedCount: 0,
                actualCount: receivedMatchingBonuses.length,
                expectedAmount: 0,
                actualAmount: receivedMatchingBonuses.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0),
                missingBonuses: [],
                details: []
            };

            // 나의 추천인 중 엣지 보너스를 발생시킨 사람 찾기
            for (const ref of referrals) {
                const edgeBonusesFromRef = receivedEdgeBonuses.filter(b => b.giver_code === ref.user_id);

                if (edgeBonusesFromRef.length > 0) {
                    // 매칭 보너스는 원래 패키지 금액(package_amount)의 25%
                    // edgeBonus.package_amount를 사용해야 함
                    let expectedMatching = 0;
                    edgeBonusesFromRef.forEach(eb => {
                        const packageAmount = parseFloat(eb.package_amount || 0);
                        expectedMatching += packageAmount * 0.25; // 패키지 금액의 25%
                    });

                    const matchingBonusesFromRef = receivedMatchingBonuses.filter(b => b.giver_code === ref.user_id);
                    const actualMatching = matchingBonusesFromRef.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0);

                    results.expectedCount += edgeBonusesFromRef.length;
                    results.expectedAmount += expectedMatching;

                    results.details.push({
                        user_id: ref.user_id,
                        name: ref.name,
                        edgeCount: edgeBonusesFromRef.length,
                        expectedMatching: expectedMatching,
                        actualMatching: actualMatching,
                        matchingCount: matchingBonusesFromRef.length,
                        status: Math.abs(expectedMatching - actualMatching) < 0.01 ? 'OK' : 'MISMATCH'
                    });

                    if (Math.abs(expectedMatching - actualMatching) > 0.01) {
                        results.missingBonuses.push({
                            user_id: ref.user_id,
                            expected: expectedMatching,
                            actual: actualMatching,
                            diff: expectedMatching - actualMatching
                        });
                    }
                }
            }

            return results;
        }

        // 롤업 보너스 검증
        function verifyRollupBonuses(data) {
            const m = data.member;
            const receivedRollupBonuses = data.received_bonuses.filter(b => b.bonus_type === 'rollup');

            const results = {
                type: 'rollup',
                expectedCount: 0, // 실제 계산 복잡하므로 현재 받은 것만 표시
                actualCount: receivedRollupBonuses.length,
                expectedAmount: 0,
                actualAmount: receivedRollupBonuses.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0),
                missingBonuses: [],
                details: []
            };

            // 레벨별 통계
            const levelStats = {};
            receivedRollupBonuses.forEach(b => {
                const level = b.level || 0;
                if (!levelStats[level]) {
                    levelStats[level] = {
                        level: level,
                        count: 0,
                        amount: 0,
                        givers: new Set()
                    };
                }
                levelStats[level].count++;
                levelStats[level].amount += parseFloat(b.amount || 0);
                levelStats[level].givers.add(b.giver_code);
            });

            Object.values(levelStats).sort((a, b) => a.level - b.level).forEach(stat => {
                results.details.push({
                    level: stat.level,
                    count: stat.count,
                    amount: stat.amount,
                    uniqueGivers: stat.givers.size,
                    status: 'INFO'
                });
            });

            return results;
        }

        // 전체 검증 실행
        async function runBonusVerification() {
            const data = window.currentMemberData;
            if (!data) {
                alert('회원 데이터가 없습니다. 먼저 회원을 선택하세요.');
                return;
            }

            // 검증 영역 표시
            const verificationArea = document.getElementById('verificationArea');
            verificationArea.innerHTML = '<div style="text-align: center; padding: 40px; color: #94a3b8;">검증 중...</div>';
            verificationArea.style.display = 'block';

            try {
                // 병렬 검증 실행
                const [referralResults, edgeResults, matchingResults, rollupResults] = await Promise.all([
                    verifyReferralBonuses(data),
                    verifyEdgeBonuses(data),
                    verifyMatchingBonuses(data),
                    Promise.resolve(verifyRollupBonuses(data))
                ]);

                renderVerificationResults([referralResults, edgeResults, matchingResults, rollupResults]);
            } catch (error) {
                console.error('검증 중 오류:', error);
                verificationArea.innerHTML = `<div style="color: #ef4444; padding: 20px;">검증 중 오류가 발생했습니다: ${error.message}</div>`;
            }
        }

        // 검증 결과 렌더링
        function renderVerificationResults(results) {
            const verificationArea = document.getElementById('verificationArea');

            const typeNames = {
                'referral': 'REFERRAL',
                'edge': 'EDGE',
                'matching': 'MATCHING',
                'rollup': 'ROLLUP'
            };

            const typeColors = {
                'referral': '#60a5fa',
                'edge': '#10b981',
                'matching': '#fbbf24',
                'rollup': '#a78bfa'
            };

            let html = `
                <div style="padding: 20px;">
                    <h3 style="color: #f8fafc; margin-bottom: 20px; font-size: 1.5em;">🔍 보너스 검증 결과</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            `;

            results.forEach(result => {
                const color = typeColors[result.type];
                const hasIssues = result.missingBonuses.length > 0;
                const statusColor = hasIssues ? '#ef4444' : '#10b981';
                const statusText = hasIssues ? '⚠️ 불일치 발견' : '✓ 정상';

                html += `
                    <div style="background: rgba(15, 23, 42, 0.4); border: 2px solid ${hasIssues ? '#ef4444' : color}; border-radius: 12px; padding: 20px;">
                        <h4 style="color: ${color}; margin-bottom: 15px; font-size: 1.2em;">${typeNames[result.type]}</h4>
                        <div style="color: ${statusColor}; font-weight: 700; margin-bottom: 15px;">${statusText}</div>

                        <div style="margin-bottom: 10px;">
                            <div style="color: #94a3b8; font-size: 0.85em;">예상 건수: <span style="color: #f8fafc; font-weight: 600;">${result.expectedCount}</span></div>
                            <div style="color: #94a3b8; font-size: 0.85em;">실제 건수: <span style="color: #f8fafc; font-weight: 600;">${result.actualCount}</span></div>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <div style="color: #94a3b8; font-size: 0.85em;">예상 금액: <span style="color: #f8fafc; font-weight: 600;">$${result.expectedAmount.toFixed(2)}</span></div>
                            <div style="color: #94a3b8; font-size: 0.85em;">실제 금액: <span style="color: #f8fafc; font-weight: 600;">$${result.actualAmount.toFixed(2)}</span></div>
                            <div style="color: ${hasIssues ? '#ef4444' : '#10b981'}; font-weight: 700; margin-top: 5px;">차이: $${(result.actualAmount - result.expectedAmount).toFixed(2)}</div>
                        </div>

                        ${result.missingBonuses.length > 0 ? `
                            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 10px; margin-top: 10px;">
                                <div style="color: #ef4444; font-size: 0.85em; font-weight: 600; margin-bottom: 5px;">누락/불일치:</div>
                                ${result.missingBonuses.slice(0, 3).map((miss, idx) => {
                                    const statusLabel = miss.status === 'MISSING_NOT_GENERATED' ? '미발생' :
                                                       miss.status === 'MISSING_WRONG_RECEIVER' ? `다른ID(${miss.wrongReceiver})` : '불일치';
                                    const canFix = miss.status === 'MISSING_WRONG_RECEIVER';
                                    return `
                                    <div style="color: #fca5a5; font-size: 0.8em; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center;">
                                        <span>${miss.user_id}: $${miss.diff.toFixed(2)} <span style="color: #fbbf24;">[${statusLabel}]</span></span>
                                        ${canFix ? `<button onclick="fixBonus('${result.type}', '${miss.user_id}', '${miss.wrongReceiver}', ${Math.abs(miss.diff)})" style="padding: 2px 8px; background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid #10b981; border-radius: 4px; cursor: pointer; font-size: 0.75em;">수정</button>` : ''}
                                    </div>
                                `}).join('')}
                                ${result.missingBonuses.length > 3 ? `<div style="color: #94a3b8; font-size: 0.75em; margin-top: 5px;">... 외 ${result.missingBonuses.length - 3}건</div>` : ''}
                            </div>
                        ` : ''}

                        <button onclick="showVerificationDetails('${result.type}')" style="margin-top: 15px; width: 100%; padding: 8px; background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid #60a5fa; border-radius: 6px; cursor: pointer; font-size: 0.85em;">
                            상세 보기
                        </button>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;

            verificationArea.innerHTML = html;

            // 결과를 전역 변수에 저장
            window.verificationResults = results;
        }

        // 상세 결과 표시
        function showVerificationDetails(type) {
            const results = window.verificationResults;
            if (!results) return;

            const result = results.find(r => r.type === type);
            if (!result) return;

            const typeNames = {
                'referral': 'REFERRAL',
                'edge': 'EDGE',
                'matching': 'MATCHING',
                'rollup': 'ROLLUP'
            };

            let detailHtml = `<h4>${typeNames[type]} 상세 내역</h4><div style="max-height: 400px; overflow-y: auto;">`;

            if (result.details.length === 0) {
                detailHtml += '<div style="color: #94a3b8; padding: 20px;">상세 내역이 없습니다.</div>';
            } else {
                result.details.forEach(detail => {
                    const statusColor = detail.status === 'OK' ? '#10b981' :
                                       (detail.status === 'MISSING_NOT_GENERATED' || detail.status === 'MISSING_WRONG_RECEIVER') ? '#ef4444' : '#94a3b8';
                    const statusText = detail.status === 'OK' ? '✓ 정상' :
                                      detail.status === 'MISSING_NOT_GENERATED' ? '❌ 누락/미발생' :
                                      detail.status === 'MISSING_WRONG_RECEIVER' ? '⚠️ 누락/다른ID 발생' :
                                      detail.status === 'INFO' ? 'ℹ️ 정보' : detail.status;

                    detailHtml += `
                        <div style="background: rgba(15, 23, 42, 0.3); border: 1px solid rgba(148, 163, 184, 0.1); border-radius: 8px; padding: 10px; margin-bottom: 10px;">
                            <div style="color: #f8fafc; font-weight: 600; margin-bottom: 5px;">${detail.user_id || 'Level ' + detail.level}</div>
                            ${Object.entries(detail).map(([key, value]) => {
                                if (key === 'user_id' || key === 'status' || key === 'wrongReceiver') return '';
                                return `<div style="color: #94a3b8; font-size: 0.85em;">${key}: <span style="color: #e2e8f0;">${typeof value === 'number' ? value.toFixed(2) : value}</span></div>`;
                            }).join('')}
                            ${detail.wrongReceiver ? `<div style="color: #fbbf24; font-size: 0.85em; margin-top: 3px;">잘못된 수령자: ${detail.wrongReceiver}</div>` : ''}
                            <div style="color: ${statusColor}; font-weight: 600; margin-top: 5px;">${statusText}</div>
                        </div>
                    `;
                });
            }

            detailHtml += '</div>';

            const modal = document.createElement('div');
            modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 10000;';
            modal.innerHTML = `
                <div style="background: #1e293b; border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 12px; padding: 30px; max-width: 800px; max-height: 80vh; overflow-y: auto; color: #f8fafc;">
                    ${detailHtml}
                    <button onclick="this.closest('div').parentElement.remove()" style="margin-top: 20px; width: 100%; padding: 10px; background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid #60a5fa; border-radius: 6px; cursor: pointer;">
                        닫기
                    </button>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // 보너스 수정 함수
        async function fixBonus(bonusType, giverId, wrongReceiverId, amount) {
            const data = window.currentMemberData;
            if (!data) {
                alert('회원 데이터가 없습니다.');
                return;
            }

            const correctReceiverId = data.member.user_id;
            const wrongReceiverIds = wrongReceiverId.split(', '); // 여러 개일 수 있음
            const firstWrongReceiver = wrongReceiverIds[0];

            // 확인 메시지
            const confirmMessage = `보너스 수정을 진행하시겠습니까?\n\n` +
                `보너스 타입: ${bonusType.toUpperCase()}\n` +
                `발생자: ${giverId}\n` +
                `잘못된 수령자: ${firstWrongReceiver}\n` +
                `올바른 수령자: ${correctReceiverId}\n` +
                `금액: $${amount.toFixed(2)}`;

            if (!confirm(confirmMessage)) {
                return;
            }

            // 진행 상황 모달 생성
            const progressModal = createProgressModal();
            document.body.appendChild(progressModal);

            try {
                // 1단계: 잘못된 보너스 삭제
                updateProgressModal('step1', 'processing', '1단계: 잘못 지급된 보너스 삭제 중...');

                await new Promise(resolve => setTimeout(resolve, 500)); // UI 업데이트를 위한 짧은 딜레이

                const formData = new FormData();
                formData.append('action', 'fix_bonus');
                formData.append('bonus_type', bonusType);
                formData.append('giver_id', giverId);
                formData.append('wrong_receiver_id', firstWrongReceiver);
                formData.append('correct_receiver_id', correctReceiverId);
                formData.append('package_amount', amount / 0.25); // 역산하여 패키지 금액 계산

                const response = await fetch('api/fix-bonus.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // 1단계 완료
                    updateProgressModal('step1', 'completed',
                        `✓ 1단계 완료: ${result.details.deleted_count}건 삭제됨 (${result.details.wrong_receiver})`);

                    await new Promise(resolve => setTimeout(resolve, 500));

                    // 2단계: 올바른 보너스 재발행
                    updateProgressModal('step2', 'processing', '2단계: 올바른 수령자에게 보너스 재발행 중...');

                    await new Promise(resolve => setTimeout(resolve, 500));

                    // 2단계 완료
                    updateProgressModal('step2', 'completed',
                        `✓ 2단계 완료: $${result.details.total_amount.toFixed(2)} 재발행됨\n` +
                        `  • 캐시: $${result.details.cash_amount.toFixed(2)}\n` +
                        `  • 아바타: $${result.details.avatar_amount.toFixed(2)}`);

                    await new Promise(resolve => setTimeout(resolve, 800));

                    // 완료 메시지
                    updateProgressModal('final', 'completed', '🎉 보너스 수정이 완료되었습니다!');

                    await new Promise(resolve => setTimeout(resolve, 1500));

                    // 페이지 새로고침
                    location.reload();
                } else {
                    updateProgressModal('error', 'error', '❌ 오류: ' + result.message);
                    setTimeout(() => progressModal.remove(), 3000);
                }
            } catch (error) {
                console.error('보너스 수정 오류:', error);
                updateProgressModal('error', 'error', '❌ 오류가 발생했습니다.');
                setTimeout(() => progressModal.remove(), 3000);
            }
        }

        // 진행 상황 모달 생성
        function createProgressModal() {
            const modal = document.createElement('div');
            modal.id = 'fixBonusProgressModal';
            modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 10000;';
            modal.innerHTML = `
                <div style="background: #1e293b; border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 12px; padding: 40px; max-width: 500px; color: #f8fafc;">
                    <h3 style="color: #f8fafc; margin-bottom: 30px; font-size: 1.3em; text-align: center;">보너스 수정 진행 중</h3>

                    <div id="progressStep1" style="margin-bottom: 20px; padding: 15px; background: rgba(15, 23, 42, 0.5); border-radius: 8px; border-left: 4px solid #64748b;">
                        <div style="color: #94a3b8; font-size: 0.9em;">대기 중...</div>
                    </div>

                    <div id="progressStep2" style="margin-bottom: 20px; padding: 15px; background: rgba(15, 23, 42, 0.5); border-radius: 8px; border-left: 4px solid #64748b;">
                        <div style="color: #94a3b8; font-size: 0.9em;">대기 중...</div>
                    </div>

                    <div id="progressFinal" style="padding: 15px; background: rgba(15, 23, 42, 0.5); border-radius: 8px; border-left: 4px solid #64748b; display: none;">
                        <div style="color: #94a3b8; font-size: 0.9em;">처리 중...</div>
                    </div>
                </div>
            `;
            return modal;
        }

        // 진행 상황 업데이트
        function updateProgressModal(step, status, message) {
            const stepElement = document.getElementById(`progress${step.charAt(0).toUpperCase() + step.slice(1)}`);
            if (!stepElement) return;

            stepElement.style.display = 'block';

            let borderColor = '#64748b';
            let bgColor = 'rgba(15, 23, 42, 0.5)';
            let textColor = '#94a3b8';

            if (status === 'processing') {
                borderColor = '#3b82f6';
                bgColor = 'rgba(59, 130, 246, 0.1)';
                textColor = '#60a5fa';
            } else if (status === 'completed') {
                borderColor = '#10b981';
                bgColor = 'rgba(16, 185, 129, 0.1)';
                textColor = '#10b981';
            } else if (status === 'error') {
                borderColor = '#ef4444';
                bgColor = 'rgba(239, 68, 68, 0.1)';
                textColor = '#ef4444';
            }

            stepElement.style.borderLeftColor = borderColor;
            stepElement.style.background = bgColor;
            stepElement.innerHTML = `<div style="color: ${textColor}; font-size: 0.9em; white-space: pre-line;">${message}</div>`;
        }

        // URL에서 user_id 파라미터 가져오기
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('user_id');

        if (!userId) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('error').textContent = '회원 아이디가 지정되지 않았습니다.';
            document.getElementById('error').style.display = 'block';
        } else {
            loadMemberData(userId);
        }

        async function loadMemberData(userId) {
            try {
                const response = await fetch(`api/member-financial.php?action=detail&user_id=${encodeURIComponent(userId)}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || '회원 정보를 불러올 수 없습니다.');
                }

                // 네비게이션 버튼 링크 설정
                document.getElementById('nav_user_detail').href = `user-detail.php?user_id=${encodeURIComponent(userId)}`;
                document.getElementById('nav_bonus_received').href = `user-bonus-received.php?user_id=${encodeURIComponent(userId)}`;
                document.getElementById('nav_bonus_given').href = `user-bonus-given.php?user_id=${encodeURIComponent(userId)}`;

                renderMemberDashboard(data);

                document.getElementById('loading').style.display = 'none';
                document.getElementById('content').style.display = 'block';

            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error').textContent = '오류: ' + error.message;
                document.getElementById('error').style.display = 'block';
            }
        }

        // 회원 현황판 렌더링
        function renderMemberDashboard(data) {
            // 전역으로 데이터 저장
            window.currentMemberData = data;

            const m = data.member;
            const dashboard = document.getElementById('memberDashboard');

            // 받은 보너스 합계
            const totalReceived = data.received_bonuses.reduce((sum, b) => sum + parseFloat(b.amount), 0);
            const totalGiven = data.given_bonuses.reduce((sum, b) => sum + parseFloat(b.amount), 0);
            const totalSales = data.sales.reduce((sum, s) => sum + parseFloat(s.amount), 0);

            // 보너스 타입별 통계 (받은 보너스)
            const receivedByType = {};
            data.received_by_type.forEach(t => {
                if (!receivedByType[t.bonus_type]) receivedByType[t.bonus_type] = 0;
                receivedByType[t.bonus_type] += parseFloat(t.total);
            });

            // 보너스 타입별 통계 (지급한 보너스)
            const givenByType = {};
            data.given_by_type.forEach(t => {
                if (!givenByType[t.bonus_type]) givenByType[t.bonus_type] = 0;
                givenByType[t.bonus_type] += parseFloat(t.total);
            });

            // 타입별 수혜자 그룹화
            const beneficiariesByType = {};
            ['referral', 'edge', 'matching', 'rollup'].forEach(type => {
                beneficiariesByType[type] = {};
            });

            data.given_bonuses.forEach(b => {
                const type = b.bonus_type;
                const receiverId = b.receiver_code || '-';
                if (!beneficiariesByType[type][receiverId]) {
                    beneficiariesByType[type][receiverId] = {
                        name: b.receiver_name || '-',
                        total: 0
                    };
                }
                beneficiariesByType[type][receiverId].total += parseFloat(b.amount);
            });

            dashboard.innerHTML = `
                <div class="member-dashboard-header">
                    <h2 class="member-dashboard-title">👤 ${m.user_id} ${m.name ? '(' + m.name + ')' : ''}</h2>
                    <div style="display: flex; gap: 10px;">
                        <a href="user-bonus-verification.php?user_id=${m.user_id}" style="padding: 10px 20px; background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 0.9em; text-decoration: none; display: inline-block;">
                            🔍 보너스 검증
                        </a>
                        <a href="bonus-verification-list.php" style="padding: 10px 20px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 0.9em; text-decoration: none; display: inline-block;">
                            📋 검증 리스트
                        </a>
                    </div>
                </div>

                <div id="verificationArea" style="display: none; background: rgba(15, 23, 42, 0.4); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 12px; margin-bottom: 20px;"></div>

                <div class="member-details-section">
                    <h3 style="color: #f8fafc; font-size: 1.3em; font-weight: 700; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid rgba(148, 163, 184, 0.2);">
                        받은 보너스
                    </h3>
                    ${renderReceivedBonusesTab(data.received_bonuses, receivedByType)}
                </div>
            `;

            // 기본으로 REFERRAL 조직도 표시
            setTimeout(() => showOrgChart('referral'), 0);
        }

        // 보너스 수혜자 카드 렌더링
        function renderBonusBeneficiaryCards(beneficiariesByType, givenByType) {
            const types = [
                { key: 'referral', label: 'REFERRAL', color: '#60a5fa' },
                { key: 'edge', label: 'EDGE', color: '#10b981' },
                { key: 'matching', label: 'MATCHING', color: '#fbbf24' },
                { key: 'rollup', label: 'ROLLUP', color: '#a78bfa' }
            ];

            let html = '';
            types.forEach(type => {
                const amount = givenByType[type.key] || 0;
                const beneficiaries = beneficiariesByType[type.key];
                const beneficiaryList = Object.entries(beneficiaries).map(([id, info]) => {
                    return `<div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(148, 163, 184, 0.05);">
                        <span style="color: #e2e8f0; font-size: 1em;">${id}</span>
                        <span style="color: #10b981; font-size: 1.1em; font-weight: 700;">$${info.total.toFixed(2)}</span>
                    </div>`;
                }).join('');

                html += `
                    <div style="background: rgba(15, 23, 42, 0.4); padding: 20px; border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.1);">
                        <div style="color: ${type.color}; font-size: 0.75em; font-weight: 600; margin-bottom: 8px; text-align: center;">${type.label}</div>
                        <div style="color: ${type.color}; font-size: 1.8em; font-weight: 700; text-align: center; margin-bottom: 12px;">$${amount.toFixed(2)}</div>
                        <div style="border-top: 1px solid rgba(148, 163, 184, 0.1); padding-top: 10px; max-height: 150px; overflow-y: auto;">
                            ${beneficiaryList || '<div style="color: #64748b; font-size: 0.85em; text-align: center; padding: 10px 0;">수혜자 없음</div>'}
                        </div>
                    </div>
                `;
            });

            return html;
        }

        // 받은 보너스 탭 렌더링
        function renderReceivedBonusesTab(bonuses, byType) {
            const typeColors = {
                'referral': '#60a5fa',
                'edge': '#10b981',
                'matching': '#fbbf24',
                'rollup': '#a78bfa'
            };

            let html = '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">';
            ['referral', 'edge', 'matching', 'rollup'].forEach(type => {
                const amount = byType[type] || 0;
                const color = typeColors[type];
                html += `
                    <div style="background: rgba(15, 23, 42, 0.4); padding: 15px; border-radius: 8px; text-align: center;">
                        <button class="bonus-type-btn" data-type="${type}" onclick="showOrgChart('${type}')" style="color: ${color};">
                            ${type.toUpperCase()}
                        </button>
                        <div style="color: #10b981; font-size: 1.5em; font-weight: 700;">$${amount.toFixed(2)}</div>
                    </div>
                `;
            });
            html += '</div>';

            // 조직도 영역
            html += `
                <div id="orgChartArea" style="min-height: 800px; background: rgba(15, 23, 42, 0.3); border: 1px solid rgba(148, 163, 184, 0.1); border-radius: 12px; margin-bottom: 20px; padding: 20px; display: flex; align-items: center; justify-content: center;">
                    <div style="color: #64748b; font-size: 0.95em;">보너스 타입을 선택하면 조직도가 표시됩니다</div>
                </div>
            `;

            return html;
        }

        // 발생 보너스 탭 렌더링
        function renderGivenBonusesTab(bonuses, byType) {
            let html = '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">';
            ['referral', 'edge', 'matching', 'rollup'].forEach(type => {
                const amount = byType[type] || 0;
                html += `
                    <div style="background: rgba(15, 23, 42, 0.4); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="color: #94a3b8; font-size: 0.8em; margin-bottom: 8px;">${type.toUpperCase()}</div>
                        <div style="color: #f59e0b; font-size: 1.5em; font-weight: 700;">$${amount.toFixed(2)}</div>
                    </div>
                `;
            });
            html += '</div>';

            if (bonuses.length === 0) {
                html += '<div style="text-align: center; padding: 40px; color: #94a3b8;">발생한 보너스가 없습니다.</div>';
            } else {
                bonuses.forEach(b => {
                    html += `
                        <div class="bonus-list-item">
                            <div class="bonus-item-header">
                                <div>
                                    <span class="bonus-type-badge ${b.bonus_type}">${b.bonus_type.toUpperCase()}${b.level ? ' L' + b.level : ''}</span>
                                    <span style="color: #94a3b8; margin-left: 10px;">→ ${b.receiver_code || '-'} ${b.receiver_name ? '(' + b.receiver_name + ')' : ''}</span>
                                </div>
                                <span style="color: #f59e0b; font-weight: 700;">$${parseFloat(b.amount).toFixed(2)}</span>
                            </div>
                            <div style="color: #94a3b8; font-size: 0.85em;">${b.description}</div>
                            <div style="color: #64748b; font-size: 0.8em; margin-top: 5px;">
                                ${b.payment_type === 'cash' ? '💵 캐시' : '🎮 아바타'} • ${b.created_at}
                            </div>
                        </div>
                    `;
                });
            }
            return html;
        }

        // 매출 탭 렌더링
        function renderSalesTab(sales) {
            if (sales.length === 0) {
                return '<div style="text-align: center; padding: 40px; color: #94a3b8;">매출 내역이 없습니다.</div>';
            }

            let html = '';
            sales.forEach(s => {
                html += `
                    <div class="bonus-list-item">
                        <div class="bonus-item-header">
                            <div>
                                <span style="color: #f8fafc; font-weight: 600;">Sale #${s.id}</span>
                                <span style="color: #94a3b8; margin-left: 10px;">Package ${s.package_id}</span>
                            </div>
                            <span class="info-value highlight">$${parseFloat(s.amount).toFixed(2)}</span>
                        </div>
                        <div style="color: #94a3b8; font-size: 0.85em;">
                            결제: ${s.payment_method || '-'} | 상태: ${s.status}
                            ${s.txid ? ' | TXID: ' + s.txid : ''}
                        </div>
                        <div style="color: #64748b; font-size: 0.8em; margin-top: 5px;">${s.created_at}</div>
                    </div>
                `;
            });
            return html;
        }

        // 네트워크 탭 렌더링
        function renderNetworkTab(referrals, sponsored) {
            let html = '<h4 style="color: #f8fafc; margin-bottom: 15px;">추천한 회원 (' + referrals.length + '명)</h4>';

            if (referrals.length === 0) {
                html += '<div style="text-align: center; padding: 20px; color: #94a3b8;">추천한 회원이 없습니다.</div>';
            } else {
                referrals.forEach(r => {
                    html += `
                        <div class="bonus-list-item" style="cursor: pointer;" onclick="location.href='user-bonus-detail.php?user_id=${r.user_id}'">
                            <div class="bonus-item-header">
                                <span style="color: #60a5fa; font-weight: 600;">${r.user_id}</span>
                                <span style="color: #e2e8f0;">${r.name || '-'}</span>
                                <span style="color: #94a3b8;">Package ${r.package_id || 0}</span>
                            </div>
                            <div style="color: #64748b; font-size: 0.8em;">${r.created_at}</div>
                        </div>
                    `;
                });
            }

            html += '<h4 style="color: #f8fafc; margin: 30px 0 15px;">스폰서한 회원 (' + sponsored.length + '명)</h4>';

            if (sponsored.length === 0) {
                html += '<div style="text-align: center; padding: 20px; color: #94a3b8;">스폰서한 회원이 없습니다.</div>';
            } else {
                sponsored.forEach(s => {
                    html += `
                        <div class="bonus-list-item" style="cursor: pointer;" onclick="location.href='user-bonus-detail.php?user_id=${s.user_id}'">
                            <div class="bonus-item-header">
                                <div>
                                    <span style="color: #60a5fa; font-weight: 600;">${s.user_id}</span>
                                    <span style="color: #e2e8f0; margin: 0 10px;">${s.name || '-'}</span>
                                    <span style="color: #94a3b8;">Package ${s.package_id || 0}</span>
                                </div>
                                <span style="color: #94a3b8;">위치: ${s.sponsor_position === 1 ? '좌측' : s.sponsor_position === 2 ? '우측' : '-'}</span>
                            </div>
                            <div style="color: #64748b; font-size: 0.8em;">${s.created_at}</div>
                        </div>
                    `;
                });
            }

            return html;
        }

        // 탭 전환
        function switchMemberTab(tabName) {
            document.querySelectorAll('.detail-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.detail-tab-content').forEach(c => c.classList.remove('active'));

            event.target.classList.add('active');
            document.getElementById(tabName + 'Content').classList.add('active');
        }
    </script>
</body>
</html>
