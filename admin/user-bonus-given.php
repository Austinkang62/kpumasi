<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>발생한 보너스 - K-Pumasi Admin</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <div style="display: flex; gap: 10px;">
                <a href="users-manage.html" class="back-button" title="회원 목록">👥</a>
                <a href="index.php" class="back-button" title="대시보드">🏠</a>
            </div>
            <div class="search-container">
                <input type="text" id="userSearchInput" placeholder="회원 ID 검색..." onkeypress="if(event.key==='Enter') searchUser()" />
                <button onclick="searchUser()">🔍</button>
            </div>
            <div class="nav-buttons">
                <a href="#" id="nav_user_detail" class="nav-button">회원</a>
                <a href="#" id="nav_bonus_received" class="nav-button">받은 보너스</a>
                <a href="#" id="nav_bonus_given" class="nav-button active">발생한 보너스</a>
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
            window.location.href = `user-bonus-given.php?user_id=${encodeURIComponent(userId)}`;
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
                </div>

                <!-- 발생한 보너스 섹션 -->
                <div style="margin: 30px 0;">
                    <h3 style="color: #f8fafc; font-size: 1.3em; font-weight: 700; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid rgba(148, 163, 184, 0.2);">
                        발생한 보너스
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                        ${renderBonusBeneficiaryCards(beneficiariesByType, givenByType)}
                    </div>
                </div>
            `;
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
                        <div style="border-top: 1px solid rgba(148, 163, 184, 0.1); padding-top: 10px;">
                            ${beneficiaryList || '<div style="color: #64748b; font-size: 0.85em; text-align: center; padding: 10px 0;">수혜자 없음</div>'}
                        </div>
                    </div>
                `;
            });

            return html;
        }

        // 받은 보너스 탭 렌더링
        function renderReceivedBonusesTab(bonuses, byType) {
            let html = '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">';
            ['referral', 'edge', 'matching', 'rollup'].forEach(type => {
                const amount = byType[type] || 0;
                html += `
                    <div style="background: rgba(15, 23, 42, 0.4); padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="color: #94a3b8; font-size: 0.8em; margin-bottom: 8px;">${type.toUpperCase()}</div>
                        <div style="color: #10b981; font-size: 1.5em; font-weight: 700;">$${amount.toFixed(2)}</div>
                    </div>
                `;
            });
            html += '</div>';

            if (bonuses.length === 0) {
                html += '<div style="text-align: center; padding: 40px; color: #94a3b8;">받은 보너스가 없습니다.</div>';
            } else {
                bonuses.forEach(b => {
                    html += `
                        <div class="bonus-list-item">
                            <div class="bonus-item-header">
                                <div>
                                    <span class="bonus-type-badge ${b.bonus_type}">${b.bonus_type.toUpperCase()}${b.level ? ' L' + b.level : ''}</span>
                                    <span style="color: #94a3b8; margin-left: 10px;">← ${b.giver_code || '-'} ${b.giver_name ? '(' + b.giver_name + ')' : ''}</span>
                                </div>
                                <span class="info-value highlight">$${parseFloat(b.amount).toFixed(2)}</span>
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
