<?php
/**
 * 회원 데이터 전수조사 스크립트
 * 모든 회원의 매출/패키지 데이터 통일성 검사
 */

require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "회원 데이터 전수조사 (Membership Data Audit)\n";
echo "=================================================================\n\n";

try {
    $db = Database::getInstance();

    // 패키지 정보 조회
    $packages = $db->select("SELECT * FROM packages");
    $packageMap = [];
    foreach ($packages as $pkg) {
        $packageMap[$pkg['id']] = $pkg;
    }

    echo "📦 패키지 정보:\n";
    foreach ($packageMap as $id => $pkg) {
        echo "   - Package {$id}: \${$pkg['price']} ({$pkg['name']})\n";
    }
    echo "\n";

    // 전체 회원 조회 (아바타 제외)
    echo "1. 전체 회원 조회 중...\n";
    $users = $db->select("
        SELECT
            id, user_id, name, email, package_id, package_date,
            is_avatar, parent_user_id, created_at
        FROM users
        WHERE is_avatar = 0
        ORDER BY id ASC
    ");

    echo "   ✅ 총 {count($users)}명의 회원 (아바타 제외)\n\n";

    // 문제점 분류
    $issues = [
        'no_sales' => [],              // package_id 있는데 sales 없음
        'sales_but_no_package' => [],  // sales 있는데 package_id 없음
        'package_mismatch' => [],      // package_id와 sales.package_id 불일치
        'amount_mismatch' => [],       // sales 금액이 잘못됨
        'duplicate_sales' => [],       // 중복 sales
        'no_bonus' => [],              // 보너스 미지급
        'ok' => []                     // 정상
    ];

    echo "2. 데이터 검증 중...\n\n";

    foreach ($users as $user) {
        $userId = $user['id'];
        $userIdStr = $user['user_id'];
        $packageId = $user['package_id'];

        // 해당 회원의 sales 조회
        $sales = $db->select("
            SELECT * FROM sales
            WHERE user_id = ?
            ORDER BY created_at ASC
        ", [$userId]);

        $salesCount = count($sales);

        // 검사 1: package_id가 있는데 sales가 없음
        if ($packageId > 0 && $salesCount == 0) {
            $issues['no_sales'][] = [
                'user_id' => $userIdStr,
                'name' => $user['name'],
                'package_id' => $packageId,
                'package_date' => $user['package_date'],
                'created_at' => $user['created_at']
            ];
            continue;
        }

        // 검사 2: sales가 있는데 package_id가 없음
        if ($salesCount > 0 && $packageId == 0) {
            $issues['sales_but_no_package'][] = [
                'user_id' => $userIdStr,
                'name' => $user['name'],
                'sales_count' => $salesCount,
                'first_sale_amount' => $sales[0]['amount']
            ];
            continue;
        }

        // 검사 3: package_id와 sales.package_id 불일치
        if ($packageId > 0 && $salesCount > 0) {
            $latestSale = end($sales);

            if ($latestSale['package_id'] != $packageId) {
                $issues['package_mismatch'][] = [
                    'user_id' => $userIdStr,
                    'name' => $user['name'],
                    'user_package_id' => $packageId,
                    'sale_package_id' => $latestSale['package_id'],
                    'sale_amount' => $latestSale['amount']
                ];
                continue;
            }

            // 검사 4: sales 금액이 패키지 가격과 다름
            $expectedAmount = $packageMap[$packageId]['price'];
            if ($latestSale['amount'] != $expectedAmount) {
                $issues['amount_mismatch'][] = [
                    'user_id' => $userIdStr,
                    'name' => $user['name'],
                    'package_id' => $packageId,
                    'expected' => $expectedAmount,
                    'actual' => $latestSale['amount'],
                    'sale_id' => $latestSale['id']
                ];
                continue;
            }
        }

        // 검사 5: 중복 sales (2개 이상)
        if ($salesCount > 1) {
            // 업그레이드가 아닌 중복 체크
            $nonUpgradeSales = array_filter($sales, function($s) {
                return $s['is_upgrade'] == 0;
            });

            if (count($nonUpgradeSales) > 1) {
                $issues['duplicate_sales'][] = [
                    'user_id' => $userIdStr,
                    'name' => $user['name'],
                    'sales_count' => $salesCount,
                    'non_upgrade_count' => count($nonUpgradeSales),
                    'sales_ids' => array_column($sales, 'id')
                ];
                continue;
            }
        }

        // 검사 6: 보너스가 제대로 지급되었는지 확인
        if ($salesCount > 0) {
            $latestSale = end($sales);
            $bonusCount = $db->selectOne("
                SELECT COUNT(*) as cnt
                FROM bonuses
                WHERE from_user_id = ?
            ", [$userId]);

            // 보너스가 하나도 없으면 의심
            if ($bonusCount['cnt'] == 0 && $packageId > 0) {
                $issues['no_bonus'][] = [
                    'user_id' => $userIdStr,
                    'name' => $user['name'],
                    'package_id' => $packageId,
                    'sale_id' => $latestSale['id'],
                    'sale_date' => $latestSale['created_at']
                ];
                continue;
            }
        }

        // 모든 검사 통과
        if ($packageId > 0 && $salesCount > 0) {
            $issues['ok'][] = [
                'user_id' => $userIdStr,
                'name' => $user['name'],
                'package_id' => $packageId
            ];
        } elseif ($packageId == 0 && $salesCount == 0) {
            // 패키지 없고 매출도 없음 (정상 - 미가입)
            $issues['ok'][] = [
                'user_id' => $userIdStr,
                'name' => $user['name'],
                'package_id' => 0,
                'status' => '미가입'
            ];
        }
    }

    // 결과 리포트 출력
    echo "=================================================================\n";
    echo "검증 결과 리포트\n";
    echo "=================================================================\n\n";

    echo "📊 전체 통계:\n";
    echo "   - 총 회원 수: " . count($users) . "명\n";
    echo "   - 정상: " . count($issues['ok']) . "명\n";
    echo "   - 문제 발견: " . (count($users) - count($issues['ok'])) . "명\n\n";

    // 1. package_id 있는데 sales 없음
    if (count($issues['no_sales']) > 0) {
        echo "⚠️  문제 1: package_id가 있는데 sales 기록이 없음 (" . count($issues['no_sales']) . "명)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        foreach ($issues['no_sales'] as $issue) {
            echo "   - {$issue['user_id']} ({$issue['name']})\n";
            echo "     Package: {$issue['package_id']}, 가입일: {$issue['created_at']}\n";
        }
        echo "\n";
    }

    // 2. sales 있는데 package_id 없음
    if (count($issues['sales_but_no_package']) > 0) {
        echo "⚠️  문제 2: sales가 있는데 package_id가 없음 (" . count($issues['sales_but_no_package']) . "명)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        foreach ($issues['sales_but_no_package'] as $issue) {
            echo "   - {$issue['user_id']} ({$issue['name']})\n";
            echo "     매출 건수: {$issue['sales_count']}, 첫 매출: \${$issue['first_sale_amount']}\n";
        }
        echo "\n";
    }

    // 3. package_id 불일치
    if (count($issues['package_mismatch']) > 0) {
        echo "⚠️  문제 3: users.package_id와 sales.package_id 불일치 (" . count($issues['package_mismatch']) . "명)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        foreach ($issues['package_mismatch'] as $issue) {
            echo "   - {$issue['user_id']} ({$issue['name']})\n";
            echo "     users.package_id: {$issue['user_package_id']}, sales.package_id: {$issue['sale_package_id']}\n";
        }
        echo "\n";
    }

    // 4. 금액 불일치
    if (count($issues['amount_mismatch']) > 0) {
        echo "⚠️  문제 4: 매출 금액이 패키지 가격과 다름 (" . count($issues['amount_mismatch']) . "명)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        foreach ($issues['amount_mismatch'] as $issue) {
            echo "   - {$issue['user_id']} ({$issue['name']})\n";
            echo "     Package {$issue['package_id']}: 예상 \${$issue['expected']}, 실제 \${$issue['actual']} (sale_id: {$issue['sale_id']})\n";
        }
        echo "\n";
    }

    // 5. 중복 sales
    if (count($issues['duplicate_sales']) > 0) {
        echo "⚠️  문제 5: 중복 매출 기록 (" . count($issues['duplicate_sales']) . "명)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        foreach ($issues['duplicate_sales'] as $issue) {
            echo "   - {$issue['user_id']} ({$issue['name']})\n";
            echo "     총 매출: {$issue['sales_count']}건, 업그레이드 제외: {$issue['non_upgrade_count']}건\n";
            echo "     Sale IDs: " . implode(', ', $issue['sales_ids']) . "\n";
        }
        echo "\n";
    }

    // 6. 보너스 미지급
    if (count($issues['no_bonus']) > 0) {
        echo "⚠️  문제 6: 보너스가 지급되지 않음 (" . count($issues['no_bonus']) . "명)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        foreach ($issues['no_bonus'] as $issue) {
            echo "   - {$issue['user_id']} ({$issue['name']})\n";
            echo "     Package: {$issue['package_id']}, sale_id: {$issue['sale_id']}, 날짜: {$issue['sale_date']}\n";
        }
        echo "\n";
    }

    echo "=================================================================\n";
    echo "추천 조치사항\n";
    echo "=================================================================\n\n";

    if (count($issues['no_sales']) > 0) {
        echo "1. package_id 있는데 sales 없음:\n";
        echo "   → 해당 회원에게 매출 기록 생성 필요\n";
        echo "   → 자동 수정 스크립트로 처리 가능\n\n";
    }

    if (count($issues['sales_but_no_package']) > 0) {
        echo "2. sales 있는데 package_id 없음:\n";
        echo "   → users 테이블의 package_id 업데이트 필요\n\n";
    }

    if (count($issues['package_mismatch']) > 0) {
        echo "3. package_id 불일치:\n";
        echo "   → users.package_id를 최신 sales.package_id로 업데이트\n\n";
    }

    if (count($issues['amount_mismatch']) > 0) {
        echo "4. 금액 불일치:\n";
        echo "   → sales 금액 수정 또는 재계산 필요\n\n";
    }

    if (count($issues['duplicate_sales']) > 0) {
        echo "5. 중복 매출:\n";
        echo "   → 수동으로 확인하여 불필요한 매출 삭제\n\n";
    }

    if (count($issues['no_bonus']) > 0) {
        echo "6. 보너스 미지급:\n";
        echo "   → 보너스 재계산 스크립트 실행 필요\n\n";
    }

    // JSON 파일로 저장
    $reportFile = __DIR__ . '/audit-report-' . date('Y-m-d-His') . '.json';
    file_put_contents($reportFile, json_encode($issues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "📄 상세 리포트가 저장되었습니다: {$reportFile}\n\n";

    echo "=================================================================\n";
    echo "🎉 전수조사 완료!\n";
    echo "=================================================================\n";

} catch (Exception $e) {
    echo "\n❌ 오류 발생:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
