<?php
/**
 * 스폰서 트리 조회 API
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$userId = $_GET['user_id'] ?? '';

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'user_id required']);
    exit;
}

try {
    $db = Database::getInstance();

    // 회원 조회
    $user = $db->selectOne("SELECT id, user_id FROM users WHERE user_id = ?", [$userId]);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // 직접 하위 조회 (1단계만)
    $directChildren = $db->select(
        "SELECT
            user_id,
            name,
            sponsor_position,
            created_at,
            (SELECT COUNT(*) FROM users WHERE sponsor_id = u.id) as child_count
         FROM users u
         WHERE sponsor_id = ?
         ORDER BY sponsor_position, created_at",
        [$user['id']]
    );

    // 각 직접 하위의 하위들도 조회 (2단계)
    $tree = [];
    foreach ($directChildren as $child) {
        $childUser = $db->selectOne("SELECT id FROM users WHERE user_id = ?", [$child['user_id']]);

        $grandChildren = $db->select(
            "SELECT
                user_id,
                name,
                sponsor_position,
                created_at,
                (SELECT COUNT(*) FROM users WHERE sponsor_id = u.id) as child_count
             FROM users u
             WHERE sponsor_id = ?
             ORDER BY sponsor_position, created_at",
            [$childUser['id']]
        );

        $tree[] = [
            'user_id' => $child['user_id'],
            'name' => $child['name'],
            'sponsor_position' => $child['sponsor_position'],
            'position_label' => $child['sponsor_position'] == 1 ? 'LEFT' : 'RIGHT',
            'created_at' => $child['created_at'],
            'child_count' => $child['child_count'],
            'children' => $grandChildren
        ];
    }

    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'direct_children' => count($directChildren),
        'tree' => $tree
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
