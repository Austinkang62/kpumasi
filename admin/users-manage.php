<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원 관리 - K-Pumasi Admin</title>
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

        .search-box {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px -8px rgba(0,0,0,0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .search-box input {
            padding: 12px 16px;
            width: 350px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            margin-right: 10px;
            background: rgba(15, 23, 42, 0.5);
            color: #f8fafc;
            font-size: 0.95em;
        }

        .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box input::placeholder {
            color: #94a3b8;
        }

        .filter-buttons {
            display: inline-flex;
            gap: 8px;
            margin-left: 15px;
        }

        .btn-filter {
            padding: 10px 16px;
            border: 2px solid rgba(148, 163, 184, 0.3);
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.5);
            color: #94a3b8;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            border-color: #3b82f6;
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
        }

        .btn-filter.active {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .btn-filter.credit-sale.active {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        .badge-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75em;
            font-weight: 700;
            margin-left: 6px;
        }

        .badge-credit {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .badge-hold {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
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

        .badge.active { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .badge.inactive { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .badge.suspended { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }

        .btn-edit {
            padding: 6px 12px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-edit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-sales {
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

        .btn-sales:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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
            max-width: 600px;
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

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.5);
            color: #f8fafc;
            font-size: 0.95em;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group select option {
            background: #1e293b;
            color: #f8fafc;
        }

        .form-group .info {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 5px;
        }

        .btn-save {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-delete {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
            margin-top: 15px;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        .delete-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid rgba(239, 68, 68, 0.3);
        }

        .delete-warning {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            color: #f87171;
            font-size: 13px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        /* 추천코드 드롭다운 */
        .referral-dropdown {
            position: relative;
        }

        .referral-dropdown input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.5);
            color: #f8fafc;
        }

        .dropdown-list {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 200px;
            overflow-y: auto;
            background: #1e293b;
            border: 1px solid rgba(59, 130, 246, 0.4);
            border-top: none;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            z-index: 1000;
        }

        .dropdown-list.show {
            display: block;
        }

        .dropdown-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .dropdown-item:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item .user-id {
            font-weight: 600;
            color: #60a5fa;
        }

        .dropdown-item .user-info {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 2px;
        }

        .dropdown-list::-webkit-scrollbar {
            width: 8px;
        }

        .dropdown-list::-webkit-scrollbar-track {
            background: #0f172a;
        }

        .dropdown-list::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }

        .dropdown-list::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* 폼 그룹 섹션 */
        .form-section {
            background: rgba(15, 23, 42, 0.5);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
        }

        .form-section-title {
            font-size: 14px;
            font-weight: 700;
            color: #60a5fa;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-row-single {
            display: block;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* 외상매출/출금홀딩 상태 표시 */
        .account-status-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .status-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
        }

        .status-item.active {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .status-item.active .status-label {
            color: #dc3545;
            font-weight: 700;
        }

        .status-label {
            font-size: 14px;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-value {
            font-size: 18px;
            font-weight: 700;
            color: #212529;
        }

        .status-value.danger {
            color: #dc3545;
        }

        .status-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn-status {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-credit-sale {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }

        .btn-credit-sale:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
        }

        .btn-credit-sale.active {
            background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);
        }

        .btn-withdrawal-hold {
            background: linear-gradient(135deg, #ffa94d 0%, #fd7e14 100%);
            color: white;
        }

        .btn-withdrawal-hold:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 169, 77, 0.3);
        }

        .btn-withdrawal-hold.active {
            background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);
        }

        @media (max-width: 768px) {
            .status-grid,
            .status-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h1 class="page-title">👥 회원 관리</h1>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="회원 아이디, 이메일, 이름 검색..." />
            <button class="btn" onclick="searchUsers()">🔍 검색</button>
            <button class="btn" onclick="downloadExcel()" style="background: #10b981; border-color: #10b981;">📥 엑셀 다운로드</button>

            <div class="filter-buttons">
                <button class="btn-filter credit-sale" id="filterCreditSale" onclick="toggleFilter('credit_sale')">
                    💳 외상매출
                </button>
                <button class="btn-filter" id="filterWithdrawalHold" onclick="toggleFilter('withdrawal_hold')">
                    🔒 출금홀딩
                </button>
            </div>
        </div>

        <div class="content-section">
            <div id="usersList">
                <div class="loading">회원 목록을 불러오는 중...</div>
            </div>

            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <!-- 회원 수정 모달 -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>회원 정보 수정</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>

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
            <div style="margin: 20px 0;">
                <button type="button" class="btn-sales" style="width: 100%; padding: 14px; font-size: 16px; font-weight: 600;" id="modal_sales_btn" onclick="openSalesModalFromEdit()">
                    💰 매출 입력 및 보너스 지급
                </button>
            </div>

            <form id="editForm">
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

    <!-- 매출 입력 모달 -->
    <div id="salesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>💰 매출 입력 ($100)</h2>
                <span class="close" onclick="closeSalesModal()">&times;</span>
            </div>

            <form id="salesForm">
                <input type="hidden" id="sales_user_id" />

                <div class="form-section">
                    <div class="form-section-title">📋 매출 정보</div>

                    <div class="form-group">
                        <label>회원 코드</label>
                        <input type="text" id="sales_user_id_display" disabled style="background: rgba(59, 130, 246, 0.1); font-weight: 600;" />
                    </div>

                    <div class="form-group">
                        <label>회원명</label>
                        <input type="text" id="sales_user_name" disabled style="background: rgba(59, 130, 246, 0.1);" />
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>매출일자 *</label>
                            <input type="datetime-local" id="sales_date" required />
                            <div class="info">매출이 발생한 일시</div>
                        </div>
                        <div class="form-group">
                            <label>금액 (USDT) *</label>
                            <input type="number" id="sales_amount" value="100" step="0.01" required readonly style="background: rgba(16, 185, 129, 0.1); font-weight: 600; font-size: 18px;" />
                            <div class="info">$100 고정</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>TXID (Transaction ID)</label>
                        <input type="text" id="sales_txid" placeholder="트랜잭션 ID를 입력하세요" />
                        <div class="info">블록체인 트랜잭션 해시</div>
                    </div>

                    <div class="form-group">
                        <label>메모</label>
                        <textarea id="sales_memo" rows="3" placeholder="매출 관련 메모를 입력하세요"></textarea>
                    </div>

                    <div class="info" style="background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); padding: 12px; border-radius: 8px; margin-top: 15px;">
                        ⚠️ <strong>매출 입력 시 자동 처리:</strong><br>
                        • Sales 테이블에 기록<br>
                        • 4가지 보너스 자동 계산 및 지급 (Referral, Edge, Matching, Rollup)<br>
                        • Users 보너스 필드 자동 업데이트<br>
                        • Transactions 로그 기록
                    </div>
                </div>

                <button type="submit" id="salesSubmitBtn" class="btn-save">💾 매출 입력 및 보너스 지급</button>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = 'api';
        let currentPage = 1;
        let currentSearch = '';
        let currentFilter = ''; // 'credit_sale', 'withdrawal_hold', '' (전체)

        // 페이지 로드시 회원 목록 불러오기
        window.onload = () => {
            checkAuth();
            loadUsers(1);
        };

        // 인증 확인
        async function checkAuth() {
            try {
                const response = await fetch(`${API_BASE}/auth.php?action=check`);
                const data = await response.json();
                if (!data.logged_in) {
                    window.location.href = 'index.html';
                }
            } catch (error) {
                console.error('Auth check error:', error);
            }
        }

        // 회원 목록 불러오기
        async function loadUsers(page = 1) {
            currentPage = page;

            try {
                let url = `${API_BASE}/users-manage.php?action=list&page=${page}&limit=20&search=${currentSearch}`;
                if (currentFilter) {
                    url += `&filter=${currentFilter}`;
                }
                const response = await fetch(url);
                const data = await response.json();

                if (!data.success) {
                    alert('회원 목록을 불러오지 못했습니다: ' + data.message);
                    return;
                }

                renderUsersList(data.users);
                renderPagination(data.pagination);

            } catch (error) {
                console.error('Load users error:', error);
                alert('회원 목록 로드 오류: ' + error.message);
            }
        }

        // 회원 목록 렌더링
        function renderUsersList(users) {
            const listDiv = document.getElementById('usersList');

            if (users.length === 0) {
                listDiv.innerHTML = '<div class="loading">검색 결과가 없습니다.</div>';
                return;
            }

            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>회원 아이디</th>
                            <th>이름</th>
                            <th>이메일</th>
                            <th>후원인</th>
                            <th>위치</th>
                            <th>그룹코드</th>
                            <th>상태</th>
                            <th>가입일</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            users.forEach(user => {
                const statusClass = user.status === 'active' ? 'active' : (user.status === 'inactive' ? 'inactive' : 'suspended');
                const position = user.sponsor_position === 1 ? 'L' : (user.sponsor_position === 2 ? 'R' : '-');

                // 외상매출/출금홀딩 뱃지
                const creditSaleAmount = parseFloat(user.credit_sale_amount || 0);
                const withdrawalHold = parseInt(user.withdrawal_hold || 0);
                let statusBadges = '';

                if (creditSaleAmount > 0) {
                    statusBadges += `<span class="badge-status badge-credit">💳 외상</span>`;
                }
                if (withdrawalHold === 1) {
                    statusBadges += `<span class="badge-status badge-hold">🔒 홀딩</span>`;
                }

                html += `
                    <tr>
                        <td>${user.id}</td>
                        <td><strong>${user.user_id}</strong>${statusBadges}</td>
                        <td>${user.name || '-'}</td>
                        <td>${user.email || '-'}</td>
                        <td>${user.sponsor_id || '-'}</td>
                        <td>${position}</td>
                        <td>${user.account_group || '-'}</td>
                        <td><span class="badge ${statusClass}">${user.status}</span></td>
                        <td>${formatDate(user.created_at)}</td>
                        <td>
                            <button class="btn-edit" onclick="openEditModal('${user.user_id}')">✏️ 수정</button>
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

        // 페이지네이션 렌더링
        function renderPagination(pagination) {
            const paginationDiv = document.getElementById('pagination');
            let html = '';

            if (pagination.page > 1) {
                html += `<button onclick="loadUsers(${pagination.page - 1})">« 이전</button>`;
            }

            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.page) {
                    html += `<button class="active">${i}</button>`;
                } else if (Math.abs(i - pagination.page) <= 2 || i === 1 || i === pagination.total_pages) {
                    html += `<button onclick="loadUsers(${i})">${i}</button>`;
                } else if (Math.abs(i - pagination.page) === 3) {
                    html += `<button disabled>...</button>`;
                }
            }

            if (pagination.page < pagination.total_pages) {
                html += `<button onclick="loadUsers(${pagination.page + 1})">다음 »</button>`;
            }

            paginationDiv.innerHTML = html;
        }

        // 검색
        function searchUsers() {
            currentSearch = document.getElementById('searchInput').value.trim();
            loadUsers(1);
        }

        // 필터 토글
        function toggleFilter(filterType) {
            const creditSaleBtn = document.getElementById('filterCreditSale');
            const withdrawalHoldBtn = document.getElementById('filterWithdrawalHold');

            if (currentFilter === filterType) {
                // 같은 필터 클릭 시 해제
                currentFilter = '';
                creditSaleBtn.classList.remove('active');
                withdrawalHoldBtn.classList.remove('active');
            } else {
                // 다른 필터 선택
                currentFilter = filterType;

                if (filterType === 'credit_sale') {
                    creditSaleBtn.classList.add('active');
                    withdrawalHoldBtn.classList.remove('active');
                } else if (filterType === 'withdrawal_hold') {
                    creditSaleBtn.classList.remove('active');
                    withdrawalHoldBtn.classList.add('active');
                }
            }

            loadUsers(1);
        }

        // Enter 키로 검색
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchUsers();
            }
        });

        // 수정 모달 열기
        async function openEditModal(userId) {
            try {
                const response = await fetch(`${API_BASE}/users-manage.php?action=detail&user_id=${userId}`);
                const data = await response.json();

                if (!data.success) {
                    alert('회원 정보를 불러오지 못했습니다: ' + data.message);
                    return;
                }

                const user = data.user;

                // 폼에 데이터 채우기
                document.getElementById('edit_user_id').value = user.user_id;
                document.getElementById('edit_user_id_display').value = user.user_id;
                document.getElementById('edit_name').value = user.name || '';
                document.getElementById('edit_email').value = user.email || '';
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_phone').value = user.phone || '';
                document.getElementById('edit_usdt_address').value = user.usdt_address || '';
                document.getElementById('edit_bnb_address').value = user.bnb_address || '';
                document.getElementById('edit_sponsor_id').value = user.sponsor_id || '';
                document.getElementById('edit_sponsor_position').value = user.sponsor_position !== null ? user.sponsor_position : '';
                document.getElementById('edit_parent_account_id').value = data.parent_account ? data.parent_account.user_id : '';
                document.getElementById('edit_account_group').value = user.account_group || '';
                document.getElementById('edit_status').value = user.status || 'active';
                document.getElementById('edit_role').value = user.role || 'user';

                // 가입일자 설정 (datetime-local 형식으로 변환)
                if (user.created_at) {
                    const createdDate = new Date(user.created_at);
                    const year = createdDate.getFullYear();
                    const month = String(createdDate.getMonth() + 1).padStart(2, '0');
                    const day = String(createdDate.getDate()).padStart(2, '0');
                    const hours = String(createdDate.getHours()).padStart(2, '0');
                    const minutes = String(createdDate.getMinutes()).padStart(2, '0');
                    document.getElementById('edit_created_at').value = `${year}-${month}-${day}T${hours}:${minutes}`;
                }

                // 추천인 아이디 설정 - referral_user_id로 표시
                const referralUserId = data.referral_user_id || ''; // API에서 referral_id에 해당하는 user_id
                document.getElementById('edit_referral_code').value = referralUserId;
                document.getElementById('selected_referral_display').textContent = referralUserId || '-';
                document.getElementById('referral_search').value = referralUserId;

                // 외상매출/출금홀딩 상태 설정
                const creditSaleAmount = parseFloat(user.credit_sale_amount || 0);
                const withdrawalHold = parseInt(user.withdrawal_hold || 0);

                document.getElementById('current_credit_sale_amount').value = creditSaleAmount;
                document.getElementById('current_withdrawal_hold').value = withdrawalHold;

                // 외상매출 상태 표시
                const creditSaleStatusEl = document.getElementById('credit_sale_status');
                const creditSaleDisplayEl = document.getElementById('credit_sale_display');
                const creditSaleBtnEl = document.getElementById('btn_toggle_credit_sale');

                if (creditSaleAmount > 0) {
                    creditSaleStatusEl.classList.add('active');
                    creditSaleDisplayEl.classList.add('danger');
                    creditSaleDisplayEl.textContent = `$${creditSaleAmount.toFixed(2)}`;
                    creditSaleBtnEl.classList.add('active');
                    creditSaleBtnEl.querySelector('span').textContent = '✅ 외상매출 해제';
                } else {
                    creditSaleStatusEl.classList.remove('active');
                    creditSaleDisplayEl.classList.remove('danger');
                    creditSaleDisplayEl.textContent = '없음';
                    creditSaleBtnEl.classList.remove('active');
                    creditSaleBtnEl.querySelector('span').textContent = '💳 외상매출 지정';
                }

                // 출금홀딩 상태 표시
                const withdrawalHoldStatusEl = document.getElementById('withdrawal_hold_status');
                const withdrawalHoldDisplayEl = document.getElementById('withdrawal_hold_display');
                const withdrawalHoldBtnEl = document.getElementById('btn_toggle_withdrawal_hold');

                if (withdrawalHold === 1) {
                    withdrawalHoldStatusEl.classList.add('active');
                    withdrawalHoldDisplayEl.classList.add('danger');
                    withdrawalHoldDisplayEl.textContent = '홀딩 중';
                    withdrawalHoldBtnEl.classList.add('active');
                    withdrawalHoldBtnEl.querySelector('span').textContent = '✅ 출금홀딩 해제';
                } else {
                    withdrawalHoldStatusEl.classList.remove('active');
                    withdrawalHoldDisplayEl.classList.remove('danger');
                    withdrawalHoldDisplayEl.textContent = '해제';
                    withdrawalHoldBtnEl.classList.remove('active');
                    withdrawalHoldBtnEl.querySelector('span').textContent = '🔒 출금홀딩 지정';
                }

                // 모달 표시
                document.getElementById('editModal').style.display = 'block';

                // 추천코드 목록 로드
                loadReferralCodes();

            } catch (error) {
                console.error('Load user detail error:', error);
                alert('회원 정보 로드 오류: ' + error.message);
            }
        }

        // 모달 닫기
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // 외상매출 토글
        async function toggleCreditSale() {
            const userId = document.getElementById('edit_user_id').value;
            const currentAmount = parseFloat(document.getElementById('current_credit_sale_amount').value || 0);

            if (currentAmount > 0) {
                // 해제
                if (!confirm('외상매출을 해제하시겠습니까?')) {
                    return;
                }

                try {
                    const response = await fetch(`${API_BASE}/account-status.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'set_credit_sale',
                            user_id: userId,
                            amount: 0
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('✅ 외상매출이 해제되었습니다.');
                        openEditModal(userId); // 새로고침
                    } else {
                        alert('❌ 실패: ' + data.message);
                    }
                } catch (error) {
                    console.error('Toggle credit sale error:', error);
                    alert('❌ 오류: ' + error.message);
                }
            } else {
                // 지정
                const amount = prompt('외상매출 금액을 입력하세요 (기본: $100):', '100');
                if (!amount || amount === '') {
                    return;
                }

                const parsedAmount = parseFloat(amount);
                if (isNaN(parsedAmount) || parsedAmount <= 0) {
                    alert('❌ 올바른 금액을 입력하세요.');
                    return;
                }

                try {
                    const response = await fetch(`${API_BASE}/account-status.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'set_credit_sale',
                            user_id: userId,
                            amount: parsedAmount
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(`✅ 외상매출 $${parsedAmount}이 지정되었습니다.\n\n출금 시 이 금액을 우선 차감합니다.`);
                        openEditModal(userId); // 새로고침
                    } else {
                        alert('❌ 실패: ' + data.message);
                    }
                } catch (error) {
                    console.error('Toggle credit sale error:', error);
                    alert('❌ 오류: ' + error.message);
                }
            }
        }

        // 출금홀딩 토글
        async function toggleWithdrawalHold() {
            const userId = document.getElementById('edit_user_id').value;
            const currentHold = parseInt(document.getElementById('current_withdrawal_hold').value || 0);

            const newHold = currentHold === 1 ? 0 : 1;
            const actionText = newHold === 1 ? '지정' : '해제';

            if (!confirm(`출금홀딩을 ${actionText}하시겠습니까?${newHold === 1 ? '\n\n지정 시 해당 회원은 출금 신청을 할 수 없습니다.' : ''}`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/account-status.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'set_withdrawal_hold',
                        user_id: userId,
                        hold: newHold
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ 출금홀딩이 ${actionText}되었습니다.`);
                    openEditModal(userId); // 새로고침
                } else {
                    alert('❌ 실패: ' + data.message);
                }
            } catch (error) {
                console.error('Toggle withdrawal hold error:', error);
                alert('❌ 오류: ' + error.message);
            }
        }

        // 폼 제출
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = {
                user_id: document.getElementById('edit_user_id').value,
                name: document.getElementById('edit_name').value,
                email: document.getElementById('edit_email').value,
                password: document.getElementById('edit_password').value,
                phone: document.getElementById('edit_phone').value,
                usdt_address: document.getElementById('edit_usdt_address').value,
                referral_user_id: document.getElementById('edit_referral_code').value, // user_id 형태로 전송
                bnb_address: document.getElementById('edit_bnb_address').value,
                sponsor_id: document.getElementById('edit_sponsor_id').value,
                sponsor_position: document.getElementById('edit_sponsor_position').value,
                parent_account_id: document.getElementById('edit_parent_account_id').value,
                account_group: document.getElementById('edit_account_group').value,
                status: document.getElementById('edit_status').value,
                role: document.getElementById('edit_role').value,
                created_at: document.getElementById('edit_created_at').value
            };

            try {
                const response = await fetch(`${API_BASE}/users-manage.php?action=update`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ 회원 정보가 수정되었습니다!');
                    closeModal();
                    loadUsers(currentPage);
                } else {
                    alert('❌ 수정 실패: ' + data.message);
                }

            } catch (error) {
                console.error('Update user error:', error);
                alert('❌ 수정 오류: ' + error.message);
            }
        });

        // 날짜 포맷팅
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('ko-KR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        }

        // 추천코드 목록 저장용
        let allReferralCodes = [];

        // 추천코드 목록 로드
        async function loadReferralCodes() {
            try {
                const response = await fetch(`${API_BASE}/users-manage.php?action=list&page=1&limit=1000`);
                const data = await response.json();

                if (data.success && data.users) {
                    allReferralCodes = data.users.map(user => ({
                        user_id: user.user_id,
                        name: user.name || '',
                        email: user.email || ''
                    }));
                }
            } catch (error) {
                console.error('Load referral codes error:', error);
            }
        }

        // 추천코드 검색 입력 이벤트
        document.getElementById('referral_search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            const dropdownList = document.getElementById('referral_dropdown_list');

            if (searchTerm === '') {
                dropdownList.classList.remove('show');
                return;
            }

            const filtered = allReferralCodes.filter(user =>
                user.user_id.toLowerCase().includes(searchTerm) ||
                (user.name && user.name.toLowerCase().includes(searchTerm)) ||
                (user.email && user.email.toLowerCase().includes(searchTerm))
            );

            renderReferralDropdown(filtered);
        });

        // 추천코드 검색 포커스 이벤트
        document.getElementById('referral_search').addEventListener('focus', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            if (searchTerm !== '') {
                const filtered = allReferralCodes.filter(user =>
                    user.user_id.toLowerCase().includes(searchTerm) ||
                    (user.name && user.name.toLowerCase().includes(searchTerm)) ||
                    (user.email && user.email.toLowerCase().includes(searchTerm))
                );
                renderReferralDropdown(filtered);
            }
        });

        // 드롭다운 렌더링
        function renderReferralDropdown(users) {
            const dropdownList = document.getElementById('referral_dropdown_list');

            if (users.length === 0) {
                dropdownList.innerHTML = '<div class="dropdown-item" style="color: #999;">검색 결과가 없습니다.</div>';
                dropdownList.classList.add('show');
                return;
            }

            let html = '';
            users.slice(0, 50).forEach(user => {
                html += `
                    <div class="dropdown-item" onclick="selectReferralCode('${user.user_id}')">
                        <div class="user-id">${user.user_id}</div>
                        <div class="user-info">${user.name || '-'} | ${user.email || '-'}</div>
                    </div>
                `;
            });

            dropdownList.innerHTML = html;
            dropdownList.classList.add('show');
        }

        // 추천코드 선택
        function selectReferralCode(userId) {
            document.getElementById('edit_referral_code').value = userId;
            document.getElementById('selected_referral_display').textContent = userId;
            document.getElementById('referral_search').value = userId;
            document.getElementById('referral_dropdown_list').classList.remove('show');
        }

        // 드롭다운 외부 클릭시 닫기
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.referral-dropdown');
            const dropdownList = document.getElementById('referral_dropdown_list');

            if (dropdown && !dropdown.contains(event.target)) {
                dropdownList.classList.remove('show');
            }
        });

        // 모달 외부 클릭시 닫기
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeModal();
            }
        };

        // 회원 삭제
        async function deleteUser() {
            const userId = document.getElementById('edit_user_id').value;
            const userIdDisplay = document.getElementById('edit_user_id_display').value;

            if (!userId) {
                alert('회원 정보를 찾을 수 없습니다.');
                return;
            }

            const deleteReason = prompt(`회원 [${userIdDisplay}]을(를) 삭제합니다.\n\n삭제 사유를 입력하세요:`);

            if (deleteReason === null) {
                return; // 취소
            }

            if (!deleteReason.trim()) {
                alert('삭제 사유를 입력해주세요.');
                return;
            }

            if (!confirm(`정말로 회원 [${userIdDisplay}]을(를) 삭제하시겠습니까?\n\n삭제 사유: ${deleteReason}\n\n이 작업은 회원을 목록에서 제외합니다.`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/users-manage.php?action=delete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: userIdDisplay,
                        delete_reason: deleteReason
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ 회원 [${userIdDisplay}]이(가) 삭제되었습니다.`);
                    closeModal();
                    loadUsers(currentPage);
                } else {
                    alert('❌ 삭제 실패: ' + data.message);
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('❌ 삭제 오류: ' + error.message);
            }
        }

        // 매출 모달 열기 (수정 팝업에서)
        function openSalesModalFromEdit() {
            const userId = document.getElementById('edit_user_id').value;
            const userName = document.getElementById('edit_name').value || userId;
            openSalesModal(userId, userName);
        }

        // 매출 모달 열기
        function openSalesModal(userId, userName) {
            document.getElementById('sales_user_id').value = userId;
            document.getElementById('sales_user_id_display').value = userId;
            document.getElementById('sales_user_name').value = userName;

            // 현재 날짜 및 시간을 기본값으로 설정
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('sales_date').value = `${year}-${month}-${day}T${hours}:${minutes}`;

            // 폼 초기화
            document.getElementById('sales_txid').value = '';
            document.getElementById('sales_memo').value = '';

            // 모달 표시
            document.getElementById('salesModal').style.display = 'block';
        }

        // 매출 모달 닫기
        function closeSalesModal() {
            document.getElementById('salesModal').style.display = 'none';
        }

        // 매출 폼 제출
        document.getElementById('salesForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // 중복 클릭 방지: 버튼 가져오기
            const submitBtn = document.getElementById('salesSubmitBtn');

            // 이미 처리 중이면 무시
            if (submitBtn.disabled) {
                return;
            }

            const formData = {
                user_id: document.getElementById('sales_user_id').value,
                amount: parseFloat(document.getElementById('sales_amount').value),
                sales_date: document.getElementById('sales_date').value,
                txid: document.getElementById('sales_txid').value.trim(),
                memo: document.getElementById('sales_memo').value.trim()
            };

            // 확인
            if (!confirm(`💰 매출 입력을 진행하시겠습니까?\n\n회원: ${document.getElementById('sales_user_id_display').value}\n금액: $${formData.amount}\n\n보너스가 자동으로 계산 및 지급됩니다.`)) {
                return;
            }

            // 버튼 비활성화 및 텍스트 변경
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ 처리 중...';
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';

            try {
                const response = await fetch(`${API_BASE}/sales-input.php?action=create`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ 매출 입력 완료!\n\n• Sales ID: ${data.sale_id}\n• 지급된 보너스: ${data.bonus_count}건\n• 총 보너스 금액: $${data.total_bonus_amount}\n\n보너스가 성공적으로 지급되었습니다.`);
                    closeSalesModal();
                    loadUsers(currentPage);
                } else {
                    alert('❌ 매출 입력 실패: ' + data.message);
                }

            } catch (error) {
                console.error('Sales input error:', error);
                alert('❌ 매출 입력 오류: ' + error.message);
            } finally {
                // 버튼 다시 활성화
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }
        });

        // 매출 모달 외부 클릭시 닫기
        window.addEventListener('click', function(event) {
            const salesModal = document.getElementById('salesModal');
            if (event.target === salesModal) {
                closeSalesModal();
            }
        });

        // 엑셀 다운로드
        async function downloadExcel() {
            try {
                // 전체 사용자 데이터 가져오기 (페이징 없이)
                const search = document.getElementById('searchInput').value;
                const filter = currentFilter;

                let url = 'api/users-manage.php?action=list&limit=10000'; // 충분히 큰 limit
                if (search) url += `&search=${encodeURIComponent(search)}`;
                if (filter) url += `&filter=${filter}`;

                const response = await fetch(url);
                const result = await response.json();

                if (!result.success) {
                    alert('데이터를 가져오는데 실패했습니다.');
                    return;
                }

                const users = result.users;

                // CSV 데이터 생성
                let csv = '\uFEFF'; // UTF-8 BOM
                csv += '아이디,이름,이메일,전화번호,USDT주소,후원인,패키지,총보너스,가용보너스,외상매출,출금홀딩,가입일\n';

                users.forEach(user => {
                    const row = [
                        user.user_id || '',
                        user.name || '',
                        user.email || '',
                        user.phone || '',
                        user.usdt_address || '',
                        user.sponsor_id || '',
                        user.package_id || '',
                        user.total_bonus || '0',
                        user.available_bonus || '0',
                        user.credit_sale_amount || '0',
                        user.withdrawal_hold == 1 ? 'Yes' : 'No',
                        user.created_at || ''
                    ];
                    csv += row.map(field => `"${field}"`).join(',') + '\n';
                });

                // 다운로드
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url2 = URL.createObjectURL(blob);
                link.setAttribute('href', url2);
                link.setAttribute('download', `회원목록_${new Date().toISOString().slice(0,10)}.csv`);
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
