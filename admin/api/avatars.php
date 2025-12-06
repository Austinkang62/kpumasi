<?php
/**
 * 아바타 관리 API
 * 아바타 리스트, 상세, 통계 조회
 */

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
        // 아바타 리스트 조회
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';

        $whereClause = 'WHERE (u.deleted_at IS NULL OR u.deleted_at = "") AND u.name LIKE "Avatar%"';
        $params = [];

        if (!empty($search)) {
            $whereClause .= " AND (u.user_id LIKE ? OR u.email LIKE ? OR u.name LIKE ?)";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam];
        }

        // 전체 개수
        $totalResult = $db->selectOne("SELECT COUNT(*) as count FROM users u $whereClause", $params);
        $total = $totalResult['count'];

        // 아바타 리스트 (is_main_account = 1인 회원들)
        $avatars = $db->select("
            SELECT
                u.id,
                u.user_id,
                u.name,
                u.email,
                u.phone,
                u.account_group,
                u.sponsor_id,
                u.sponsor_position,
                u.package_id,
                u.package_date,
                u.total_bonus,
                u.available_bonus,
                u.status,
                u.created_at,
                u.total_sales,
                u.parent_account_id,
                (SELECT COUNT(*) FROM users WHERE parent_account_id = u.user_id AND name LIKE 'Avatar%' AND (deleted_at IS NULL OR deleted_at = '')) as child_avatar_count
            FROM users u
            $whereClause
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$limit, $offset]));

        // 각 아바타의 세대 계산
        foreach ($avatars as &$avatar) {
            $generation = 1; // 기본 1세대
            $currentParentId = $avatar['parent_account_id'];
            $depth = 0;

            // parent_account_id를 따라 올라가면서 아바타만 카운트
            while (!empty($currentParentId) && $depth < 20) {
                // parent_account_id가 INT(내부 ID)인지 VARCHAR(user_id)인지 확인 필요
                // 먼저 내부 ID로 조회
                $parent = $db->selectOne("
                    SELECT id, user_id, name, parent_account_id
                    FROM users
                    WHERE id = ? AND (deleted_at IS NULL OR deleted_at = '')
                ", [$currentParentId]);

                // 없으면 user_id로 조회
                if (!$parent) {
                    $parent = $db->selectOne("
                        SELECT id, user_id, name, parent_account_id
                        FROM users
                        WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = '')
                    ", [$currentParentId]);
                }

                if ($parent) {
                    // 부모가 아바타인 경우에만 세대 증가
                    if (strpos($parent['name'], 'Avatar') === 0) {
                        $generation++;
                        $currentParentId = $parent['parent_account_id'];
                    } else {
                        // 부모가 일반 회원이면 중단 (현재 아바타는 1세대)
                        break;
                    }
                } else {
                    break;
                }
                $depth++;
            }

            $avatar['generation'] = $generation;
        }
        unset($avatar); // 참조 해제

        ob_clean();
        echo json_encode([
            'success' => true,
            'avatars' => $avatars,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ]);

    } elseif ($method === 'GET' && $action === 'detail') {
        // 아바타 상세 (하위 계정 포함)
        $avatarId = $_GET['avatar_id'] ?? '';

        if (empty($avatarId)) {
            throw new Exception('Avatar ID is required');
        }

        // 아바타 정보
        $avatar = $db->selectOne("
            SELECT
                u.id,
                u.user_id,
                u.name,
                u.email,
                u.phone,
                u.account_group,
                u.sponsor_id,
                u.sponsor_position,
                u.package_id,
                u.package_date,
                u.total_bonus,
                u.available_bonus,
                u.total_sales,
                u.usdt_address,
                u.bnb_address,
                u.status,
                u.created_at
            FROM users u
            WHERE u.user_id = ? AND (u.deleted_at IS NULL OR u.deleted_at = '')
        ", [$avatarId]);

        if (!$avatar) {
            throw new Exception('Avatar not found');
        }

        // 부모 아바타 정보 (이 아바타를 생산한 아바타)
        $parentAvatar = null;
        if (!empty($avatar['parent_account_id'])) {
            $parentAvatar = $db->selectOne("
                SELECT user_id, name, email, package_id, total_bonus, total_sales, status, created_at
                FROM users
                WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = '')
            ", [$avatar['parent_account_id']]);
        }

        // 조상 아바타들 (가계도 - 위로 추적) 및 세대 계산
        $ancestors = [];
        $currentParentId = $avatar['parent_account_id'];
        $depth = 0;
        $generation = 1;

        while (!empty($currentParentId) && $depth < 20) { // 무한루프 방지
            // parent_account_id가 INT(내부 ID)인지 VARCHAR(user_id)인지 확인
            $ancestor = $db->selectOne("
                SELECT id, user_id, name, parent_account_id, package_id, total_bonus, created_at
                FROM users
                WHERE id = ? AND (deleted_at IS NULL OR deleted_at = '')
            ", [$currentParentId]);

            // 없으면 user_id로 조회
            if (!$ancestor) {
                $ancestor = $db->selectOne("
                    SELECT id, user_id, name, parent_account_id, package_id, total_bonus, created_at
                    FROM users
                    WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = '')
                ", [$currentParentId]);
            }

            if ($ancestor) {
                // 부모가 아바타인 경우에만 조상 목록에 추가 및 세대 증가
                if (strpos($ancestor['name'], 'Avatar') === 0) {
                    $ancestors[] = $ancestor;
                    $currentParentId = $ancestor['parent_account_id'];
                    $generation++;
                } else {
                    // 부모가 일반 회원이면 중단
                    break;
                }
            } else {
                break;
            }
            $depth++;
        }

        $avatar['generation'] = $generation;

        // 자식 아바타들 (이 아바타가 생산한 아바타들)
        $childAvatars = $db->select("
            SELECT
                user_id,
                name,
                email,
                sponsor_id,
                sponsor_position,
                package_id,
                package_date,
                total_bonus,
                available_bonus,
                total_sales,
                status,
                created_at,
                (SELECT COUNT(*) FROM users WHERE parent_account_id = users.user_id AND name LIKE 'Avatar%' AND (deleted_at IS NULL OR deleted_at = '')) as child_count
            FROM users
            WHERE parent_account_id = ? AND name LIKE 'Avatar%' AND (deleted_at IS NULL OR deleted_at = '')
            ORDER BY created_at DESC
        ", [$avatarId]);

        // 아바타 그룹의 총 매출 (users 테이블의 total_sales 합계)
        $salesResult = $db->selectOne("
            SELECT
                SUM(total_sales) as total_sales,
                COUNT(*) as account_count
            FROM users
            WHERE (parent_account_id = ? OR user_id = ?) AND (deleted_at IS NULL OR deleted_at = '')
        ", [$avatarId, $avatarId]);

        // 아바타 그룹의 보너스 통계
        $bonusStats = $db->selectOne("
            SELECT
                SUM(CASE WHEN bonus_type = 'direct' THEN amount ELSE 0 END) as total_referral,
                SUM(CASE WHEN bonus_type = 'binary' THEN amount ELSE 0 END) as total_edge,
                SUM(CASE WHEN bonus_type = 'binary' THEN amount ELSE 0 END) as total_matching,
                SUM(CASE WHEN bonus_type = 'avatar' THEN amount ELSE 0 END) as total_rollup
            FROM bonuses
            WHERE user_id IN (
                SELECT id FROM users WHERE (parent_account_id = ? OR user_id = ?) AND (deleted_at IS NULL OR deleted_at = '')
            )
        ", [$avatarId, $avatarId]);

        ob_clean();
        echo json_encode([
            'success' => true,
            'avatar' => $avatar,
            'parent_avatar' => $parentAvatar,
            'ancestors' => $ancestors,
            'child_avatars' => $childAvatars,
            'sales' => $salesResult,
            'bonus_stats' => $bonusStats
        ]);

    } elseif ($method === 'GET' && $action === 'tree') {
        // 아바타의 조직도 트리 데이터
        $avatarId = $_GET['avatar_id'] ?? '';

        if (empty($avatarId)) {
            throw new Exception('Avatar ID is required');
        }

        // 아바타와 하위 계정들의 조직도
        $allAccounts = $db->select("
            SELECT user_id FROM users
            WHERE (parent_account_id = ? OR user_id = ?) AND deleted_at IS NULL
        ", [$avatarId, $avatarId]);

        $userIds = array_column($allAccounts, 'user_id');

        if (empty($userIds)) {
            throw new Exception('No accounts found');
        }

        // 재귀적으로 조직도 가져오기
        function getTreeData($db, $userId) {
            $user = $db->selectOne("
                SELECT
                    user_id,
                    name,
                    package_id,
                    package_date,
                    total_bonus,
                    status
                FROM users
                WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = '')
            ", [$userId]);

            if (!$user) return null;

            $children = $db->select("
                SELECT user_id
                FROM users
                WHERE sponsor_id = ? AND (deleted_at IS NULL OR deleted_at = '')
                ORDER BY sponsor_position ASC
            ", [$userId]);

            $user['children'] = [];
            foreach ($children as $child) {
                $childData = getTreeData($db, $child['user_id']);
                if ($childData) {
                    $user['children'][] = $childData;
                }
            }

            return $user;
        }

        $tree = getTreeData($db, $avatarId);

        ob_clean();
        echo json_encode([
            'success' => true,
            'tree' => $tree
        ]);

    } elseif ($method === 'GET' && $action === 'stats') {
        // 전체 아바타 통계
        $stats = $db->selectOne("
            SELECT
                COUNT(*) as total_avatars,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_avatars,
                SUM(CASE WHEN package_id > 0 THEN 1 ELSE 0 END) as avatars_with_package
            FROM users
            WHERE name LIKE 'Avatar%' AND (deleted_at IS NULL OR deleted_at = '')
        ");

        // 루트 아바타 수 (부모가 없는 최초 아바타)
        $rootAvatarsResult = $db->selectOne("
            SELECT COUNT(*) as root_avatars
            FROM users
            WHERE name LIKE 'Avatar%' AND (parent_account_id IS NULL OR parent_account_id = '') AND (deleted_at IS NULL OR deleted_at = '')
        ");

        $stats['root_avatars'] = $rootAvatarsResult['root_avatars'];

        // 자식 아바타를 가진 아바타 수
        // parent_account_id가 아바타인 경우만 카운트
        $parentAvatarsResult = $db->selectOne("
            SELECT COUNT(DISTINCT u.user_id) as parent_avatars
            FROM users u
            WHERE u.name LIKE 'Avatar%'
            AND (u.deleted_at IS NULL OR u.deleted_at = '')
            AND EXISTS (
                SELECT 1 FROM users child
                WHERE child.parent_account_id = u.id
                AND child.name LIKE 'Avatar%'
                AND (child.deleted_at IS NULL OR child.deleted_at = '')
            )
        ");

        $stats['parent_avatars'] = $parentAvatarsResult['parent_avatars'];

        ob_clean();
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);

    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();
