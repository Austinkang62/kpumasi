<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원 상세 정보 - K-Pumasi Admin</title>
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
            gap: 10px;
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

        .page-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px -8px rgba(0,0,0,0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .page-header h1 {
            color: #f8fafc;
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-header .user-id {
            color: #94a3b8;
            font-size: 1.1em;
        }

        .content-section {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 32px -8px rgba(0,0,0,0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
            margin-bottom: 20px;
        }

        .account-status-section {
            padding: 20px;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .status-item {
            padding: 15px;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .status-label {
            color: #94a3b8;
            font-size: 0.85em;
            margin-bottom: 8px;
        }

        .status-value {
            color: #f8fafc;
            font-size: 1.1em;
            font-weight: 600;
        }

        .status-value.active {
            color: #fca5a5;
        }

        .status-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn-status {
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-credit-sale {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .btn-withdrawal-hold {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-status:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .btn-sales {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin: 20px 0;
        }

        .btn-sales:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(15, 23, 42, 0.3);
            border-radius: 12px;
        }

        .form-section-title {
            color: #f8fafc;
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(148, 163, 184, 0.2);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group label {
            display: block;
            color: #94a3b8;
            font-size: 0.9em;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            color: #f8fafc;
            font-size: 1em;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input:disabled {
            background: rgba(30, 41, 59, 0.5);
            color: #64748b;
            cursor: not-allowed;
        }

        .info {
            color: #64748b;
            font-size: 0.85em;
            margin-top: 5px;
        }

        .btn-save {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px -5px rgba(59, 130, 246, 0.4);
        }

        .delete-section {
            margin-top: 40px;
            padding: 20px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
        }

        .delete-warning {
            color: #fca5a5;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .btn-delete {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
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

        .referral-dropdown {
            position: relative;
        }

        .dropdown-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #1e293b;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .dropdown-list.show {
            display: block;
        }

        .dropdown-item {
            padding: 10px 15px;
            color: #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background: rgba(59, 130, 246, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-nav">
            <div style="display: flex; gap: 10px;">
                <a href="users-manage.html" class="back-button">← 회원 목록</a>
                <a href="index.php" class="back-button">← 대시보드</a>
            </div>
            <div class="nav-buttons">
                <a href="#" id="nav_user_detail" class="nav-button active">회원</a>
                <a href="#" id="nav_bonus_received" class="nav-button">받은 보너스</a>
                <a href="#" id="nav_bonus_given" class="nav-button">발생한 보너스</a>
            </div>
        </div>

        <div class="page-header">
            <h1>회원 상세 정보</h1>
            <p class="user-id" id="page_user_id">로딩 중...</p>
        </div>

        <div id="loading" class="loading">회원 정보를 불러오는 중...</div>
        <div id="error" class="error-message" style="display: none;"></div>

        <div id="content" style="display: none;">
            <div class="content-section">
                <!-- 외상매출/출금홀딩 상태 표시 -->
                <div class="account-status-section">
                    <div class="status-grid">
                        <div class="status-item" id="credit_sale_status">
                            <div class="status-label">💳 외상매출</div>
                            <div class="status-value" id="credit_sale_display">없음</div>
                        </div>
                        <div class="status-item" id="withdrawal_hold_status">
                            <div class="status-label">🔒 출금홀딩</div>
                            <div class="status-value" id="withdrawal_hold_display">해제</div>
                        </div>
                    </div>
                    <div class="status-buttons">
                        <button type="button" class="btn-status btn-credit-sale" id="btn_toggle_credit_sale" onclick="toggleCreditSale()">
                            <span>💳 외상매출 지정</span>
                        </button>
                        <button type="button" class="btn-status btn-withdrawal-hold" id="btn_toggle_withdrawal_hold" onclick="toggleWithdrawalHold()">
                            <span>🔒 출금홀딩 지정</span>
                        </button>
                    </div>
                </div>

                <!-- 매출 입력 버튼 -->
                <button type="button" class="btn-sales" onclick="openSalesModal()">
                    💰 매출 입력 및 보너스 지급
                </button>

                <form id="editForm" onsubmit="saveUser(event)">
                    <input type="hidden" id="edit_user_id" />
                    <input type="hidden" id="current_credit_sale_amount" value="0" />
                    <input type="hidden" id="current_withdrawal_hold" value="0" />

                    <!-- 기본 정보 -->
                    <div class="form-section">
                        <div class="form-section-title">📋 기본 정보</div>
                        <div class="form-group">
                            <label>회원 아이디</label>
                            <input type="text" id="edit_user_id_display" disabled />
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>이름</label>
                                <input type="text" id="edit_name" />
                            </div>
                            <div class="form-group">
                                <label>전화번호</label>
                                <input type="text" id="edit_phone" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>이메일</label>
                            <input type="email" id="edit_email" />
                        </div>
                        <div class="form-group">
                            <label>비밀번호 (변경시에만 입력)</label>
                            <input type="password" id="edit_password" placeholder="변경하지 않으려면 비워두세요" />
                            <div class="info">최소 8자 이상</div>
                        </div>
                        <div class="form-group">
                            <label>회원가입일자</label>
                            <input type="datetime-local" id="edit_created_at" />
                            <div class="info">회원가입 일시를 수정할 수 있습니다</div>
                        </div>
                    </div>

                    <!-- 추천인 & 후원인 정보 -->
                    <div class="form-section">
                        <div class="form-section-title">🔗 추천인 & 후원인 정보</div>
                        <div class="form-group">
                            <label>추천인 아이디 (referral_id)</label>
                            <div class="referral-dropdown">
                                <input type="text" id="referral_search" placeholder="추천인 아이디 검색..." autocomplete="off" />
                                <div id="referral_dropdown_list" class="dropdown-list"></div>
                            </div>
                            <input type="hidden" id="edit_referral_code" />
                            <div class="info">선택된 추천인: <span id="selected_referral_display">-</span></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>후원인 아이디 (sponsor_id)</label>
                                <input type="text" id="edit_sponsor_id" />
                                <div class="info">이진트리 상위 배치 노드</div>
                            </div>
                            <div class="form-group">
                                <label>후원 위치 (sponsor_position)</label>
                                <select id="edit_sponsor_position">
                                    <option value="">선택</option>
                                    <option value="1">왼쪽 (1-L)</option>
                                    <option value="2">오른쪽 (2-R)</option>
                                </select>
                                <div class="info">이진트리에서 좌/우 위치</div>
                            </div>
                        </div>
                    </div>

                    <!-- 지갑 주소 정보 -->
                    <div class="form-section">
                        <div class="form-section-title">💰 지갑 주소</div>
                        <div class="form-group">
                            <label>USDT 주소 (TRC20)</label>
                            <input type="text" id="edit_usdt_address" placeholder="TRC20 USDT 지갑 주소" />
                        </div>
                        <div class="form-group">
                            <label>BNB 주소</label>
                            <input type="text" id="edit_bnb_address" placeholder="BNB 지갑 주소" />
                        </div>
                    </div>

                    <!-- 그룹 계정 정보 -->
                    <div class="form-section">
                        <div class="form-section-title">👥 그룹 계정 정보</div>
                        <div class="form-group">
                            <label>그룹 대표자 아이디 (parent_account_id)</label>
                            <input type="text" id="edit_parent_account_id" placeholder="비워두면 본인이 대표자" />
                            <div class="info">복수 계정 그룹의 대표 계정 아이디</div>
                        </div>
                        <div class="form-group">
                            <label>그룹 코드 (account_group)</label>
                            <input type="text" id="edit_account_group" />
                            <div class="info">같은 그룹의 계정들이 공유하는 코드</div>
                        </div>
                    </div>

                    <!-- 계정 상태 & 권한 -->
                    <div class="form-section">
                        <div class="form-section-title">⚙️ 계정 상태 & 권한</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>상태 (status)</label>
                                <select id="edit_status">
                                    <option value="active">활성 (active)</option>
                                    <option value="inactive">비활성 (inactive)</option>
                                    <option value="suspended">정지 (suspended)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>역할 (role)</label>
                                <select id="edit_role">
                                    <option value="user">일반 회원 (user)</option>
                                    <option value="admin">관리자 (admin)</option>
                                    <option value="super">슈퍼 관리자 (super)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-save">💾 저장</button>

                    <!-- 회원 삭제 섹션 -->
                    <div class="delete-section">
                        <div class="delete-warning">
                            ⚠️ <strong>주의:</strong> 회원을 삭제하면 목록에서 제외됩니다. 삭제된 회원은 복원할 수 있습니다.
                        </div>
                        <button type="button" class="btn-delete" onclick="deleteUser()">🗑️ 회원 삭제</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // URL에서 user_id 파라미터 가져오기
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('user_id');

        if (!userId) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('error').textContent = '회원 아이디가 지정되지 않았습니다.';
            document.getElementById('error').style.display = 'block';
        } else {
            loadUserData(userId);
        }

        async function loadUserData(userId) {
            try {
                const response = await fetch(`api/users-manage.php?action=detail&user_id=${userId}`);
                const data = await response.json();

                if (data.success && data.user) {
                    const user = data.user;

                    // 페이지 헤더 업데이트
                    document.getElementById('page_user_id').textContent = user.user_id + ' (' + user.name + ')';

                    // 네비게이션 버튼 링크 설정
                    document.getElementById('nav_user_detail').href = `user-detail.php?user_id=${user.user_id}`;
                    document.getElementById('nav_bonus_received').href = `user-bonus-received.php?user_id=${user.user_id}`;
                    document.getElementById('nav_bonus_given').href = `user-bonus-given.php?user_id=${user.user_id}`;

                    // 폼 필드 채우기
                    document.getElementById('edit_user_id').value = user.user_id;
                    document.getElementById('edit_user_id_display').value = user.user_id;
                    document.getElementById('edit_name').value = user.name || '';
                    document.getElementById('edit_phone').value = user.phone || '';
                    document.getElementById('edit_email').value = user.email || '';

                    // 추천인 정보 - API에서 referral_user_id로 제공
                    const referralUserId = data.referral_user_id || '';
                    document.getElementById('edit_referral_code').value = referralUserId;
                    if (referralUserId) {
                        document.getElementById('referral_search').value = referralUserId;
                    }

                    document.getElementById('edit_sponsor_id').value = user.sponsor_id || '';
                    document.getElementById('edit_sponsor_position').value = user.sponsor_position || '';
                    document.getElementById('edit_usdt_address').value = user.usdt_address || '';
                    document.getElementById('edit_bnb_address').value = user.bnb_address || '';
                    document.getElementById('edit_parent_account_id').value = user.parent_account_id || '';
                    document.getElementById('edit_account_group').value = user.account_group || '';
                    document.getElementById('edit_status').value = user.status || 'active';
                    document.getElementById('edit_role').value = user.role || 'user';

                    // 회원가입일 설정
                    if (user.created_at) {
                        const date = new Date(user.created_at);
                        const localDatetime = new Date(date.getTime() - (date.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
                        document.getElementById('edit_created_at').value = localDatetime;
                    }

                    // 추천인 표시
                    if (data.referrer) {
                        document.getElementById('selected_referral_display').textContent = data.referral_user_id + ' (' + data.referrer.name + ')';
                    }

                    // 외상매출/출금홀딩 상태
                    updateCreditSaleStatus(user.credit_sale_amount || 0);
                    updateWithdrawalHoldStatus(user.withdrawal_hold || 0);

                    // 로딩 숨기고 콘텐츠 표시
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('content').style.display = 'block';
                } else {
                    throw new Error(data.error || '회원 정보를 불러올 수 없습니다.');
                }
            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error').textContent = '오류: ' + error.message;
                document.getElementById('error').style.display = 'block';
            }
        }

        function updateCreditSaleStatus(amount) {
            document.getElementById('current_credit_sale_amount').value = amount;
            const display = document.getElementById('credit_sale_display');
            const statusItem = document.getElementById('credit_sale_status');
            const btn = document.getElementById('btn_toggle_credit_sale');

            if (amount > 0) {
                display.textContent = '$' + amount.toFixed(2);
                display.classList.add('active');
                statusItem.style.borderColor = '#f59e0b';
                btn.querySelector('span').textContent = '💳 외상매출 해제';
            } else {
                display.textContent = '없음';
                display.classList.remove('active');
                statusItem.style.borderColor = 'transparent';
                btn.querySelector('span').textContent = '💳 외상매출 지정';
            }
        }

        function updateWithdrawalHoldStatus(hold) {
            document.getElementById('current_withdrawal_hold').value = hold;
            const display = document.getElementById('withdrawal_hold_display');
            const statusItem = document.getElementById('withdrawal_hold_status');
            const btn = document.getElementById('btn_toggle_withdrawal_hold');

            if (hold == 1) {
                display.textContent = '활성';
                display.classList.add('active');
                statusItem.style.borderColor = '#ef4444';
                btn.querySelector('span').textContent = '🔒 출금홀딩 해제';
            } else {
                display.textContent = '해제';
                display.classList.remove('active');
                statusItem.style.borderColor = 'transparent';
                btn.querySelector('span').textContent = '🔒 출금홀딩 지정';
            }
        }

        async function saveUser(event) {
            event.preventDefault();

            const formData = {
                user_id: document.getElementById('edit_user_id').value,
                name: document.getElementById('edit_name').value,
                phone: document.getElementById('edit_phone').value,
                email: document.getElementById('edit_email').value,
                password: document.getElementById('edit_password').value,
                referral_user_id: document.getElementById('edit_referral_code').value,
                sponsor_id: document.getElementById('edit_sponsor_id').value,
                sponsor_position: document.getElementById('edit_sponsor_position').value,
                usdt_address: document.getElementById('edit_usdt_address').value,
                bnb_address: document.getElementById('edit_bnb_address').value,
                parent_account_id: document.getElementById('edit_parent_account_id').value,
                account_group: document.getElementById('edit_account_group').value,
                status: document.getElementById('edit_status').value,
                role: document.getElementById('edit_role').value,
                created_at: document.getElementById('edit_created_at').value
            };

            try {
                const response = await fetch('api/users-manage.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    alert('회원 정보가 저장되었습니다.');
                    location.reload();
                } else {
                    alert('오류: ' + data.error);
                }
            } catch (error) {
                alert('저장 중 오류가 발생했습니다: ' + error.message);
            }
        }

        async function toggleCreditSale() {
            const userId = document.getElementById('edit_user_id').value;
            const currentAmount = parseFloat(document.getElementById('current_credit_sale_amount').value);

            if (currentAmount > 0) {
                // 해제
                if (!confirm('외상매출을 해제하시겠습니까?')) return;

                try {
                    const response = await fetch('api/users-manage.php?action=update', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: userId,
                            credit_sale_amount: 0
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        alert('외상매출이 해제되었습니다.');
                        updateCreditSaleStatus(0);
                    } else {
                        alert('오류: ' + data.error);
                    }
                } catch (error) {
                    alert('오류: ' + error.message);
                }
            } else {
                // 지정
                const amount = prompt('외상매출 금액을 입력하세요:');
                if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
                    alert('유효한 금액을 입력해주세요.');
                    return;
                }

                try {
                    const response = await fetch('api/users-manage.php?action=update', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: userId,
                            credit_sale_amount: parseFloat(amount)
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        alert('외상매출이 지정되었습니다.');
                        updateCreditSaleStatus(parseFloat(amount));
                    } else {
                        alert('오류: ' + data.error);
                    }
                } catch (error) {
                    alert('오류: ' + error.message);
                }
            }
        }

        async function toggleWithdrawalHold() {
            const userId = document.getElementById('edit_user_id').value;
            const currentHold = parseInt(document.getElementById('current_withdrawal_hold').value);

            const newHold = currentHold === 1 ? 0 : 1;
            const action = newHold === 1 ? '지정' : '해제';

            if (!confirm(`출금홀딩을 ${action}하시겠습니까?`)) return;

            try {
                const response = await fetch('api/users-manage.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: userId,
                        withdrawal_hold: newHold
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert(`출금홀딩이 ${action}되었습니다.`);
                    updateWithdrawalHoldStatus(newHold);
                } else {
                    alert('오류: ' + data.error);
                }
            } catch (error) {
                alert('오류: ' + error.message);
            }
        }

        async function deleteUser() {
            if (!confirm('정말로 이 회원을 삭제하시겠습니까?')) return;

            const userId = document.getElementById('edit_user_id').value;

            try {
                const response = await fetch('api/users-manage.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: userId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert('회원이 삭제되었습니다.');
                    window.location.href = 'users-manage.html';
                } else {
                    alert('오류: ' + data.error);
                }
            } catch (error) {
                alert('오류: ' + error.message);
            }
        }

        function openSalesModal() {
            const userId = document.getElementById('edit_user_id').value;
            const userName = document.getElementById('edit_name').value;
            // 매출 입력 페이지로 이동하거나 별도 처리
            alert('매출 입력 기능은 별도 페이지에서 구현됩니다.');
        }

        // 추천인 검색 (간단한 구현)
        document.getElementById('referral_search').addEventListener('input', async function() {
            const search = this.value.trim();
            if (search.length < 2) {
                document.getElementById('referral_dropdown_list').classList.remove('show');
                return;
            }

            try {
                const response = await fetch(`api/users-manage.php?action=search_users&q=${encodeURIComponent(search)}`);
                const data = await response.json();

                if (data.success && data.users.length > 0) {
                    const dropdown = document.getElementById('referral_dropdown_list');
                    dropdown.innerHTML = data.users.map(u =>
                        `<div class="dropdown-item" onclick="selectReferral('${u.user_id}', '${u.name}')">${u.user_id} (${u.name})</div>`
                    ).join('');
                    dropdown.classList.add('show');
                } else {
                    document.getElementById('referral_dropdown_list').classList.remove('show');
                }
            } catch (error) {
                console.error('검색 오류:', error);
            }
        });

        function selectReferral(userId, name) {
            document.getElementById('edit_referral_code').value = userId;
            document.getElementById('referral_search').value = userId;
            document.getElementById('selected_referral_display').textContent = userId + ' (' + name + ')';
            document.getElementById('referral_dropdown_list').classList.remove('show');
        }

        // 드롭다운 외부 클릭 시 닫기
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.referral-dropdown')) {
                document.getElementById('referral_dropdown_list').classList.remove('show');
            }
        });
    </script>
</body>
</html>
