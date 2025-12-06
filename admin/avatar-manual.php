<?php
/**
 * 수동 아바타 생성 페이지
 * UI 제공 + 버튼 클릭으로 실행
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

// 대기 중인 회원 조회
$eligibleUsers = $db->select("
    SELECT id, user_id, name, avatar_points
    FROM users
    WHERE avatar_points >= 100.00
      AND deleted_at IS NULL
      AND is_avatar = 0
    ORDER BY avatar_points DESC
");

$totalWaiting = count($eligibleUsers);
$totalAvatarsToCreate = 0;
foreach ($eligibleUsers as $user) {
    $totalAvatarsToCreate += floor(floatval($user['avatar_points']) / 100);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수동 아바타 생성 - K-Pumasi Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            padding: 20px;
            color: #cbd5e1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 25px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px -8px rgba(0,0,0,0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            color: #f8fafc;
            font-size: 1.8em;
            font-weight: 700;
        }

        .back-btn {
            padding: 10px 20px;
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 25px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        }

        .stat-card h3 {
            color: #94a3b8;
            font-size: 0.9em;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .stat-card .value {
            color: #60a5fa;
            font-size: 2.5em;
            font-weight: 700;
        }

        .stat-card.success .value {
            color: #34d399;
        }

        .stat-card.warning .value {
            color: #fbbf24;
        }

        .warning-box {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid rgba(245, 158, 11, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            color: #fbbf24;
        }

        .warning-box h3 {
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .warning-box ul {
            margin-left: 20px;
            line-height: 1.8;
        }

        .table-container {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px -8px rgba(0,0,0,0.3);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .table-container h2 {
            color: #34d399;
            margin-bottom: 20px;
            font-size: 1.4em;
            border-bottom: 2px solid rgba(52, 211, 153, 0.3);
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background: rgba(15, 23, 42, 0.5);
        }

        th {
            padding: 15px;
            text-align: left;
            color: #93c5fd;
            font-weight: 600;
            border-bottom: 2px solid rgba(59, 130, 246, 0.3);
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        .user-id {
            color: #60a5fa;
            font-weight: 600;
        }

        .avatar-points {
            color: #34d399;
            font-weight: 600;
        }

        .avatar-count {
            color: #fbbf24;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 18px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.4);
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        #log-container {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            line-height: 1.6;
            display: none;
        }

        #log-container.active {
            display: block;
        }

        .log-entry {
            margin-bottom: 5px;
        }

        .log-entry.success {
            color: #34d399;
        }

        .log-entry.error {
            color: #f87171;
        }

        .log-entry.info {
            color: #93c5fd;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state h3 {
            color: #34d399;
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🛠️ 수동 아바타 생성</h1>
            <a href="pages/avatar-generator.html" class="back-btn">← 돌아가기</a>
        </header>

        <div class="stats-grid">
            <div class="stat-card warning">
                <h3>대기 중인 회원</h3>
                <div class="value"><?= $totalWaiting ?></div>
            </div>
            <div class="stat-card success">
                <h3>생성 가능 아바타</h3>
                <div class="value"><?= $totalAvatarsToCreate ?></div>
            </div>
            <div class="stat-card">
                <h3>처리 방식</h3>
                <div class="value" style="font-size: 1.2em;">배치</div>
            </div>
        </div>

        <?php if ($totalWaiting > 0): ?>
            <div class="warning-box">
                <h3>⚠️ 주의사항</h3>
                <ul>
                    <li>실행 중 다른 페이지를 닫지 마세요</li>
                    <li>다른 아바타 생성 작업과 동시에 실행하지 마세요</li>
                    <li>Lock wait timeout 오류 발생 시 1-2분 후 재시도하세요</li>
                    <li>가급적 Cron Job 자동 실행을 권장합니다</li>
                </ul>
            </div>

            <div class="table-container">
                <h2>📋 생성 대기 목록</h2>
                <table>
                    <thead>
                        <tr>
                            <th>회원 ID</th>
                            <th>이름</th>
                            <th>Avatar Points</th>
                            <th>생성 개수</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eligibleUsers as $user):
                            $avatarPoints = floatval($user['avatar_points']);
                            $avatarCount = floor($avatarPoints / 100);
                        ?>
                        <tr>
                            <td class="user-id"><?= htmlspecialchars($user['user_id']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td class="avatar-points">$<?= number_format($avatarPoints, 2) ?></td>
                            <td class="avatar-count"><?= $avatarCount ?>개</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="action-buttons">
                    <button class="btn btn-primary" id="executeBtn">
                        ▶️ 아바타 생성 실행
                    </button>
                    <button class="btn btn-secondary" onclick="location.reload()">
                        🔄 새로고침
                    </button>
                </div>

                <div id="log-container"></div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <div class="empty-state">
                    <h3>✅ 생성 대기 중인 아바타가 없습니다</h3>
                    <p>Avatar Points가 $100 이상인 회원이 없습니다.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const executeBtn = document.getElementById('executeBtn');
        const logContainer = document.getElementById('log-container');

        function addLog(message, type = 'info') {
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            entry.textContent = message;
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        executeBtn?.addEventListener('click', async function() {
            if (!confirm('<?= $totalAvatarsToCreate ?>개의 아바타를 생성하시겠습니까?\n\n처리 시간이 소요될 수 있습니다.')) {
                return;
            }

            executeBtn.disabled = true;
            executeBtn.textContent = '⏳ 처리 중...';
            logContainer.innerHTML = '';
            logContainer.classList.add('active');

            try {
                addLog('[' + new Date().toLocaleTimeString() + '] 아바타 생성 시작...', 'info');

                const response = await fetch('avatar-manual-execute.php', {
                    method: 'POST'
                });

                const text = await response.text();

                // 로그 파싱 및 출력
                const lines = text.split('\n');
                lines.forEach(line => {
                    if (line.trim()) {
                        if (line.includes('✅') || line.includes('완료')) {
                            addLog(line, 'success');
                        } else if (line.includes('❌') || line.includes('실패')) {
                            addLog(line, 'error');
                        } else {
                            addLog(line, 'info');
                        }
                    }
                });

                addLog('[' + new Date().toLocaleTimeString() + '] 처리 완료!', 'success');

                setTimeout(() => {
                    if (confirm('생성이 완료되었습니다. 페이지를 새로고침하시겠습니까?')) {
                        location.reload();
                    } else {
                        executeBtn.disabled = false;
                        executeBtn.textContent = '▶️ 아바타 생성 실행';
                    }
                }, 1000);

            } catch (error) {
                addLog('오류: ' + error.message, 'error');
                executeBtn.disabled = false;
                executeBtn.textContent = '▶️ 아바타 생성 실행';
            }
        });
    </script>
</body>
</html>
