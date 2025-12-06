<?php
/**
 * Admin Withdrawals Management API
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();

    if ($method === 'GET' && $action === 'list') {
        // 출금 요청 목록
        $status = $_GET['status'] ?? 'all';
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;

        $whereClause = '';
        $params = [];

        if ($status !== 'all') {
            $whereClause = "WHERE w.status = ?";
            $params = [$status];
        }

        // 총 개수
        $totalQuery = "SELECT COUNT(*) as total FROM withdrawals w $whereClause";
        $total = $db->selectOne($totalQuery, $params)['total'];

        // debt_amount 컬럼 존재 여부 확인
        $debtSelect = "0 as debt_amount"; // 기본값
        try {
            $columns = $db->select("SHOW COLUMNS FROM users LIKE 'debt_amount'");
            if (!empty($columns)) {
                $debtSelect = "COALESCE(u.debt_amount, 0) as debt_amount";
            }
        } catch (Exception $e) {
            // 컬럼이 없으면 0으로 설정
            error_log('debt_amount column check error: ' . $e->getMessage());
        }

        // 출금 목록

        $query = "
            SELECT
                w.id as withdrawal_id,
                w.user_id,
                w.amount,
                w.fee,
                w.net_amount,
                w.withdrawal_address as usdt_address,
                w.status,
                w.requested_at as created_at,
                w.approved_at as processed_at,
                w.txid,
                w.admin_note as notes,
                u.user_id as login_id,
                u.email,
                $debtSelect
            FROM withdrawals w
            JOIN users u ON w.user_id = u.id
            $whereClause
            ORDER BY w.requested_at DESC
            LIMIT $limit OFFSET $offset
        ";

        $withdrawals = $db->select($query, $params);

        echo json_encode([
            'success' => true,
            'withdrawals' => $withdrawals,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);

    } elseif ($method === 'POST' && $action === 'approve') {
        // 출금 승인
        $data = json_decode(file_get_contents('php://input'), true);
        $withdrawalId = intval($data['withdrawal_id'] ?? 0);
        $txid = $data['txid'] ?? '';

        if (empty($txid)) {
            echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
            exit;
        }

        // 출금 정보 조회
        $withdrawal = $db->selectOne("
            SELECT * FROM withdrawals WHERE id = ? AND status = 'pending'
        ", [$withdrawalId]);

        if (!$withdrawal) {
            echo json_encode(['success' => false, 'message' => 'Withdrawal not found or already processed']);
            exit;
        }

        // 트랜잭션 시작
        $db->beginTransaction();

        try {
            // 출금 상태 업데이트
            $db->execute("
                UPDATE withdrawals
                SET status = 'completed',
                    completed_at = NOW(),
                    approved_at = NOW(),
                    txid = ?
                WHERE id = ?
            ", [$txid, $withdrawalId]);

            // 커밋
            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Withdrawal approved successfully'
            ]);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } elseif ($method === 'POST' && $action === 'reject') {
        // 출금 거부
        $data = json_decode(file_get_contents('php://input'), true);
        $withdrawalId = intval($data['withdrawal_id'] ?? 0);
        $reason = $data['reason'] ?? 'Rejected by admin';

        // 출금 정보 조회
        $withdrawal = $db->selectOne("
            SELECT * FROM withdrawals WHERE id = ? AND status = 'pending'
        ", [$withdrawalId]);

        if (!$withdrawal) {
            echo json_encode(['success' => false, 'message' => 'Withdrawal not found or already processed']);
            exit;
        }

        // 트랜잭션 시작
        $db->beginTransaction();

        try {
            // 출금 상태 업데이트
            $db->execute("
                UPDATE withdrawals
                SET status = 'rejected',
                    completed_at = NOW(),
                    admin_note = ?
                WHERE id = ?
            ", [$reason, $withdrawalId]);

            // 사용자 잔액 복구 (available_bonus에 복구)
            $db->execute("
                UPDATE users
                SET available_bonus = available_bonus + ?
                WHERE id = ?
            ", [$withdrawal['amount'], $withdrawal['user_id']]);

            // 커밋
            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Withdrawal rejected and balance restored'
            ]);

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }

} catch (Exception $e) {
    error_log('Admin Withdrawals Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
