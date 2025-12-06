<?php
/**
 * 회원 목록 간단 버전 엑셀 다운로드 API
 * GET /api/admin/export-members-excel-simple.php
 *
 * 세션 체크 없이 테스트용 (실제 운영시 위 파일 사용)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    // 회원 데이터 조회
    $sql = "
        SELECT
            u.user_id AS '아이디',
            u.email AS '이메일',
            COALESCE(ref_user.user_id, '-') AS '추천인',
            COALESCE(spon_user.user_id, '-') AS '후원인',
            CASE
                WHEN o.position = 'left' THEN '좌측'
                WHEN o.position = 'right' THEN '우측'
                ELSE '-'
            END AS '위치',
            (SELECT COUNT(*) FROM users WHERE referral_id = u.id) AS '추천개수',
            COALESCE(u.total_bonus, 0) AS '누적보너스',
            COALESCE(u.available_bonus, 0) AS '출금가능보너스',
            COALESCE(u.total_withdrawn, 0) AS '총출금액',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'referral'),
                0
            ) AS '추천수당',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'edge'),
                0
            ) AS '엣지수당',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'matching'),
                0
            ) AS '매칭수당',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'rollup'),
                0
            ) AS '롤업수당',
            COALESCE(o.level, 0) AS '레벨',
            u.status AS '상태',
            DATE_FORMAT(u.created_at, '%Y-%m-%d %H:%i') AS '가입일시'
        FROM users u
        LEFT JOIN users ref_user ON u.referral_id = ref_user.id
        LEFT JOIN users spon_user ON u.sponsor_id = spon_user.user_id
        LEFT JOIN organization o ON u.id = o.user_id
        WHERE u.status = 'active'
        ORDER BY u.id ASC
    ";

    $stmt = $db->query($sql);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($members)) {
        die('다운로드할 회원 데이터가 없습니다.');
    }

    // CSV 파일명
    $filename = '회원목록_' . date('Ymd_His') . '.csv';

    // CSV 헤더
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');

    // UTF-8 BOM (엑셀 한글 깨짐 방지)
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // 헤더
    fputcsv($output, array_keys($members[0]));

    // 데이터
    foreach ($members as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    error_log('엑셀 다운로드 오류: ' . $e->getMessage());
    http_response_code(500);
    die('오류: ' . $e->getMessage());
}
