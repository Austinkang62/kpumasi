<?php
/**
 * Super Admin - User Change History
 * 회원 정보 변경 이력 조회
 */
require_once __DIR__ . '/../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원 변경 이력 - Super Admin</title>
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
        }

        .filter-bar input,
        .filter-bar select {
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95em;
        }

        .filter-bar input {
            flex: 1;
            min-width: 200px;
        }

        .filter-bar select {
            min-width: 150px;
        }

        .change-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .change-update { background: #dbeafe; color: #1e40af; }
        .change-delete { background: #fee2e2; color: #991b1b; }
        .change-status_change { background: #fef3c7; color: #92400e; }
        .change-create { background: #d1fae5; color: #065f46; }

        .value-box {
            display: inline-block;
            padding: 4px 8px;
            background: #f3f4f6;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }

        .value-old { background: #fee2e2; color: #991b1b; }
        .value-new { background: #d1fae5; color: #065f46; }

        .timeline-item {
            padding: 20px;
            border-left: 3px solid #e5e7eb;
            margin-left: 20px;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
        }

        .timeline-item:hover {
            background: #f9fafb;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-box .number {
            font-size: 2em;
            font-weight: 800;
            color: var(--primary);
        }

        .stat-box .label {
            color: var(--gray);
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="content-header">
            <h1>📝 회원 변경 이력</h1>
        </header>

        <!-- 통계 -->
        <div class="stats-row" id="statsRow">
            <div class="stat-box">
                <div class="number" id="statTotalChanges">-</div>
                <div class="label">오늘 변경</div>
            </div>
            <div class="stat-box">
                <div class="number" id="statAffectedUsers">-</div>
                <div class="label">영향받은 회원</div>
            </div>
            <div class="stat-box">
                <div class="number" id="statActiveAdmins">-</div>
                <div class="label">작업한 관리자</div>
            </div>
        </div>

        <!-- 필터 -->
        <div class="filter-bar">
            <input type="text" id="searchUser" placeholder="User ID 또는 Login ID 검색...">
            <select id="filterChangeType">
                <option value="">모든 변경 유형</option>
                <option value="update">정보 수정</option>
                <option value="status_change">상태 변경</option>
                <option value="delete">삭제</option>
                <option value="create">생성</option>
            </select>
            <select id="filterAdmin">
                <option value="">모든 관리자</option>
            </select>
            <input type="date" id="filterDateFrom" placeholder="시작일">
            <input type="date" id="filterDateTo" placeholder="종료일">
            <button class="btn-primary" onclick="loadHistory()">검색</button>
            <button class="btn-secondary" onclick="resetFilters()">초기화</button>
        </div>

        <!-- 변경 이력 목록 -->
        <div class="card">
            <div class="card-header">
                <h2>변경 이력</h2>
            </div>
            <div class="card-body">
                <div id="historyTimeline">
                    <div class="loading">로딩 중...</div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // 통계 로드
        async function loadStats() {
            try {
                const response = await fetch('../api/change-history.php?action=today-stats');
                const result = await response.json();

                if (result.success) {
                    document.getElementById('statTotalChanges').textContent = result.data.total_changes || 0;
                    document.getElementById('statAffectedUsers').textContent = result.data.affected_users || 0;
                    document.getElementById('statActiveAdmins').textContent = result.data.active_admins || 0;
                }
            } catch (error) {
                console.error('Load stats error:', error);
            }
        }

        // 관리자 목록 로드
        async function loadAdminList() {
            try {
                const response = await fetch('../api/admin-manage.php?action=list&limit=100');
                const result = await response.json();

                if (result.success) {
                    const select = document.getElementById('filterAdmin');
                    result.data.forEach(admin => {
                        const option = document.createElement('option');
                        option.value = admin.username;
                        option.textContent = `${admin.username} (${admin.full_name || admin.email})`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Load admin list error:', error);
            }
        }

        // 변경 이력 로드
        async function loadHistory() {
            const searchUser = document.getElementById('searchUser').value;
            const changeType = document.getElementById('filterChangeType').value;
            const admin = document.getElementById('filterAdmin').value;
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo = document.getElementById('filterDateTo').value;

            const params = new URLSearchParams({
                action: 'list',
                limit: 100
            });

            if (searchUser) params.append('search_user', searchUser);
            if (changeType) params.append('change_type', changeType);
            if (admin) params.append('admin', admin);
            if (dateFrom) params.append('date_from', dateFrom);
            if (dateTo) params.append('date_to', dateTo);

            try {
                const response = await fetch(`../api/change-history.php?${params}`);
                const result = await response.json();

                const timeline = document.getElementById('historyTimeline');

                if (result.success && result.data.length > 0) {
                    timeline.innerHTML = result.data.map(item => `
                        <div class="timeline-item">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <div>
                                    <strong>User ID: ${item.user_id}</strong>
                                    ${item.user_login_id ? `(${item.user_login_id})` : ''}
                                    <span class="change-badge change-${item.change_type}">${item.change_type}</span>
                                </div>
                                <div style="color: var(--gray); font-size: 0.9em;">
                                    ${new Date(item.created_at).toLocaleString('ko-KR')}
                                </div>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>필드:</strong> ${item.table_name}.${item.field_name}
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>변경:</strong>
                                <span class="value-box value-old">${item.old_value || 'NULL'}</span>
                                →
                                <span class="value-box value-new">${item.new_value || 'NULL'}</span>
                            </div>
                            ${item.reason ? `
                                <div style="margin-bottom: 10px;">
                                    <strong>사유:</strong> ${item.reason}
                                </div>
                            ` : ''}
                            <div style="color: var(--gray); font-size: 0.9em;">
                                관리자: <strong>${item.admin_username}</strong>
                                ${item.admin_full_name ? `(${item.admin_full_name})` : ''}
                                | IP: ${item.ip_address}
                            </div>
                        </div>
                    `).join('');
                } else {
                    timeline.innerHTML = '<div class="loading">변경 이력이 없습니다.</div>';
                }
            } catch (error) {
                console.error('Load history error:', error);
                document.getElementById('historyTimeline').innerHTML = '<div class="error">로드 중 오류가 발생했습니다.</div>';
            }
        }

        // 필터 초기화
        function resetFilters() {
            document.getElementById('searchUser').value = '';
            document.getElementById('filterChangeType').value = '';
            document.getElementById('filterAdmin').value = '';
            document.getElementById('filterDateFrom').value = '';
            document.getElementById('filterDateTo').value = '';
            loadHistory();
        }

        // 페이지 로드
        loadStats();
        loadAdminList();
        loadHistory();
    </script>
</body>
</html>
