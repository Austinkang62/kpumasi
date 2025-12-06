<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>보너스 랭킹 - K-Pumasi Admin</title>
    <!-- Updated: 2025-11-20 v3 with avatar column -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }

        .content {
            max-width: 1600px;
            margin: 0 auto;
            padding: 80px 30px 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            color: #f8fafc;
            font-size: 1.8em;
            font-weight: 700;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-select {
            padding: 10px 15px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 0.9em;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .stat-label {
            color: #94a3b8;
            font-size: 0.85em;
            margin-bottom: 8px;
        }

        .stat-value {
            color: #f8fafc;
            font-size: 1.8em;
            font-weight: 700;
        }

        .stat-value.highlight {
            color: #10b981;
        }

        .rank-table {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: rgba(15, 23, 42, 0.8);
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        th.text-right {
            text-align: right;
        }

        td {
            padding: 16px;
            border-top: 1px solid rgba(148, 163, 184, 0.1);
        }

        td.text-right {
            text-align: right;
        }

        tbody tr {
            transition: all 0.2s;
            cursor: pointer;
        }

        tbody tr:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        tbody tr.expanded {
            background: rgba(59, 130, 246, 0.15);
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 0.9em;
        }

        .rank-badge.gold {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #78350f;
        }

        .rank-badge.silver {
            background: linear-gradient(135deg, #cbd5e1, #94a3b8);
            color: #1e293b;
        }

        .rank-badge.bronze {
            background: linear-gradient(135deg, #fb923c, #ea580c);
            color: #7c2d12;
        }

        .rank-badge.normal {
            background: rgba(148, 163, 184, 0.2);
            color: #cbd5e1;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-id {
            color: #f8fafc;
            font-weight: 600;
        }

        .user-email {
            color: #94a3b8;
            font-size: 0.85em;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75em;
            font-weight: 600;
        }

        .badge-avatar {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }

        .badge-user {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        .amount {
            font-weight: 700;
            font-size: 1.1em;
        }

        .amount.total {
            color: #10b981;
        }

        .amount.cash {
            color: #3b82f6;
        }

        .amount.avatar {
            color: #fbbf24;
        }

        .bonus-breakdown {
            display: flex;
            gap: 15px;
            font-size: 0.85em;
        }

        .bonus-type {
            color: #94a3b8;
        }

        .bonus-type strong {
            color: #e2e8f0;
        }

        .detail-row {
            display: none;
            background: rgba(15, 23, 42, 0.6);
        }

        .detail-row.show {
            display: table-row;
        }

        .detail-content {
            padding: 20px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .detail-card {
            background: rgba(30, 41, 59, 0.8);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid;
        }

        .detail-card.referral { border-left-color: #3b82f6; }
        .detail-card.edge { border-left-color: #8b5cf6; }
        .detail-card.matching { border-left-color: #ec4899; }
        .detail-card.rollup { border-left-color: #10b981; }

        .detail-card h4 {
            color: #f8fafc;
            margin-bottom: 10px;
            font-size: 0.9em;
            text-transform: uppercase;
        }

        .detail-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .detail-label {
            color: #94a3b8;
            font-size: 0.85em;
        }

        .detail-value {
            color: #f8fafc;
            font-weight: 600;
        }

        .recent-bonuses {
            margin-top: 15px;
        }

        .recent-bonuses h5 {
            color: #94a3b8;
            font-size: 0.8em;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .bonus-item {
            padding: 6px 0;
            font-size: 0.85em;
            color: #cbd5e1;
            display: flex;
            justify-content: space-between;
        }

        .bonus-amount {
            color: #10b981;
            font-weight: 600;
        }

        .expand-icon {
            display: inline-block;
            transition: transform 0.3s;
            margin-right: 8px;
            color: #60a5fa;
        }

        .expanded .expand-icon {
            transform: rotate(90deg);
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination button {
            padding: 10px 20px;
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pagination button:hover:not(:disabled) {
            background: rgba(59, 130, 246, 0.3);
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="content">
        <div class="page-header">
            <h1 class="page-title">🏆 보너스 랭킹</h1>
            <div class="filter-controls">
                <select class="filter-select" id="bonusTypeFilter" onchange="loadRankings()">
                    <option value="total">전체 보너스</option>
                    <option value="cash">캐시 보너스만</option>
                    <option value="avatar">아바타 포인트만</option>
                </select>
                <select class="filter-select" id="periodFilter" onchange="loadRankings()">
                    <option value="all">전체 기간</option>
                    <option value="today">오늘</option>
                    <option value="week">이번 주</option>
                    <option value="month">이번 달</option>
                </select>
            </div>
        </div>

        <div class="stats-grid" id="stats">
            <div class="stat-card">
                <div class="stat-label">총 회원 수</div>
                <div class="stat-value" id="totalUsers">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">총 보너스 지급액</div>
                <div class="stat-value highlight" id="totalBonus">$0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">평균 보너스</div>
                <div class="stat-value" id="avgBonus">$0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">최고 보너스</div>
                <div class="stat-value highlight" id="topBonus">$0</div>
            </div>
        </div>

        <div class="rank-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;"></th>
                        <th style="width: 60px;">순위</th>
                        <th>회원 정보</th>
                        <th class="text-right">총 보너스</th>
                        <th class="text-right">캐시</th>
                        <th class="text-right">아바타 포인트</th>
                        <th class="text-right">추천</th>
                        <th class="text-right">엣지</th>
                        <th class="text-right">매칭</th>
                        <th class="text-right">롤업</th>
                        <th class="text-right">🤖 아바타</th>
                        <th class="text-right">보너스 건수</th>
                    </tr>
                </thead>
                <tbody id="rankList">
                    <tr>
                        <td colspan="12" class="loading">로딩 중...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination" id="pagination"></div>
    </div>

    
    <script>
        const API_BASE = 'api';
        let currentPage = 1;
        let totalPages = 1;

        // 페이지 로드시 데이터 불러오기
        window.onload = () => {
            loadRankings();
        };

        // 랭킹 목록 불러오기
        async function loadRankings(page = 1) {
            currentPage = page;
            const bonusType = document.getElementById('bonusTypeFilter').value;
            const period = document.getElementById('periodFilter').value;

            try {
                const response = await fetch(`${API_BASE}/bonus-rank.php?action=list&page=${page}&limit=50&type=${bonusType}&period=${period}`);
                const data = await response.json();

                if (data.success) {
                    renderRankings(data.rankings);
                    updateStats(data.stats);
                    renderPagination(data.total, data.page, data.limit);
                } else {
                    document.getElementById('rankList').innerHTML = `
                        <tr><td colspan="12" style="text-align: center; color: #ef4444;">${data.message}</td></tr>
                    `;
                }
            } catch (error) {
                console.error('Load error:', error);
                document.getElementById('rankList').innerHTML = `
                    <tr><td colspan="12" style="text-align: center; color: #ef4444;">데이터 로드 오류</td></tr>
                `;
            }
        }

        // 랭킹 목록 렌더링
        function renderRankings(rankings) {
            const tbody = document.getElementById('rankList');

            if (rankings.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="12">
                            <div class="empty-state">
                                <div class="empty-state-icon">📊</div>
                                <p>보너스 내역이 없습니다.</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            rankings.forEach((user, index) => {
                const rank = (currentPage - 1) * 50 + index + 1;
                const rankBadgeClass = rank === 1 ? 'gold' : rank === 2 ? 'silver' : rank === 3 ? 'bronze' : 'normal';
                const isAvatar = user.is_avatar === 1;
                const avatarCount = parseInt(user.avatar_count || 0);

                html += `
                    <tr id="row-${user.user_id}" onclick="toggleDetail('${user.user_id}')">
                        <td><span class="expand-icon">▶</span></td>
                        <td>
                            <div class="rank-badge ${rankBadgeClass}">${rank}</div>
                        </td>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">${isAvatar ? '🤖' : '👤'}</div>
                                <div class="user-details">
                                    <div class="user-id">
                                        ${user.user_id}
                                        ${isAvatar ? '<span class="badge badge-avatar">아바타</span>' : '<span class="badge badge-user">회원</span>'}
                                    </div>
                                    <div class="user-email">${user.email || '-'}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-right">
                            <div class="amount total">$${parseFloat(user.total_bonus).toFixed(2)}</div>
                        </td>
                        <td class="text-right">
                            <div class="amount cash">$${parseFloat(user.cash_bonus).toFixed(2)}</div>
                        </td>
                        <td class="text-right">
                            <div class="amount avatar">$${parseFloat(user.avatar_bonus).toFixed(2)}</div>
                        </td>
                        <td class="text-right">${user.referral_bonus > 0 ? '$' + parseFloat(user.referral_bonus).toFixed(2) : '-'}</td>
                        <td class="text-right">${user.edge_bonus > 0 ? '$' + parseFloat(user.edge_bonus).toFixed(2) : '-'}</td>
                        <td class="text-right">${user.matching_bonus > 0 ? '$' + parseFloat(user.matching_bonus).toFixed(2) : '-'}</td>
                        <td class="text-right">${user.rollup_bonus > 0 ? '$' + parseFloat(user.rollup_bonus).toFixed(2) : '-'}</td>
                        <td class="text-right">
                            ${avatarCount > 0 ? `<span style="color: #fbbf24; font-weight: 600;">🤖 ${avatarCount}개</span>` : '<span style="color: #64748b;">-</span>'}
                        </td>
                        <td class="text-right">
                            <span style="color: #94a3b8;">${user.bonus_count}건</span>
                        </td>
                    </tr>
                    <tr id="detail-${user.user_id}" class="detail-row">
                        <td colspan="12">
                            <div id="detail-content-${user.user_id}" class="loading">로딩 중...</div>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // 상세 내역 토글
        async function toggleDetail(userId) {
            const row = document.getElementById(`row-${userId}`);
            const detail = document.getElementById(`detail-${userId}`);
            const content = document.getElementById(`detail-content-${userId}`);

            if (detail.classList.contains('show')) {
                detail.classList.remove('show');
                row.classList.remove('expanded');
                return;
            }

            // 모든 다른 상세 내역 닫기
            document.querySelectorAll('.detail-row').forEach(d => d.classList.remove('show'));
            document.querySelectorAll('tbody tr').forEach(r => r.classList.remove('expanded'));

            detail.classList.add('show');
            row.classList.add('expanded');

            // 상세 내역 로드
            try {
                const bonusType = document.getElementById('bonusTypeFilter').value;
                const period = document.getElementById('periodFilter').value;
                const response = await fetch(`${API_BASE}/bonus-rank.php?action=detail&user_id=${encodeURIComponent(userId)}&type=${bonusType}&period=${period}`);
                const data = await response.json();

                console.log('Detail API Response:', data);
                console.log('Avatar History:', data.avatar_history);

                if (data.success) {
                    renderDetail(content, data);
                } else {
                    content.innerHTML = `<p style="color: #ef4444; padding: 20px;">${data.message}</p>`;
                }
            } catch (error) {
                console.error('Detail load error:', error);
                content.innerHTML = `<p style="color: #ef4444; padding: 20px;">상세 내역 로드 오류</p>`;
            }
        }

        // 상세 내역 렌더링
        function renderDetail(container, data) {
            const { user, bonus_details, recent_bonuses, avatar_history } = data;

            console.log('Rendering detail for:', user.user_id);
            console.log('Avatar history in render:', avatar_history);

            let html = '<div class="detail-content">';
            html += '<div class="detail-grid">';

            // Referral Bonus
            if (bonus_details.referral) {
                html += renderBonusCard('referral', '추천 보너스', bonus_details.referral, recent_bonuses.referral);
            }

            // Edge Bonus
            if (bonus_details.edge) {
                html += renderBonusCard('edge', '엣지 보너스', bonus_details.edge, recent_bonuses.edge);
            }

            // Matching Bonus
            if (bonus_details.matching) {
                html += renderBonusCard('matching', '매칭 보너스', bonus_details.matching, recent_bonuses.matching);
            }

            // Rollup Bonus
            if (bonus_details.rollup) {
                html += renderBonusCard('rollup', '롤업 보너스', bonus_details.rollup, recent_bonuses.rollup);
            }

            html += '</div>';

            // 아바타 발생 이력
            console.log('Checking avatar_history:', avatar_history, 'length:', avatar_history ? avatar_history.length : 'null');
            if (avatar_history && avatar_history.length > 0) {
                console.log('Rendering avatar history...');
                html += renderAvatarHistory(avatar_history);
            } else {
                console.log('No avatar history to render');
                // 디버그용: 아바타 이력이 없음을 표시
                html += '<div style="margin-top: 20px; padding: 15px; background: rgba(148, 163, 184, 0.1); border-radius: 8px; color: #94a3b8; text-align: center;">';
                html += '🤖 이 회원의 아바타 발생 이력이 없습니다.';
                html += '</div>';
            }

            html += '</div>';

            container.innerHTML = html;
        }

        // 보너스 카드 렌더링
        function renderBonusCard(type, title, details, recentBonuses) {
            let html = `<div class="detail-card ${type}">`;
            html += `<h4>${title}</h4>`;

            html += `
                <div class="detail-stats">
                    <span class="detail-label">총액</span>
                    <span class="detail-value">$${parseFloat(details.total).toFixed(2)}</span>
                </div>
                <div class="detail-stats">
                    <span class="detail-label">캐시</span>
                    <span class="detail-value">$${parseFloat(details.cash).toFixed(2)}</span>
                </div>
                <div class="detail-stats">
                    <span class="detail-label">아바타 포인트</span>
                    <span class="detail-value">$${parseFloat(details.avatar).toFixed(2)}</span>
                </div>
                <div class="detail-stats">
                    <span class="detail-label">건수</span>
                    <span class="detail-value">${details.count}건</span>
                </div>
            `;

            // 최근 보너스 내역
            if (recentBonuses && recentBonuses.length > 0) {
                html += '<div class="recent-bonuses">';
                html += '<h5>최근 5건</h5>';
                recentBonuses.slice(0, 5).forEach(bonus => {
                    html += `
                        <div class="bonus-item">
                            <span>${formatDate(bonus.created_at)} · ${bonus.from_user_id || 'System'}</span>
                            <span class="bonus-amount">$${parseFloat(bonus.amount).toFixed(2)}</span>
                        </div>
                    `;
                });
                html += '</div>';
            }

            html += '</div>';
            return html;
        }

        // 통계 업데이트
        function updateStats(stats) {
            document.getElementById('totalUsers').textContent = stats.total_users || 0;
            document.getElementById('totalBonus').textContent = '$' + (stats.total_bonus ? parseFloat(stats.total_bonus).toFixed(2) : '0.00');
            document.getElementById('avgBonus').textContent = '$' + (stats.avg_bonus ? parseFloat(stats.avg_bonus).toFixed(2) : '0.00');
            document.getElementById('topBonus').textContent = '$' + (stats.top_bonus ? parseFloat(stats.top_bonus).toFixed(2) : '0.00');
        }

        // 페이지네이션 렌더링
        function renderPagination(total, page, limit) {
            totalPages = Math.ceil(total / limit);
            const pagination = document.getElementById('pagination');

            let html = `
                <button onclick="loadRankings(${page - 1})" ${page <= 1 ? 'disabled' : ''}>◀ 이전</button>
                <span style="color: #94a3b8; padding: 0 20px;">Page ${page} / ${totalPages}</span>
                <button onclick="loadRankings(${page + 1})" ${page >= totalPages ? 'disabled' : ''}>다음 ▶</button>
            `;

            pagination.innerHTML = html;
        }

        // 아바타 발생 이력 렌더링
        function renderAvatarHistory(avatars) {
            let html = '<div style="margin-top: 20px; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 8px; padding: 20px;">';
            html += '<h3 style="color: #fbbf24; margin-bottom: 15px; font-size: 1.1em; display: flex; align-items: center; gap: 8px;">';
            html += '🤖 아바타 발생 이력 <span style="background: rgba(251, 191, 36, 0.2); padding: 4px 10px; border-radius: 6px; font-size: 0.9em;">' + avatars.length + '개</span>';
            html += '</h3>';

            html += '<div style="display: grid; gap: 10px;">';

            avatars.forEach((avatar, index) => {
                const avatarDate = new Date(avatar.created_at);
                const formattedDate = avatarDate.toLocaleString('ko-KR', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                html += `
                    <div style="background: rgba(30, 41, 59, 0.6); padding: 15px; border-radius: 8px; border-left: 3px solid #fbbf24; display: flex; justify-content: space-between; align-items: center; gap: 15px;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                                <span style="font-size: 1.3em;">🤖</span>
                                <span style="color: #fbbf24; font-weight: 600; font-size: 1.05em;">${avatar.avatar_user_id}</span>
                                <span style="background: rgba(251, 191, 36, 0.2); color: #fbbf24; padding: 2px 8px; border-radius: 4px; font-size: 0.75em;">아바타 #${index + 1}</span>
                            </div>
                            <div style="color: #94a3b8; font-size: 0.85em;">
                                생성일: ${formattedDate}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: #94a3b8; font-size: 0.75em; margin-bottom: 4px;">트리거 금액</div>
                            <div style="color: #10b981; font-weight: 700; font-size: 1.2em;">$${parseFloat(avatar.trigger_amount).toFixed(2)}</div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <a href="/html/organization.html?user_id=${encodeURIComponent(avatar.parent_user_id)}"
                               target="_blank"
                               style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 6px; color: #60a5fa; text-decoration: none; font-size: 0.85em; font-weight: 600; transition: all 0.2s; white-space: nowrap;"
                               onmouseover="this.style.background='rgba(59, 130, 246, 0.3)'; this.style.borderColor='rgba(59, 130, 246, 0.6)'; this.style.transform='scale(1.05)';"
                               onmouseout="this.style.background='rgba(59, 130, 246, 0.2)'; this.style.borderColor='rgba(59, 130, 246, 0.4)'; this.style.transform='scale(1)';">
                                <span>👤</span>
                                <span>부모 조직도</span>
                            </a>
                            <a href="/html/organization.html?user_id=${encodeURIComponent(avatar.avatar_user_id)}"
                               target="_blank"
                               style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.4); border-radius: 6px; color: #a78bfa; text-decoration: none; font-size: 0.85em; font-weight: 600; transition: all 0.2s; white-space: nowrap;"
                               onmouseover="this.style.background='rgba(139, 92, 246, 0.3)'; this.style.borderColor='rgba(139, 92, 246, 0.6)'; this.style.transform='scale(1.05)';"
                               onmouseout="this.style.background='rgba(139, 92, 246, 0.2)'; this.style.borderColor='rgba(139, 92, 246, 0.4)'; this.style.transform='scale(1)';">
                                <span>🤖</span>
                                <span>아바타 조직도</span>
                            </a>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            html += '</div>';

            return html;
        }

        // 날짜 포맷팅
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ko-KR', {
                month: 'short',
                day: 'numeric'
            });
        }
    </script>
</body>
</html>
