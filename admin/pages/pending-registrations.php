<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>가입 승인 관리 - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            padding-top: 50px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-title {
            color: #f8fafc;
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .stats-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 8px 32px -8px rgba(0,0,0,0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
            text-align: center;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px -8px rgba(0,0,0,0.4);
        }

        .stat-card h3 {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-card p {
            color: #94a3b8;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card.pending h3 {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-card.approved h3 {
            background: linear-gradient(135deg, #34d399, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-card.rejected h3 {
            background: linear-gradient(135deg, #f87171, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: #60a5fa;
            transition: all 0.2s;
        }

        .tab:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        .tab.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-color: transparent;
        }

        .tab .count {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
        }

        .content-section {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px -8px rgba(0,0,0,0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(15, 23, 42, 0.5);
            padding: 14px;
            text-align: left;
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        td {
            padding: 14px;
            color: #e2e8f0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        tr:hover td {
            background: rgba(59, 130, 246, 0.05);
        }

        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge.pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .badge.approved { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .badge.rejected { background: rgba(239, 68, 68, 0.2); color: #f87171; }

        .btn-approve {
            padding: 6px 12px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-approve:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-reject {
            padding: 6px 12px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-reject:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-view {
            padding: 6px 12px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-view:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 12px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: rgba(30, 41, 59, 0.5);
            border-radius: 6px;
            cursor: pointer;
            color: #94a3b8;
            transition: all 0.2s;
        }

        .pagination button:hover {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        .pagination button.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-color: transparent;
        }

        /* 모달 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
        }

        .modal-content {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            max-width: 700px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 16px;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid rgba(148, 163, 184, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .modal-header h2 {
            font-size: 20px;
            color: #f8fafc;
            font-weight: 700;
        }

        .close {
            font-size: 28px;
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.2s;
        }

        .close:hover {
            color: #f8fafc;
        }

        .detail-group {
            margin-bottom: 15px;
            padding: 12px;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .detail-group label {
            display: block;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 5px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-group .value {
            color: #e2e8f0;
            word-break: break-all;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .action-buttons button {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-approve-large {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-approve-large:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-reject-large {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-reject-large:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            resize: vertical;
            min-height: 80px;
            background: rgba(15, 23, 42, 0.5);
            color: #e2e8f0;
            font-family: inherit;
        }

        textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        .txid-link {
            color: #60a5fa;
            text-decoration: none;
            font-family: monospace;
            font-size: 12px;
        }

        .txid-link:hover {
            text-decoration: underline;
            color: #93c5fd;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1 class="page-title">📝 가입 승인 관리</h1>

        <!-- 통계 -->
        <div class="stats-box">
            <div class="stat-card pending">
                <h3 id="pendingCount">0</h3>
                <p>승인 대기</p>
            </div>
            <div class="stat-card approved">
                <h3 id="approvedCount">0</h3>
                <p>승인 완료</p>
            </div>
            <div class="stat-card rejected">
                <h3 id="rejectedCount">0</h3>
                <p>거절됨</p>
            </div>
        </div>

        <!-- 탭 -->
        <div class="tabs">
            <div class="tab active" data-status="pending" onclick="changeTab('pending')">
                승인 대기 <span class="count" id="pendingTab">0</span>
            </div>
            <div class="tab" data-status="approved" onclick="changeTab('approved')">
                승인 완료 <span class="count" id="approvedTab">0</span>
            </div>
            <div class="tab" data-status="rejected" onclick="changeTab('rejected')">
                거절됨 <span class="count" id="rejectedTab">0</span>
            </div>
        </div>

        <div class="content-section">
            <div id="registrationsList">
                <div class="loading">목록을 불러오는 중...</div>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <!-- 상세 보기 모달 -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>가입 신청 상세</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>

            <div id="detailContent">
                <div class="detail-group">
                    <label>이메일</label>
                    <div class="value" id="detail_email"></div>
                </div>

                <div class="detail-group">
                    <label>TXID (트랜잭션 ID)</label>
                    <div class="value">
                        <a href="#" id="detail_txid_link" class="txid-link" target="_blank"></a>
                    </div>
                </div>

                <div class="detail-group">
                    <label>네트워크</label>
                    <div class="value" id="detail_network"></div>
                </div>

                <div class="detail-group">
                    <label>입금 금액</label>
                    <div class="value" id="detail_amount"></div>
                </div>

                <div class="detail-group">
                    <label>추천인 코드</label>
                    <div class="value" id="detail_referral"></div>
                </div>

                <div class="detail-group">
                    <label>후원인 코드</label>
                    <div class="value" id="detail_sponsor"></div>
                </div>

                <div class="detail-group">
                    <label>후원 위치</label>
                    <div class="value" id="detail_position"></div>
                </div>

                <div class="detail-group">
                    <label>신청일</label>
                    <div class="value" id="detail_created"></div>
                </div>

                <div class="detail-group">
                    <label>상태</label>
                    <div class="value" id="detail_status"></div>
                </div>

                <div id="userMemoSection" style="display: none;">
                    <div class="detail-group">
                        <label>사용자 메모</label>
                        <div class="value" id="detail_user_memo" style="background: rgba(0, 255, 255, 0.05); border: 1px solid rgba(0, 255, 255, 0.2); border-radius: 8px; padding: 12px; white-space: pre-wrap; line-height: 1.6;"></div>
                    </div>
                </div>

                <div id="adminNoteSection" style="display: none;">
                    <div class="detail-group">
                        <label>관리자 메모</label>
                        <div class="value" id="detail_admin_note"></div>
                    </div>
                </div>
            </div>

            <div id="actionSection">
                <div class="detail-group">
                    <label>관리자 메모 (선택)</label>
                    <textarea id="adminNote" placeholder="승인/거절 사유를 입력하세요"></textarea>
                </div>

                <div class="action-buttons">
                    <button class="btn-approve-large" onclick="approveRegistration()">✅ 승인</button>
                    <button class="btn-reject-large" onclick="rejectRegistration()">❌ 거절</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '/admin/api/pending-registrations.php';
        let currentPage = 1;
        let currentStatus = 'pending';
        let currentRegistrationId = null;

        window.onload = () => {
            loadStats();
            loadRegistrations(1);
        };

        // 통계 로드
        async function loadStats() {
            try {
                const response = await fetch(`${API_BASE}?action=stats`);
                const data = await response.json();

                if (data.success) {
                    document.getElementById('pendingCount').textContent = data.data.pending_count;
                    document.getElementById('approvedCount').textContent = data.data.approved_count;
                    document.getElementById('rejectedCount').textContent = data.data.rejected_count;

                    document.getElementById('pendingTab').textContent = data.data.pending_count;
                    document.getElementById('approvedTab').textContent = data.data.approved_count;
                    document.getElementById('rejectedTab').textContent = data.data.rejected_count;
                }
            } catch (error) {
                console.error('Stats load error:', error);
            }
        }

        // 탭 변경
        function changeTab(status) {
            currentStatus = status;
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.dataset.status === status) {
                    tab.classList.add('active');
                }
            });
            loadRegistrations(1);
        }

        // 목록 로드
        async function loadRegistrations(page = 1) {
            currentPage = page;

            try {
                const url = `${API_BASE}?action=list&status=${currentStatus}&page=${page}&limit=20`;
                const response = await fetch(url);
                const data = await response.json();

                if (!data.success) {
                    alert('목록을 불러오지 못했습니다: ' + data.message);
                    return;
                }

                renderList(data.data);
                renderPagination(data.pagination);

            } catch (error) {
                console.error('Load registrations error:', error);
                alert('목록 로드 오류: ' + error.message);
            }
        }

        // 목록 렌더링
        function renderList(registrations) {
            const listDiv = document.getElementById('registrationsList');

            if (registrations.length === 0) {
                listDiv.innerHTML = '<div class="loading">신청 내역이 없습니다.</div>';
                return;
            }

            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>이메일</th>
                            <th>TXID</th>
                            <th>금액</th>
                            <th>추천인</th>
                            <th>메모</th>
                            <th>신청일</th>
                            <th>상태</th>
                            ${currentStatus === 'approved' ? '<th>회원코드</th>' : ''}
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            registrations.forEach(reg => {
                const statusClass = reg.status;
                const txidShort = reg.txid.substring(0, 20) + '...';
                const memoShort = reg.memo ? (reg.memo.length > 30 ? reg.memo.substring(0, 30) + '...' : reg.memo) : '-';

                html += `
                    <tr>
                        <td>${reg.id}</td>
                        <td>${reg.email}</td>
                        <td><code style="font-size: 11px;">${txidShort}</code></td>
                        <td>$${reg.payment_amount}</td>
                        <td>${reg.referral_id || '-'}</td>
                        <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${reg.memo || ''}">${memoShort}</td>
                        <td>${formatDate(reg.created_at)}</td>
                        <td><span class="badge ${statusClass}">${getStatusText(reg.status)}</span></td>
                        ${currentStatus === 'approved' ? `
                            <td>
                                ${reg.created_user_id ? `
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="color: #fbbf24; font-weight: 600; font-family: monospace;">${reg.created_user_id}</span>
                                        <button onclick="copyUserId('${reg.created_user_id}')" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); padding: 3px 8px; border-radius: 4px; font-size: 11px; cursor: pointer;">복사</button>
                                        <a href="/html/login.html?id=${reg.created_user_id}" target="_blank" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4); padding: 3px 8px; border-radius: 4px; font-size: 11px; text-decoration: none;">로그인</a>
                                    </div>
                                ` : '-'}
                            </td>
                        ` : ''}
                        <td>
                            <button class="btn-view" onclick="viewDetail(${reg.id})">상세</button>
                            ${reg.status === 'pending' ? `
                                <button class="btn-approve" onclick="quickApprove(${reg.id})">승인</button>
                                <button class="btn-reject" onclick="quickReject(${reg.id})">거절</button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            listDiv.innerHTML = html;
        }

        // 회원코드 복사
        function copyUserId(userId) {
            navigator.clipboard.writeText(userId).then(() => {
                alert(`K-Pumasi 가입을 축하합니다!\n\n회원코드: ${userId}\n\n클립보드에 복사되었습니다.`);
            }).catch(err => {
                alert('복사 실패: ' + err);
            });
        }

        // 상태 텍스트
        function getStatusText(status) {
            const statusMap = {
                'pending': '대기중',
                'approved': '승인됨',
                'rejected': '거절됨'
            };
            return statusMap[status] || status;
        }

        // 페이지네이션
        function renderPagination(pagination) {
            const paginationDiv = document.getElementById('pagination');
            let html = '';

            if (pagination.page > 1) {
                html += `<button onclick="loadRegistrations(${pagination.page - 1})">« 이전</button>`;
            }

            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.page) {
                    html += `<button class="active">${i}</button>`;
                } else if (Math.abs(i - pagination.page) <= 2 || i === 1 || i === pagination.total_pages) {
                    html += `<button onclick="loadRegistrations(${i})">${i}</button>`;
                } else if (Math.abs(i - pagination.page) === 3) {
                    html += `<button disabled>...</button>`;
                }
            }

            if (pagination.page < pagination.total_pages) {
                html += `<button onclick="loadRegistrations(${pagination.page + 1})">다음 »</button>`;
            }

            paginationDiv.innerHTML = html;
        }

        // 상세 보기
        async function viewDetail(id) {
            try {
                const response = await fetch(`${API_BASE}?action=detail&id=${id}`);
                const data = await response.json();

                if (!data.success) {
                    alert('상세 정보를 불러오지 못했습니다: ' + data.message);
                    return;
                }

                const reg = data.data;
                currentRegistrationId = reg.id;

                // 정보 채우기
                document.getElementById('detail_email').textContent = reg.email;

                // TXID 링크 (TronScan)
                const txidLink = document.getElementById('detail_txid_link');
                txidLink.textContent = reg.txid;
                if (reg.network === 'TRC20') {
                    txidLink.href = `https://tronscan.org/#/transaction/${reg.txid}`;
                } else {
                    txidLink.href = `https://bscscan.com/tx/${reg.txid}`;
                }

                document.getElementById('detail_network').textContent = reg.network;
                document.getElementById('detail_amount').textContent = `$${reg.payment_amount} USDT`;
                document.getElementById('detail_referral').textContent = reg.referral_id || '없음';
                document.getElementById('detail_sponsor').textContent = reg.sponsor_id || '없음';

                const positionText = reg.sponsor_position === 1 ? '좌측' : (reg.sponsor_position === 2 ? '우측' : '자동');
                document.getElementById('detail_position').textContent = positionText;

                document.getElementById('detail_created').textContent = formatDateTime(reg.created_at);
                document.getElementById('detail_status').innerHTML = `<span class="badge ${reg.status}">${getStatusText(reg.status)}</span>`;

                // 사용자 메모
                if (reg.memo) {
                    document.getElementById('userMemoSection').style.display = 'block';
                    document.getElementById('detail_user_memo').textContent = reg.memo;
                } else {
                    document.getElementById('userMemoSection').style.display = 'none';
                }

                // 관리자 메모
                if (reg.admin_note) {
                    document.getElementById('adminNoteSection').style.display = 'block';
                    document.getElementById('detail_admin_note').textContent = reg.admin_note;
                } else {
                    document.getElementById('adminNoteSection').style.display = 'none';
                }

                // 액션 섹션
                if (reg.status === 'pending') {
                    document.getElementById('actionSection').style.display = 'block';
                } else {
                    document.getElementById('actionSection').style.display = 'none';
                }

                document.getElementById('detailModal').style.display = 'block';

            } catch (error) {
                console.error('View detail error:', error);
                alert('상세 정보 로드 오류: ' + error.message);
            }
        }

        // 빠른 승인
        async function quickApprove(id) {
            if (!confirm('이 가입 신청을 승인하시겠습니까?')) return;

            try {
                const response = await fetch(`${API_BASE}?action=approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, admin_note: '' })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ 승인 완료!\n생성된 회원코드: ${data.data.created_user_id}`);
                    loadStats();
                    loadRegistrations(currentPage);
                } else {
                    alert('❌ 승인 실패: ' + data.message);
                }
            } catch (error) {
                alert('❌ 오류: ' + error.message);
            }
        }

        // 빠른 거절
        async function quickReject(id) {
            const reason = prompt('거절 사유를 입력하세요:');
            if (reason === null) return;

            try {
                const response = await fetch(`${API_BASE}?action=reject`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, admin_note: reason })
                });

                const data = await response.json();

                if (data.success) {
                    alert('가입 신청이 거절되었습니다.');
                    loadStats();
                    loadRegistrations(currentPage);
                } else {
                    alert('❌ 거절 실패: ' + data.message);
                }
            } catch (error) {
                alert('❌ 오류: ' + error.message);
            }
        }

        // 모달에서 승인
        async function approveRegistration() {
            if (!currentRegistrationId) return;
            if (!confirm('이 가입 신청을 승인하시겠습니까?')) return;

            const adminNote = document.getElementById('adminNote').value;

            try {
                const response = await fetch(`${API_BASE}?action=approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: currentRegistrationId,
                        admin_note: adminNote
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ 승인 완료!\n생성된 회원코드: ${data.data.created_user_id}`);
                    closeModal();
                    loadStats();
                    loadRegistrations(currentPage);
                } else {
                    alert('❌ 승인 실패: ' + data.message);
                }
            } catch (error) {
                alert('❌ 오류: ' + error.message);
            }
        }

        // 모달에서 거절
        async function rejectRegistration() {
            if (!currentRegistrationId) return;

            const adminNote = document.getElementById('adminNote').value;
            if (!adminNote.trim()) {
                alert('거절 사유를 입력해주세요.');
                return;
            }

            if (!confirm('이 가입 신청을 거절하시겠습니까?')) return;

            try {
                const response = await fetch(`${API_BASE}?action=reject`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: currentRegistrationId,
                        admin_note: adminNote
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('가입 신청이 거절되었습니다.');
                    closeModal();
                    loadStats();
                    loadRegistrations(currentPage);
                } else {
                    alert('❌ 거절 실패: ' + data.message);
                }
            } catch (error) {
                alert('❌ 오류: ' + error.message);
            }
        }

        // 모달 닫기
        function closeModal() {
            document.getElementById('detailModal').style.display = 'none';
            currentRegistrationId = null;
            document.getElementById('adminNote').value = '';
        }

        // 날짜 포맷
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('ko-KR');
        }

        function formatDateTime(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleString('ko-KR');
        }

        // 모달 외부 클릭시 닫기
        window.onclick = function(event) {
            const modal = document.getElementById('detailModal');
            if (event.target === modal) {
                closeModal();
            }
        };
    </script>
    
</body>
</html>
