<?php
/**
 * 회원 관리 API
 * 관리자가 회원 정보를 조회/수정
 */

// 출력 버퍼링 시작
ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

// 관리자 인증 확인
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
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
        // 회원 목록 조회 (검색, 페이징, 필터링)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? ''; // 'credit_sale' or 'withdrawal_hold'

        $whereClause = 'WHERE deleted_at IS NULL';
        $params = [];

        if (!empty($search)) {
            $whereClause = "WHERE deleted_at IS NULL AND (user_id LIKE ? OR email LIKE ? OR name LIKE ?)";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam];
        }

        // 필터 조건 추가
        if ($filter === 'credit_sale') {
            $whereClause .= " AND credit_sale_amount > 0";
        } elseif ($filter === 'withdrawal_hold') {
            $whereClause .= " AND withdrawal_hold = 1";
        }

        // 전체 개수
        $totalResult = $db->selectOne("SELECT COUNT(*) as count FROM users $whereClause", $params);
        $total = $totalResult['count'];

        // debt_amount 컬럼 존재 여부 확인
        $debtSelect = "0 as debt_amount";
        try {
            $columns = $db->select("SHOW COLUMNS FROM users LIKE 'debt_amount'");
            if (!empty($columns)) {
                $debtSelect = "COALESCE(debt_amount, 0) as debt_amount";
            }
        } catch (Exception $e) {
            // 컬럼이 없으면 0으로 설정
        }

        // 회원 목록
        $users = $db->select("
            SELECT
                id,
                user_id,
                name,
                email,
                phone,
                usdt_address,
                bnb_address,
                sponsor_id,
                sponsor_position,
                package_id,
                package_date,
                email_verified,
                role,
                status,
                is_main_account,
                parent_account_id,
                account_group,
                total_bonus,
                available_bonus,
                credit_sale_amount,
                withdrawal_hold,
                $debtSelect,
                created_at
            FROM users
            $whereClause
            ORDER BY created_at ASC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$limit, $offset]));

        ob_clean();
        echo json_encode([
            'success' => true,
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
        ob_end_flush();

    } elseif ($method === 'GET' && $action === 'detail') {
        // 회원 상세 정보 조회
        $userId = $_GET['user_id'] ?? '';

        if (empty($userId)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            ob_end_flush();
            exit;
        }

        $user = $db->selectOne("
            SELECT
                id,
                user_id,
                name,
                email,
                phone,
                usdt_address,
                bnb_address,
                referral_id,
                sponsor_id,
                sponsor_position,
                package_id,
                package_date,
                email_verified,
                role,
                status,
                is_avatar,
                parent_user_id,
                avatar_count,
                direct_referrals,
                total_bonus,
                available_bonus,
                credit_sale_amount,
                withdrawal_hold,
                total_sales,
                total_withdrawn,
                last_withdrawal_date,
                is_main_account,
                parent_account_id,
                account_group,
                account_count,
                created_at,
                updated_at
            FROM users
            WHERE user_id = ?
        ", [$userId]);

        if (!$user) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User not found']);
            ob_end_flush();
            exit;
        }

        // 후원인 정보 조회 (sponsor)
        $sponsor = null;
        if (!empty($user['sponsor_id'])) {
            $sponsor = $db->selectOne(
                "SELECT user_id, name, email FROM users WHERE user_id = ?",
                [$user['sponsor_id']]
            );
        }

        // 추천인 정보 조회 (referral)
        $referrer = null;
        $referralUserId = '';
        if (!empty($user['referral_id'])) {
            $referrer = $db->selectOne(
                "SELECT user_id, name, email FROM users WHERE id = ?",
                [$user['referral_id']]
            );
            // referral_id(int)에 해당하는 user_id 추출
            if ($referrer) {
                $referralUserId = $referrer['user_id'];
            }
        }

        // 그룹 대표자 정보 (parent_account)
        $parentAccount = null;
        if (!empty($user['parent_account_id'])) {
            $parentAccount = $db->selectOne(
                "SELECT user_id, name, email FROM users WHERE id = ?",
                [$user['parent_account_id']]
            );
        }

        // 패키지 정보
        $package = null;
        if (!empty($user['package_id'])) {
            $package = $db->selectOne(
                "SELECT id, name, price FROM packages WHERE id = ?",
                [$user['package_id']]
            );
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'user' => $user,
            'referral_user_id' => $referralUserId, // referral_id에 해당하는 user_id
            'sponsor' => $sponsor,
            'referrer' => $referrer,
            'parent_account' => $parentAccount,
            'package' => $package
        ]);
        ob_end_flush();

    } elseif ($method === 'POST' && $action === 'update') {
        // 회원 정보 수정
        $data = json_decode(file_get_contents('php://input'), true);

        $userId = $data['user_id'] ?? '';

        if (empty($userId)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            ob_end_flush();
            exit;
        }

        // 기존 회원 확인
        $existingUser = $db->selectOne("SELECT id FROM users WHERE user_id = ?", [$userId]);
        if (!$existingUser) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User not found']);
            ob_end_flush();
            exit;
        }

        $updates = [];
        $params = [];

        // 비밀번호 변경
        if (!empty($data['password'])) {
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        }

        // 이메일 변경
        if (isset($data['email'])) {
            $updates[] = "email = ?";
            $params[] = $data['email'];
        }

        // 이름 변경
        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $params[] = $data['name'];
        }

        // 전화번호 변경
        if (isset($data['phone'])) {
            $updates[] = "phone = ?";
            $params[] = $data['phone'];
        }

        // USDT 주소 변경
        if (isset($data['usdt_address'])) {
            $updates[] = "usdt_address = ?";
            $params[] = $data['usdt_address'];
        }

        // BNB 주소 변경
        if (isset($data['bnb_address'])) {
            $updates[] = "bnb_address = ?";
            $params[] = $data['bnb_address'];
        }

        // 추천인(referral_user_id -> referral_id) 변경
        if (isset($data['referral_user_id'])) {
            // 기존 추천인 정보 조회 (이력 기록용)
            $currentUser = $db->selectOne(
                "SELECT id, user_id, referral_id FROM users WHERE user_id = ?",
                [$userId]
            );

            $oldReferralId = $currentUser['referral_id'];
            $oldReferralUserId = null;
            if ($oldReferralId) {
                $oldReferrer = $db->selectOne(
                    "SELECT user_id FROM users WHERE id = ?",
                    [$oldReferralId]
                );
                $oldReferralUserId = $oldReferrer ? $oldReferrer['user_id'] : null;
            }

            $newReferralId = null;
            $newReferralUserId = null;

            if (empty($data['referral_user_id'])) {
                $updates[] = "referral_id = NULL";
            } else {
                // referral_user_id로 해당 user의 id 조회
                $referralUser = $db->selectOne(
                    "SELECT id, user_id FROM users WHERE user_id = ?",
                    [$data['referral_user_id']]
                );
                if ($referralUser) {
                    $updates[] = "referral_id = ?";
                    $params[] = $referralUser['id'];
                    $newReferralId = $referralUser['id'];
                    $newReferralUserId = $referralUser['user_id'];
                } else {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Referral user not found: ' . $data['referral_user_id']]);
                    ob_end_flush();
                    exit;
                }
            }

            // 추천인이 실제로 변경된 경우에만 이력 기록
            if ($oldReferralId != $newReferralId) {
                $adminUserId = $_SESSION['admin_user_id'] ?? 'admin';
                $changeReason = $data['referral_change_reason'] ?? '관리자에 의한 변경';
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

                // 이력 기록 테이블에 삽입
                try {
                    $db->execute("
                        INSERT INTO referral_change_history
                        (user_id, change_type, old_referral_id, old_referral_user_id, new_referral_id, new_referral_user_id, changed_by, change_reason, ip_address)
                        VALUES (?, 'referral', ?, ?, ?, ?, ?, ?, ?)
                    ", [
                        $userId,
                        $oldReferralId,
                        $oldReferralUserId,
                        $newReferralId,
                        $newReferralUserId,
                        $adminUserId,
                        $changeReason,
                        $ipAddress
                    ]);
                } catch (Exception $e) {
                    // 테이블이 없는 경우 무시 (나중에 테이블 생성 후 작동)
                }
            }
        }

        // 후원인(sponsor_id) 및 후원위치(sponsor_position) 변경
        $sponsorChanged = false;
        $oldSponsorId = null;
        $oldSponsorPosition = null;
        $newSponsorId = null;
        $newSponsorPosition = null;

        if (isset($data['sponsor_id']) || isset($data['sponsor_position'])) {
            // 기존 후원인 정보 조회 (이력 기록용)
            $currentUser = $db->selectOne(
                "SELECT id, user_id, sponsor_id, sponsor_position FROM users WHERE user_id = ?",
                [$userId]
            );

            $oldSponsorId = $currentUser['sponsor_id'];
            $oldSponsorPosition = $currentUser['sponsor_position'];

            // 후원인(sponsor_id) 변경
            if (isset($data['sponsor_id'])) {
                $newSponsorId = $data['sponsor_id'];
                $updates[] = "sponsor_id = ?";
                $params[] = $newSponsorId;

                if ($oldSponsorId != $newSponsorId) {
                    $sponsorChanged = true;
                }
            } else {
                $newSponsorId = $oldSponsorId;
            }

            // 후원위치(sponsor_position) 변경
            if (isset($data['sponsor_position'])) {
                $newSponsorPosition = $data['sponsor_position'];

                // sponsor_position 유효성 검사: 1 또는 2만 허용
                if ($newSponsorPosition !== '' && $newSponsorPosition !== null &&
                    $newSponsorPosition != '1' && $newSponsorPosition != '2') {
                    // 유효하지 않은 값이면 변경하지 않음
                    $newSponsorPosition = $oldSponsorPosition;
                } else if ($newSponsorPosition === '' || $newSponsorPosition === null) {
                    // 빈 값이면 변경하지 않음
                    $newSponsorPosition = $oldSponsorPosition;
                } else {
                    // 유효한 값(1 또는 2)인 경우에만 업데이트
                    $updates[] = "sponsor_position = ?";
                    $params[] = $newSponsorPosition;

                    if ($oldSponsorPosition != $newSponsorPosition) {
                        $sponsorChanged = true;
                    }
                }
            } else {
                $newSponsorPosition = $oldSponsorPosition;
            }

            // 후원인 또는 위치가 실제로 변경된 경우 이력 기록
            if ($sponsorChanged) {
                $adminUserId = $_SESSION['admin_user_id'] ?? 'admin';
                $changeReason = $data['sponsor_change_reason'] ?? '관리자에 의한 변경';
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

                // 이력 기록 테이블에 삽입
                try {
                    $db->execute("
                        INSERT INTO referral_change_history
                        (user_id, change_type, old_sponsor_id, new_sponsor_id, old_sponsor_position, new_sponsor_position, changed_by, change_reason, ip_address)
                        VALUES (?, 'sponsor', ?, ?, ?, ?, ?, ?, ?)
                    ", [
                        $userId,
                        $oldSponsorId,
                        $newSponsorId,
                        $oldSponsorPosition,
                        $newSponsorPosition,
                        $adminUserId,
                        $changeReason,
                        $ipAddress
                    ]);
                } catch (Exception $e) {
                    // 테이블이 없는 경우 무시
                }
            }
        }

        // 그룹 대표자(parent_account_id) 변경
        if (isset($data['parent_account_id'])) {
            if (empty($data['parent_account_id'])) {
                $updates[] = "parent_account_id = NULL";
            } else {
                // parent_account의 id 조회
                $parentUser = $db->selectOne(
                    "SELECT id FROM users WHERE user_id = ?",
                    [$data['parent_account_id']]
                );
                if ($parentUser) {
                    $updates[] = "parent_account_id = ?";
                    $params[] = $parentUser['id'];
                }
            }
        }

        // 그룹코드(account_group) 변경
        if (isset($data['account_group'])) {
            $updates[] = "account_group = ?";
            $params[] = $data['account_group'];
        }

        // 상태(status) 변경
        if (isset($data['status'])) {
            $updates[] = "status = ?";
            $params[] = $data['status'];
        }

        // 역할(role) 변경
        if (isset($data['role'])) {
            $updates[] = "role = ?";
            $params[] = $data['role'];
        }

        // 가입일자(created_at) 변경
        if (isset($data['created_at']) && !empty($data['created_at'])) {
            // datetime-local 형식(2024-11-19T14:30)을 MySQL DATETIME 형식으로 변환
            $createdAt = str_replace('T', ' ', $data['created_at']) . ':00';
            $updates[] = "created_at = ?";
            $params[] = $createdAt;
        }

        if (empty($updates)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            ob_end_flush();
            exit;
        }

        // updated_at 추가
        $updates[] = "updated_at = NOW()";

        // 업데이트 실행
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";

        $db->execute($sql, $params);

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
        ob_end_flush();

    } elseif ($method === 'POST' && $action === 'delete') {
        // 회원 삭제 (Soft Delete)
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['user_id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            ob_end_flush();
            exit;
        }

        $userId = $data['user_id'];
        $deleteReason = $data['delete_reason'] ?? '관리자에 의한 삭제';
        $adminId = $_SESSION['admin_user_id'] ?? 'admin';

        // 회원 존재 여부 확인
        $user = $db->selectOne("SELECT id, user_id, email FROM users WHERE user_id = ? AND deleted_at IS NULL", [$userId]);

        if (!$user) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => '회원을 찾을 수 없습니다.']);
            ob_end_flush();
            exit;
        }

        // 관리자 계정은 삭제 불가
        $userRole = $db->selectOne("SELECT role FROM users WHERE user_id = ?", [$userId]);
        if ($userRole && in_array($userRole['role'], ['admin', 'super'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => '관리자 계정은 삭제할 수 없습니다.']);
            ob_end_flush();
            exit;
        }

        // Soft Delete 실행
        $db->update(
            "UPDATE users SET deleted_at = NOW(), deleted_by = ?, delete_reason = ? WHERE user_id = ?",
            [$adminId, $deleteReason, $userId]
        );

        // 로그 기록
        error_log("User deleted: {$userId} by {$adminId}, reason: {$deleteReason}");

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => '회원이 삭제되었습니다.',
            'data' => [
                'user_id' => $userId,
                'deleted_at' => date('Y-m-d H:i:s')
            ]
        ]);
        ob_end_flush();

    } elseif ($method === 'GET' && $action === 'deleted_list') {
        // 삭제된 회원 목록 조회
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;

        $totalResult = $db->selectOne("SELECT COUNT(*) as count FROM users WHERE deleted_at IS NOT NULL", []);
        $total = $totalResult['count'];

        $deletedUsers = $db->select("
            SELECT
                id, user_id, email, name,
                total_bonus, total_sales,
                created_at, deleted_at, deleted_by, delete_reason
            FROM users
            WHERE deleted_at IS NOT NULL
            ORDER BY deleted_at DESC
            LIMIT ? OFFSET ?
        ", [$limit, $offset]);

        ob_clean();
        echo json_encode([
            'success' => true,
            'users' => $deletedUsers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
        ob_end_flush();

    } elseif ($method === 'POST' && $action === 'restore') {
        // 삭제된 회원 복원
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['user_id'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            ob_end_flush();
            exit;
        }

        $userId = $data['user_id'];

        $db->update(
            "UPDATE users SET deleted_at = NULL, deleted_by = NULL, delete_reason = NULL WHERE user_id = ?",
            [$userId]
        );

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => '회원이 복원되었습니다.'
        ]);
        ob_end_flush();

    } elseif ($method === 'POST' && $action === 'settle_debt') {
        // 외상 정산
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? '';

        if (empty($userId)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            ob_end_flush();
            exit;
        }

        // 회원 조회
        $user = $db->selectOne("SELECT id, user_id, debt_amount FROM users WHERE user_id = ?", [$userId]);
        if (!$user) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User not found']);
            ob_end_flush();
            exit;
        }

        $debtAmount = floatval($user['debt_amount'] ?? 0);

        if ($debtAmount <= 0) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => '정산할 외상이 없습니다.']);
            ob_end_flush();
            exit;
        }

        // 외상 정산 (0으로 설정)
        $db->execute("UPDATE users SET debt_amount = 0 WHERE user_id = ?", [$userId]);

        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => "외상 ${debtAmount}이 정산되었습니다.",
            'settled_amount' => $debtAmount
        ]);
        ob_end_flush();

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        ob_end_flush();
    }

} catch (Exception $e) {
    error_log('Users Manage Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    ob_end_flush();
}
