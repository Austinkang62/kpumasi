<?php
/**
 * 매칭 보너스 수정 도구
 * 잘못 발생된 매칭 보너스를 삭제하고 올바른 수취인에게 재발생
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // 출력 버퍼 클리어
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    try {
        $action = $_POST['action'];

        if ($action === 'delete_and_regenerate') {
            $correctReceiverId = trim($_POST['correct_receiver_id']); // user_id (string)
            $fromUserIds = json_decode($_POST['from_user_ids'], true); // array of user_ids (strings)

            if (!$correctReceiverId || !$fromUserIds || !is_array($fromUserIds)) {
                throw new Exception('필수 파라미터가 누락되었습니다.');
            }

            // 트랜잭션 시작
            $db->beginTransaction();

            // 1. 올바른 수취인의 ID 조회
            $correctReceiverRow = $db->selectOne(
                "SELECT id FROM users WHERE user_id = ?",
                [$correctReceiverId]
            );

            if (!$correctReceiverRow || !isset($correctReceiverRow['id'])) {
                throw new Exception("올바른 수취인({$correctReceiverId})을 찾을 수 없습니다.");
            }

            $correctReceiverIdNum = $correctReceiverRow['id'];

            $results = [];
            $totalDeleted = 0;
            $totalRegenerated = 0;

            foreach ($fromUserIds as $fromUserId) {
                $fromUserId = trim($fromUserId);

                // 2. from_user의 ID 조회
                $fromUserRow = $db->selectOne(
                    "SELECT id FROM users WHERE user_id = ?",
                    [$fromUserId]
                );

                if (!$fromUserRow || !isset($fromUserRow['id'])) {
                    $results[] = [
                        'from_user_id' => $fromUserId,
                        'status' => 'error',
                        'message' => 'from_user를 찾을 수 없음'
                    ];
                    continue;
                }

                $fromUserIdNum = $fromUserRow['id'];

                // 3. 잘못 발생된 매칭 보너스 조회
                $wrongBonuses = $db->select(
                    "SELECT b.*, u.user_id as wrong_receiver_user_id
                     FROM bonuses b
                     JOIN users u ON b.user_id = u.id
                     WHERE b.bonus_type = 'matching'
                     AND b.from_user_id = ?
                     AND b.user_id != ?
                     AND b.status = 'paid'",
                    [$fromUserIdNum, $correctReceiverIdNum]
                );

                if (empty($wrongBonuses)) {
                    $results[] = [
                        'from_user_id' => $fromUserId,
                        'status' => 'skip',
                        'message' => '삭제할 잘못된 보너스가 없음'
                    ];
                    continue;
                }

                foreach ($wrongBonuses as $bonus) {
                    $wrongReceiverId = $bonus['user_id'];
                    $wrongReceiverUserId = $bonus['wrong_receiver_user_id'];
                    $amount = floatval($bonus['amount']);
                    $packageAmount = floatval($bonus['package_amount']);
                    $paymentType = $bonus['payment_type'];

                    // 4. 잘못 받은 사람의 데이터 차감
                    if ($paymentType === 'cash') {
                        $db->execute(
                            "UPDATE users
                             SET total_bonus = total_bonus - ?,
                                 available_bonus = available_bonus - ?,
                                 total_matching_bonus = total_matching_bonus - ?
                             WHERE id = ?",
                            [$amount, $amount, $amount, $wrongReceiverId]
                        );
                    } else {
                        // avatar_point
                        $db->execute(
                            "UPDATE users
                             SET total_bonus = total_bonus - ?,
                                 avatar_points = avatar_points - ?,
                                 total_matching_bonus = total_matching_bonus - ?
                             WHERE id = ?",
                            [$amount, $amount, $amount, $wrongReceiverId]
                        );
                    }

                    // 5. 잘못된 보너스 삭제
                    $db->execute(
                        "DELETE FROM bonuses WHERE id = ?",
                        [$bonus['id']]
                    );

                    $totalDeleted++;

                    // 6. 올바른 수취인에게 보너스 재발생
                    $db->execute(
                        "INSERT INTO bonuses
                         (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, status, description, created_at)
                         VALUES (?, ?, 'matching', ?, ?, ?, 'paid', ?, ?)",
                        [
                            $correctReceiverIdNum,
                            $fromUserIdNum,
                            $paymentType,
                            $amount,
                            $packageAmount,
                            "수정: {$wrongReceiverUserId} → {$correctReceiverId}",
                            $bonus['created_at']
                        ]
                    );

                    // 7. 올바른 수취인의 데이터 증가
                    if ($paymentType === 'cash') {
                        $db->execute(
                            "UPDATE users
                             SET total_bonus = total_bonus + ?,
                                 available_bonus = available_bonus + ?,
                                 total_matching_bonus = total_matching_bonus + ?
                             WHERE id = ?",
                            [$amount, $amount, $amount, $correctReceiverIdNum]
                        );
                    } else {
                        // avatar_point
                        $db->execute(
                            "UPDATE users
                             SET total_bonus = total_bonus + ?,
                                 avatar_points = avatar_points + ?,
                                 total_matching_bonus = total_matching_bonus + ?
                             WHERE id = ?",
                            [$amount, $amount, $amount, $correctReceiverIdNum]
                        );
                    }

                    $totalRegenerated++;
                }

                $results[] = [
                    'from_user_id' => $fromUserId,
                    'status' => 'success',
                    'deleted_count' => count($wrongBonuses),
                    'message' => count($wrongBonuses) . '건 처리 완료'
                ];
            }

            // 트랜잭션 커밋
            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => "총 {$totalDeleted}건 삭제, {$totalRegenerated}건 재발생 완료",
                'total_deleted' => $totalDeleted,
                'total_regenerated' => $totalRegenerated,
                'results' => $results
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } else {
            throw new Exception('알 수 없는 액션입니다.');
        }

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollback();
        }

        // 오류 로그 기록
        error_log('Fix Matching Bonus Error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'error_detail' => APP_ENV === 'development' ? $e->getTraceAsString() : null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>매칭 보너스 수정 - K-Pumasi Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 0.95em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
            font-family: monospace;
        }

        .hint {
            color: #666;
            font-size: 0.85em;
            margin-top: 5px;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #28a745;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #dc3545;
            color: #721c24;
        }

        .result-box {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .result-item {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 6px;
            background: white;
        }

        .result-item.success {
            border-left: 4px solid #28a745;
        }

        .result-item.error {
            border-left: 4px solid #dc3545;
        }

        .result-item.skip {
            border-left: 4px solid #6c757d;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 매칭 보너스 수정</h1>
        <p class="subtitle">잘못 발생된 매칭 보너스를 삭제하고 올바른 수취인에게 재발생합니다.</p>

        <div class="alert alert-warning">
            <strong>⚠️ 주의사항</strong><br>
            • 이 작업은 되돌릴 수 없습니다.<br>
            • 잘못 받은 사람의 보너스가 차감되고, 올바른 수취인에게 재발생됩니다.<br>
            • 실행 전 반드시 데이터를 확인하세요.
        </div>

        <form id="fixForm">
            <div class="form-group">
                <label for="correctReceiverId">올바른 수취인 ID (user_id) *</label>
                <input type="text" id="correctReceiverId" name="correct_receiver_id" placeholder="예: BIAW0618" required>
                <div class="hint">매칭 보너스를 받아야 하는 올바른 회원의 user_id를 입력하세요.</div>
            </div>

            <div class="form-group">
                <label for="fromUserIds">from_user_id 목록 (줄바꿈으로 구분) *</label>
                <textarea id="fromUserIds" name="from_user_ids" placeholder="예:&#10;ISAE6428&#10;DWBP7440" required></textarea>
                <div class="hint">매칭 보너스를 발생시킨 구매자들의 user_id를 한 줄에 하나씩 입력하세요.</div>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn">
                🔄 삭제 및 재발생 실행
            </button>
        </form>

        <div id="resultContainer" style="display: none;">
            <div class="result-box">
                <h3 id="resultTitle"></h3>
                <div id="resultContent"></div>
            </div>
        </div>

        <a href="user-bonus-verification.php" class="back-link">← 보너스 검증으로 돌아가기</a>
    </div>

    <script>
        document.getElementById('fixForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const correctReceiverId = document.getElementById('correctReceiverId').value.trim();
            const fromUserIdsText = document.getElementById('fromUserIds').value.trim();

            if (!correctReceiverId || !fromUserIdsText) {
                alert('모든 필드를 입력하세요.');
                return;
            }

            const fromUserIds = fromUserIdsText.split('\n').map(id => id.trim()).filter(id => id);

            if (fromUserIds.length === 0) {
                alert('from_user_id를 최소 1개 이상 입력하세요.');
                return;
            }

            const confirmMsg = `다음 작업을 실행하시겠습니까?\n\n` +
                `올바른 수취인: ${correctReceiverId}\n` +
                `from_user_id: ${fromUserIds.join(', ')}\n\n` +
                `총 ${fromUserIds.length}건의 매칭 보너스를 삭제하고 재발생합니다.`;

            if (!confirm(confirmMsg)) {
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ 처리 중...';

            try {
                const formData = new FormData();
                formData.append('action', 'delete_and_regenerate');
                formData.append('correct_receiver_id', correctReceiverId);
                formData.append('from_user_ids', JSON.stringify(fromUserIds));

                const response = await fetch('fix-matching-bonus.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                const resultContainer = document.getElementById('resultContainer');
                const resultTitle = document.getElementById('resultTitle');
                const resultContent = document.getElementById('resultContent');

                resultContainer.style.display = 'block';

                if (result.success) {
                    resultTitle.innerHTML = `✅ ${result.message}`;

                    let html = '';
                    result.results.forEach(item => {
                        const statusClass = item.status;
                        const icon = item.status === 'success' ? '✓' :
                                   item.status === 'error' ? '✗' : '○';
                        html += `
                            <div class="result-item ${statusClass}">
                                <strong>${icon} ${item.from_user_id}</strong>: ${item.message}
                            </div>
                        `;
                    });
                    resultContent.innerHTML = html;

                    // 폼 초기화
                    document.getElementById('fixForm').reset();

                } else {
                    resultTitle.innerHTML = `❌ 오류 발생`;
                    resultContent.innerHTML = `<div class="result-item error">${result.message}</div>`;
                }

            } catch (error) {
                alert('처리 중 오류가 발생했습니다: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '🔄 삭제 및 재발생 실행';
            }
        });
    </script>
</body>
</html>
