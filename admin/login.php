<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K-Pumasi - Admin Login</title>
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
        }

        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-box {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 50px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            border: 1px solid rgba(148, 163, 184, 0.1);
            width: 100%;
            max-width: 440px;
        }

        .login-box h1 {
            text-align: center;
            color: #f8fafc;
            margin-bottom: 10px;
            font-size: 2em;
            font-weight: 700;
        }

        .login-box p {
            text-align: center;
            color: #94a3b8;
            margin-bottom: 35px;
            font-size: 0.95em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #e2e8f0;
            font-weight: 500;
            font-size: 0.9em;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 10px;
            font-size: 1em;
            color: #f8fafc;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px -5px rgba(59, 130, 246, 0.4);
        }

        .error-message {
            color: #dc3545;
            text-align: center;
            margin-top: 15px;
            display: none;
        }
    </style>
</head>
<body>
    <!-- 로그인 화면 -->
    <div class="login-container">
        <div class="login-box">
            <h1>🔐 Admin Login</h1>
            <p>K-Pumasi 관리자 페이지</p>
            <form id="loginForm" autocomplete="off">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="username" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password" autocomplete="off" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
                <div class="error-message" id="errorMessage"></div>
            </form>
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
                    console.log('✅ Login successful, redirecting to dashboard');
                    // 로그인 성공 시 index.php로 이동
                    window.location.href = 'index.php';
                } else {
                    showError(data.message);
                }
            } catch (error) {
                showError('Login failed. Please try again.');
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
            console.log('🔐 Checking authentication...');
            try {
                const response = await fetch(`${API_BASE}/auth.php?action=check`);
                const data = await response.json();
                console.log('Auth response:', data);

                if (data.logged_in) {
                    console.log('✅ Already logged in, redirecting to dashboard');
                    // 이미 로그인되어 있으면 대시보드로 이동
                    window.location.href = 'index.php';
                }
            } catch (error) {
                console.error('Auth check error:', error);
            }
        }

        // 입력란 초기화
        function clearInputs() {
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
        }

        // 초기화
        window.addEventListener('load', clearInputs);
        checkAuth();
    </script>
</body>
</html>
