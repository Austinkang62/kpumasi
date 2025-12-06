<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>시스템 설정 - K-Pumasi Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            padding-top: 50px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-title {
            color: #f8fafc;
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .setting-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 8px 32px -8px rgba(0,0,0,0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .setting-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .setting-title {
            font-size: 1.1em;
            font-weight: 600;
            color: #f8fafc;
        }

        .setting-description {
            color: #94a3b8;
            font-size: 0.9em;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .setting-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 8px;
            margin-top: 10px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.active {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .status-badge.inactive {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #475569;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #10b981;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .slider:hover {
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.3);
        }

        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 30px;
            color: #93c5fd;
        }

        .warning-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            color: #fca5a5;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        .save-indicator {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 25px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
            font-weight: 600;
            display: none;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: #94a3b8;
        }

        .stat-value {
            color: #fbbf24;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h1 class="page-title">⚙️ 시스템 설정</h1>

        <div id="settingsContainer">
            <div class="loading">설정을 불러오는 중...</div>
        </div>

        <div class="info-box">
            💡 <strong>안내:</strong> 설정 변경은 즉시 적용됩니다. 변경 사항은 자동으로 저장됩니다.
        </div>
    </div>

    <div id="saveIndicator" class="save-indicator">
        ✅ 저장되었습니다
    </div>

    <script>
        const API_BASE = 'api';
        let settings = {};

        // 페이지 로드시 설정 불러오기
        window.onload = () => {
            checkAuth();
            loadSettings();
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

        // 설정 불러오기
        async function loadSettings() {
            try {
                const response = await fetch(`${API_BASE}/settings.php?action=get_all`);
                const data = await response.json();

                if (!data.success) {
                    alert('설정을 불러오지 못했습니다: ' + data.message);
                    return;
                }

                settings = data.settings;
                renderSettings();

            } catch (error) {
                console.error('Load settings error:', error);
                alert('설정 로드 오류: ' + error.message);
            }
        }

        // 설정 화면 렌더링
        function renderSettings() {
            const container = document.getElementById('settingsContainer');

            const registrationEnabled = settings.registration_enabled === '1';
            const maintenanceMode = settings.maintenance_mode === '1';

            let html = `
                <div class="settings-grid">
                    <!-- 회원가입 설정 -->
                    <div class="setting-card">
                        <div class="setting-header">
                            <div class="setting-title">👥 회원가입</div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="registration_enabled" ${registrationEnabled ? 'checked' : ''} onchange="updateSetting('registration_enabled', this.checked ? '1' : '0')">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="setting-description">
                            신규 회원가입을 허용할지 설정합니다. 비활성화하면 회원가입 페이지가 차단됩니다.
                        </div>
                        <div class="setting-status">
                            <span class="status-badge ${registrationEnabled ? 'active' : 'inactive'}">
                                ${registrationEnabled ? '✓ 활성화' : '✕ 비활성화'}
                            </span>
                            <span style="color: #94a3b8; font-size: 0.9em;">
                                ${registrationEnabled ? '회원가입이 가능합니다' : '회원가입이 차단됩니다'}
                            </span>
                        </div>
                        ${!registrationEnabled ? `
                            <div class="warning-box" style="margin-top: 15px;">
                                ⚠️ 현재 신규 회원가입이 차단된 상태입니다.
                            </div>
                        ` : ''}
                    </div>

                    <!-- 점검 모드 설정 -->
                    <div class="setting-card">
                        <div class="setting-header">
                            <div class="setting-title">🔧 점검 모드</div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="maintenance_mode" ${maintenanceMode ? 'checked' : ''} onchange="updateSetting('maintenance_mode', this.checked ? '1' : '0')">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="setting-description">
                            시스템 점검 중일 때 활성화합니다. 점검 모드에서는 일반 사용자의 접근이 제한됩니다.
                        </div>
                        <div class="setting-status">
                            <span class="status-badge ${maintenanceMode ? 'inactive' : 'active'}">
                                ${maintenanceMode ? '⚠️ 점검 중' : '✓ 정상 운영'}
                            </span>
                            <span style="color: #94a3b8; font-size: 0.9em;">
                                ${maintenanceMode ? '사용자 접근 제한됨' : '정상 운영 중'}
                            </span>
                        </div>
                        ${maintenanceMode ? `
                            <div class="warning-box" style="margin-top: 15px;">
                                ⚠️ 점검 모드가 활성화되어 있습니다. 관리자만 접근 가능합니다.
                            </div>
                        ` : ''}
                    </div>

                    <!-- 기타 설정 정보 -->
                    <div class="setting-card">
                        <div class="setting-title" style="margin-bottom: 15px;">📊 시스템 정보</div>
                        <div class="stat-item">
                            <span class="stat-label">최소 출금 금액</span>
                            <span class="stat-value">$${settings.min_withdrawal_amount || '50'}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">출금 수수료</span>
                            <span class="stat-value">${settings.withdrawal_fee_percent || '5'}%</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">출금 제한 기간</span>
                            <span class="stat-value">${settings.withdrawal_limit_days || '7'}일</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">USDT 시스템 주소</span>
                            <span class="stat-value" style="font-size: 0.75em; word-break: break-all;">
                                ${settings.usdt_system_address || '-'}
                            </span>
                        </div>
                    </div>

                    <!-- 사이트 정보 -->
                    <div class="setting-card">
                        <div class="setting-title" style="margin-bottom: 15px;">🌐 사이트 정보</div>
                        <div class="stat-item">
                            <span class="stat-label">사이트 이름</span>
                            <span class="stat-value">${settings.site_name || 'AI BTC BOT'}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">BTC 목표 (아바타)</span>
                            <span class="stat-value">${settings.btc_accumulation_target || '1'} BTC</span>
                        </div>
                    </div>
                </div>
            `;

            container.innerHTML = html;
        }

        // 설정 업데이트
        async function updateSetting(key, value) {
            try {
                const response = await fetch(`${API_BASE}/settings.php?action=update`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        setting_key: key,
                        setting_value: value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    settings[key] = value;
                    showSaveIndicator();

                    // 화면 갱신
                    renderSettings();
                } else {
                    alert('❌ 설정 업데이트 실패: ' + data.message);
                    // 실패시 원래 상태로 되돌리기
                    loadSettings();
                }

            } catch (error) {
                console.error('Update setting error:', error);
                alert('❌ 설정 업데이트 오류: ' + error.message);
                loadSettings();
            }
        }

        // 저장 알림 표시
        function showSaveIndicator() {
            const indicator = document.getElementById('saveIndicator');
            indicator.style.display = 'block';

            setTimeout(() => {
                indicator.style.display = 'none';
            }, 2000);
        }
    </script>
    
</body>
</html>
