<?php
session_start();
require_once '../config/db.php';

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.html');
    exit;
}

$user_id = $_GET['user_id'] ?? '';

if (empty($user_id)) {
    die('회원 아이디가 필요합니다.');
}

// 회원 정보 조회
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('회원을 찾을 수 없습니다.');
}

// 보너스 내역 조회 (보너스 타입별 그룹화)
$bonusQuery = "
    SELECT
        bonus_type,
        SUM(amount) as total_amount,
        GROUP_CONCAT(DISTINCT from_user_id ORDER BY from_user_id SEPARATOR ', ') as from_users
    FROM bonuses
    WHERE user_id = ?
    GROUP BY bonus_type
    ORDER BY bonus_type
";
$stmt = $pdo->prepare($bonusQuery);
$stmt->execute([$user_id]);
$bonuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 보너스 타입별 데이터 정리
$bonusData = [
    'referral' => ['amount' => 0, 'from_users' => ''],
    'edge' => ['amount' => 0, 'from_users' => ''],
    'matching' => ['amount' => 0, 'from_users' => ''],
    'rollup' => ['amount' => 0, 'from_users' => '']
];

foreach ($bonuses as $bonus) {
    $type = strtolower($bonus['bonus_type']);
    if (isset($bonusData[$type])) {
        $bonusData[$type]['amount'] = floatval($bonus['total_amount']);
        $bonusData[$type]['from_users'] = $bonus['from_users'] ?? '';
    }
}

// 전체 보너스 합계
$totalBonus = array_sum(array_column($bonusData, 'amount'));
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수당 상세 - <?= htmlspecialchars($user_id) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .user-info p {
            margin: 5px 0;
            color: #666;
        }

        .user-info strong {
            color: #333;
        }

        .bonus-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .bonus-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .bonus-card h3 {
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .bonus-card .amount {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .bonus-card .from-users {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.3);
            min-height: 20px;
        }

        .total-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            color: white;
        }

        .total-card h2 {
            font-size: 18px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .total-card .total-amount {
            font-size: 48px;
            font-weight: bold;
        }

        .btn-back {
            display: inline-block;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .footer {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>💰 회원 수당 상세</h1>

            <div class="user-info">
                <p><strong>회원 아이디:</strong> <?= htmlspecialchars($user_id) ?></p>
                <p><strong>이름:</strong> <?= htmlspecialchars($user['name'] ?? '-') ?></p>
                <p><strong>이메일:</strong> <?= htmlspecialchars($user['email'] ?? '-') ?></p>
            </div>

            <div class="bonus-grid">
                <div class="bonus-card">
                    <h3>REFERRAL</h3>
                    <div class="amount">$<?= number_format($bonusData['referral']['amount'], 2) ?></div>
                    <div class="from-users">
                        <?php if (!empty($bonusData['referral']['from_users'])): ?>
                            (<?= htmlspecialchars($bonusData['referral']['from_users']) ?>)
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bonus-card">
                    <h3>EDGE</h3>
                    <div class="amount">$<?= number_format($bonusData['edge']['amount'], 2) ?></div>
                    <div class="from-users">
                        <?php if (!empty($bonusData['edge']['from_users'])): ?>
                            (<?= htmlspecialchars($bonusData['edge']['from_users']) ?>)
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bonus-card">
                    <h3>MATCHING</h3>
                    <div class="amount">$<?= number_format($bonusData['matching']['amount'], 2) ?></div>
                    <div class="from-users">
                        <?php if (!empty($bonusData['matching']['from_users'])): ?>
                            (<?= htmlspecialchars($bonusData['matching']['from_users']) ?>)
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bonus-card">
                    <h3>ROLLUP</h3>
                    <div class="amount">$<?= number_format($bonusData['rollup']['amount'], 2) ?></div>
                    <div class="from-users">
                        <?php if (!empty($bonusData['rollup']['from_users'])): ?>
                            (<?= htmlspecialchars($bonusData['rollup']['from_users']) ?>)
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="total-card">
                <h2>총 수당</h2>
                <div class="total-amount">$<?= number_format($totalBonus, 2) ?></div>
            </div>

            <div class="footer">
                <a href="pages/users-manage.html" class="btn-back">← 회원 관리로 돌아가기</a>
            </div>
        </div>
    </div>
</body>
</html>
