<?php
/**
 * Super Admin Dashboard
 */
require_once __DIR__ . '/includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - K-Pumasi</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <!-- 사이드바 -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h1>🔐 Super Admin</h1>
            <p>K-Pumasi</p>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item active">
                <span class="icon">📊</span>
                <span>대시보드</span>
            </a>
            <a href="pages/admin-list.php" class="nav-item">
                <span class="icon">👥</span>
                <span>관리자 계정 관리</span>
            </a>
            <a href="pages/system-settings.php" class="nav-item">
                <span class="icon">⚙️</span>
                <span>시스템 설정</span>
            </a>
            <a href="pages/activity-logs.php" class="nav-item">
                <span class="icon">📝</span>
                <span>활동 로그</span>
            </a>

            <div class="nav-divider"></div>

            <a href="../admin/index.php" class="nav-item" target="_blank">
                <span class="icon">🔗</span>
                <span>일반 관리자 페이지</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">SA</div>
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($currentSuperAdmin['username']) ?></div>
                    <div class="user-role">Super Admin</div>
                </div>
            </div>
            <button class="btn-logout" onclick="logout()">로그아웃</button>
        </div>
    </aside>

    <!-- 메인 컨텐츠 -->
    <main class="main-content">
        <header class="content-header">
            <h1>Super Admin Dashboard</h1>
            <div class="header-actions">
                <span class="current-time" id="currentTime"></span>
            </div>
        </header>

        <!-- 통계 카드 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">👥</div>
                <div class="stat-info">
                    <div class="stat-label">전체 관리자</div>
                    <div class="stat-value" id="totalAdmins">-</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">🔐</div>
                <div class="stat-info">
                    <div class="stat-label">Super Admins</div>
                    <div class="stat-value" id="superAdmins">-</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">✅</div>
                <div class="stat-info">
                    <div class="stat-label">활성 관리자</div>
                    <div class="stat-value" id="activeAdmins">-</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">📈</div>
                <div class="stat-info">
                    <div class="stat-label">오늘 로그인</div>
                    <div class="stat-value" id="todayLogins">-</div>
                </div>
            </div>
        </div>

        <!-- 시스템 상태 -->
        <div class="content-row">
            <div class="card">
                <div class="card-header">
                    <h2>🖥️ 시스템 상태</h2>
                </div>
                <div class="card-body">
                    <div class="system-status">
                        <div class="status-item">
                            <span class="status-label">전체 사용자</span>
                            <span class="status-value" id="totalUsers">-</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">총 매출</span>
                            <span class="status-value" id="totalSales">-</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">대기 출금</span>
                            <span class="status-value" id="pendingWithdrawals">-</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">데이터베이스</span>
                            <span class="status-badge status-active">정상</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>📝 최근 관리자 활동</h2>
                </div>
                <div class="card-body">
                    <div class="activity-list" id="recentActivities">
                        <div class="loading">로딩 중...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 관리자 목록 -->
        <div class="card">
            <div class="card-header">
                <h2>👥 관리자 목록</h2>
                <a href="pages/admin-list.php" class="btn-primary">전체 보기</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>상태</th>
                                <th>마지막 로그인</th>
                            </tr>
                        </thead>
                        <tbody id="adminListTable">
                            <tr>
                                <td colspan="5" class="loading">로딩 중...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
    <script>
        // 로그아웃
        async function logout() {
            if (!confirm('로그아웃 하시겠습니까?')) return;

            try {
                const response = await fetch('api/auth.php?action=logout', {
                    method: 'POST'
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = 'login.php';
                }
            } catch (error) {
                console.error('Logout error:', error);
                alert('로그아웃 중 오류가 발생했습니다.');
            }
        }

        // 현재 시간 업데이트
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleString('ko-KR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('currentTime').textContent = timeStr;
        }

        setInterval(updateTime, 1000);
        updateTime();

        // 데이터 로드
        async function loadDashboardData() {
            try {
                // 통계 데이터 로드
                const statsResponse = await fetch('api/stats.php');
                const stats = await statsResponse.json();

                if (stats.success) {
                    document.getElementById('totalAdmins').textContent = stats.data.total_admins;
                    document.getElementById('superAdmins').textContent = stats.data.super_admins;
                    document.getElementById('activeAdmins').textContent = stats.data.active_admins;
                    document.getElementById('todayLogins').textContent = stats.data.today_logins;
                    document.getElementById('totalUsers').textContent = stats.data.total_users;
                    document.getElementById('totalSales').textContent = '$' + stats.data.total_sales;
                    document.getElementById('pendingWithdrawals').textContent = stats.data.pending_withdrawals;
                }

                // 관리자 목록 로드
                const adminsResponse = await fetch('api/admin-manage.php?action=list&limit=5');
                const admins = await adminsResponse.json();

                if (admins.success) {
                    const tbody = document.getElementById('adminListTable');
                    tbody.innerHTML = admins.data.map(admin => `
                        <tr>
                            <td>${admin.username}</td>
                            <td>${admin.email}</td>
                            <td><span class="role-badge role-${admin.role}">${admin.role}</span></td>
                            <td><span class="status-badge status-${admin.is_active ? 'active' : 'inactive'}">${admin.is_active ? '활성' : '비활성'}</span></td>
                            <td>${admin.last_login || '없음'}</td>
                        </tr>
                    `).join('');
                }

                // 최근 활동 로드
                const logsResponse = await fetch('api/activity-logs.php?limit=5');
                const logs = await logsResponse.json();

                if (logs.success) {
                    const activityList = document.getElementById('recentActivities');
                    activityList.innerHTML = logs.data.map(log => `
                        <div class="activity-item">
                            <span class="activity-icon">${getActivityIcon(log.action)}</span>
                            <div class="activity-details">
                                <div class="activity-text">${log.username} - ${log.action}</div>
                                <div class="activity-time">${log.created_at}</div>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Dashboard data load error:', error);
            }
        }

        function getActivityIcon(action) {
            const icons = {
                'super_login_success': '🔐',
                'super_logout': '🚪',
                'admin_created': '➕',
                'admin_updated': '✏️',
                'admin_deleted': '🗑️',
                'settings_updated': '⚙️'
            };
            return icons[action] || '📝';
        }

        // 페이지 로드 시 실행
        loadDashboardData();
    </script>
</body>
</html>
