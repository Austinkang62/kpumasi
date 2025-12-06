<?php
/**
 * 매출 입력 API (관리자 전용)
 * 매출 입력 시 보너스 자동 계산 및 지급
 */

// 에러 리포팅 설정
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 출력 버퍼링 시작
ob_start();

try {
    // config 로드
    require_once __DIR__ . '/../../config/database.php';

    // JSON 헤더 설정
    header('Content-Type: application/json; charset=utf-8');

    // 세션 시작
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

    if ($method === 'POST' && $action === 'create') {
        // 매출 입력 및 보너스 지급
        $data = json_decode(file_get_contents('php://input'), true);

        $userId = $data['user_id'] ?? '';
        $amount = $data['amount'] ?? 0;
        $salesDate = $data['sales_date'] ?? '';
        $txid = $data['txid'] ?? '';
        $memo = $data['memo'] ?? '';

        // 유효성 검사
        if (empty($userId)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => '회원 ID가 필요합니다.']);
            ob_end_flush();
            exit;
        }

        if ($amount <= 0) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => '올바른 금액을 입력해주세요.']);
            ob_end_flush();
            exit;
        }

        // Database 인스턴스 생성
        $db = Database::getInstance();

        // 회원 정보 조회
        $user = $db->selectOne("SELECT id, user_id, name FROM users WHERE user_id = ? AND deleted_at IS NULL", [$userId]);

        if (!$user) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => '회원을 찾을 수 없습니다.']);
            ob_end_flush();
            exit;
        }

        $userInternalId = $user['id'];

        // 매출일자 형식 변환 (datetime-local -> MySQL DATETIME)
        $salesDateTime = str_replace('T', ' ', $salesDate) . ':00';

        // 패키지 ID 결정 ($100 = package_id 2, $50 = package_id 1)
        $packageId = ($amount == 100) ? 2 : (($amount == 50) ? 1 : 2);

        // 트랜잭션 시작
        $db->beginTransaction();

        try {
            // 1. Sales 테이블에 매출 기록
            $db->execute("
                INSERT INTO sales (user_id, package_id, amount, payment_method, txid, status, confirmed_at, created_at)
                VALUES (?, ?, ?, 'USDT_TRC20', ?, 'completed', ?, ?)
            ", [$userInternalId, $packageId, $amount, $txid, $salesDateTime, $salesDateTime]);

            $saleId = $db->lastInsertId();

            // 2. Users 테이블 업데이트 (패키지 정보)
            $db->execute("
                UPDATE users
                SET
                    package_id = ?,
                    package_date = ?,
                    total_sales = total_sales + ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [$packageId, $salesDateTime, $amount, $userInternalId]);

            // 3. 보너스 계산 및 지급
            $bonusResult = calculateAndDistributeBonuses($db, $userInternalId, $saleId, $amount, $packageId);

            // 4. Transaction 로그 기록
            $description = "패키지 구매 (\${$amount})";
            $db->execute("
                INSERT INTO transactions (user_id, type, amount, currency, reference_id, description, created_at)
                VALUES (?, 'purchase', ?, 'USDT', ?, ?, ?)
            ", [$userInternalId, $amount, $saleId, $description, $salesDateTime]);

            // 커밋
            $db->commit();

            // 5. 아바타 자동 생성은 Cron Job에서 처리 (매 5분마다)
            // 즉시 생성하지 않음 - Lock 충돌 방지 및 안정성 향상

            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => '매출 입력 및 보너스 지급 완료',
                'sale_id' => $saleId,
                'bonus_count' => $bonusResult['count'],
                'total_bonus_amount' => $bonusResult['total_amount'],
                'note' => '아바타는 5분 이내에 자동 생성됩니다.'
            ]);
            ob_end_flush();

        } catch (Exception $e) {
            // 롤백
            $db->rollback();
            throw $e;
        }

    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        ob_end_flush();
    }

} catch (Exception $e) {
    error_log('Sales Input API Error: ' . $e->getMessage());
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

/**
 * 보너스 계산 및 분배
 * 4가지 보너스: Referral, Edge, Matching, Rollup
 */
function calculateAndDistributeBonuses($db, $userInternalId, $saleId, $amount, $packageId) {
    $bonusCount = 0;
    $totalBonusAmount = 0;

    // 1. Referral Bonus (추천 보너스)
    // 직접 추천인에게 25% 지급
    $referralResult = calculateReferralBonus($db, $userInternalId, $saleId, $amount, $packageId);
    $bonusCount += $referralResult['count'];
    $totalBonusAmount += $referralResult['amount'];

    // 2. Edge Bonus (엣지 보너스)
    // Binary tree에서 상위로 올라가면서 처음 방향이 바뀐 지점의 부모에게 지급
    $edgeResult = calculateEdgeBonus($db, $userInternalId, $saleId, $amount, $packageId);
    $bonusCount += $edgeResult['count'];
    $totalBonusAmount += $edgeResult['amount'];
    $edgeReceiverId = $edgeResult['receiver_id'] ?? null;

    // 3. Matching Bonus (매칭 보너스)
    // Edge 보너스 수령자의 추천인에게 지급
    $matchingResult = calculateMatchingBonus($db, $userInternalId, $saleId, $amount, $packageId, $edgeReceiverId);
    $bonusCount += $matchingResult['count'];
    $totalBonusAmount += $matchingResult['amount'];

    // 4. Rollup Bonus (롤업 보너스)
    // 추천 라인을 따라 상위 25레벨까지 $1씩 지급
    // 직접 추천인 수에 따라 받을 수 있는 레벨 제한
    $rollupResult = calculateRollupBonus($db, $userInternalId, $saleId, $amount, $packageId);
    $bonusCount += $rollupResult['count'];
    $totalBonusAmount += $rollupResult['amount'];

    return [
        'count' => $bonusCount,
        'total_amount' => $totalBonusAmount
    ];
}

/**
 * 1. Referral Bonus (추천 보너스)
 * 직접 추천인에게 25% 지급
 */
function calculateReferralBonus($db, $userInternalId, $saleId, $amount, $packageId) {
    $count = 0;
    $totalAmount = 0;

    // 추천인 조회
    $referrer = $db->selectOne("
        SELECT referral_id FROM users WHERE id = ?
    ", [$userInternalId]);

    if ($referrer && $referrer['referral_id']) {
        $referrerId = $referrer['referral_id'];

        // 보너스 수령자 확인 (아바타인 경우 캐시만 부모에게 전달)
        $receiver = $db->selectOne("
            SELECT id, is_avatar, referral_id FROM users WHERE id = ?
        ", [$referrerId]);

        // 보너스 금액: 매출의 25%
        $bonusAmount = $amount * 0.25;

        // 보너스 분할: 65% 캐시, 35% 아바타 포인트
        $cashBonus = $bonusAmount * 0.65;
        $avatarPoints = $bonusAmount * 0.35;

        // 보너스 지급 기록 - 캐시 (65%)
        $db->execute("
            INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at)
            VALUES (?, ?, 'referral', 'cash', ?, ?, 1, 'paid', NOW())
        ", [$referrerId, $userInternalId, $cashBonus, $amount]);

        // 보너스 지급 기록 - 아바타 포인트 (35%)
        $db->execute("
            INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at)
            VALUES (?, ?, 'referral', 'avatar_point', ?, ?, 1, 'paid', NOW())
        ", [$referrerId, $userInternalId, $avatarPoints, $amount]);

        if ($receiver && $receiver['is_avatar']) {
            // 아바타인 경우: 65% 캐시는 부모에게, 35% 아바타 포인트는 본인에게
            $parentId = $receiver['referral_id'];

            // 부모에게 캐시 지급
            $db->execute("
                UPDATE users
                SET
                    total_bonus = total_bonus + ?,
                    available_bonus = available_bonus + ?,
                    total_referral_bonus = total_referral_bonus + ?
                WHERE id = ?
            ", [$cashBonus, $cashBonus, $cashBonus, $parentId]);

            // 아바타 본인에게 아바타 포인트 적립
            $db->execute("
                UPDATE users
                SET avatar_points = avatar_points + ?
                WHERE id = ?
            ", [$avatarPoints, $referrerId]);

        } else {
            // 일반 회원인 경우: 본인에게 모두 지급
            $db->execute("
                UPDATE users
                SET
                    total_bonus = total_bonus + ?,
                    available_bonus = available_bonus + ?,
                    avatar_points = avatar_points + ?,
                    total_referral_bonus = total_referral_bonus + ?
                WHERE id = ?
            ", [$bonusAmount, $cashBonus, $avatarPoints, $bonusAmount, $referrerId]);
        }

        $count = 1;
        $totalAmount = $bonusAmount;
    }

    return ['count' => $count, 'amount' => $totalAmount];
}

/**
 * 2. Edge Bonus (엣지 보너스)
 * Binary tree에서 상위로 올라가면서 처음 방향이 바뀐 지점의 부모에게 25% 지급
 * BonusDistributor와 동일한 로직 사용 (users 테이블 기반)
 */
function calculateEdgeBonus($db, $userInternalId, $saleId, $amount, $packageId) {
    $count = 0;
    $totalAmount = 0;

    // 1. 매출 발생 회원의 정보 조회 (users 테이블)
    $newUser = $db->selectOne("
        SELECT id, user_id, sponsor_id, sponsor_position
        FROM users
        WHERE id = ?
    ", [$userInternalId]);

    if (!$newUser || !$newUser['sponsor_position']) {
        return ['count' => $count, 'amount' => $totalAmount, 'receiver_id' => null];
    }

    $bonusAmount = $amount * 0.25; // 25%
    $cashBonus = $bonusAmount * 0.65;
    $avatarPoints = $bonusAmount * 0.35;

    // 2. 엣지(꺾임) 찾기
    $currentUserId = $newUser['user_id'];
    $lastPosition = intval($newUser['sponsor_position']); // 1=좌측, 2=우측
    $level = 0;
    $edgeReceiverId = null;

    while (true) {
        $parent = $db->selectOne("
            SELECT id, user_id, sponsor_id, sponsor_position
            FROM users
            WHERE user_id = ?
        ", [$currentUserId]);

        if (!$parent || !$parent['sponsor_id']) {
            // 루트까지 갔는데 꺾임 없음 - 엣지 보너스 발생 안 함
            break;
        }

        $level++;
        $currentPosition = intval($parent['sponsor_position']);

        // 꺾임 발생 확인
        if ($level > 1 && $currentPosition !== $lastPosition) {
            // 꺾임 발견! 꺾인 지점의 부모 = 받는 사람
            $edgeReceiver = $db->selectOne("
                SELECT id, user_id
                FROM users
                WHERE user_id = ?
            ", [$parent['sponsor_id']]);

            if ($edgeReceiver) {
                $edgeReceiverId = $edgeReceiver['id'];

                // 보너스 지급 기록 - 캐시 (65%)
                $db->execute("
                    INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at)
                    VALUES (?, ?, 'edge', 'cash', ?, ?, ?, 'paid', NOW())
                ", [$edgeReceiverId, $userInternalId, $cashBonus, $amount, $level]);

                // 보너스 지급 기록 - 아바타 포인트 (35%)
                $db->execute("
                    INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at)
                    VALUES (?, ?, 'edge', 'avatar_point', ?, ?, ?, 'paid', NOW())
                ", [$edgeReceiverId, $userInternalId, $avatarPoints, $amount, $level]);

                // 수령자 확인 (아바타 여부)
                $receiver = $db->selectOne("
                    SELECT id, is_avatar, referral_id FROM users WHERE id = ?
                ", [$edgeReceiverId]);

                if ($receiver && $receiver['is_avatar']) {
                    // 아바타: 65% 캐시는 부모에게, 35% 아바타 포인트는 본인에게
                    $parentId = $receiver['referral_id'];

                    $db->execute("
                        UPDATE users
                        SET
                            total_bonus = total_bonus + ?,
                            available_bonus = available_bonus + ?,
                            total_edge_bonus = total_edge_bonus + ?
                        WHERE id = ?
                    ", [$cashBonus, $cashBonus, $cashBonus, $parentId]);

                    $db->execute("
                        UPDATE users
                        SET avatar_points = avatar_points + ?
                        WHERE id = ?
                    ", [$avatarPoints, $edgeReceiverId]);

                } else {
                    // 일반 회원: 본인에게 모두 지급
                    $db->execute("
                        UPDATE users
                        SET
                            total_bonus = total_bonus + ?,
                            available_bonus = available_bonus + ?,
                            avatar_points = avatar_points + ?,
                            total_edge_bonus = total_edge_bonus + ?
                        WHERE id = ?
                    ", [$bonusAmount, $cashBonus, $avatarPoints, $bonusAmount, $edgeReceiverId]);
                }

                $count = 1;
                $totalAmount = $bonusAmount;

                return [
                    'count' => $count,
                    'amount' => $totalAmount,
                    'receiver_id' => $edgeReceiverId
                ];
            }
            break;
        }

        $lastPosition = $currentPosition;
        $currentUserId = $parent['sponsor_id'];
    }

    return ['count' => $count, 'amount' => $totalAmount, 'receiver_id' => null];
}

/**
 * 3. Matching Bonus (매칭 보너스)
 * Edge 보너스를 받은 사람의 추천인에게 25% 지급
 * @param int $edgeReceiverId Edge 보너스를 받은 회원 ID (calculateEdgeBonus 결과 필요)
 */
function calculateMatchingBonus($db, $userInternalId, $saleId, $amount, $packageId, $edgeReceiverId = null) {
    $count = 0;
    $totalAmount = 0;

    if (!$edgeReceiverId) {
        return ['count' => $count, 'amount' => $totalAmount]; // Edge가 없으면 Matching도 없음
    }

    $bonusAmount = $amount * 0.25; // 25%
    $cashBonus = $bonusAmount * 0.65;
    $avatarPoints = $bonusAmount * 0.35;

    // Edge 보너스 수령자의 추천인 찾기
    $edgeReceiver = $db->selectOne("
        SELECT id, referral_id FROM users WHERE id = ?
    ", [$edgeReceiverId]);

    if (!$edgeReceiver || !$edgeReceiver['referral_id']) {
        return ['count' => $count, 'amount' => $totalAmount]; // 추천인 없음
    }

    $matchingReceiverId = $edgeReceiver['referral_id'];

    // 보너스 지급 기록 - 캐시 (65%)
    $db->execute("
        INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at)
        VALUES (?, ?, 'matching', 'cash', ?, ?, 1, 'paid', NOW())
    ", [$matchingReceiverId, $userInternalId, $cashBonus, $amount]);

    // 보너스 지급 기록 - 아바타 포인트 (35%)
    $db->execute("
        INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at)
        VALUES (?, ?, 'matching', 'avatar_point', ?, ?, 1, 'paid', NOW())
    ", [$matchingReceiverId, $userInternalId, $avatarPoints, $amount]);

    // 수령자 확인 (아바타 여부)
    $receiver = $db->selectOne("
        SELECT id, is_avatar, referral_id FROM users WHERE id = ?
    ", [$matchingReceiverId]);

    if ($receiver && $receiver['is_avatar']) {
        // 아바타: 65% 캐시는 부모에게, 35% 아바타 포인트는 본인에게
        $parentId = $receiver['referral_id'];

        $db->execute("
            UPDATE users
            SET
                total_bonus = total_bonus + ?,
                available_bonus = available_bonus + ?,
                total_matching_bonus = total_matching_bonus + ?
            WHERE id = ?
        ", [$cashBonus, $cashBonus, $cashBonus, $parentId]);

        $db->execute("
            UPDATE users
            SET avatar_points = avatar_points + ?
            WHERE id = ?
        ", [$avatarPoints, $matchingReceiverId]);

    } else {
        // 일반 회원: 본인에게 모두 지급
        $db->execute("
            UPDATE users
            SET
                total_bonus = total_bonus + ?,
                available_bonus = available_bonus + ?,
                avatar_points = avatar_points + ?,
                total_matching_bonus = total_matching_bonus + ?
            WHERE id = ?
        ", [$bonusAmount, $cashBonus, $avatarPoints, $bonusAmount, $matchingReceiverId]);
    }

    $count = 1;
    $totalAmount = $bonusAmount;

    return ['count' => $count, 'amount' => $totalAmount];
}

/**
 * 4. Rollup Bonus (롤업 보너스)
 * 추천 라인을 따라 상위 25레벨까지 $1씩 지급
 * 직접 추천인 수에 따라 받을 수 있는 최대 레벨 제한:
 * - 0명: 10레벨까지, 1명: 12레벨까지, 2명: 14레벨까지, 3명: 16레벨까지
 * - 4명: 18레벨까지, 5명: 20레벨까지, 6명: 22레벨까지, 7명 이상: 25레벨까지
 */
function calculateRollupBonus($db, $userInternalId, $saleId, $amount, $packageId) {
    $count = 0;
    $totalAmount = 0;

    // 각 레벨당 $1.00 지급
    $bonusPerLevel = 1.00;

    // 최대 25레벨까지 상위로 올라감
    $currentUserId = $userInternalId;
    $maxPossibleLevel = 25;

    for ($level = 1; $level <= $maxPossibleLevel; $level++) {
        // 상위 추천인 조회
        $parent = $db->selectOne("
            SELECT referral_id FROM users WHERE id = ?
        ", [$currentUserId]);

        if (!$parent || !$parent['referral_id']) {
            break; // 더 이상 상위가 없음
        }

        $parentId = $parent['referral_id'];

        // 상위 회원의 직접 추천인 수 조회
        $directReferralCount = $db->selectOne("
            SELECT COUNT(*) as cnt
            FROM users
            WHERE referral_id = ?
        ", [$parentId]);

        $referralCount = (int)($directReferralCount['cnt'] ?? 0);

        // 직접 추천인 수에 따른 최대 레벨 결정
        $maxLevelForUser = 0;
        if ($referralCount >= 7) {
            $maxLevelForUser = 25;
        } elseif ($referralCount == 6) {
            $maxLevelForUser = 22;
        } elseif ($referralCount == 5) {
            $maxLevelForUser = 20;
        } elseif ($referralCount == 4) {
            $maxLevelForUser = 18;
        } elseif ($referralCount == 3) {
            $maxLevelForUser = 16;
        } elseif ($referralCount == 2) {
            $maxLevelForUser = 14;
        } elseif ($referralCount == 1) {
            $maxLevelForUser = 12;
        } elseif ($referralCount == 0) {
            $maxLevelForUser = 10;
        }

        // 현재 레벨이 최대 레벨을 초과하면 보너스 지급하지 않음
        if ($level > $maxLevelForUser) {
            $currentUserId = $parentId;
            continue;
        }

        // 보너스 지급
        $bonusAmount = $bonusPerLevel;

        // 보너스 수령자 확인 (아바타인 경우 캐시만 부모에게 전달)
        $receiver = $db->selectOne("
            SELECT id, is_avatar, referral_id FROM users WHERE id = ?
        ", [$parentId]);

        // 보너스 분할: 65% 캐시, 35% 아바타 포인트
        $cashBonus = $bonusAmount * 0.65;
        $avatarPoints = $bonusAmount * 0.35;

        // 보너스 지급 기록 - 캐시 (65%)
        $db->execute("
            INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at)
            VALUES (?, ?, 'rollup', 'cash', ?, ?, ?, 'paid', NOW())
        ", [$parentId, $userInternalId, $cashBonus, $amount, $level]);

        // 보너스 지급 기록 - 아바타 포인트 (35%)
        $db->execute("
            INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, level, status, created_at)
            VALUES (?, ?, 'rollup', 'avatar_point', ?, ?, ?, 'paid', NOW())
        ", [$parentId, $userInternalId, $avatarPoints, $amount, $level]);

        if ($receiver && $receiver['is_avatar']) {
            // 아바타인 경우: 65% 캐시는 부모에게, 35% 아바타 포인트는 본인에게
            $ownerId = $receiver['referral_id'];

            // 부모에게 캐시 지급
            $db->execute("
                UPDATE users
                SET
                    total_bonus = total_bonus + ?,
                    available_bonus = available_bonus + ?,
                    total_rollup_bonus = total_rollup_bonus + ?
                WHERE id = ?
            ", [$cashBonus, $cashBonus, $cashBonus, $ownerId]);

            // 아바타 본인에게 아바타 포인트 적립
            $db->execute("
                UPDATE users
                SET avatar_points = avatar_points + ?
                WHERE id = ?
            ", [$avatarPoints, $parentId]);

        } else {
            // 일반 회원인 경우: 본인에게 모두 지급
            $db->execute("
                UPDATE users
                SET
                    total_bonus = total_bonus + ?,
                    available_bonus = available_bonus + ?,
                    avatar_points = avatar_points + ?,
                    total_rollup_bonus = total_rollup_bonus + ?
                WHERE id = ?
            ", [$bonusAmount, $cashBonus, $avatarPoints, $bonusAmount, $parentId]);
        }

        $count++;
        $totalAmount += $bonusAmount;

        $currentUserId = $parentId;
    }

    return ['count' => $count, 'amount' => $totalAmount];
}

/**
 * 스필오버 위치 찾기 (Binary Tree)
 * users 테이블의 sponsor_id/sponsor_position 기준으로 BFS 방식으로 빈 자리 찾기
 */
function findSpilloverPosition($db, $referrerId) {
    // 추천인 정보 조회
    $referrer = $db->selectOne("
        SELECT id, user_id
        FROM users
        WHERE id = ?
    ", [$referrerId]);

    if (!$referrer) {
        error_log("findSpilloverPosition: 추천인을 찾을 수 없음 - referrerId: {$referrerId}");
        return null;
    }

    // BFS 큐 초기화 (추천인부터 시작)
    $queue = [['id' => $referrer['id'], 'user_id' => $referrer['user_id']]];
    $visited = []; // 무한 루프 방지

    while (count($queue) > 0) {
        $current = array_shift($queue);
        $currentId = $current['id'];

        // 이미 방문한 노드는 스킵 (무한 루프 방지)
        if (in_array($currentId, $visited)) {
            continue;
        }
        $visited[] = $currentId;

        // 현재 노드의 자식들 조회 (sponsor_id가 현재 노드의 user_id인 회원들)
        $children = $db->select("
            SELECT id, user_id, sponsor_position
            FROM users
            WHERE sponsor_id = ?
            ORDER BY sponsor_position
        ", [$current['user_id']]);

        // LEFT (position=1) 자리 확인
        $hasLeft = false;
        $hasRight = false;
        $leftChild = null;
        $rightChild = null;

        foreach ($children as $child) {
            if ($child['sponsor_position'] == 1) {
                $hasLeft = true;
                $leftChild = $child;
            } elseif ($child['sponsor_position'] == 2) {
                $hasRight = true;
                $rightChild = $child;
            }
        }

        // 왼쪽 자리가 비어있으면 반환
        if (!$hasLeft) {
            error_log("findSpilloverPosition: LEFT 빈 자리 발견 - parent_id: {$currentId}, position: 1");
            return [
                'parent_id' => $currentId,
                'position' => 1  // sponsor_position 값으로 직접 사용
            ];
        }

        // 오른쪽 자리가 비어있으면 반환
        if (!$hasRight) {
            error_log("findSpilloverPosition: RIGHT 빈 자리 발견 - parent_id: {$currentId}, position: 2");
            return [
                'parent_id' => $currentId,
                'position' => 2  // sponsor_position 값으로 직접 사용
            ];
        }

        // 양쪽 자리가 다 차있으면 자식들을 큐에 추가 (BFS 계속)
        if ($leftChild) {
            $queue[] = ['id' => $leftChild['id'], 'user_id' => $leftChild['user_id']];
        }
        if ($rightChild) {
            $queue[] = ['id' => $rightChild['id'], 'user_id' => $rightChild['user_id']];
        }
    }

    // 빈 자리를 찾지 못함
    error_log("findSpilloverPosition: 빈 자리를 찾지 못함 - referrerId: {$referrerId}");
    return null;
}

/**
 * 아바타 계정 생성
 */
function createAvatarAccount($db, $parentUserId, $packageId = 2) {
    // 1. 부모 회원 정보 조회
    $parent = $db->selectOne("
        SELECT id, user_id, name, avatar_count
        FROM users
        WHERE id = ?
    ", [$parentUserId]);

    if (!$parent) {
        throw new Exception("부모 회원을 찾을 수 없습니다.");
    }

    // 2. 아바타 user_id 생성 (전체 시스템에서 고유한 번호)
    // 전체 아바타 수 조회 (이름이 'Avatar'로 시작하는 회원 수)
    $totalAvatarsResult = $db->selectOne("
        SELECT COUNT(*) as total
        FROM users
        WHERE name LIKE 'Avatar %'
        AND deleted_at IS NULL
    ");
    $totalAvatars = intval($totalAvatarsResult['total']) + 1;

    // AVA + 5자리 숫자 형식 (예: AVA00001, AVA00002)
    $avatarUserId = 'AVA' . str_pad($totalAvatars, 5, '0', STR_PAD_LEFT);

    // 중복 체크 (만약 이미 존재하면 다음 번호 사용)
    $attempts = 0;
    while ($attempts < 100) {
        $exists = $db->selectOne("SELECT id FROM users WHERE user_id = ?", [$avatarUserId]);
        if (!$exists) {
            break;
        }
        $totalAvatars++;
        $avatarUserId = 'AVA' . str_pad($totalAvatars, 5, '0', STR_PAD_LEFT);
        $attempts++;
    }

    if ($attempts >= 100) {
        throw new Exception("아바타 ID 생성 실패: 중복된 ID가 너무 많습니다.");
    }

    // 3. 스필오버 위치 찾기
    $spilloverPos = findSpilloverPosition($db, $parentUserId);

    if (!$spilloverPos) {
        // 빈 자리를 찾지 못한 경우, 부모 바로 아래 왼쪽에 배치
        error_log("createAvatarAccount: 스필오버 위치를 찾지 못해 기본값(LEFT) 사용 - parentUserId: {$parentUserId}");
        $spilloverPos = [
            'parent_id' => $parentUserId,
            'position' => 1  // LEFT
        ];
    }

    $sponsorInternalId = $spilloverPos['parent_id'];
    $position = $spilloverPos['position'];  // 1 (LEFT) 또는 2 (RIGHT)

    // sponsor의 user_id 조회 (sponsor_id는 user_id 문자열을 저장)
    $sponsor = $db->selectOne("SELECT user_id FROM users WHERE id = ?", [$sponsorInternalId]);
    if (!$sponsor) {
        throw new Exception("스폰서를 찾을 수 없습니다. (sponsor_id: {$sponsorInternalId})");
    }
    $sponsorUserId = $sponsor['user_id'];

    error_log("createAvatarAccount: 아바타 생성 - parent: {$parentUserId}, sponsor: {$sponsorUserId} (ID: {$sponsorInternalId}), position: {$position}");

    // 4. Users 테이블에 아바타 계정 생성 (sponsor_id와 sponsor_position 포함)
    // parent_account_id 설정 (부모 아바타의 user_id)
    $parentAccountId = $parent['user_id'];

    $db->execute("
        INSERT INTO users (
            user_id, password, name, email,
            is_avatar, referral_id, sponsor_id, sponsor_position, package_id,
            parent_account_id,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, NOW(), NOW())
    ", [
        $avatarUserId,
        password_hash('avatar_' . time(), PASSWORD_BCRYPT),
        'Avatar ' . $totalAvatars,
        $avatarUserId . '@avatar.local',
        $parentUserId,        // referral_id: 추천인은 부모
        $sponsorUserId,       // sponsor_id: 스필오버 위치의 부모 (user_id 문자열)
        $position,            // sponsor_position: 1 (LEFT) 또는 2 (RIGHT)
        $packageId,
        $parentAccountId      // parent_account_id: 부모 아바타의 user_id
    ]);

    $avatarInternalId = $db->lastInsertId();

    error_log("createAvatarAccount: 아바타 계정 생성 완료 - avatar_id: {$avatarUserId}, internal_id: {$avatarInternalId}");

    // 5. Avatars 테이블에 기록
    $db->execute("
        INSERT INTO avatars (parent_user_id, avatar_user_id, trigger_amount, package_id, status, created_at)
        VALUES (?, ?, 100.00, ?, 'active', NOW())
    ", [$parentUserId, $avatarInternalId, $packageId]);

    // 6. 부모의 avatar_count 증가
    $db->execute("
        UPDATE users SET avatar_count = avatar_count + 1 WHERE id = ?
    ", [$parentUserId]);

    return [
        'avatar_user_id' => $avatarUserId,
        'avatar_internal_id' => $avatarInternalId
    ];
}

/**
 * 아바타 포인트 체크 및 자동 생성
 */
function checkAndCreateAvatars($db) {
    $createdAvatars = [];

    // avatar_points >= 100 인 회원 조회
    $eligibleUsers = $db->select("
        SELECT id, user_id, avatar_points
        FROM users
        WHERE avatar_points >= 100.00
          AND deleted_at IS NULL
        ORDER BY avatar_points DESC
    ");

    foreach ($eligibleUsers as $user) {
        $userId = $user['id'];
        $avatarPoints = floatval($user['avatar_points']);

        // $100 단위로 아바타 생성
        $avatarCount = floor($avatarPoints / 100);

        for ($i = 0; $i < $avatarCount; $i++) {
            try {
                // 1. 아바타 계정 생성
                $avatarInfo = createAvatarAccount($db, $userId, 2);

                // 2. 아바타 포인트 $100 차감
                $db->execute("
                    UPDATE users SET avatar_points = avatar_points - 100.00 WHERE id = ?
                ", [$userId]);

                // 3. 아바타의 $100 매출 자동 처리
                $salesDateTime = date('Y-m-d H:i:s');

                $db->execute("
                    INSERT INTO sales (user_id, package_id, amount, payment_method, txid, status, confirmed_at, created_at)
                    VALUES (?, 2, 100.00, 'AVATAR_AUTO', 'AVATAR_GEN', 'completed', ?, ?)
                ", [$avatarInfo['avatar_internal_id'], $salesDateTime, $salesDateTime]);

                $saleId = $db->lastInsertId();

                $db->execute("
                    UPDATE users
                    SET package_id = 2, package_date = ?, total_sales = 100.00, updated_at = NOW()
                    WHERE id = ?
                ", [$salesDateTime, $avatarInfo['avatar_internal_id']]);

                // 4. 보너스 계산 및 지급
                $bonusResult = calculateAndDistributeBonuses(
                    $db,
                    $avatarInfo['avatar_internal_id'],
                    $saleId,
                    100.00,
                    2
                );

                $db->execute("
                    INSERT INTO transactions (user_id, type, amount, currency, reference_id, description, created_at)
                    VALUES (?, 'avatar_purchase', 100.00, 'AVATAR_POINTS', ?, 'Avatar 자동 생성', ?)
                ", [$avatarInfo['avatar_internal_id'], $saleId, $salesDateTime]);

                $createdAvatars[] = [
                    'parent_user_id' => $user['user_id'],
                    'avatar_user_id' => $avatarInfo['avatar_user_id'],
                    'bonus_distributed' => $bonusResult
                ];

            } catch (Exception $e) {
                error_log("Avatar creation error for user {$user['user_id']}: " . $e->getMessage());
                continue;
            }
        }
    }

    return $createdAvatars;
}
