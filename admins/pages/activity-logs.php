<?php
/**
 * Super Admin - Activity Logs Page
 * 관리자 활동 로그 조회
 */
require_once __DIR__ . '/../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>활동 로그 - Super Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar input,
        .filter-bar select {
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95em;
        }

        .filter-bar input[type="text"] {
            flex: 1;
            min-width: 200px;
        }

        .filter-bar select {
            min-width: 150px;
        }

        .log-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid;
            transition: all 0.3s;
        }

        .log-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }

        .log-item.super_login_success { border-color: #10b981; }
        .log-item.super_logout { border-color: #6b7280; }
        .log-item.admin_created { border-color: #3b82f6; }
        .log-item.admin_updated { border-color: #f59e0b; }
        .log-item.admin_deleted { border-color: #ef4444; }
        .log-item.admin_status_toggled { border-color: #8b5cf6; }
        .log-item.password_changed { border-color: #ec4899; }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .log-action {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .action-super_login_success { background: #d1fae5; color: #065f46; }
        .action-super_logout { background: #f3f4f6; color: #374151; }
        .action-admin_created { background: #dbeafe; color: #1e40af; }
        .action-admin_updated { background: #fef3c7; color: #92400e; }
        .action-admin_deleted { background: #fee2e2; color: #991b1b; }
        .action-admin_status_toggled { background: #ede9fe; color: #5b21b6; }
        .action-password_changed { background: #fce7f3; color: #9f1239; }

        .log-time {
            color: #6b7280;
            font-size: 0.9em;
        }

        .log-admin {
            font-weight: 600;
            color: #667eea;
        }

        .log-details {
            background: #f9fafb;
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 0.9em;
            color: #374151;
        }

        .log-ip {
            color: #6b7280;
            font-size: 0.85em;
            margin-top: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            color: #6b7280;
            margin-top: 8px;
            font-size: 0.9em;
        }

        .no-logs {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination button {
            padding: 10px 20px;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .pagination button:hover:not(:disabled) {
            border-color: #667eea;
            color: #667eea;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="content-header">
            <h1>📝 활동 로그</h1>
        </header>

        <!-- 통계 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="statTotalLogs">-</div>
                <div class="stat-label">전체 로그</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="statTodayLogs">-</div>
                <div class="stat-label">오늘 활동</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="statUniqueAdmins">-</div>
                <div class="stat-label">활동 관리자</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="statLogins">-</div>
                <div class="stat-label">오늘 로그인</div>
            </div>
        </div>

        <!-- 필터 -->
        <div class="filter-bar">
            <input type="text" id="searchAdmin" placeholder="관리자 Username 검색...">
            <select id="filterAction">
                <option value="">모든 활동</option>
                <option value="super_login_success">Super 로그인</option>
                <option value="super_logout">Super 로그아웃</option>
                <option value="login_success">일반 로그인</option>
                <option value="logout">일반 로그아웃</option>
                <option value="admin_created">관리자 생성</option>
                <option value="admin_updated">관리자 수정</option>
                <option value="admin_deleted">관리자 삭제</option>
                <option value="admin_status_toggled">상태 변경</option>
                <option value="password_changed">비밀번호 변경</option>
            </select>
            <input type="date" id="filterDateFrom">
            <input type="date" id="filterDateTo">
            <button class="btn-primary" onclick="loadLogs()">검색</button>
            <button class="btn-secondary" onclick="resetFilters()">초기화</button>
        </div>

        <!-- 로그 목록 -->
        <div id="logsContainer">
            <div class="loading" style="text-align: center; padding: 60px; color: #6b7280;">
                로딩 중...
            </div>
        </div>

        <!-- 페이지네이션 -->
        <div class="pagination" id="pagination"></div>
    </main>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        const logsPerPage = 20;

        // 통계 로드
        async function loadStats() {
            try {
                const response = await fetch('../api/activity-logs.php?action=stats');
                const result = await response.json();

                if (result.success) {
                    document.getElementById('statTotalLogs').textContent = result.data.total || 0;
                    document.getElementById('statTodayLogs').textContent = result.data.today || 0;
                    document.getElementById('statUniqueAdmins').textContent = result.data.unique_admins || 0;
                    document.getElementById('statLogins').textContent = result.data.today_logins || 0;
                }
            } catch (error) {
                console.error('Load stats error:', error);
            }
        }

        // 로그 로드
        async function loadLogs(page = 1) {
            currentPage = page;
            const searchAdmin = document.getElementById('searchAdmin').value;
            const filterAction = document.getElementById('filterAction').value;
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo = document.getElementById('filterDateTo').value;

            const params = new URLSearchParams({
                action: 'list',
                limit: logsPerPage,
                offset: (page - 1) * logsPerPage
            });

            if (searchAdmin) params.append('search_admin', searchAdmin);
            if (filterAction) params.append('filter_action', filterAction);
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);

            try {
                const response = await fetch(`../api/activity-logs.php?${params}`);
                const result = await response.json();

                const container = document.getElementById('logsContainer');

                if (result.success && result.data.length > 0) {
                    container.innerHTML = result.data.map(log => {
                        const details = log.details ? JSON.parse(log.details) : {};
                        const detailsStr = Object.keys(details).length > 0
                            ? JSON.stringify(details, null, 2)
                            : '';

                        return `
                            <div class="log-item ${log.action}">
                                <div class="log-header">
                                    <div>
                                        <span class="log-action action-${log.action}">${getActionText(log.action)}</span>
                                        <span class="log-admin">${log.username || 'Unknown'}</span>
                                    </div>
                                    <div class="log-time">${new Date(log.created_at).toLocaleString('ko-KR')}</div>
                                </div>
                                ${detailsStr ? `<div class="log-details">${detailsStr}</div>` : ''}
                                <div class="log-ip">
                                    IP: ${log.ip_address} | ${log.user_agent ? log.user_agent.substring(0, 60) + '...' : 'Unknown'}
                                </div>
                            </div>
                        `;
                    }).join('');

                    // 페이지네이션
                    totalPages = Math.ceil(result.total / logsPerPage);
                    renderPagination();
                } else {
                    container.innerHTML = `
                        <div class="no-logs">
                            📝 로그가 없습니다.
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Load logs error:', error);
                document.getElementById('logsContainer').innerHTML = `
                    <div class="no-logs">
                        ❌ 로그를 불러오는 중 오류가 발생했습니다.
                    </div>
                `;
            }
        }

        // 페이지네이션 렌더링
        function renderPagination() {
            const pagination = document.getElementById('pagination');
            let html = '';

            // 이전 버튼
            html += `<button onclick="loadLogs(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>‹ 이전</button>`;

            // 페이지 번호
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += `<button onclick="loadLogs(${i})" class="${i === currentPage ? 'active' : ''}">${i}</button>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<button disabled>...</button>`;
                }
            }

            // 다음 버튼
            html += `<button onclick="loadLogs(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>다음 ›</button>`;

            pagination.innerHTML = html;
        }

        // 액션 텍스트 변환
        function getActionText(action) {
            const actionTexts = {
                'super_login_success': '🔐 Super 로그인',
                'super_logout': '🚪 Super 로그아웃',
                'login_success': '🔓 로그인',
                'login_failed': '❌ 로그인 실패',
                'logout': '🚪 로그아웃',
                'admin_created': '➕ 관리자 생성',
                'admin_updated': '✏️ 관리자 수정',
                'admin_deleted': '🗑️ 관리자 삭제',
                'admin_status_toggled': '🔄 상태 변경',
                'password_changed': '🔑 비밀번호 변경',
                'settings_updated': '⚙️ 설정 변경'
            };
            return actionTexts[action] || action;
        }

        // 필터 초기화
        function resetFilters() {
            document.getElementById('searchAdmin').value = '';
            document.getElementById('filterAction').value = '';
            document.getElementById('filterDateFrom').value = '';
            document.getElementById('filterDateTo').value = '';
            loadLogs(1);
        }

        // 페이지 로드
        loadStats();
        loadLogs(1);

        // 5초마다 자동 새로고침 (선택적)
        // setInterval(() => loadLogs(currentPage), 5000);
    </script>
</body>
</html>
