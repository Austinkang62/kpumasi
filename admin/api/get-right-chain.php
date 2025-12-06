<?php
/**
 * 특정 회원의 우측 체인 조회 API
 * sponsor_position = 2인 노드들을 끝까지 추적
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$userId = $_GET['user_id'] ?? '';
$direction = $_GET['direction'] ?? 'right'; // 'left' or 'right'

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'user_id required']);
    exit;
}

try {
    $db = Database::getInstance();

    // 시작 회원 조회
    $startUser = $db->selectOne("SELECT id, user_id, name FROM users WHERE user_id = ?", [$userId]);

    if (!$startUser) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // 체인 추적
    $chain = [];
    $currentUserId = $startUser['id'];
    $visitedUsers = [];
    $maxDepth = 100;
    $depth = 0;
    $targetPosition = ($direction === 'right') ? 2 : 1;

    while ($currentUserId && $depth < $maxDepth) {
        // 무한루프 방지
        if (in_array($currentUserId, $visitedUsers)) {
            break;
        }
        $visitedUsers[] = $currentUserId;

        // 현재 노드의 정보
        $currentNode = $db->selectOne(
            "SELECT id, user_id, name, created_at,
                    (SELECT COUNT(*) FROM users WHERE sponsor_id = u.user_id) as total_children,
                    (SELECT COUNT(*) FROM users WHERE sponsor_id = u.user_id AND sponsor_position = 1) as left_children,
                    (SELECT COUNT(*) FROM users WHERE sponsor_id = u.user_id AND sponsor_position = 2) as right_children
             FROM users u
             WHERE id = ?",
            [$currentUserId]
        );

        if (!$currentNode) {
            break;
        }

        // 체인에 추가
        $chain[] = [
            'user_id' => $currentNode['user_id'],
            'name' => $currentNode['name'],
            'created_at' => $currentNode['created_at'],
            'depth' => $depth,
            'total_children' => $currentNode['total_children'],
            'left_children' => $currentNode['left_children'],
            'right_children' => $currentNode['right_children']
        ];

        // 다음 노드 찾기 (우측 또는 좌측 자식)
        $nextNode = $db->selectOne(
            "SELECT id, user_id FROM users
             WHERE sponsor_id = ? AND sponsor_position = ?
             ORDER BY created_at ASC
             LIMIT 1",
            [$currentNode['user_id'], $targetPosition]
        );

        if (!$nextNode) {
            // 더 이상 진행할 수 없음
            break;
        }

        $currentUserId = $nextNode['id'];
        $depth++;
    }

    echo json_encode([
        'success' => true,
        'start_user' => $userId,
        'direction' => $direction,
        'chain_length' => count($chain),
        'chain' => $chain
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
