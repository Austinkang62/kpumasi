<?php
/**
 * 후원인의 좌/우 위치 사용 가능 여부 확인 API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

try {
    $db = Database::getInstance();

    // 후원인 ID 확인
    $sponsorId = $_GET['sponsor_id'] ?? '';

    if (empty($sponsorId)) {
        echo json_encode([
            'success' => false,
            'message' => '후원인 ID가 필요합니다.'
        ]);
        exit;
    }

    // 후원인 정보 조회
    $sponsor = $db->selectOne(
        "SELECT id, user_id, name, sponsor_id, sponsor_position FROM users WHERE user_id = ?",
        [$sponsorId]
    );

    if (!$sponsor) {
        echo json_encode([
            'success' => false,
            'message' => '존재하지 않는 후원인입니다.'
        ]);
        exit;
    }

    // 레벨 계산 (재귀적으로 상위 부모 카운트)
    function getBinaryLevel($db, $userId) {
        $level = 0;
        $currentId = $userId;
        while ($currentId) {
            $parent = $db->selectOne(
                "SELECT sponsor_id FROM users WHERE id = ?",
                [$currentId]
            );
            if ($parent && $parent['sponsor_id']) {
                $level++;
                $currentId = $parent['sponsor_id'];
            } else {
                break;
            }
        }
        return $level;
    }

    $sponsor['level'] = getBinaryLevel($db, $sponsor['id']);

    // 좌/우 하위 직추천인 조회 (sponsor_position: 1=좌측, 2=우측)
    $leftChild = $db->selectOne(
        "SELECT user_id, name FROM users WHERE sponsor_id = ? AND sponsor_position = 1",
        [$sponsor['id']]
    );

    $rightChild = $db->selectOne(
        "SELECT user_id, name FROM users WHERE sponsor_id = ? AND sponsor_position = 2",
        [$sponsor['id']]
    );

    // 좌/우 통계 조회 (재귀적으로 하위 카운트)
    function countBinaryDescendants($db, $parentId, $position) {
        $direct = $db->selectOne(
            "SELECT id FROM users WHERE sponsor_id = ? AND sponsor_position = ?",
            [$parentId, $position]
        );

        if (!$direct) {
            return 0;
        }

        $count = 1; // 직속 자식
        $count += countBinaryDescendants($db, $direct['id'], 1); // 좌측 하위
        $count += countBinaryDescendants($db, $direct['id'], 2); // 우측 하위

        return $count;
    }

    $leftCount = countBinaryDescendants($db, $sponsor['id'], 1);
    $rightCount = countBinaryDescendants($db, $sponsor['id'], 2);

    echo json_encode([
        'success' => true,
        'data' => [
            'sponsor' => [
                'user_id' => $sponsor['user_id'],
                'name' => $sponsor['name'],
                'level' => $sponsor['level']
            ],
            'positions' => [
                'left' => [
                    'available' => empty($leftChild),
                    'occupied_by' => $leftChild ? [
                        'user_id' => $leftChild['user_id'],
                        'name' => $leftChild['name']
                    ] : null
                ],
                'right' => [
                    'available' => empty($rightChild),
                    'occupied_by' => $rightChild ? [
                        'user_id' => $rightChild['user_id'],
                        'name' => $rightChild['name']
                    ] : null
                ]
            ],
            'statistics' => [
                'left_count' => $leftCount,
                'right_count' => $rightCount
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log('위치 확인 오류: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '위치 확인 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ]);
}
