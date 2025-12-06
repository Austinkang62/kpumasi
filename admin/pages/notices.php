<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>공지사항 관리 - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            color: #fff;
        }

        .dashboard-content {
            max-width: 1600px;
            margin: 0 auto;
            padding: 80px 30px 30px 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 10px;
        }

        .btn-create {
            padding: 12px 24px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }

        .notice-table {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(15, 23, 42, 0.5);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            color: #e2e8f0;
        }

        tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-important {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge-normal {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .badge-active {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .badge-inactive {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-edit, .btn-delete, .btn-toggle {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }

        .btn-edit {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .btn-edit:hover {
            background: rgba(59, 130, 246, 0.3);
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.3);
        }

        .btn-toggle {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .btn-toggle:hover {
            background: rgba(148, 163, 184, 0.3);
        }

        /* 모달 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: #f8fafc;
        }

        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 24px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #e2e8f0;
            font-weight: 500;
            font-size: 14px;
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 12px;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            color: #f8fafc;
            font-size: 14px;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-checkbox input {
            width: 18px;
            height: 18px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1 class="page-title">📢 공지사항 관리</h1>
            <button class="btn-create" onclick="showCreateModal()">+ 새 공지사항</button>
        </div>

        <div class="notice-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>제목</th>
                        <th style="width: 100px;">중요</th>
                        <th style="width: 100px;">상태</th>
                        <th style="width: 150px;">작성일</th>
                        <th style="width: 200px;">작업</th>
                    </tr>
                </thead>
                <tbody id="noticeTableBody">
                    <tr>
                        <td colspan="6" class="empty-state">공지사항을 불러오는 중...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 공지사항 작성/수정 모달 -->
    <div class="modal" id="noticeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">새 공지사항</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="noticeForm" onsubmit="saveNotice(event)">
                <input type="hidden" id="noticeId" value="">

                <div class="form-group">
                    <label class="form-label">제목</label>
                    <input type="text" id="noticeTitle" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">내용</label>
                    <textarea id="noticeContent" class="form-textarea" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" id="noticeImportant">
                        <span>중요 공지사항으로 표시</span>
                    </label>
                </div>

                <button type="submit" class="btn-submit">저장</button>
            </form>
        </div>
    </div>

    
    <script>
        let notices = [];

        // 공지사항 목록 로드
        async function loadNotices() {
            try {
                const response = await fetch('/api/admin/notices/get-list.php', {
                    credentials: 'include'
                });
                const result = await response.json();

                if (result.success) {
                    notices = result.data;
                    renderNotices();
                } else {
                    alert('공지사항을 불러올 수 없습니다: ' + result.message);
                }
            } catch (error) {
                console.error('공지사항 로드 오류:', error);
                alert('공지사항을 불러올 수 없습니다.');
            }
        }

        // 공지사항 렌더링
        function renderNotices() {
            const tbody = document.getElementById('noticeTableBody');

            if (notices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">등록된 공지사항이 없습니다</td></tr>';
                return;
            }

            tbody.innerHTML = notices.map(notice => `
                <tr>
                    <td>${notice.id}</td>
                    <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        ${escapeHtml(notice.title)}
                    </td>
                    <td>
                        <span class="badge ${notice.is_important ? 'badge-important' : 'badge-normal'}">
                            ${notice.is_important ? '중요' : '일반'}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${notice.status === 'active' ? 'badge-active' : 'badge-inactive'}">
                            ${notice.status === 'active' ? '활성' : '비활성'}
                        </span>
                    </td>
                    <td>${formatDate(notice.created_at)}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-edit" onclick="editNotice(${notice.id})">수정</button>
                            <button class="btn-toggle" onclick="toggleStatus(${notice.id})">
                                ${notice.status === 'active' ? '비활성화' : '활성화'}
                            </button>
                            <button class="btn-delete" onclick="deleteNotice(${notice.id})">삭제</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        // 작성 모달 표시
        function showCreateModal() {
            document.getElementById('modalTitle').textContent = '새 공지사항';
            document.getElementById('noticeForm').reset();
            document.getElementById('noticeId').value = '';
            document.getElementById('noticeModal').classList.add('show');
        }

        // 수정 모달 표시
        async function editNotice(id) {
            const notice = notices.find(n => n.id === id);
            if (!notice) return;

            document.getElementById('modalTitle').textContent = '공지사항 수정';
            document.getElementById('noticeId').value = notice.id;
            document.getElementById('noticeTitle').value = notice.title;
            document.getElementById('noticeContent').value = notice.content;
            document.getElementById('noticeImportant').checked = notice.is_important == 1;
            document.getElementById('noticeModal').classList.add('show');
        }

        // 공지사항 저장
        async function saveNotice(event) {
            event.preventDefault();

            const id = document.getElementById('noticeId').value;
            const title = document.getElementById('noticeTitle').value;
            const content = document.getElementById('noticeContent').value;
            const isImportant = document.getElementById('noticeImportant').checked ? 1 : 0;

            const url = id ? '/api/admin/notices/update.php' : '/api/admin/notices/create.php';
            const data = { title, content, is_important: isImportant };
            if (id) data.id = id;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert(id ? '공지사항이 수정되었습니다.' : '공지사항이 작성되었습니다.');
                    closeModal();
                    loadNotices();
                } else {
                    alert('저장 실패: ' + result.message);
                }
            } catch (error) {
                console.error('저장 오류:', error);
                alert('저장 중 오류가 발생했습니다.');
            }
        }

        // 상태 토글
        async function toggleStatus(id) {
            const notice = notices.find(n => n.id === id);
            if (!notice) return;

            const newStatus = notice.status === 'active' ? 'inactive' : 'active';

            try {
                const response = await fetch('/api/admin/notices/update-status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ id, status: newStatus })
                });

                const result = await response.json();

                if (result.success) {
                    loadNotices();
                } else {
                    alert('상태 변경 실패: ' + result.message);
                }
            } catch (error) {
                console.error('상태 변경 오류:', error);
                alert('상태 변경 중 오류가 발생했습니다.');
            }
        }

        // 삭제
        async function deleteNotice(id) {
            if (!confirm('정말 삭제하시겠습니까?')) return;

            try {
                const response = await fetch('/api/admin/notices/delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ id })
                });

                const result = await response.json();

                if (result.success) {
                    alert('공지사항이 삭제되었습니다.');
                    loadNotices();
                } else {
                    alert('삭제 실패: ' + result.message);
                }
            } catch (error) {
                console.error('삭제 오류:', error);
                alert('삭제 중 오류가 발생했습니다.');
            }
        }

        // 모달 닫기
        function closeModal() {
            document.getElementById('noticeModal').classList.remove('show');
        }

        // 날짜 포맷
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ko-KR') + ' ' + date.toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' });
        }

        // HTML 이스케이프
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 초기화
        window.addEventListener('DOMContentLoaded', () => {
            loadNotices();
        });
    </script>
</body>
</html>
