<?php
/**
 * User Class
 * 회원 관리 클래스
 * Version: 2.0 - Multi-account support with binary spillover
 */

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        error_log('User 클래스 로드됨 - Version 2.0 (다중 계정 지원)');
    }

    /**
     * 회원가입
     * 필수: password, email, bnb_address
     * 선택: referral_id, sponsor_id, account_count
     */
    public function register($data) {
        try {
            // 이메일 중복 체크 - 임시로 비활성화 (다계정 지원)
            // 동일 이메일로 여러 계정 생성 가능
            // if ($this->isEmailExists($data['email'])) {
            //     return [
            //         'success' => false,
            //         'message' => '이미 사용 중인 이메일입니다.'
            //     ];
            // }

            // 계정 개수 확인 (1~7)
            $accountCount = !empty($data['account_count']) ? intval($data['account_count']) : 1;

            // 추천방식 확인 (main_only 또는 cascade)
            $referralMethod = !empty($data['referral_method']) ? $data['referral_method'] : 'main_only';

            // 디버깅 로그
            error_log('===== 회원가입 시작 =====');
            error_log('받은 account_count 값: ' . var_export($data['account_count'], true));
            error_log('변환된 accountCount: ' . $accountCount);
            error_log('추천방식: ' . $referralMethod);

            if ($accountCount < 1 || $accountCount > 7) {
                return [
                    'success' => false,
                    'message' => '계정 개수는 1~7개만 가능합니다.'
                ];
            }

            if (!in_array($referralMethod, ['main_only', 'cascade'])) {
                return [
                    'success' => false,
                    'message' => '유효하지 않은 추천방식입니다.'
                ];
            }

            // 추천인 확인 (추천코드)
            $referralId = null;
            if (!empty($data['referral_id'])) {
                $referralId = $this->getUserIdByUserId($data['referral_id']);
                if (!$referralId) {
                    return [
                        'success' => false,
                        'message' => '유효하지 않은 추천코드입니다.'
                    ];
                }
            }

            // 후원인 확인 (후원코드)
            $sponsorId = null;
            $sponsorPosition = null;
            if (!empty($data['sponsor_id'])) {
                $sponsorCheck = $this->getUserIdByUserId($data['sponsor_id']);
                if (!$sponsorCheck) {
                    return [
                        'success' => false,
                        'message' => '유효하지 않은 후원코드입니다.'
                    ];
                }
                $sponsorId = $data['sponsor_id'];

                // 후원인 위치 (1=좌측, 2=우측)
                if (!empty($data['sponsor_position'])) {
                    $sponsorPosition = intval($data['sponsor_position']);

                    // 위치 검증 (1 또는 2만 허용)
                    if (!in_array($sponsorPosition, [1, 2])) {
                        return [
                            'success' => false,
                            'message' => '유효하지 않은 위치입니다.'
                        ];
                    }
                }
            }

            // 비밀번호 해싱
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

            // 트랜잭션 시작
            $this->db->beginTransaction();

            // 생성된 계정 ID들을 저장할 배열
            $createdAccounts = [];

            // 다중 계정 등록
            error_log("계정 생성 시작: 총 {$accountCount}개 생성 예정");
            for ($i = 0; $i < $accountCount; $i++) {
                $accountNum = $i + 1;
                error_log("계정 #{$accountNum} 생성 중...");

                // 회원코드 자동 생성
                $userId = $this->generateUserCode();
                error_log("생성된 user_id: {$userId}");

                // 이메일 처리: 다중 계정의 경우 +번호 추가 (선택사항)
                // 이메일 중복 허용 중이므로 모든 계정이 동일 이메일 사용 가능
                $accountEmail = $data['email'];

                // 다중 계정일 경우 선택적으로 +번호 추가 (구분하기 쉽게)
                if ($accountCount > 1 && $i > 0) {
                    // example@domain.com -> example+1@domain.com (선택사항)
                    // 하지만 이메일 중복이 허용되므로 +번호 없이 동일 이메일 사용도 가능
                    $emailParts = explode('@', $data['email']);
                    if (count($emailParts) === 2) {
                        $accountEmail = $emailParts[0] . '+' . $accountNum . '@' . $emailParts[1];
                    }
                }
                error_log("사용할 이메일: {$accountEmail}");

                // 첫 번째 계정 (대표 계정)
                if ($i === 0) {
                    // 대표 계정은 입력받은 추천인/후원인 사용
                    $currentReferralId = $referralId;
                    $currentSponsorId = $sponsorId;
                    $currentSponsorPosition = $sponsorPosition;
                } else {
                    // 서브 계정의 추천인 설정
                    // 바이너리 스필오버: BFS 알고리즘으로 빈 자리 찾기
                    $positionInfo = $this->findAvailablePosition($createdAccounts[0]);
                    $currentSponsorId = $positionInfo['sponsor_id']; // 빈 자리가 있는 부모의 user_id
                    $currentSponsorPosition = $positionInfo['position']; // 1=좌측, 2=우측

                    // 추천방식에 따라 referral_id 설정
                    if ($referralMethod === 'main_only') {
                        // 대표자 전부 추천: 모든 서브 계정의 추천인이 대표(메인) 계정
                        $currentReferralId = $this->getUserIdByUserId($createdAccounts[0]);
                        error_log("서브 계정 #{$accountNum}: 대표자 전부 추천 - referral_id = {$createdAccounts[0]}");
                    } else if ($referralMethod === 'cascade') {
                        // 내리추천: 서브 계정의 추천인이 sponsor(바이너리 트리 부모)
                        $currentReferralId = $this->getUserIdByUserId($currentSponsorId);
                        error_log("서브 계정 #{$accountNum}: 내리추천 - referral_id = {$currentSponsorId} (sponsor와 동일)");
                    }
                }

                // 패키지 정보 (고정: $100 패키지만 사용)
                $packageId = 2; // $100 패키지

                // 회원 등록
                $sql = "INSERT INTO users (
                    user_id, password, email, bnb_address,
                    referral_id, sponsor_id, sponsor_position,
                    package_id, package_date, email_verified
                ) VALUES (
                    :user_id, :password, :email, :bnb_address,
                    :referral_id, :sponsor_id, :sponsor_position,
                    :package_id, NOW(), 0
                )";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':user_id' => $userId,
                    ':password' => $hashedPassword,
                    ':email' => $accountEmail, // 다중 계정용 이메일 사용
                    ':bnb_address' => $data['bnb_address'],
                    ':referral_id' => $currentReferralId,
                    ':sponsor_id' => $currentSponsorId,
                    ':sponsor_position' => $currentSponsorPosition,
                    ':package_id' => $packageId
                ]);

                // 생성된 계정 ID 저장
                $createdAccounts[] = $userId;
                error_log("계정 #{$accountNum} 생성 완료: {$userId}");

                // 추천인의 직접 추천 수 증가 (대표 계정만)
                if ($i === 0 && $referralId) {
                    $sql = "UPDATE users SET direct_referrals = direct_referrals + 1 WHERE id = :referral_id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([':referral_id' => $referralId]);
                }
            }

            error_log("전체 생성 완료: " . count($createdAccounts) . "개 계정");
            error_log("생성된 계정 목록: " . implode(', ', $createdAccounts));

            // 패키지 금액 (고정: $100)
            $packageAmount = 100.00;

            // 보너스 배포 (각 계정마다)
            require_once __DIR__ . '/BonusDistributor.php';
            $bonusDistributor = new BonusDistributor();

            foreach ($createdAccounts as $accountUserId) {
                // account_user_id를 숫자 ID로 변환
                $accountId = $this->getUserIdByUserId($accountUserId);
                if ($accountId) {
                    try {
                        // 1. 매출 레코드 생성 (보너스 배포 전에 반드시 생성)
                        error_log("매출 레코드 생성: user_id={$accountUserId}, id={$accountId}, package_id=2, amount=\${$packageAmount}");
                        $salesSql = "INSERT INTO sales (user_id, package_id, amount, status, confirmed_at, created_at)
                                     VALUES (?, ?, ?, 'confirmed', NOW(), NOW())";
                        $salesStmt = $this->db->prepare($salesSql);
                        $salesStmt->execute([$accountId, 2, $packageAmount]);
                        error_log("매출 레코드 생성 완료: sales_id=" . $this->db->lastInsertId());

                        // 2. 보너스 배포
                        error_log("보너스 배포 시작: user_id={$accountUserId}, id={$accountId}, amount=\${$packageAmount}");
                        $bonusResult = $bonusDistributor->distributeAllBonuses($accountId, $packageAmount);
                        error_log("보너스 배포 성공: 총 \${$bonusResult['total_distributed']}");
                    } catch (Exception $bonusError) {
                        // 보너스 배포 실패 시 로그만 남기고 회원가입은 계속 진행
                        error_log("매출/보너스 처리 실패: " . $bonusError->getMessage());
                        error_log("매출/보너스 처리 실패 스택: " . $bonusError->getTraceAsString());
                        // 회원가입 자체는 성공하도록 예외를 던지지 않음
                    }
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => $accountCount > 1
                    ? "{$accountCount}개의 계정이 생성되었습니다."
                    : '회원가입이 완료되었습니다.',
                'data' => [
                    'user_id' => $createdAccounts[0], // 대표 계정 ID
                    'email' => $data['email'],
                    'account_count' => $accountCount,
                    'all_accounts' => $createdAccounts // 생성된 모든 계정 ID
                ]
            ];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('===== User registration error =====');
            error_log('에러 메시지: ' . $e->getMessage());
            error_log('에러 파일: ' . $e->getFile());
            error_log('에러 라인: ' . $e->getLine());
            error_log('스택 트레이스: ' . $e->getTraceAsString());
            error_log('===================================');

            return [
                'success' => false,
                'message' => '회원가입 처리 중 오류가 발생했습니다.',
                'debug' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
        }
    }

    /**
     * 회원코드 자동 생성
     * 형식: 영문 대문자 4글자 + 숫자 4글자 = 총 8글자 (예: ABCD1234)
     * 랜덤 생성 방식
     */
    private function generateUserCode() {
        $maxAttempts = 100; // 최대 시도 횟수
        $attempt = 0;

        try {
            while ($attempt < $maxAttempts) {
                // 랜덤 코드 생성: 영문 대문자 4글자 + 숫자 4글자
                $letters = '';
                for ($i = 0; $i < 4; $i++) {
                    $letters .= chr(rand(65, 90)); // A-Z (ASCII 65-90)
                }

                $numbers = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

                $userCode = $letters . $numbers;

                // 중복 체크
                $sql = "SELECT COUNT(*) FROM users WHERE user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userCode]);
                $count = $stmt->fetchColumn();

                // 중복이 없으면 반환
                if ($count == 0) {
                    return $userCode;
                }

                $attempt++;
            }

            // 최대 시도 초과 시 타임스탬프 포함하여 고유성 보장
            $letters = '';
            for ($i = 0; $i < 4; $i++) {
                $letters .= chr(rand(65, 90));
            }
            $timestamp = substr(time(), -4); // 마지막 4자리
            return $letters . $timestamp;

        } catch (Exception $e) {
            error_log('Generate user code error: ' . $e->getMessage());
            // 에러 발생 시 타임스탬프 기반 코드 생성
            $letters = '';
            for ($i = 0; $i < 4; $i++) {
                $letters .= chr(rand(65, 90));
            }
            return $letters . substr(time(), -4);
        }
    }

    /**
     * 로얄코드 할당
     * royal_codes 테이블에서 사용 가능한 코드를 가져와 할당
     */
    private function assignRoyalCode($userId) {
        try {
            $this->db->beginTransaction();

            // 사용 가능한 로얄코드 조회 (FOR UPDATE로 락 걸기)
            $sql = "SELECT id, code FROM royal_codes
                    WHERE status = 'available'
                    ORDER BY RAND()
                    LIMIT 1
                    FOR UPDATE";
            $stmt = $this->db->query($sql);
            $royalCode = $stmt->fetch();

            if (!$royalCode) {
                // 사용 가능한 로얄코드가 없으면 새로 생성
                $newCode = $this->generateRoyalCode();

                $insertSql = "INSERT INTO royal_codes (code, user_id, status, assigned_at)
                              VALUES (?, ?, 'assigned', NOW())";
                $insertStmt = $this->db->prepare($insertSql);
                $insertStmt->execute([$newCode, $userId]);

                $this->db->commit();
                return $newCode;
            }

            // 로얄코드 할당
            $updateSql = "UPDATE royal_codes
                          SET user_id = ?, status = 'assigned', assigned_at = NOW()
                          WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([$userId, $royalCode['id']]);

            $this->db->commit();
            return $royalCode['code'];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Assign royal code error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 로얄코드 생성 (중복 체크 포함)
     */
    private function generateRoyalCode() {
        $maxAttempts = 50;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            // 랜덤 로얄코드 생성
            $letters = '';
            for ($i = 0; $i < 4; $i++) {
                $letters .= chr(rand(65, 90));
            }
            $numbers = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $code = $letters . $numbers;

            // 중복 체크
            $sql = "SELECT COUNT(*) FROM royal_codes WHERE code = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$code]);
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                return $code;
            }

            $attempt++;
        }

        // 최대 시도 초과 시 타임스탬프 사용
        $letters = '';
        for ($i = 0; $i < 4; $i++) {
            $letters .= chr(rand(65, 90));
        }
        return $letters . substr(time(), -4);
    }

    /**
     * 로그인
     */
    public function login($userId, $password, $rememberMe = false) {
        try {
            $sql = "SELECT * FROM users WHERE user_id = :user_id AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => '아이디 또는 비밀번호가 올바르지 않습니다.'
                ];
            }

            // 비밀번호 확인
            if (!password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => '아이디 또는 비밀번호가 올바르지 않습니다.'
                ];
            }

            // 세션 토큰 생성
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

            // 세션 저장
            $sql = "INSERT INTO sessions (user_id, token, ip_address, user_agent, last_activity, expires_at)
                    VALUES (:user_id, :token, :ip, :user_agent, NOW(), :expires_at)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $user['id'],
                ':token' => $token,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':expires_at' => $expiresAt
            ]);

            // PHP 세션 설정 (auth-check.js의 check-session.php에서 사용)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_internal_id'] = $user['id'];
            $_SESSION['session_token'] = $token;

            return [
                'success' => true,
                'message' => '로그인 성공',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'user_id' => $user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'package_id' => $user['package_id'],
                    'role' => $user['role']
                ]
            ];

        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => '로그인 처리 중 오류가 발생했습니다.'
            ];
        }
    }

    /**
     * 토큰으로 사용자 정보 조회
     */
    public function getUserByToken($token) {
        try {
            $sql = "SELECT u.* FROM users u
                    INNER JOIN sessions s ON u.id = s.user_id
                    WHERE s.token = :token
                    AND s.expires_at > NOW()
                    AND u.status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);

            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Get user by token error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 세션 토큰 검증
     */
    public function validateSession($token) {
        try {
            $sql = "SELECT u.* FROM users u
                    INNER JOIN sessions s ON u.id = s.user_id
                    WHERE s.token = :token
                    AND s.expires_at > NOW()
                    AND u.status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);
            $user = $stmt->fetch();

            if ($user) {
                // 세션 활동 시간 업데이트
                $updateSql = "UPDATE sessions SET last_activity = NOW() WHERE token = :token";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([':token' => $token]);
            }

            return $user;
        } catch (Exception $e) {
            error_log('Validate session error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 아이디 중복 확인
     */
    private function isUserIdExists($userId) {
        $sql = "SELECT COUNT(*) FROM users WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * 이메일 중복 확인
     */
    private function isEmailExists($email) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * user_id로 내부 ID 조회
     */
    private function getUserIdByUserId($userId) {
        $sql = "SELECT id FROM users WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $id = $stmt->fetchColumn();
        return $id ? $id : null;
    }

    /**
     * 바이너리 스필오버: BFS 알고리즘으로 빈 자리 찾기
     * @param string $rootUserId 대표 계정의 user_id
     * @return array ['sponsor_id' => user_id, 'position' => 1 or 2]
     */
    private function findAvailablePosition($rootUserId) {
        try {
            // BFS 큐 초기화: 대표 계정부터 시작
            $queue = [$rootUserId];
            $visited = [];

            while (!empty($queue)) {
                $currentUserId = array_shift($queue);

                // 중복 방문 방지
                if (in_array($currentUserId, $visited)) {
                    continue;
                }
                $visited[] = $currentUserId;

                // 현재 노드의 좌측/우측 자식 확인
                $sql = "SELECT user_id, sponsor_position
                        FROM users
                        WHERE sponsor_id = :sponsor_id
                        ORDER BY sponsor_position ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':sponsor_id' => $currentUserId]);
                $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // 좌측/우측 자식 존재 여부 확인
                $hasLeft = false;
                $hasRight = false;

                foreach ($children as $child) {
                    if ($child['sponsor_position'] == 1) {
                        $hasLeft = true;
                        $queue[] = $child['user_id']; // 좌측 자식을 큐에 추가
                    } elseif ($child['sponsor_position'] == 2) {
                        $hasRight = true;
                        $queue[] = $child['user_id']; // 우측 자식을 큐에 추가
                    }
                }

                // 빈 자리 발견 시 현재 노드를 sponsor로, 빈 위치 반환
                if (!$hasLeft) {
                    return [
                        'sponsor_id' => $currentUserId,
                        'position' => 1 // 좌측 빈 자리
                    ];
                }
                if (!$hasRight) {
                    return [
                        'sponsor_id' => $currentUserId,
                        'position' => 2 // 우측 빈 자리
                    ];
                }
            }

            // 빈 자리를 찾지 못한 경우 (이론적으로 발생하지 않아야 함)
            return [
                'sponsor_id' => $rootUserId,
                'position' => 1 // 기본값: 루트의 좌측
            ];

        } catch (Exception $e) {
            error_log('Find available position error: ' . $e->getMessage());
            return [
                'sponsor_id' => $rootUserId,
                'position' => 1 // 에러 시 기본값: 루트의 좌측
            ];
        }
    }

    /**
     * 회원 정보 조회
     */
    public function getUserInfo($userId) {
        try {
            $sql = "SELECT
                    u.*,
                    p.name as package_name,
                    p.price as package_price,
                    (SELECT COUNT(*) FROM users WHERE referral_id = u.id) as referral_count
                FROM users u
                LEFT JOIN packages p ON u.package_id = p.id
                WHERE u.id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);

            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Get user info error: ' . $e->getMessage());
            return null;
        }
    }
}
