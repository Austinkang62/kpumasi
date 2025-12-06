<?php
/**
 * Super Admin - Admin List Page
 */
require_once __DIR__ . '/../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 계정 관리 - Super Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            margin-bottom: 25px;
        }

        .modal-header h2 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1em;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: #e5e7eb;
            color: var(--dark);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-submit {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85em;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-edit {
            background: var(--info);
            color: white;
        }

        .btn-toggle {
            background: var(--warning);
            color: white;
        }

        .btn-delete {
            background: var(--danger);
            color: white;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-box input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="content-header">
            <h1>👥 관리자 계정 관리</h1>
            <button class="btn-primary" onclick="openCreateModal()">➕ 새 관리자 추가</button>
        </header>

        <div class="card">
            <div class="card-body">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Username, Email, 이름 검색..." onkeyup="searchAdmins()">
                    <button class="btn-primary" onclick="searchAdmins()">검색</button>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>이름</th>
                                <th>Role</th>
                                <th>상태</th>
                                <th>마지막 로그인</th>
                                <th>생성일</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody id="adminTable">
                            <tr>
                                <td colspan="9" class="loading">로딩 중...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Create Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>새 관리자 추가</h2>
            </div>
            <form id="createForm" onsubmit="createAdmin(event)">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>이름</label>
                    <input type="text" name="full_name">
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeCreateModal()">취소</button>
                    <button type="submit" class="btn-submit">생성</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 관리자 목록 로드
        async function loadAdmins(search = '') {
            try {
                const url = search
                    ? `../api/admin-manage.php?action=list&search=${encodeURIComponent(search)}`
                    : '../api/admin-manage.php?action=list&limit=100';

                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    const tbody = document.getElementById('adminTable');
                    tbody.innerHTML = result.data.map(admin => `
                        <tr>
                            <td>${admin.admin_id}</td>
                            <td><strong>${admin.username}</strong></td>
                            <td>${admin.email}</td>
                            <td>${admin.full_name || '-'}</td>
                            <td><span class="role-badge role-${admin.role}">${admin.role}</span></td>
                            <td><span class="status-badge status-${admin.is_active ? 'active' : 'inactive'}">${admin.is_active ? '활성' : '비활성'}</span></td>
                            <td>${admin.last_login || '없음'}</td>
                            <td>${admin.created_at}</td>
                            <td class="action-buttons">
                                <button class="btn-sm btn-toggle" onclick="toggleStatus(${admin.admin_id})">
                                    ${admin.is_active ? '비활성화' : '활성화'}
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Load admins error:', error);
            }
        }

        // 검색
        function searchAdmins() {
            const search = document.getElementById('searchInput').value;
            loadAdmins(search);
        }

        // 모달 열기/닫기
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.remove('active');
            document.getElementById('createForm').reset();
        }

        // 관리자 생성
        async function createAdmin(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            try {
                const response = await fetch('../api/admin-manage.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert('관리자가 생성되었습니다.');
                    closeCreateModal();
                    loadAdmins();
                } else {
                    alert('오류: ' + result.message);
                }
            } catch (error) {
                console.error('Create admin error:', error);
                alert('관리자 생성 중 오류가 발생했습니다.');
            }
        }

        // 상태 토글
        async function toggleStatus(adminId) {
            if (!confirm('이 관리자의 상태를 변경하시겠습니까?')) return;

            try {
                const response = await fetch('../api/admin-manage.php?action=toggle-status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ admin_id: adminId })
                });

                const result = await response.json();

                if (result.success) {
                    loadAdmins();
                } else {
                    alert('오류: ' + result.message);
                }
            } catch (error) {
                console.error('Toggle status error:', error);
            }
        }

        // 페이지 로드
        loadAdmins();
    </script>
</body>
</html>
