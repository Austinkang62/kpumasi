<?php
/**
 * 보너스 배포 클래스
 * 4가지 보너스: 추천(referral), 엣지(edge), 추천매칭(matching), 롤업(rollup)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Database.php';

class BonusDistributor {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * 특정 보너스만 배포 (재발행 용도)
     *
     * @param int $newUserId 회원 ID
     * @param string $sponsorCode 후원인 코드
     * @param float $packageAmount 패키지 금액
     * @param array $bonusTypes 배포할 보너스 타입 배열 (예: ['rollup'])
     * @return array 배포 결과
     */
    public function distributeBonus($newUserId, $sponsorCode, $packageAmount, $bonusTypes = ['rollup']) {
        $result = [];

        if (in_array('rollup', $bonusTypes)) {
            $rollupBonuses = $this->distributeRollupBonus($newUserId, $sponsorCode, $packageAmount);
            if (!empty($rollupBonuses)) {
                $result['rollup'] = $rollupBonuses;
            }
        }

        return $result;
    }

    /**
     * 신규 회원 가입 시 모든 보너스 배포
     *
     * @param int $newUserId 신규 가입 회원 ID
     * @param float $packageAmount 패키지 금액 (예: 100.00)
     * @return array 배포 결과
     */
    public function distributeAllBonuses($newUserId, $packageAmount = 100.00) {
        try {
            // 트랜잭션은 상위 호출자(User.php)가 관리
            // $this->db->beginTransaction(); // 제거: 중첩 트랜잭션 방지

            // 신규 회원 정보 조회
            $newUser = $this->db->selectOne(
                "SELECT id, user_id as user_code, referral_id, sponsor_id FROM users WHERE id = ?",
                [$newUserId]
            );

            if (!$newUser) {
                throw new Exception("신규 회원을 찾을 수 없습니다.");
            }

            $result = [
                'success' => true,
                'total_distributed' => 0,
                'bonuses' => []
            ];

            // 1. 추천 보너스 배포 (25%)
            $referralBonus = $this->distributeReferralBonus($newUserId, $newUser['referral_id'], $packageAmount);
            if ($referralBonus) {
                $result['bonuses']['referral'] = $referralBonus;
                $result['total_distributed'] += $referralBonus['amount'];
            }

            // 2. 엣지 보너스 배포 (25%)
            $edgeBonus = $this->distributeEdgeBonus($newUserId, $newUser['sponsor_id'], $packageAmount);
            $edgeReceiverId = null;
            if ($edgeBonus) {
                $result['bonuses']['edge'] = $edgeBonus;
                $result['total_distributed'] += $edgeBonus['amount'];
                $edgeReceiverId = $edgeBonus['user_id']; // 엣지 보너스 수령자 ID
            }

            // 3. 추천매칭 보너스 배포 (엣지 수령자의 추천인에게 패키지의 25%)
            $matchingBonus = $this->distributeMatchingBonus($newUserId, $edgeReceiverId, $packageAmount);
            if ($matchingBonus) {
                $result['bonuses']['matching'] = $matchingBonus;
                $result['total_distributed'] += $matchingBonus['amount'];
            }

            // 4. 롤업 보너스 배포 (상위 25단계, 각 1%)
            $rollupBonuses = $this->distributeRollupBonus($newUserId, $newUser['sponsor_id'], $packageAmount);
            if (!empty($rollupBonuses)) {
                $result['bonuses']['rollup'] = $rollupBonuses;
                foreach ($rollupBonuses as $rb) {
                    $result['total_distributed'] += $rb['amount'];
                }
            }

            // 5. bonus_summary 테이블 업데이트 (집계)
            $this->updateBonusSummary($newUserId, $result['bonuses']);

            // 트랜잭션은 상위 호출자(User.php)가 관리
            // $this->db->commit(); // 제거: 중첩 트랜잭션 방지

            error_log("보너스 배포 완료: 신규회원 ID={$newUserId}, 총 배포액=\${$result['total_distributed']}");

            return $result;

        } catch (Exception $e) {
            // 트랜잭션은 상위 호출자가 관리하므로 여기서 rollback 하지 않음
            // 대신 예외를 던져서 상위에서 처리하게 함
            error_log("보너스 배포 실패: " . $e->getMessage());
            error_log("보너스 배포 실패 스택: " . $e->getTraceAsString());
            throw $e; // 예외를 상위로 전파

            // 아래 코드는 실행되지 않지만 호환성을 위해 유지
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 1. 추천 보너스 배포 (Referral Bonus)
     * 직접 추천한 회원에게 25% 지급 (65% 캐시, 35% 아바타 포인트)
     */
    private function distributeReferralBonus($newUserId, $referralId, $packageAmount) {
        if (!$referralId) {
            return null; // 추천인 없음
        }

        $bonusAmount = $packageAmount * 0.25; // 25%
        $cashAmount = $bonusAmount * 0.65; // 65% 캐시
        $avatarPointAmount = $bonusAmount * 0.35; // 35% 아바타 포인트

        // 추천인 정보 조회
        $referrer = $this->db->selectOne(
            "SELECT id, user_id as user_code FROM users WHERE id = ?",
            [$referralId]
        );

        if (!$referrer) {
            return null;
        }

        // 보너스 기록 - 캐시 (65%)
        $cashBonusId = $this->db->insert(
            "INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
             VALUES (?, ?, 'referral', 'cash', ?, ?, ?, NOW())",
            [
                $referrer['id'],
                $newUserId,
                $cashAmount,
                $packageAmount,
                "직접 추천 보너스 - 캐시 (65%)"
            ]
        );

        // 보너스 기록 - 아바타 포인트 (35%)
        $avatarBonusId = $this->db->insert(
            "INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
             VALUES (?, ?, 'referral', 'avatar_point', ?, ?, ?, NOW())",
            [
                $referrer['id'],
                $newUserId,
                $avatarPointAmount,
                $packageAmount,
                "직접 추천 보너스 - 아바타 포인트 (35%)"
            ]
        );

        // 회원 보너스 잔액 업데이트
        $this->updateUserBonus($referrer['id'], $cashAmount, $avatarPointAmount, 'referral');

        return [
            'cash_bonus_id' => $cashBonusId,
            'avatar_bonus_id' => $avatarBonusId,
            'user_id' => $referrer['id'],
            'user_code' => $referrer['user_code'],
            'amount' => $bonusAmount,
            'cash_amount' => $cashAmount,
            'avatar_point_amount' => $avatarPointAmount,
            'type' => 'referral'
        ];
    }

    /**
     * 2. 엣지 보너스 배포 (Edge Bonus)
     * 상위로 올라가면서 처음 방향이 바뀐 지점의 부모에게 25% 지급 (65% 캐시, 35% 아바타 포인트)
     */
    private function distributeEdgeBonus($newUserId, $sponsorCode, $packageAmount) {
        if (!$sponsorCode) {
            return null; // 후원인 없음
        }

        $bonusAmount = $packageAmount * 0.25; // 25%
        $cashAmount = $bonusAmount * 0.65; // 65% 캐시
        $avatarPointAmount = $bonusAmount * 0.35; // 35% 아바타 포인트

        // 신규 회원 정보 조회
        $newUser = $this->db->selectOne(
            "SELECT id, user_id, sponsor_id, sponsor_position FROM users WHERE id = ?",
            [$newUserId]
        );

        if (!$newUser || !$newUser['sponsor_position']) {
            return null;
        }

        // 엣지(꺾임) 찾기: 상위로 올라가면서 sponsor_position이 바뀌는 첫 지점
        $currentUserCode = $newUser['sponsor_id']; // 신규회원의 스폰서부터 시작
        $lastPosition = intval($newUser['sponsor_position']); // 신규회원의 위치 (1=좌측, 2=우측)
        $edgeReceiver = null;
        $visitedUsers = []; // 무한루프 방지

        while ($currentUserCode) {
            // 무한루프 방지
            if (in_array($currentUserCode, $visitedUsers)) {
                break;
            }
            $visitedUsers[] = $currentUserCode;

            // 현재 노드 조회
            $currentNode = $this->db->selectOne(
                "SELECT id, user_id, sponsor_id, sponsor_position FROM users WHERE user_id = ?",
                [$currentUserCode]
            );

            if (!$currentNode || !$currentNode['sponsor_position'] || !$currentNode['sponsor_id']) {
                // 루트 도달 또는 sponsor_position 없음 - 엣지 없음
                return null;
            }

            $currentPosition = intval($currentNode['sponsor_position']);

            // 꺾임 발생 확인 (위치가 바뀜)
            if ($currentPosition !== $lastPosition) {
                // 꺾임 발견! 현재 노드의 부모 = 엣지 받는 사람
                $edgeReceiver = $this->db->selectOne(
                    "SELECT id, user_id as user_code FROM users WHERE user_id = ?",
                    [$currentNode['sponsor_id']]
                );
                break;
            }

            // 다음 상위로 이동
            $currentUserCode = $currentNode['sponsor_id'];
        }

        if (!$edgeReceiver) {
            return null; // 엣지를 받을 사람이 없음
        }

        // edge_position 계산: 엣지 수령자의 직접 하위 찾기
        // 신규 회원 → sponsor → ... → 엣지 수령자의 직접 하위
        $direction = null;
        $currentId = $newUserId;
        $visitedIds = [];
        $maxDepth = 50;
        $depth = 0;

        while ($currentId && $depth < $maxDepth) {
            if (in_array($currentId, $visitedIds)) {
                break;
            }
            $visitedIds[] = $currentId;

            $currentUser = $this->db->selectOne(
                "SELECT id, user_id, sponsor_id, sponsor_position FROM users WHERE id = ?",
                [$currentId]
            );

            if (!$currentUser || !$currentUser['sponsor_id']) {
                break;
            }

            // 현재 노드의 sponsor가 엣지 수령자인지 확인
            $sponsor = $this->db->selectOne(
                "SELECT id, user_id FROM users WHERE id = ?",
                [$currentUser['sponsor_id']]
            );

            if ($sponsor && $sponsor['id'] === $edgeReceiver['id']) {
                // 엣지 수령자의 직접 하위를 찾았음!
                $direction = ($currentUser['sponsor_position'] == 1) ? 'left' : 'right';
                break;
            }

            // 다음 상위로 이동
            $currentId = $currentUser['sponsor_id'];
            $depth++;
        }

        // fallback: 계산 실패 시 신규 회원의 position 사용
        if (!$direction) {
            $direction = $newUser['sponsor_position'] == 1 ? 'left' : 'right';
        }
        $cashBonusId = $this->db->insert(
            "INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, edge_position, description, created_at)
             VALUES (?, ?, 'edge', 'cash', ?, ?, ?, ?, NOW())",
            [
                $edgeReceiver['id'],
                $newUserId,
                $cashAmount,
                $packageAmount,
                $direction,
                "바이너리 엣지 보너스 - 캐시 ({$direction}, 65%)"
            ]
        );

        // 보너스 기록 - 아바타 포인트 (35%)
        $avatarBonusId = $this->db->insert(
            "INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, edge_position, description, created_at)
             VALUES (?, ?, 'edge', 'avatar_point', ?, ?, ?, ?, NOW())",
            [
                $edgeReceiver['id'],
                $newUserId,
                $avatarPointAmount,
                $packageAmount,
                $direction,
                "바이너리 엣지 보너스 - 아바타 포인트 ({$direction}, 35%)"
            ]
        );

        // 회원 보너스 잔액 업데이트
        $this->updateUserBonus($edgeReceiver['id'], $cashAmount, $avatarPointAmount, 'edge');

        return [
            'cash_bonus_id' => $cashBonusId,
            'avatar_bonus_id' => $avatarBonusId,
            'user_id' => $edgeReceiver['id'],
            'user_code' => $edgeReceiver['user_code'],
            'amount' => $bonusAmount,
            'cash_amount' => $cashAmount,
            'avatar_point_amount' => $avatarPointAmount,
            'type' => 'edge',
            'position' => $direction
        ];
    }

    /**
     * 3. 추천매칭 보너스 배포 (Matching Bonus)
     * 엣지 보너스 수령자를 추천한 사람에게 패키지 금액의 25% 지급 (65% 캐시, 35% 아바타 포인트)
     *
     * @param int $newUserId 신규 가입 회원 ID
     * @param int|null $edgeReceiverId 엣지 보너스를 받은 회원 ID (distributeEdgeBonus 결과에서 전달)
     * @param float $packageAmount 패키지 금액
     */
    private function distributeMatchingBonus($newUserId, $edgeReceiverId, $packageAmount) {
        if (!$edgeReceiverId) {
            return null; // 엣지 보너스가 발생하지 않았으면 매칭도 없음
        }

        // 엣지 보너스 수령자 정보 조회
        $edgeReceiver = $this->db->selectOne(
            "SELECT id, user_id as user_code, referral_id FROM users WHERE id = ?",
            [$edgeReceiverId]
        );

        if (!$edgeReceiver || !$edgeReceiver['referral_id']) {
            return null; // 엣지 보너스 수령자의 추천인이 없음
        }

        // 엣지 보너스 수령자의 추천인 정보 조회
        $matchingReceiver = $this->db->selectOne(
            "SELECT id, user_id as user_code FROM users WHERE id = ?",
            [$edgeReceiver['referral_id']]
        );

        if (!$matchingReceiver) {
            return null;
        }

        $bonusAmount = $packageAmount * 0.25; // 패키지 금액의 25%
        $cashAmount = $bonusAmount * 0.65; // 65% 캐시
        $avatarPointAmount = $bonusAmount * 0.35; // 35% 아바타 포인트

        // 보너스 기록 - 캐시 (65%)
        $cashBonusId = $this->db->insert(
            "INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
             VALUES (?, ?, 'matching', 'cash', ?, ?, ?, NOW())",
            [
                $matchingReceiver['id'],
                $newUserId,
                $cashAmount,
                $packageAmount,
                "추천매칭 보너스 - 캐시 (엣지 수령자의 추천인, 65%)"
            ]
        );

        // 보너스 기록 - 아바타 포인트 (35%)
        $avatarBonusId = $this->db->insert(
            "INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, package_amount, description, created_at)
             VALUES (?, ?, 'matching', 'avatar_point', ?, ?, ?, NOW())",
            [
                $matchingReceiver['id'],
                $newUserId,
                $avatarPointAmount,
                $packageAmount,
                "추천매칭 보너스 - 아바타 포인트 (엣지 수령자의 추천인, 35%)"
            ]
        );

        // 회원 보너스 잔액 업데이트
        $this->updateUserBonus($matchingReceiver['id'], $cashAmount, $avatarPointAmount, 'matching');

        return [
            'cash_bonus_id' => $cashBonusId,
            'avatar_bonus_id' => $avatarBonusId,
            'user_id' => $matchingReceiver['id'],
            'user_code' => $matchingReceiver['user_code'],
            'amount' => $bonusAmount,
            'cash_amount' => $cashAmount,
            'avatar_point_amount' => $avatarPointAmount,
            'type' => 'matching'
        ];
    }

    /**
     * 4. 롤업 보너스 배포 (Rollup Bonus)
     * 후원 계보 상위 25단계에 각 1% 지급 (65% 캐시, 35% 아바타 포인트)
     * 중요: 각 레벨은 한 번만 사용되며, 동일인이 중복 수령 불가
     */
    private function distributeRollupBonus($newUserId, $sponsorCode, $packageAmount) {
        if (!$sponsorCode) {
            return [];
        }

        $bonuses = [];
        $bonusAmount = $packageAmount * 0.01; // 1%
        $cashAmount = $bonusAmount * 0.65; // 65% 캐시
        $avatarPointAmount = $bonusAmount * 0.35; // 35% 아바타 포인트
        $currentSponsorCode = $sponsorCode;
        $maxLevel = 25;
        $processedUsers = []; // 이미 보너스를 받은 사용자 추적

        for ($level = 1; $level <= $maxLevel; $level++) {
            // 현재 단계 후원인 조회
            $currentSponsor = $this->db->selectOne(
                "SELECT id, user_id as user_code, sponsor_id FROM users WHERE user_id = ?",
                [$currentSponsorCode]
            );

            if (!$currentSponsor) {
                break; // 더 이상 상위가 없음
            }

            // 중복 체크: 이미 보너스를 받은 사용자면 중단
            if (in_array($currentSponsor['id'], $processedUsers)) {
                break; // 같은 사람이 다시 나타남 = 상위 레벨 없음
            }

            // 이 사용자를 처리 완료 목록에 추가
            $processedUsers[] = $currentSponsor['id'];

            // 롤업 보너스 기록 - 캐시 (65%)
            $cashBonusId = $this->db->insert(
                "INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, level, package_amount, description, created_at)
                 VALUES (?, ?, 'rollup', 'cash', ?, ?, ?, ?, NOW())",
                [
                    $currentSponsor['id'],
                    $newUserId,
                    $cashAmount,
                    $level,
                    $packageAmount,
                    "롤업 보너스 L{$level} - 캐시 (65%)"
                ]
            );

            // 롤업 보너스 기록 - 아바타 포인트 (35%)
            $avatarBonusId = $this->db->insert(
                "INSERT INTO bonuses (user_id, from_user_id, bonus_type, payment_type, amount, level, package_amount, description, created_at)
                 VALUES (?, ?, 'rollup', 'avatar_point', ?, ?, ?, ?, NOW())",
                [
                    $currentSponsor['id'],
                    $newUserId,
                    $avatarPointAmount,
                    $level,
                    $packageAmount,
                    "롤업 보너스 L{$level} - 아바타 포인트 (35%)"
                ]
            );

            // 회원 보너스 잔액 업데이트
            $this->updateUserBonus($currentSponsor['id'], $cashAmount, $avatarPointAmount, 'rollup');

            $bonuses[] = [
                'cash_bonus_id' => $cashBonusId,
                'avatar_bonus_id' => $avatarBonusId,
                'user_id' => $currentSponsor['id'],
                'user_code' => $currentSponsor['user_code'],
                'amount' => $bonusAmount,
                'cash_amount' => $cashAmount,
                'avatar_point_amount' => $avatarPointAmount,
                'level' => $level,
                'type' => 'rollup'
            ];

            // 다음 상위 단계로 이동
            $currentSponsorCode = $currentSponsor['sponsor_id'];
            if (!$currentSponsorCode) {
                break; // 최상위 도달
            }

            // 순환 참조 방지: 다음 sponsor가 이미 처리된 사용자인지 확인
            $nextSponsor = $this->db->selectOne(
                "SELECT id FROM users WHERE user_id = ?",
                [$currentSponsorCode]
            );
            if ($nextSponsor && in_array($nextSponsor['id'], $processedUsers)) {
                break; // 이미 처리한 사용자로 돌아옴 (순환 참조)
            }
        }

        return $bonuses;
    }

    /**
     * 회원 보너스 잔액 업데이트 (65% 캐시, 35% 아바타 포인트)
     * 아바타인 경우: 캐시는 오너(parent_account_id)에게, 아바타포인트는 본인에게
     */
    private function updateUserBonus($userId, $cashAmount, $avatarPointAmount, $bonusType) {
        $totalAmount = $cashAmount + $avatarPointAmount;

        // 보너스 수령자 정보 조회 (아바타 여부 확인)
        $receiver = $this->db->selectOne(
            "SELECT id, is_avatar, parent_account_id FROM users WHERE id = ?",
            [$userId]
        );

        if (!$receiver) {
            error_log("updateUserBonus: 사용자를 찾을 수 없습니다. userId={$userId}");
            return;
        }

        if ($receiver['is_avatar'] && $receiver['parent_account_id']) {
            // 아바타인 경우: 캐시 → 오너, 아바타포인트 → 본인
            $ownerId = $receiver['parent_account_id'];

            // 캐시 → 오너 계정
            $this->db->update(
                "UPDATE users SET
                    available_bonus = available_bonus + ?,
                    total_bonus = total_bonus + ?
                 WHERE id = ?",
                [$cashAmount, $cashAmount, $ownerId]
            );

            // 아바타포인트 → 아바타 본인
            $this->db->update(
                "UPDATE users SET
                    avatar_points = avatar_points + ?
                 WHERE id = ?",
                [$avatarPointAmount, $userId]
            );

            // 보너스 타입별 누적 업데이트 (오너)
            $field = "total_{$bonusType}_bonus";
            $this->db->update(
                "UPDATE users SET {$field} = {$field} + ? WHERE id = ?",
                [$totalAmount, $ownerId]
            );

            error_log("아바타 보너스 지급: 아바타ID={$userId}, 오너ID={$ownerId}, 캐시=\${$cashAmount} → 오너, 아바타포인트=\${$avatarPointAmount} → 아바타");

        } else {
            // 일반 회원인 경우: 캐시 + 아바타포인트 모두 본인
            $this->db->update(
                "UPDATE users SET
                    available_bonus = available_bonus + ?,
                    avatar_points = avatar_points + ?,
                    total_bonus = total_bonus + ?
                 WHERE id = ?",
                [$cashAmount, $avatarPointAmount, $totalAmount, $userId]
            );

            // 보너스 타입별 누적 업데이트
            $field = "total_{$bonusType}_bonus";
            $this->db->update(
                "UPDATE users SET {$field} = {$field} + ? WHERE id = ?",
                [$totalAmount, $userId]
            );

            error_log("일반 회원 보너스 지급: userId={$userId}, 캐시=\${$cashAmount}, 아바타포인트=\${$avatarPointAmount}");
        }
    }

    /**
     * 바이너리 트리에서 신규 회원의 위치 확인 (좌측/우측)
     */
    private function getPositionInTree($newUserId, $sponsorId) {
        $org = $this->db->selectOne(
            "SELECT position FROM organization WHERE user_id = ?",
            [$newUserId]
        );

        return $org ? $org['position'] : 'left';
    }

    /**
     * bonus_summary 테이블 업데이트 (집계 테이블)
     * 보너스 배포 후 각 유저별 집계 업데이트
     */
    private function updateBonusSummary($newUserId, $bonuses) {
        // 각 보너스를 받은 사용자별로 그룹화
        $userBonuses = [];

        // 1. 추천 보너스
        if (!empty($bonuses['referral'])) {
            $userId = $bonuses['referral']['user_id'];
            if (!isset($userBonuses[$userId])) {
                $userBonuses[$userId] = ['referral' => 0, 'edge' => 0, 'matching' => 0, 'rollup' => 0];
            }
            $userBonuses[$userId]['referral'] += $bonuses['referral']['amount'];
        }

        // 2. 엣지 보너스
        if (!empty($bonuses['edge'])) {
            $userId = $bonuses['edge']['user_id'];
            if (!isset($userBonuses[$userId])) {
                $userBonuses[$userId] = ['referral' => 0, 'edge' => 0, 'matching' => 0, 'rollup' => 0];
            }
            $userBonuses[$userId]['edge'] += $bonuses['edge']['amount'];
        }

        // 3. 매칭 보너스
        if (!empty($bonuses['matching'])) {
            $userId = $bonuses['matching']['user_id'];
            if (!isset($userBonuses[$userId])) {
                $userBonuses[$userId] = ['referral' => 0, 'edge' => 0, 'matching' => 0, 'rollup' => 0];
            }
            $userBonuses[$userId]['matching'] += $bonuses['matching']['amount'];
        }

        // 4. 롤업 보너스 (여러 유저에게 분산)
        if (!empty($bonuses['rollup'])) {
            foreach ($bonuses['rollup'] as $rb) {
                $userId = $rb['user_id'];
                if (!isset($userBonuses[$userId])) {
                    $userBonuses[$userId] = ['referral' => 0, 'edge' => 0, 'matching' => 0, 'rollup' => 0];
                }
                $userBonuses[$userId]['rollup'] += $rb['amount'];
            }
        }

        // bonus_summary 테이블 업데이트 (UPSERT)
        foreach ($userBonuses as $userId => $amounts) {
            $this->db->query(
                "INSERT INTO bonus_summary
                    (user_id, total_referral_bonus, total_edge_bonus, total_matching_bonus, total_rollup_bonus,
                     referral_count, edge_count, matching_count, rollup_count, last_bonus_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                     total_referral_bonus = total_referral_bonus + VALUES(total_referral_bonus),
                     total_edge_bonus = total_edge_bonus + VALUES(total_edge_bonus),
                     total_matching_bonus = total_matching_bonus + VALUES(total_matching_bonus),
                     total_rollup_bonus = total_rollup_bonus + VALUES(total_rollup_bonus),
                     referral_count = referral_count + VALUES(referral_count),
                     edge_count = edge_count + VALUES(edge_count),
                     matching_count = matching_count + VALUES(matching_count),
                     rollup_count = rollup_count + VALUES(rollup_count),
                     last_bonus_at = NOW()",
                [
                    $userId,
                    $amounts['referral'],
                    $amounts['edge'],
                    $amounts['matching'],
                    $amounts['rollup'],
                    $amounts['referral'] > 0 ? 1 : 0,
                    $amounts['edge'] > 0 ? 1 : 0,
                    $amounts['matching'] > 0 ? 1 : 0,
                    !empty($bonuses['rollup']) ? count(array_filter($bonuses['rollup'], fn($rb) => $rb['user_id'] == $userId)) : 0
                ]
            );
        }
    }
}
