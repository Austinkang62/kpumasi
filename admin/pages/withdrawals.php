<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>출금 관리 - K-Pumasi Admin</title>
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

        .filters {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px -8px rgba(0,0,0,0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-color: transparent;
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

        .badge-pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .badge-completed { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .badge-rejected { background: rgba(239, 68, 68, 0.2); color: #f87171; }

        .btn-approve {
            padding: 6px 12px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
            margin-right: 5px;
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

        .user-info {
            font-size: 13px;
        }

        .user-info strong {
            color: #60a5fa;
            display: block;
        }

        .user-info small {
            color: #94a3b8;
        }

        .wallet-address {
            font-family: monospace;
            font-size: 11px;
            color: #94a3b8;
            word-break: break-all;
            max-width: 150px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
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
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            max-width: 500px;
            width: 90%;
            padding: 30px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }

        .modal-content h2 {
            color: #f8fafc;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #e2e8f0;
            font-size: 0.9em;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.5);
            color: #f8fafc;
            font-size: 0.95em;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-modal {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-modal-approve {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-modal-reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-modal-cancel {
            background: rgba(107, 114, 128, 0.3);
            color: #94a3b8;
        }

        .btn-modal:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1 class="page-title">💰 출금 관리</h1>

        <div class="filters">
            <button class="filter-btn active" onclick="filterStatus('all')">전체</button>
            <button class="filter-btn" onclick="filterStatus('pending')">대기중</button>
            <button class="filter-btn" onclick="filterStatus('completed')">완료</button>
            <button class="filter-btn" onclick="filterStatus('rejected')">거절됨</button>
            <button class="filter-btn" onclick="downloadExcel()" style="background: #10b981; border-color: #10b981; margin-left: auto;">📥 엑셀 다운로드</button>
        </div>

        <div class="content-section">
            <div id="withdrawalsList">
                <div class="loading">출금 요청을 불러오는 중...</div>
            </div>
        </div>
    </div>

    <!-- 승인 모달 -->
    <div class="modal" id="approveModal">
        <div class="modal-content">
            <h2>출금 승인</h2>
            <div class="form-group">
                <label>Transaction ID (TXID)</label>
                <input type="text" id="txidInput" placeholder="블록체인 트랜잭션 ID 입력">
            </div>
            <div class="modal-buttons">
                <button class="btn-modal btn-modal-approve" onclick="confirmApprove()">승인</button>
                <button class="btn-modal btn-modal-cancel" onclick="closeModal()">취소</button>
            </div>
        </div>
    </div>

    <!-- 거부 모달 -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <h2>출금 거절</h2>
            <div class="form-group">
                <label>거절 사유</label>
                <textarea id="reasonInput" rows="4" placeholder="거절 사유를 입력하세요"></textarea>
            </div>
            <div class="modal-buttons">
                <button class="btn-modal btn-modal-reject" onclick="confirmReject()">거절</button>
                <button class="btn-modal btn-modal-cancel" onclick="closeModal()">취소</button>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../api';
        let currentStatus = 'all';
        let currentWithdrawalId = null;

        // 출금 목록 로드
        async function loadWithdrawals() {
            try {
                const response = await fetch(`${API_BASE}/withdrawals.php?action=list&status=${currentStatus}`);
                const data = await response.json();

                if (!data.success) {
                    console.error('API Error:', data);
                    document.getElementById('withdrawalsList').innerHTML =
                        `<div class="loading">출금 목록을 불러오지 못했습니다.<br>오류: ${data.message || '알 수 없는 오류'}</div>`;
                    return;
                }

                renderWithdrawals(data.withdrawals || []);
            } catch (error) {
                console.error('Load error:', error);
                document.getElementById('withdrawalsList').innerHTML =
                    `<div class="loading">네트워크 오류가 발생했습니다.<br>오류: ${error.message}<br><br>브라우저 콘솔(F12)에서 자세한 오류를 확인하세요.</div>`;
            }
        }

        // 출금 목록 렌더링
        function renderWithdrawals(withdrawals) {
            const listDiv = document.getElementById('withdrawalsList');

            if (withdrawals.length === 0) {
                listDiv.innerHTML = '<div class="loading">출금 요청이 없습니다.</div>';
                return;
            }

            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>회원</th>
                            <th>금액</th>
                            <th>수수료</th>
                            <th>실수령액</th>
                            <th>외상금액</th>
                            <th>잔액</th>
                            <th>지갑주소</th>
                            <th>상태</th>
                            <th>신청일</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            withdrawals.forEach(w => {
                const statusText = {
                    'pending': '대기중',
                    'completed': '완료',
                    'rejected': '거절됨'
                }[w.status] || w.status;

                html += `
                    <tr>
                        <td>${w.withdrawal_id}</td>
                        <td class="user-info">
                            <strong>${w.login_id || w.user_id}</strong>
                            <small>${w.email || '-'}</small>
                        </td>
                        <td>$${parseFloat(w.amount).toFixed(2)}</td>
                        <td>$${parseFloat(w.fee).toFixed(2)}</td>
                        <td>$${parseFloat(w.net_amount).toFixed(2)}</td>
                        <td style="color: #ef4444;">$${parseFloat(w.debt_amount || 0).toFixed(2)}</td>
                        <td style="color: #10b981; font-weight: 600;">$${(parseFloat(w.net_amount) - parseFloat(w.debt_amount || 0)).toFixed(2)}</td>
                        <td class="wallet-address">${w.usdt_address || '-'}</td>
                        <td><span class="badge badge-${w.status}">${statusText}</span></td>
                        <td>${formatDate(w.created_at)}</td>
                        <td>
                            ${w.status === 'pending' ? `
                                <button class="btn-approve" onclick="openApproveModal(${w.withdrawal_id})">승인</button>
                                <button class="btn-reject" onclick="openRejectModal(${w.withdrawal_id})">거절</button>
                            ` : '-'}
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

        // 날짜 포맷
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('ko-KR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // 필터 변경
        function filterStatus(status) {
            currentStatus = status;

            // 버튼 활성화 상태 변경
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            loadWithdrawals();
        }

        // 승인 모달 열기
        function openApproveModal(withdrawalId) {
            currentWithdrawalId = withdrawalId;
            document.getElementById('approveModal').style.display = 'flex';
        }

        // 거부 모달 열기
        function openRejectModal(withdrawalId) {
            currentWithdrawalId = withdrawalId;
            document.getElementById('rejectModal').style.display = 'flex';
        }

        // 모달 닫기
        function closeModal() {
            document.getElementById('approveModal').style.display = 'none';
            document.getElementById('rejectModal').style.display = 'none';
            document.getElementById('txidInput').value = '';
            document.getElementById('reasonInput').value = '';
        }

        // 승인 확인
        async function confirmApprove() {
            const txid = document.getElementById('txidInput').value.trim();

            if (!txid) {
                alert('Transaction ID를 입력하세요.');
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/withdrawals.php?action=approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        withdrawal_id: currentWithdrawalId,
                        txid: txid
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ 출금이 승인되었습니다.');
                    closeModal();
                    loadWithdrawals();
                } else {
                    alert('❌ 오류: ' + data.message);
                }
            } catch (error) {
                alert('❌ 승인 처리에 실패했습니다.');
            }
        }

        // 거부 확인
        async function confirmReject() {
            const reason = document.getElementById('reasonInput').value.trim();

            if (!reason) {
                alert('거절 사유를 입력하세요.');
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/withdrawals.php?action=reject`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        withdrawal_id: currentWithdrawalId,
                        reason: reason
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ 출금이 거절되고 잔액이 복구되었습니다.');
                    closeModal();
                    loadWithdrawals();
                } else {
                    alert('❌ 오류: ' + data.message);
                }
            } catch (error) {
                alert('❌ 거절 처리에 실패했습니다.');
            }
        }

        // 초기 로드
        loadWithdrawals();

        // 엑셀 다운로드
        async function downloadExcel() {
            try {
                // 전체 출금 데이터 가져오기
                const response = await fetch('../api/withdrawals.php?action=list&limit=10000');
                const result = await response.json();

                if (!result.success) {
                    alert('데이터를 가져오는데 실패했습니다.');
                    return;
                }

                const withdrawals = result.withdrawals;

                // 필터링 적용
                let filteredData = withdrawals;
                if (currentStatus !== 'all') {
                    filteredData = withdrawals.filter(w => w.status === currentStatus);
                }

                // CSV 데이터 생성
                let csv = '\uFEFF'; // UTF-8 BOM
                csv += '회원ID,금액,실수령액,수수료,외상금액,잔액,지갑주소,상태,신청일,처리일,거절사유\n';

                filteredData.forEach(w => {
                    const debtAmount = parseFloat(w.debt_amount || 0);
                    const netAmount = parseFloat(w.net_amount || 0);
                    const balance = netAmount - debtAmount;

                    const row = [
                        w.login_id || w.user_id || '',
                        w.amount || '0',
                        w.net_amount || '0',
                        w.fee || (w.amount - w.net_amount) || '0',
                        debtAmount.toFixed(2),
                        balance.toFixed(2),
                        w.usdt_address || '',
                        w.status === 'pending' ? '대기중' : w.status === 'completed' ? '완료' : '거절됨',
                        w.created_at || '',
                        w.processed_at || '',
                        w.notes || ''
                    ];
                    csv += row.map(field => `"${field}"`).join(',') + '\n';
                });

                // 다운로드
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `출금내역_${new Date().toISOString().slice(0,10)}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (error) {
                console.error('Excel download error:', error);
                alert('엑셀 다운로드 중 오류가 발생했습니다.');
            }
        }
    </script>
    
</body>
</html>
