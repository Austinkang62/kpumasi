<?php
/**
 * 캐시 내역 API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

try {
    $db = Database::getInstance();

    // 세션 토큰 확인
    $sessionToken = $_GET['session_token'] ?? '';

    if (empty($sessionToken)) {
        echo json_encode([
            'success' => false,
            'message' => '세션 토큰이 필요합니다.'
        ]);
        exit;
    }

    // 세션에서 사용자 조회
    $session = $db->selectOne(
        "SELECT user_id FROM sessions WHERE token = ? AND expires_at > NOW()",
        [$sessionToken]
    );

    if (!$session) {
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 세션입니다.'
        ]);
        exit;
    }

    $userId = $session['user_id'];

    // 현재 캐시
    $user = $db->selectOne(
        "SELECT available_bonus FROM users WHERE id = ?",
        [$userId]
    );

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => '사용자 정보를 찾을 수 없습니다.'
        ]);
        exit;
    }

    // 누적 캐시 수익 (보너스의 65%)
    $totalCashEarned = $db->selectOne(
        "SELECT COALESCE(SUM(amount), 0) * 0.65 as total_cash FROM bonuses WHERE user_id = ?",
        [$userId]
    );

    if (!$totalCashEarned) {
        $totalCashEarned = ['total_cash' => 0];
    }

    // 총 출금액
    $totalWithdrawn = $db->selectOne(
        "SELECT COALESCE(SUM(amount), 0) as total FROM withdrawals WHERE user_id = ? AND status IN ('approved', 'processing', 'completed')",
        [$userId]
    );

    if (!$totalWithdrawn) {
        $totalWithdrawn = ['total' => 0];
    }

    // 최근 출금 내역 (최근 10건)
    $recentWithdrawals = $db->select(
        "SELECT
            w.amount,
            w.status,
            CASE
                WHEN w.status = 'pending' THEN '대기중'
                WHEN w.status = 'approved' THEN '승인됨'
                WHEN w.status = 'processing' THEN '처리중'
                WHEN w.status = 'completed' THEN '완료'
                WHEN w.status = 'rejected' THEN '거절됨'
                ELSE w.status
            END as status_name,
            DATE_FORMAT(w.created_at, '%Y-%m-%d %H:%i') as created_at,
            DATE_FORMAT(w.processed_at, '%Y-%m-%d %H:%i') as processed_at
        FROM withdrawals w
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
        LIMIT 10",
        [$userId]
    );

    echo json_encode([
        'success' => true,
        'data' => [
            'current_cash' => $user['available_bonus'],
            'total_cash_earned' => $totalCashEarned['total_cash'],
            'total_withdrawn' => $totalWithdrawn['total'],
            'withdrawable' => $user['available_bonus'],
            'recent_withdrawals' => $recentWithdrawals
        ]
    ]);

} catch (Exception $e) {
    error_log('캐시 내역 조회 오류: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '캐시 내역을 불러오는 중 오류가 발생했습니다.',
        'error_detail' => $e->getMessage()
    ]);
}
