<?php
/**
 * 패키지 구매 API
 * POST /api/sales/purchase.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Admin.php';

/**
 * 추천 보너스 분배 함수
 *
 * @param Database $db 데이터베이스 객체
 * @param int $saleId 판매 ID
 * @param int $buyerUserId 구매자 사용자 ID
 * @param float $saleAmount 판매 금액
 */
function distributeReferralBonuses($db, $saleId, $buyerUserId, $saleAmount) {
    error_log("Bonus Distribution - Starting for sale_id: $saleId, buyer: $buyerUserId, amount: $saleAmount");

    // 패키지 금액에 따른 보너스 설정
    $bonusAmount1to5 = 0;
    $bonusAmount6to15 = 0;

    if ($saleAmount >= 100) {
        // $100 패키지
        $bonusAmount1to5 = 9.0;   // 1~5단계: $9
        $bonusAmount6to15 = 4.5;  // 6~15단계: $4.5
    } elseif ($saleAmount >= 50) {
        // $50 패키지
        $bonusAmount1to5 = 4.0;   // 1~5단계: $4
        $bonusAmount6to15 = 2.0;  // 6~15단계: $2
    } else {
        // 기타 패키지 (비율 적용)
        error_log("Bonus Distribution - Unknown package amount: $saleAmount");
        return; // 정의되지 않은 패키지는 보너스 미지급
    }

    error_log("Bonus Distribution - Package: $saleAmount, Bonus 1-5: $bonusAmount1to5, Bonus 6-15: $bonusAmount6to15");

    // 현재 사용자의 추천인부터 시작
    $currentUserId = $buyerUserId;
    $level = 0;

    // 최대 15단계까지 순회
    while ($level < 15) {
        // 현재 사용자의 추천인 조회
        $query = "SELECT id, referral_id FROM users WHERE id = ?";
        $currentUser = $db->selectOne($query, [$currentUserId]);

        if (!$currentUser || !$currentUser['referral_id']) {
            error_log("Bonus Distribution - No referrer found at level $level");
            break; // 더 이상 추천인이 없으면 종료
        }

        $level++;
        $referrerUserId = $currentUser['referral_id']; // user_id (문자열)

        // 추천인 정보 조회 (user_id로 조회)
        $referrerQuery = "SELECT id, direct_referral_count FROM users WHERE user_id = ?";
        $referrer = $db->selectOne($referrerQuery, [$referrerUserId]);

        if (!$referrer) {
            error_log("Bonus Distribution - Referrer not found: $referrerUserId");
            break;
        }

        $referrerId = $referrer['id']; // 숫자 ID 추출
        $referralOrg = (int)($referrer['direct_referral_count'] ?? 0);
        error_log("Bonus Distribution - Level $level: User ID $referrerId (user_id: $referrerUserId), direct_referral_count: $referralOrg");

        // 자격 확인
        $qualified = false;
        if ($referralOrg >= 3) {
            $qualified = ($level <= 15); // 3명 이상: 15단계까지
        } elseif ($referralOrg >= 2) {
            $qualified = ($level <= 10); // 2명 이상: 10단계까지
        } elseif ($referralOrg >= 1) {
            $qualified = ($level <= 5);  // 1명 이상: 5단계까지
        }

        if (!$qualified) {
            error_log("Bonus Distribution - Level $level: Not qualified (direct_referral_count: $referralOrg)");
            $currentUserId = $referrerId;
            continue; // 자격 없으면 다음 단계로
        }

        // 보너스 계산 (단계별 고정 금액)
        if ($level <= 5) {
            $bonusAmount = $bonusAmount1to5;
        } else {
            $bonusAmount = $bonusAmount6to15;
        }

        // 보너스 비율 계산 (기록용)
        $bonusPercentage = round(($bonusAmount / $saleAmount) * 100, 2);

        error_log("Bonus Distribution - Level $level: Amount: $bonusAmount, Percentage: {$bonusPercentage}%");

        // 보너스 지급 내역 기록
        try {
            $insertBonus = "INSERT INTO referral_bonuses
                           (sale_id, from_user_id, to_user_id, level, bonus_amount, bonus_percentage, status)
                           VALUES (?, ?, ?, ?, ?, ?, 'paid')";
            $db->insert($insertBonus, [
                $saleId,
                $buyerUserId,
                $referrerId,
                $level,
                $bonusAmount,
                $bonusPercentage
            ]);

            // user_statistics 업데이트 (보너스 수익 추가)
            $updateStats = "UPDATE user_statistics
                           SET total_earnings = total_earnings + ?,
                               updated_at = NOW()
                           WHERE user_id = ?";
            $db->update($updateStats, [$bonusAmount, $referrerId]);

            error_log("Bonus Distribution - Level $level: Bonus paid to user $referrerId");

        } catch (Exception $e) {
            error_log("Bonus Distribution - Error at level $level: " . $e->getMessage());
        }

        // 다음 단계로
        $currentUserId = $referrerId;
    }

    error_log("Bonus Distribution - Completed. Total levels processed: $level");
}

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // JSON 데이터 파싱
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // 필수 필드 검증
    if (empty($data['session_token']) || empty($data['product_code'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '필수 정보가 누락되었습니다.'
        ]);
        exit;
    }

    // 세션 검증 (일반 사용자 또는 관리자)
    $user = new User();
    $adminUser = $user->validateSession($data['session_token']);

    if (!$adminUser) {
        // 일반 사용자 토큰이 아니면 관리자 토큰으로 시도
        $admin = new Admin();
        $adminUser = $admin->validateSession($data['session_token']);

        if (!$adminUser) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => '유효하지 않은 세션입니다.'
            ]);
            exit;
        }
        error_log('Purchase - Validated as admin user');
    } else {
        error_log('Purchase - Validated as normal user');
    }

    // 데이터베이스 연결
    $db = Database::getInstance();

    // 구매자 결정: target_user_id가 있으면 타인 구매, 없으면 본인 구매
    if (!empty($data['target_user_id'])) {
        // 타인 구매 (관리자가 회원 대신 구매)
        error_log('Purchase - Target user mode: ' . $data['target_user_id']);
        $targetUserQuery = "SELECT id, user_id, name FROM users WHERE user_id = ?";
        $buyerUser = $db->selectOne($targetUserQuery, [$data['target_user_id']]);

        if (!$buyerUser) {
            error_log('Purchase - ERROR: Buyer user not found! target_user_id: ' . $data['target_user_id']);
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => '구매자를 찾을 수 없습니다. (user_id: ' . $data['target_user_id'] . ')'
            ]);
            exit;
        }

        error_log('Purchase - ✓ Found buyer: ID=' . $buyerUser['id'] . ', user_id=' . $buyerUser['user_id'] . ', name=' . $buyerUser['name']);
        error_log('Purchase - Session user (admin): ' . $adminUser['id'] . ' (' . ($adminUser['user_id'] ?? $adminUser['username']) . ')');
    } else {
        // 본인 구매 (세션 사용자가 직접 구매)
        error_log('Purchase - Self purchase mode');
        $buyerUser = $adminUser;
        error_log('Purchase - ✓ Buyer (self): ID=' . $buyerUser['id'] . ', user_id=' . ($buyerUser['user_id'] ?? $buyerUser['username']));
    }

    // 디버깅: 전달받은 product_code 로그
    error_log('Purchase - product_code: ' . $data['product_code']);

    // 제품 정보 조회
    $productQuery = "SELECT * FROM products WHERE product_code = ? AND status = 'active'";
    $product = $db->selectOne($productQuery, [$data['product_code']]);

    // 디버깅: 조회 결과 로그
    error_log('Purchase - product found: ' . ($product ? 'YES' : 'NO'));
    if (!$product) {
        // 전체 제품 목록 조회 (디버깅용)
        $allProducts = $db->select("SELECT product_code, product_name, status FROM products");
        error_log('Purchase - All products: ' . json_encode($allProducts));
    }

    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 제품입니다. (코드: ' . $data['product_code'] . ')'
        ]);
        exit;
    }

    // 구매 정보 계산
    $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
    $unitPrice = (float)$product['price'];
    $totalAmount = $unitPrice * $quantity;

    // 결제 정보
    $paymentMethod = $data['payment_method'] ?? 'usdt';
    $transactionId = $data['transaction_id'] ?? null;
    $note = $data['note'] ?? null;

    // 트랜잭션 시작
    $db->beginTransaction();

    error_log('Purchase - Starting transaction');
    error_log('Purchase - Buyer User ID: ' . $buyerUser['id'] . ' (' . $buyerUser['user_id'] . ')');
    error_log('Purchase - Product ID: ' . $product['id']);

    // 매출 기록 생성
    $saleQuery = "INSERT INTO sales
                  (user_id, product_id, quantity, unit_price, total_amount,
                   payment_method, payment_status, transaction_id, note, payment_date)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    try {
        $saleId = $db->insert($saleQuery, [
            $buyerUser['id'], // URL 파라미터로 지정된 구매자
            $product['id'],
            $quantity,
            $unitPrice,
            $totalAmount,
            $paymentMethod,
            'completed', // 즉시 완료 처리
            $transactionId,
            $note
        ]);

        error_log('Purchase - Sale ID: ' . ($saleId ? $saleId : 'NULL'));
    } catch (Exception $insertError) {
        error_log('Purchase - Insert error: ' . $insertError->getMessage());
        $db->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '매출 기록 생성 실패: ' . $insertError->getMessage()
        ]);
        exit;
    }

    if (!$saleId) {
        $db->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '구매 처리 중 오류가 발생했습니다. (Sale ID 생성 실패)'
        ]);
        exit;
    }

    // user_statistics 업데이트 (트리거가 없을 경우를 대비)
    try {
        // user_statistics 레코드가 있는지 확인
        $statsCheck = $db->selectOne("SELECT user_id FROM user_statistics WHERE user_id = ?", [$buyerUser['id']]);

        if (!$statsCheck) {
            // 없으면 생성
            error_log('Purchase - Creating user_statistics record for user_id: ' . $buyerUser['id']);
            $db->insert("INSERT INTO user_statistics (user_id, total_earnings) VALUES (?, ?)",
                       [$buyerUser['id'], $totalAmount]);
        } else {
            // 있으면 업데이트
            error_log('Purchase - Updating user_statistics for user_id: ' . $buyerUser['id']);
            $updateStatsQuery = "UPDATE user_statistics
                                 SET total_earnings = total_earnings + ?,
                                     updated_at = NOW()
                                 WHERE user_id = ?";
            $db->update($updateStatsQuery, [$totalAmount, $buyerUser['id']]);
        }
    } catch (Exception $statsError) {
        error_log('Purchase - Stats update error: ' . $statsError->getMessage());
        // user_statistics 업데이트 실패해도 구매는 계속 진행
    }

    // 활동 로그 기록 (실패해도 구매는 계속)
    try {
        $logQuery = "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, details)
                     VALUES (?, ?, ?, ?, ?)";
        $db->insert($logQuery, [
            $buyerUser['id'], // 실제 구매자의 활동 로그
            'purchase',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            json_encode([
                'product_code' => $data['product_code'],
                'product_name' => $product['product_name'],
                'amount' => $totalAmount,
                'sale_id' => $saleId,
                'admin_user_id' => $adminUser['id'] // 관리자 정보도 기록
            ])
        ]);
        error_log('Purchase - Activity log created');
    } catch (Exception $logError) {
        error_log('Purchase - Activity log error: ' . $logError->getMessage());
        // 로그 실패해도 구매는 계속 진행
    }

    // TXID가 있는 경우 payment_transactions와 연결
    if ($transactionId && $transactionId !== 'demo' && $transactionId !== 'test') {
        try {
            // payment_transactions의 sale_id 업데이트
            $updatePaymentQuery = "UPDATE payment_transactions
                                  SET sale_id = ?, updated_at = NOW()
                                  WHERE txid = ? AND status = 'confirmed'";
            $db->update($updatePaymentQuery, [$saleId, $transactionId]);
            error_log('Purchase - Payment transaction linked: ' . $transactionId);
        } catch (Exception $linkError) {
            error_log('Purchase - Payment link error: ' . $linkError->getMessage());
            // 연결 실패해도 구매는 계속 진행
        }
    }

    // 추천 보너스 분배 (구매자의 추천인 체인에 분배)
    try {
        distributeReferralBonuses($db, $saleId, $buyerUser['id'], $totalAmount);
    } catch (Exception $bonusError) {
        error_log('Purchase - Bonus distribution error: ' . $bonusError->getMessage());
        // 보너스 분배 실패해도 구매는 계속 진행
    }

    // 트랜잭션 커밋
    $db->commit();
    error_log('Purchase - Transaction committed successfully');

    // 성공 응답
    echo json_encode([
        'success' => true,
        'message' => '구매가 완료되었습니다.',
        'data' => [
            'sale_id' => $saleId,
            'product_name' => $product['product_name'],
            'total_amount' => $totalAmount,
            'payment_status' => 'completed'
        ]
    ]);

} catch (Exception $e) {
    // 트랜잭션 롤백
    if (isset($db) && $db) {
        try {
            $db->rollback();
            error_log('Purchase - Transaction rolled back');
        } catch (Exception $rollbackError) {
            error_log('Purchase - Rollback error: ' . $rollbackError->getMessage());
        }
    }

    error_log('Purchase error: ' . $e->getMessage());
    error_log('Purchase error trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '구매 처리 중 오류가 발생했습니다.',
        'error_detail' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}
