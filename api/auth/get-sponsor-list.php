<?php
/**
 * 추천인 산하 후원 가능한 회원 리스트 조회
 * GET /api/organization/get-sponsor-list.php?referral_id=XXXX
 */

// 에러 표시 활성화 (디버깅용)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GET 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // 추천인 코드 받기
    $referralId = $_GET['referral_id'] ?? null;

    if (!$referralId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '추천인 코드가 필요합니다.'
        ]);
        exit;
    }

    $db = Database::getInstance();

    // 추천인 존재 확인
    $referralQuery = "SELECT user_id, name FROM users WHERE user_id = ?";
    $referral = $db->selectOne($referralQuery, [$referralId]);

    if (!$referral) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '추천인을 찾을 수 없습니다.'
        ]);
        exit;
    }

    // BFS로 추천인 산하 모든 회원 조회 (sponsor_id 기준 바이너리 트리)
    $sponsorList = [];
    $queue = [['user_id' => $referralId, 'depth' => 0]];
    $visited = [];

    while (!empty($queue)) {
        $current = array_shift($queue);
        $currentUserId = $current['user_id'];
        $currentDepth = $current['depth'];

        // 중복 방문 방지
        if (in_array($currentUserId, $visited)) {
            continue;
        }
        $visited[] = $currentUserId;

        // 현재 노드의 자식들 조회 (sponsor_id 기준)
        $childrenQuery = "
            SELECT user_id, sponsor_position, name
            FROM users
            WHERE sponsor_id = ?
            ORDER BY sponsor_position ASC
        ";
        $children = $db->select($childrenQuery, [$currentUserId]);

        // 좌측/우측 확인
        $hasLeft = false;
        $hasRight = false;
        $leftChild = null;
        $rightChild = null;

        foreach ($children as $child) {
            if ($child['sponsor_position'] == 1) {
                $hasLeft = true;
                $leftChild = $child;
                $queue[] = ['user_id' => $child['user_id'], 'depth' => $currentDepth + 1];
            } elseif ($child['sponsor_position'] == 2) {
                $hasRight = true;
                $rightChild = $child;
                $queue[] = ['user_id' => $child['user_id'], 'depth' => $currentDepth + 1];
            }
        }

        // 현재 노드의 빈 자리 정보 추가
        if (!$hasLeft) {
            $sponsorList[] = [
                'user_id' => $currentUserId,
                'depth' => $currentDepth,
                'position' => 'left',
                'position_code' => 1,
                'position_text' => '좌',
                'available' => true
            ];
        }

        if (!$hasRight) {
            $sponsorList[] = [
                'user_id' => $currentUserId,
                'depth' => $currentDepth,
                'position' => 'right',
                'position_code' => 2,
                'position_text' => '우',
                'available' => true
            ];
        }

        // 최대 깊이 제한 (10단계)
        if ($currentDepth >= 10) {
            break;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'referral' => $referral,
            'sponsor_list' => $sponsorList,
            'total_count' => count($sponsorList)
        ]
    ]);

} catch (Exception $e) {
    error_log('Get sponsor list error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '조회 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
