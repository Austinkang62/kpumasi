<!-- Super Admin Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <h1>🔐 Super Admin</h1>
        <p>K-Pumasi</p>
    </div>

    <nav class="sidebar-nav">
        <a href="../index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
            <span class="icon">📊</span>
            <span>대시보드</span>
        </a>
        <a href="../pages/admin-list.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'admin-list.php' ? 'active' : '' ?>">
            <span class="icon">👥</span>
            <span>관리자 계정 관리</span>
        </a>
        <a href="../pages/system-settings.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'system-settings.php' ? 'active' : '' ?>">
            <span class="icon">⚙️</span>
            <span>시스템 설정</span>
        </a>
        <a href="../pages/activity-logs.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'activity-logs.php' ? 'active' : '' ?>">
            <span class="icon">📝</span>
            <span>활동 로그</span>
        </a>

        <div class="nav-divider"></div>

        <a href="../../admin/index.php" class="nav-item" target="_blank">
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

<script>
// 로그아웃 함수
async function logout() {
    if (!confirm('로그아웃 하시겠습니까?')) return;

    try {
        const response = await fetch('../api/auth.php?action=logout', {
            method: 'POST'
        });
        const data = await response.json();

        if (data.success) {
            window.location.href = '../login.php';
        }
    } catch (error) {
        console.error('Logout error:', error);
        alert('로그아웃 중 오류가 발생했습니다.');
    }
}
</script>
