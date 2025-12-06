<?php
/**
 * 회원 삭제 확인 및 처리 페이지
 *
 * 사용 방법:
 * 1. 브라우저에서 이 파일 접속
 * 2. 삭제 대상 확인
 * 3. 삭제 버튼 클릭
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance();

// 삭제 대상 회원 ID
$targetUsers = [
    'BYDY7570',
    'WRGP3948',
    'JNRT1315',
    'LMVK8706',
    'JZTN7477',
    'SPVQ8259',
    'RPBC6035'
];

// 삭제 처리
$deleteExecuted = false;
$deleteResult = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $deleteExecuted = true;

    try {
        $db->beginTransaction();

        foreach ($targetUsers as $userId) {
            $result = ['user_id' => $userId];

            // 회원 정보 조회
            $user = $db->selectOne(
                "SELECT id, user_id, name, email FROM users WHERE user_id = ?",
                [$userId]
            );

            if (!$user) {
                $result['status'] = 'not_found';
                $result['message'] = '회원을 찾을 수 없습니다.';
                $deleteResult[] = $result;
                continue;
            }

            $internalId = $user['id'];
            $result['name'] = $user['name'];
            $result['email'] = $user['email'];

            // 관련 데이터 삭제
            $deletedCounts = [];

            // PDO 연결 직접 사용
            $pdo = $db->getConnection();

            // 1. 보너스 내역
            $stmt = $pdo->prepare("DELETE FROM bonuses WHERE user_id = ? OR from_user_id = ?");
            $stmt->execute([$internalId, $internalId]);
            $deletedCounts['bonuses'] = $stmt->rowCount();
            $stmt->closeCursor();

            // 2. 보너스 요약
            $stmt = $pdo->prepare("DELETE FROM bonus_summary WHERE user_id = ?");
            $stmt->execute([$internalId]);
            $deletedCounts['bonus_summary'] = $stmt->rowCount();
            $stmt->closeCursor();

            // 3. 아바타
            $stmt = $pdo->prepare("DELETE FROM avatars WHERE parent_user_id = ? OR avatar_user_id = ?");
            $stmt->execute([$internalId, $internalId]);
            $deletedCounts['avatars'] = $stmt->rowCount();
            $stmt->closeCursor();

            // 4. 매출
            $stmt = $pdo->prepare("DELETE FROM sales WHERE user_id = ?");
            $stmt->execute([$internalId]);
            $deletedCounts['sales'] = $stmt->rowCount();
            $stmt->closeCursor();

            // 5. 출금
            $stmt = $pdo->prepare("DELETE FROM withdrawals WHERE user_id = ?");
            $stmt->execute([$internalId]);
            $deletedCounts['withdrawals'] = $stmt->rowCount();
            $stmt->closeCursor();

            // 6. 거래 로그
            $stmt = $pdo->prepare("DELETE FROM transactions WHERE user_id = ?");
            $stmt->execute([$internalId]);
            $deletedCounts['transactions'] = $stmt->rowCount();
            $stmt->closeCursor();

            // 7. 세션
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id = ?");
            $stmt->execute([$internalId]);
            $deletedCounts['sessions'] = $stmt->rowCount();
            $stmt->closeCursor();

            // 8. 조직도
            $stmt = $pdo->prepare("DELETE FROM organization WHERE user_id = ?");
            $stmt->execute([$internalId]);
            $deletedCounts['organization'] = $stmt->rowCount();
            $stmt->closeCursor();

            // 9. 회원 삭제
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$internalId]);
            $deletedCounts['users'] = $stmt->rowCount();
            $stmt->closeCursor();

            $result['status'] = 'success';
            $result['deleted_counts'] = $deletedCounts;
            $deleteResult[] = $result;
        }

        $db->commit();

    } catch (Exception $e) {
        $db->rollback();
        $deleteResult[] = [
            'status' => 'error',
            'user_id' => 'ALL',
            'message' => '전체 오류 발생: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// 현재 상태 확인
$currentStatus = [];
foreach ($targetUsers as $userId) {
    $user = $db->selectOne(
        "SELECT
            u.id, u.user_id, u.name, u.email, u.status, u.deleted_at, u.created_at,
            u.total_bonus, u.available_bonus, u.avatar_points,
            (SELECT COUNT(*) FROM bonuses WHERE user_id = u.id OR from_user_id = u.id) as bonus_count,
            (SELECT COUNT(*) FROM avatars WHERE parent_user_id = u.id OR avatar_user_id = u.id) as avatar_count,
            (SELECT COUNT(*) FROM sales WHERE user_id = u.id) as sales_count,
            (SELECT COUNT(*) FROM organization WHERE user_id = u.id) as org_count
        FROM users u
        WHERE u.user_id = ?",
        [$userId]
    );

    $currentStatus[] = [
        'user_id' => $userId,
        'exists' => $user ? true : false,
        'data' => $user
    ];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원 삭제 관리 - K-Pumasi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Malgun Gothic', -apple-system, sans-serif;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            min-height: 100vh;
            padding: 30px;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        h1 {
            font-size: 36px;
            color: #d4af37;
            margin-bottom: 10px;
            border-bottom: 3px solid #d4af37;
            padding-bottom: 15px;
        }

        .timestamp {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .alert {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 5px solid;
        }

        .alert-success {
            background: #ECFDF5;
            border-color: #10B981;
            color: #065F46;
        }

        .alert-error {
            background: #FEF2F2;
            border-color: #EF4444;
            color: #991B1B;
        }

        .alert-warning {
            background: #FFFBEB;
            border-color: #F59E0B;
            color: #92400E;
        }

        .alert-info {
            background: #EFF6FF;
            border-color: #3B82F6;
            color: #1E40AF;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            padding-left: 15px;
            border-left: 4px solid #d4af37;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f8f9fa;
            font-weight: 700;
            color: #333;
            font-size: 14px;
        }

        td {
            font-size: 14px;
        }

        tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #ECFDF5;
            color: #10B981;
        }

        .badge-danger {
            background: #FEF2F2;
            color: #EF4444;
        }

        .badge-warning {
            background: #FFFBEB;
            color: #F59E0B;
        }

        .badge-info {
            background: #EFF6FF;
            color: #3B82F6;
        }

        .user-id {
            font-weight: 700;
            color: #d4af37;
            font-family: 'Courier New', monospace;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #DC2626, #B91C1C);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(239, 68, 68, 0.4);
        }

        .btn-secondary {
            background: #6B7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4B5563;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .delete-confirmation {
            background: #FEF2F2;
            border: 2px solid #EF4444;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .delete-confirmation h3 {
            color: #DC2626;
            margin-bottom: 15px;
        }

        .delete-confirmation ul {
            margin: 15px 0 20px 20px;
        }

        .delete-confirmation li {
            margin: 8px 0;
            color: #991B1B;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #d4af37;
        }

        .stat-label {
            font-size: 12px;
            color: #6B7280;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 900;
            color: #333;
        }

        .deleted-row {
            background: #FEE2E2 !important;
            opacity: 0.7;
        }

        .count-badge {
            background: #3B82F6;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗑️ 회원 삭제 관리</h1>
        <div class="timestamp">처리 시각: <?php echo date('Y-m-d H:i:s'); ?></div>

        <?php if ($deleteExecuted): ?>
            <div class="alert alert-success">
                <h3 style="margin-bottom: 15px;">✅ 삭제 처리 완료</h3>
                <p>총 <?php echo count($deleteResult); ?>명의 회원 삭제가 완료되었습니다.</p>
            </div>

            <div class="section">
                <div class="section-title">처리 결과</div>
                <table>
                    <thead>
                        <tr>
                            <th>회원 ID</th>
                            <th>이름</th>
                            <th>이메일</th>
                            <th>상태</th>
                            <th>삭제된 데이터</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deleteResult as $result): ?>
                            <tr>
                                <td class="user-id"><?php echo $result['user_id']; ?></td>
                                <td><?php echo $result['name'] ?? '-'; ?></td>
                                <td><?php echo $result['email'] ?? '-'; ?></td>
                                <td>
                                    <?php if ($result['status'] === 'success'): ?>
                                        <span class="badge badge-success">✅ 삭제 완료</span>
                                    <?php elseif ($result['status'] === 'not_found'): ?>
                                        <span class="badge badge-warning">⚠️ 없음</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">❌ 오류</span>
                                        <div style="margin-top: 10px; font-size: 12px; color: #DC2626;">
                                            <?php echo $result['message'] ?? '알 수 없는 오류'; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($result['deleted_counts'])): ?>
                                        보너스: <?php echo $result['deleted_counts']['bonuses']; ?>,
                                        아바타: <?php echo $result['deleted_counts']['avatars']; ?>,
                                        매출: <?php echo $result['deleted_counts']['sales']; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="user-cleanup.php" class="btn btn-secondary">다시 확인하기</a>
            </div>
        <?php else: ?>
            <div class="section">
                <div class="section-title">📊 삭제 대상 현황</div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">총 대상 회원</div>
                        <div class="stat-value"><?php echo count($targetUsers); ?>명</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">존재하는 회원</div>
                        <div class="stat-value">
                            <?php echo count(array_filter($currentStatus, fn($s) => $s['exists'])); ?>명
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">이미 삭제됨</div>
                        <div class="stat-value">
                            <?php echo count(array_filter($currentStatus, fn($s) => !$s['exists'])); ?>명
                        </div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>회원 ID</th>
                            <th>이름</th>
                            <th>이메일</th>
                            <th>상태</th>
                            <th>보너스</th>
                            <th>APT</th>
                            <th>관련 데이터</th>
                            <th>가입일</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currentStatus as $status): ?>
                            <tr class="<?php echo !$status['exists'] ? 'deleted-row' : ''; ?>">
                                <td class="user-id"><?php echo $status['user_id']; ?></td>
                                <?php if ($status['exists']): ?>
                                    <td><?php echo $status['data']['name']; ?></td>
                                    <td><?php echo $status['data']['email']; ?></td>
                                    <td>
                                        <?php if ($status['data']['deleted_at']): ?>
                                            <span class="badge badge-warning">소프트 삭제됨</span>
                                        <?php else: ?>
                                            <span class="badge badge-info"><?php echo $status['data']['status']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($status['data']['available_bonus'], 2); ?></td>
                                    <td>$<?php echo number_format($status['data']['avatar_points'], 2); ?></td>
                                    <td>
                                        <span class="count-badge">보너스 <?php echo $status['data']['bonus_count']; ?></span>
                                        <span class="count-badge">아바타 <?php echo $status['data']['avatar_count']; ?></span>
                                        <span class="count-badge">매출 <?php echo $status['data']['sales_count']; ?></span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($status['data']['created_at'])); ?></td>
                                <?php else: ?>
                                    <td colspan="6" style="text-align: center;">
                                        <span class="badge badge-success">✅ 이미 삭제됨</span>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $existingUsers = array_filter($currentStatus, fn($s) => $s['exists']);
            if (count($existingUsers) > 0):
            ?>
                <div class="delete-confirmation">
                    <h3>⚠️ 삭제 확인</h3>
                    <p><strong>다음 회원들을 완전히 삭제하시겠습니까?</strong></p>
                    <ul>
                        <?php foreach ($existingUsers as $status): ?>
                            <li>
                                <strong><?php echo $status['user_id']; ?></strong>
                                (<?php echo $status['data']['name']; ?>) -
                                보너스 <?php echo $status['data']['bonus_count']; ?>건,
                                아바타 <?php echo $status['data']['avatar_count']; ?>건,
                                매출 <?php echo $status['data']['sales_count']; ?>건
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="margin-top: 15px; font-weight: 600;">
                        ⚠️ 이 작업은 되돌릴 수 없습니다. 모든 관련 데이터가 영구적으로 삭제됩니다.
                    </p>

                    <form method="POST" onsubmit="return confirm('정말로 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다!');">
                        <div class="action-buttons">
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                🗑️ 영구 삭제 실행
                            </button>
                            <a href="/admin/" class="btn btn-secondary">취소</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <h3>✅ 모든 대상 회원이 이미 삭제되었습니다</h3>
                    <p>삭제할 회원이 없습니다.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
