<?php
/**
 * 전체 매출별 보너스 지급 내역 리포트 (Excel/CSV 형식)
 * Excel에서 열 수 있는 CSV 파일로 저장
 */

require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "매출별 보너스 지급 내역 리포트 - Excel 생성\n";
echo "=================================================================\n\n";

try {
    $db = Database::getInstance();

    // 1. 전체 통계 수집
    echo "1. 데이터 수집 중...\n";

    $totalSales = $db->selectOne("SELECT COUNT(*) as cnt, SUM(amount) as total FROM sales");
    $totalBonuses = $db->selectOne("
        SELECT
            COUNT(*) as cnt,
            SUM(amount) as total,
            SUM(CASE WHEN payment_type = 'cash' THEN amount ELSE 0 END) as total_cash,
            SUM(CASE WHEN payment_type = 'avatar_point' THEN amount ELSE 0 END) as total_avatar
        FROM bonuses
    ");

    $bonusByType = $db->select("
        SELECT
            bonus_type,
            COUNT(*) as count,
            SUM(amount) as total,
            SUM(CASE WHEN payment_type = 'cash' THEN amount ELSE 0 END) as cash,
            SUM(CASE WHEN payment_type = 'avatar_point' THEN amount ELSE 0 END) as avatar
        FROM bonuses
        GROUP BY bonus_type
    ");

    // 모든 매출 조회
    $sales = $db->select("
        SELECT
            s.id,
            s.user_id as buyer_id,
            u.user_id as buyer_code,
            u.name as buyer_name,
            s.amount,
            s.created_at
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.id
        ORDER BY s.created_at ASC
    ");

    echo "   총 매출: {$totalSales['cnt']}건\n";
    echo "   총 보너스: {$totalBonuses['cnt']}건\n\n";

    // 2. CSV 파일 생성
    echo "2. Excel 파일 생성 중...\n";

    $filename = __DIR__ . '/bonus-distribution-report-' . date('Y-m-d-His') . '.csv';
    $fp = fopen($filename, 'w');

    // UTF-8 BOM 추가 (Excel에서 한글 제대로 표시)
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

    // === 통계 섹션 ===
    fputcsv($fp, ['=== 전체 통계 ===']);
    fputcsv($fp, []);
    fputcsv($fp, ['항목', '건수', '금액']);
    fputcsv($fp, ['총 매출', $totalSales['cnt'], number_format($totalSales['total'], 2)]);
    fputcsv($fp, ['총 보너스', $totalBonuses['cnt'], number_format($totalBonuses['total'], 2)]);
    fputcsv($fp, ['  - 캐시 (65%)', '', number_format($totalBonuses['total_cash'], 2)]);
    fputcsv($fp, ['  - 아바타 포인트 (35%)', '', number_format($totalBonuses['total_avatar'], 2)]);
    fputcsv($fp, []);

    fputcsv($fp, ['보너스 타입별 통계']);
    fputcsv($fp, ['타입', '건수', '총액', '캐시', '아바타 포인트']);
    foreach ($bonusByType as $type) {
        fputcsv($fp, [
            $type['bonus_type'],
            $type['count'],
            number_format($type['total'], 2),
            number_format($type['cash'], 2),
            number_format($type['avatar'], 2)
        ]);
    }
    fputcsv($fp, []);
    fputcsv($fp, []);

    // === 매출별 상세 내역 ===
    fputcsv($fp, ['=== 매출별 보너스 지급 상세 내역 ===']);
    fputcsv($fp, []);
    fputcsv($fp, [
        'Sale ID',
        '구매자 ID',
        '구매자 이름',
        '매출 금액',
        '매출 날짜',
        '보너스 타입',
        '레벨',
        '수령자 ID',
        '수령자 이름',
        '지급 형태',
        '보너스 금액',
        '설명'
    ]);

    $processedCount = 0;
    foreach ($sales as $sale) {
        $saleId = $sale['id'];
        $buyerCode = $sale['buyer_code'];
        $buyerName = $sale['buyer_name'] ?: '';
        $amount = $sale['amount'];
        $date = $sale['created_at'];

        // 이 매출로 발생한 보너스 조회
        $bonuses = $db->select("
            SELECT
                b.id as bonus_id,
                b.bonus_type,
                b.payment_type,
                b.amount,
                b.level,
                receiver.user_id as receiver_code,
                receiver.name as receiver_name,
                b.description
            FROM bonuses b
            LEFT JOIN users receiver ON b.user_id = receiver.id
            WHERE b.from_user_id = ?
            ORDER BY
                FIELD(b.bonus_type, 'referral', 'edge', 'matching', 'rollup'),
                b.level ASC,
                FIELD(b.payment_type, 'cash', 'avatar_point')
        ", [$sale['buyer_id']]);

        if (empty($bonuses)) {
            // 보너스가 없는 매출도 기록
            fputcsv($fp, [
                $saleId,
                $buyerCode,
                $buyerName,
                number_format($amount, 2),
                $date,
                '보너스 없음',
                '',
                '',
                '',
                '',
                '',
                ''
            ]);
        } else {
            $firstRow = true;
            foreach ($bonuses as $bonus) {
                fputcsv($fp, [
                    $firstRow ? $saleId : '',
                    $firstRow ? $buyerCode : '',
                    $firstRow ? $buyerName : '',
                    $firstRow ? number_format($amount, 2) : '',
                    $firstRow ? $date : '',
                    $bonus['bonus_type'],
                    $bonus['level'] ?: '',
                    $bonus['receiver_code'],
                    $bonus['receiver_name'] ?: '',
                    $bonus['payment_type'] === 'cash' ? '캐시' : '아바타',
                    number_format($bonus['amount'], 2),
                    $bonus['description']
                ]);
                $firstRow = false;
            }
        }

        $processedCount++;
        if ($processedCount % 50 == 0) {
            echo "   처리 중... {$processedCount}/{$totalSales['cnt']}\n";
        }
    }

    fclose($fp);

    echo "\n";
    echo "=================================================================\n";
    echo "✅ Excel 파일 생성 완료!\n";
    echo "=================================================================\n";
    echo "파일 위치: {$filename}\n";
    echo "\n";
    echo "📌 사용 방법:\n";
    echo "1. 파일을 다운로드하여 Excel에서 엽니다\n";
    echo "2. '데이터' > '텍스트 나누기'를 사용하여 열을 정리할 수 있습니다\n";
    echo "3. 필터와 정렬 기능을 활용하여 데이터를 분석하세요\n";
    echo "\n";

    // 파일 크기 표시
    $fileSize = filesize($filename);
    $fileSizeKB = round($fileSize / 1024, 2);
    echo "파일 크기: {$fileSizeKB} KB\n";

} catch (Exception $e) {
    echo "\n❌ 오류 발생:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
