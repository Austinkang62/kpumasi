<?php
/**
 * Super Admin - Admin Management API
 * 관리자 계정 관리 (CRUD)
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// Super Admin 권한 확인
if (!isset($_SESSION['super_admin_logged_in']) || !$_SESSION['super_admin_logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();

    if ($method === 'GET' && $action === 'list') {
        // 관리자 목록 조회
        $limit = intval($_GET['limit'] ?? 20);
        $offset = intval($_GET['offset'] ?? 0);
        $search = $_GET['search'] ?? '';

        $whereClause = '';
        $params = [];

        if (!empty($search)) {
            $whereClause = "WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam];
        }

        $admins = $db->select(
            "SELECT admin_id, username, email, full_name, role, is_active, last_login, created_at
             FROM admins
             {$whereClause}
             ORDER BY admin_id DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        $total = $db->selectOne(
            "SELECT COUNT(*) as count FROM admins {$whereClause}",
            $params
        )['count'];

        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => $admins,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
        ob_end_flush();

    } elseif ($method === 'GET' && $action === 'detail') {
        // 관리자 상세 정보
        $adminId = intval($_GET['admin_id'] ?? 0);

        if ($adminId <= 0) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid admin_id']);
            ob_end_flush();
            exit;
        }

        $admin = $db->selectOne(
            "SELECT admin_id, username, email, full_name, role, is_active, last_login, created_at, updated_at
             FROM admins
             WHERE admin_id = ?",
            [$adminId]
        );

        if (!$admin) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
            ob_end_flush();
            exit;
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => $admin
        ]);
        ob_end_flush();

    } elseif ($method === 'POST' && $action === 'create') {
        // 새 관리자 생성
        $data = json_decode(file_get_contents('php://input'), true);

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $email = $data['email'] ?? '';
        $fullName = $data['full_name'] ?? '';
        $role = $data['role'] ?? 'admin';

        // 입력 검증
        if (empty($username) || empty($password) || empty($email)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Required fields missing']);
            ob_end_flush();
            exit;
        }

        // 역할 검증
        if (!in_array($role, ['super_admin', 'admin', 'manager'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid role']);
            ob_end_flush();
            exit;
        }

        // 중복 확인
        $existing = $db->selectOne(
            "SELECT admin_id FROM admins WHERE username = ? OR email = ?",
            [$username, $email]
        );

        if ($existing) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            ob_end_flush();
            exit;
        }

        // 비밀번호 해싱
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

        // 관리자 생성
        $db->execute(
            "INSERT INTO admins (username, password, email, full_name, role, is_active, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, 1, ?, NOW())",
            [$username, $hashedPassword, $email, $fullName, $role, $_SESSION['super_admin_id']]
        );

        $newAdminId = $db->getConnection()->lastInsertId();

        // 로그 기록
        $db->execute(
            "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
             VALUES (?, 'admin_created', ?, ?, ?)",
            [
                $_SESSION['super_admin_id'],
                json_encode(['new_admin_id' => $newAdminId, 'username' => $username, 'role' => $role]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        );

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Admin created successfully',
            'admin_id' => $newAdminId
        ]);
        ob_end_flush();

    } elseif ($method === 'POST' && $action === 'update') {
        // 관리자 정보 수정
        $data = json_decode(file_get_contents('php://input'), true);

        $adminId = intval($data['admin_id'] ?? 0);
        $email = $data['email'] ?? '';
        $fullName = $data['full_name'] ?? '';
        $role = $data['role'] ?? '';

        if ($adminId <= 0) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid admin_id']);
            ob_end_flush();
            exit;
        }

        // 존재 확인
        $admin = $db->selectOne(
            "SELECT admin_id FROM admins WHERE admin_id = ?",
            [$adminId]
        );

        if (!$admin) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
            ob_end_flush();
            exit;
        }

        // 업데이트할 필드 준비
        $updateFields = [];
        $updateParams = [];

        if (!empty($email)) {
            $updateFields[] = "email = ?";
            $updateParams[] = $email;
        }
        if (!empty($fullName)) {
            $updateFields[] = "full_name = ?";
            $updateParams[] = $fullName;
        }
        if (!empty($role) && in_array($role, ['super_admin', 'admin', 'manager'])) {
            $updateFields[] = "role = ?";
            $updateParams[] = $role;
        }

        if (empty($updateFields)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            ob_end_flush();
            exit;
        }

        $updateParams[] = $adminId;

        $db->execute(
            "UPDATE admins SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE admin_id = ?",
            $updateParams
        );

        // 로그 기록
        $db->execute(
            "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
             VALUES (?, 'admin_updated', ?, ?, ?)",
            [
                $_SESSION['super_admin_id'],
                json_encode(['updated_admin_id' => $adminId, 'fields' => array_keys($data)]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        );

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Admin updated successfully'
        ]);
        ob_end_flush();

    } elseif ($method === 'POST' && $action === 'toggle-status') {
        // 관리자 활성화/비활성화
        $data = json_decode(file_get_contents('php://input'), true);
        $adminId = intval($data['admin_id'] ?? 0);

        if ($adminId <= 0) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid admin_id']);
            ob_end_flush();
            exit;
        }

        // 자기 자신은 비활성화 불가
        if ($adminId === $_SESSION['super_admin_id']) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Cannot deactivate yourself']);
            ob_end_flush();
            exit;
        }

        $db->execute(
            "UPDATE admins SET is_active = NOT is_active, updated_at = NOW() WHERE admin_id = ?",
            [$adminId]
        );

        // 로그 기록
        $db->execute(
            "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
             VALUES (?, 'admin_status_toggled', ?, ?, ?)",
            [
                $_SESSION['super_admin_id'],
                json_encode(['target_admin_id' => $adminId]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        );

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Admin status toggled'
        ]);
        ob_end_flush();

    } elseif ($method === 'POST' && $action === 'delete') {
        // 관리자 삭제 (실제로는 비활성화만)
        $data = json_decode(file_get_contents('php://input'), true);
        $adminId = intval($data['admin_id'] ?? 0);

        if ($adminId <= 0) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid admin_id']);
            ob_end_flush();
            exit;
        }

        // 자기 자신은 삭제 불가
        if ($adminId === $_SESSION['super_admin_id']) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Cannot delete yourself']);
            ob_end_flush();
            exit;
        }

        $db->execute(
            "UPDATE admins SET is_active = 0, updated_at = NOW() WHERE admin_id = ?",
            [$adminId]
        );

        // 로그 기록
        $db->execute(
            "INSERT INTO admin_logs (admin_id, action, details, ip_address, user_agent)
             VALUES (?, 'admin_deleted', ?, ?, ?)",
            [
                $_SESSION['super_admin_id'],
                json_encode(['deleted_admin_id' => $adminId]),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        );

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Admin deleted successfully'
        ]);
        ob_end_flush();

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        ob_end_flush();
    }

} catch (Exception $e) {
    error_log('Admin Management Error: ' . $e->getMessage());

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => defined('APP_ENV') && APP_ENV === 'development' ? $e->getMessage() : null
    ]);
    ob_end_flush();
}
?>
