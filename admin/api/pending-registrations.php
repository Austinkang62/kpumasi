<?php
/**
 * 가입 승인 대기 관리 API
 * GET: 대기 목록 조회
 * POST: 승인/거절 처리
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? 'list';
$db = Database::getInstance()->getConnection();

try {
    switch ($action) {
        case 'list':
            // 대기 목록 조회
            $status = $_GET['status'] ?? 'pending';
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(10, min(100, intval($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // 총 개수
            $countStmt = $db->prepare("SELECT COUNT(*) as total FROM pending_registrations WHERE status = ?");
            $countStmt->execute([$status]);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // 목록 조회
            $stmt = $db->prepare("
                SELECT id, email, txid, network, payment_amount,
                       referral_id, sponsor_id, sponsor_position,
                       status, admin_note, approved_by, approved_at,
                       created_user_id, created_at
                FROM pending_registrations
                WHERE status = ?
                ORDER BY created_at DESC
                LIMIT " . intval($limit) . " OFFSET " . intval($offset) . "
            ");
            $stmt->execute([$status]);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $registrations,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            break;

        case 'detail':
            // 상세 조회
            $id = intval($_GET['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID가 필요합니다.');
            }

            $stmt = $db->prepare("SELECT * FROM pending_registrations WHERE id = ?");
            $stmt->execute([$id]);
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registration) {
                throw new Exception('가입 신청을 찾을 수 없습니다.');
            }

            echo json_encode([
                'success' => true,
                'data' => $registration
            ]);
            break;

        case 'approve':
            // 승인 처리
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST 요청만 허용됩니다.');
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || !isset($data['id'])) {
                throw new Exception('ID가 필요합니다.');
            }

            $id = intval($data['id']);
            $adminNote = $data['admin_note'] ?? '';
            $adminId = $data['admin_id'] ?? 'admin';

            // 가입 신청 정보 조회
            $stmt = $db->prepare("SELECT * FROM pending_registrations WHERE id = ? AND status = 'pending'");
            $stmt->execute([$id]);
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registration) {
                throw new Exception('승인 대기 중인 신청을 찾을 수 없습니다.');
            }

            // User 클래스를 사용하여 실제 회원 등록
            // (User->register()가 내부적으로 트랜잭션을 관리함)
            $user = new User();
            $result = $user->register([
                'password' => 'HASHED', // 이미 해시됨, 아래에서 직접 설정
                'email' => $registration['email'],
                'bnb_address' => '0x0000000000000000000000000000000000000000',
                'referral_id' => $registration['referral_id'],
                'sponsor_id' => $registration['sponsor_id'],
                'sponsor_position' => $registration['sponsor_position'],
                'account_count' => 1
            ]);

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            $createdUserId = $result['data']['user_id'];

            // 비밀번호 직접 업데이트 (이미 해시된 비밀번호 사용)
            $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $updateStmt->execute([$registration['password'], $createdUserId]);

            // 승인 상태 업데이트
            $updateRegStmt = $db->prepare("
                UPDATE pending_registrations
                SET status = 'approved',
                    admin_note = ?,
                    approved_by = ?,
                    approved_at = NOW(),
                    created_user_id = ?
                WHERE id = ?
            ");
            $updateRegStmt->execute([$adminNote, $adminId, $createdUserId, $id]);

            echo json_encode([
                'success' => true,
                'message' => '회원가입이 승인되었습니다.',
                'data' => [
                    'registration_id' => $id,
                    'created_user_id' => $createdUserId,
                    'email' => $registration['email']
                ]
            ]);
            break;

        case 'reject':
            // 거절 처리
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST 요청만 허용됩니다.');
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || !isset($data['id'])) {
                throw new Exception('ID가 필요합니다.');
            }

            $id = intval($data['id']);
            $adminNote = $data['admin_note'] ?? '거절됨';
            $adminId = $data['admin_id'] ?? 'admin';

            $stmt = $db->prepare("
                UPDATE pending_registrations
                SET status = 'rejected',
                    admin_note = ?,
                    approved_by = ?,
                    approved_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $result = $stmt->execute([$adminNote, $adminId, $id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('승인 대기 중인 신청을 찾을 수 없습니다.');
            }

            echo json_encode([
                'success' => true,
                'message' => '가입 신청이 거절되었습니다.'
            ]);
            break;

        case 'stats':
            // 통계
            $stmt = $db->query("
                SELECT
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                    COUNT(*) as total_count
                FROM pending_registrations
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;

        default:
            throw new Exception('알 수 없는 액션입니다.');
    }

} catch (Exception $e) {
    error_log('Pending Registrations API Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
