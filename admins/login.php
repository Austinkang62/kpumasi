<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login - K-Pumasi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            padding: 60px;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo h1 {
            color: #667eea;
            font-size: 2.2em;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .logo p {
            color: #6b7280;
            font-size: 0.95em;
        }

        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 700;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 0.9em;
        }

        .form-group input {
            width: 100%;
            padding: 16px;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1em;
            color: #1f2937;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.05em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
            font-size: 0.9em;
            border-left: 4px solid #dc2626;
        }

        .info-box {
            background: #eff6ff;
            color: #1e40af;
            padding: 15px;
            border-radius: 8px;
            margin-top: 25px;
            font-size: 0.85em;
            border-left: 4px solid #3b82f6;
        }

        .info-box strong {
            display: block;
            margin-bottom: 8px;
            color: #1e40af;
        }

        .divider {
            text-align: center;
            color: #9ca3af;
            margin: 30px 0;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e5e7eb;
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .link-admin {
            text-align: center;
            margin-top: 20px;
        }

        .link-admin a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
        }

        .link-admin a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🔐 Super Admin</h1>
            <p>K-Pumasi 최고 관리자 로그인</p>
            <span class="badge">Super Admin Only</span>
        </div>

        <form id="loginForm">
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login">Login as Super Admin</button>
            <div class="error-message" id="errorMessage"></div>
        </form>

        <div class="info-box">
            <strong>⚠️ Super Admin 전용 페이지</strong>
            이 페이지는 super_admin 권한이 있는 계정만 접근할 수 있습니다.
            일반 관리자는 /admin 페이지를 이용하세요.
        </div>

        <div class="divider">또는</div>

        <div class="link-admin">
            <a href="../admin/login.php">→ 일반 관리자 로그인</a>
        </div>
    </div>

    <script>
        const API_BASE = 'api';

        // 로그인 처리
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            try {
                const response = await fetch(`${API_BASE}/auth.php?action=login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();

                if (data.success) {
                    // 슈퍼 관리자 권한 확인
                    if (data.user.role !== 'super_admin') {
                        showError('Super Admin 권한이 필요합니다. 일반 관리자는 /admin을 이용하세요.');
                        return;
                    }

                    console.log('✅ Super Admin login successful');
                    window.location.href = 'index.php';
                } else {
                    showError(data.message);
                }
            } catch (error) {
                console.error('Login error:', error);
                showError('로그인 중 오류가 발생했습니다.');
            }
        });

        // 에러 메시지 표시
        function showError(message) {
            const errorEl = document.getElementById('errorMessage');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }

        // 페이지 로드 시 로그인 상태 확인
        async function checkAuth() {
            try {
                const response = await fetch(`${API_BASE}/auth.php?action=check`);
                const data = await response.json();

                if (data.logged_in && data.user.role === 'super_admin') {
                    console.log('✅ Already logged in as Super Admin');
                    window.location.href = 'index.php';
                }
            } catch (error) {
                console.error('Auth check error:', error);
            }
        }

        // 초기화
        checkAuth();
    </script>
</body>
</html>
