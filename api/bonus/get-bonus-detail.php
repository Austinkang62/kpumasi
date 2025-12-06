<?php
/**
 * 보너스 상세 내역 조회 API
 * GET /api/bonus/get-bonus-detail.php?type={bonusType}
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../../config/database.php';
}
if (!class_exists('Database')) {
    require_once __DIR__ . '/../../classes/Database.php';
}

/**
 * 엣지 정보 계산 함수
 * @param int $fromUserId 발생 회원 ID
 * @param Database $db
 * @return string "좌상N" 또는 "우상N" 또는 "-"
 */
function calculateEdgeInfo($fromUserId, $db) {
    try {
        // 발생 회원 정보 조회
        $user = $db->select(
            "SELECT sponsor_id, sponsor_position FROM users WHERE user_id = :user_id",
            [':user_id' => $fromUserId]
        );

        if (empty($user)) {
            return '-';
        }

        $currentUserId = $fromUserId;
        $lastPosition = intval($user[0]['sponsor_position']); // 1=좌측, 2=우측
        $level = 0;

        // 상위로 올라가면서 꺾임 찾기
        while (true) {
            $parent = $db->select(
                "SELECT user_id, sponsor_id, sponsor_position FROM users WHERE user_id = :user_id",
                [':user_id' => $currentUserId]
            );

            if (empty($parent) || empty($parent[0]['sponsor_id'])) {
                // 루트까지 갔는데 꺾임 없음
                return '-';
            }

            $level++;
            $currentPosition = intval($parent[0]['sponsor_position']);

            // 꺾임 발생 확인
            if ($level > 1 && $currentPosition !== $lastPosition) {
                // 꺾임 발견!
                $direction = $lastPosition === 1 ? '좌' : '우';
                return $direction . '상' . ($level - 1);
            }

            $lastPosition = $currentPosition;
            $currentUserId = $parent[0]['sponsor_id'];
        }

    } catch (Exception $e) {
        error_log('Edge info calculation error: ' . $e->getMessage());
        return '-';
    }
}

try {
    $bonusType = $_GET['type'] ?? '';

    if (empty($bonusType)) {
        echo json_encode([
            'success' => false,
            'message' => '보너스 타입이 필요합니다.'
        ]);
        exit;
    }

    $db = Database::getInstance();

    // 보너스 타입에 따라 다른 테이블/쿼리 사용
    if ($bonusType === 'rollup') {
        // rollup_bonuses 테이블에서 조회
        $query = "
            SELECT
                rb.*,
                u_to.user_id as to_user_id,
                u_from.user_id as from_user_id
            FROM rollup_bonuses rb
            LEFT JOIN users u_to ON rb.user_id = u_to.id
            LEFT JOIN users u_from ON rb.from_user_id = u_from.id
            WHERE rb.status = 'paid'
            ORDER BY rb.created_at DESC
            LIMIT 100
        ";

        $bonuses = $db->select($query);
    } else {
        // bonuses 테이블에서 조회 (referral, edge, matching)
        // edge 타입은 cancelled 상태도 포함 (엣지 미발생)
        if ($bonusType === 'edge') {
            $query = "
                SELECT
                    b.*,
                    u_to.user_id as to_user_id,
                    u_from.user_id as from_user_id
                FROM bonuses b
                LEFT JOIN users u_to ON b.user_id = u_to.id AND b.user_id > 0
                LEFT JOIN users u_from ON b.from_user_id = u_from.id
                WHERE b.bonus_type = :bonus_type
                AND b.status IN ('paid', 'cancelled')
                ORDER BY b.created_at DESC
                LIMIT 100
            ";
        } else {
            $query = "
                SELECT
                    b.*,
                    u_to.user_id as to_user_id,
                    u_from.user_id as from_user_id
                FROM bonuses b
                LEFT JOIN users u_to ON b.user_id = u_to.id
                LEFT JOIN users u_from ON b.from_user_id = u_from.id
                WHERE b.bonus_type = :bonus_type
                AND b.status = 'paid'
                ORDER BY b.created_at DESC
                LIMIT 100
            ";
        }

        $bonuses = $db->select($query, [':bonus_type' => $bonusType]);

        // edge 타입일 경우 엣지 정보 추가
        if ($bonusType === 'edge' && !empty($bonuses)) {
            foreach ($bonuses as &$bonus) {
                $bonus['edge_info'] = calculateEdgeInfo($bonus['from_user_id'], $db);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $bonuses,
        'count' => count($bonuses)
    ]);

} catch (Exception $e) {
    error_log('Bonus detail API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '서버 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
