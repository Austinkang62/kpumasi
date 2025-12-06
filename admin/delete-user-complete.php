<?php
/**
 * 회원 완전 삭제 (관리자 전용)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../config/database.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database config load error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$user_ids = $input['user_ids'] ?? [];

// 디버깅 로그
error_log('Received input: ' . print_r($input, true));
error_log('User IDs: ' . print_r($user_ids, true));

if (empty($user_ids) || !is_array($user_ids)) {
    echo json_encode([
        'success' => false,
        'message' => '삭제할 회원 ID가 없습니다.',
        'debug_input' => $input,
        'debug_user_ids' => $user_ids
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $conn->begin_transaction();

    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $types = str_repeat('s', count($user_ids));

    $deleted_count = 0;
    $errors = [];

    // 1. 세션 삭제
    $stmt = $conn->prepare("DELETE FROM sessions WHERE user_id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$user_ids);
        $stmt->execute();
        $stmt->close();
    }

    // 2. 이메일 인증 삭제
    $stmt = $conn->prepare("DELETE FROM email_verifications WHERE user_id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$user_ids);
        $stmt->execute();
        $stmt->close();
    }

    // 3. 거래 내역 삭제
    $stmt = $conn->prepare("DELETE FROM transactions WHERE user_id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$user_ids);
        $stmt->execute();
        $stmt->close();
    }

    // 4. 아바타 삭제
    $stmt = $conn->prepare("DELETE FROM avatars WHERE owner_id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$user_ids);
        $stmt->execute();
        $stmt->close();
    }

    // 5. 출금 내역 삭제
    $stmt = $conn->prepare("DELETE FROM withdrawals WHERE user_id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$user_ids);
        $stmt->execute();
        $stmt->close();
    }

    // 6. 보너스 내역 삭제 (받은 보너스)
    $stmt = $conn->prepare("DELETE FROM bonuses WHERE user_id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$user_ids);
        $stmt->execute();
        $stmt->close();
    }

    // 7. 보너스 내역 삭제 (발생시킨 보너스)
    $stmt = $conn->prepare("DELETE FROM bonuses WHERE from_user_id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$user_ids);
        $stmt->execute();
        $stmt->close();
    }

    // 8. 판매 내역 삭제
    $stmt = $conn->prepare("DELETE FROM sales WHERE user_id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$user_ids);
        $stmt->execute();
        $stmt->close();
    }

    // 9. 조직도에서 삭제
    $stmt = $conn->prepare("DELETE FROM organization WHERE user_id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$user_ids);
        $stmt->execute();
        $stmt->close();
    }

    // 10. users 테이블에서 최종 삭제
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param($types, ...$user_ids);
        $stmt->execute();
        $deleted_count = $stmt->affected_rows;
        $stmt->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "{$deleted_count}명의 회원이 완전히 삭제되었습니다.",
        'deleted_count' => $deleted_count,
        'user_ids' => $user_ids
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => '삭제 중 오류 발생: ' . $e->getMessage(),
        'error_detail' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
