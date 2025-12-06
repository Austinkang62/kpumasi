<?php
/**
 * Super Admin - System Settings
 * 시스템 설정 (향후 구현 예정)
 */
require_once __DIR__ . '/../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>시스템 설정 - Super Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .coming-soon {
            text-align: center;
            padding: 100px 20px;
        }

        .coming-soon h2 {
            font-size: 3em;
            color: #667eea;
            margin-bottom: 20px;
        }

        .coming-soon p {
            font-size: 1.2em;
            color: #6b7280;
            margin-bottom: 40px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .feature-card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .feature-card ul {
            list-style: none;
            padding: 0;
        }

        .feature-card li {
            padding: 8px 0;
            color: #6b7280;
            border-bottom: 1px solid #f3f4f6;
        }

        .feature-card li:before {
            content: "✓ ";
            color: #10b981;
            font-weight: bold;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="content-header">
            <h1>⚙️ 시스템 설정</h1>
        </header>

        <div class="coming-soon">
            <h2>🚧 Coming Soon</h2>
            <p>시스템 설정 페이지는 현재 개발 중입니다.</p>

            <div class="features-grid">
                <div class="feature-card">
                    <h3>📧 이메일 설정</h3>
                    <ul>
                        <li>SMTP 서버 설정</li>
                        <li>이메일 템플릿 관리</li>
                        <li>발신자 정보 설정</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <h3>💰 결제 설정</h3>
                    <ul>
                        <li>시스템 지갑 주소</li>
                        <li>패키지 가격 설정</li>
                        <li>수수료 설정</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <h3>🎯 보너스 설정</h3>
                    <ul>
                        <li>레벨별 보너스 비율</li>
                        <li>아바타 생성 기준</li>
                        <li>보너스 지급 규칙</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <h3>🔒 보안 설정</h3>
                    <ul>
                        <li>세션 타임아웃</li>
                        <li>IP 화이트리스트</li>
                        <li>2단계 인증</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
