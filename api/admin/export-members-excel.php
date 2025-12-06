<?php
/**
 * 회원 목록 엑셀 다운로드 API
 * GET /api/admin/export-members-excel.php
 *
 * 관리자 전용 - 회원 데이터를 엑셀(CSV) 파일로 다운로드
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// 관리자 권한 확인 (세션 체크)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('관리자 권한이 필요합니다.');
}

try {
    $db = Database::getInstance()->getConnection();

    // 회원 데이터 조회 (모든 필요 정보 포함)
    $sql = "
        SELECT
            u.user_id AS '아이디',
            u.name AS '이름',
            u.email AS '이메일',
            COALESCE(ref_user.user_id, '-') AS '추천인',
            COALESCE(spon_user.user_id, '-') AS '후원인',
            CASE
                WHEN u.sponsor_position = 1 THEN '좌측'
                WHEN u.sponsor_position = 2 THEN '우측'
                ELSE '-'
            END AS '위치',
            (SELECT COUNT(*) FROM users WHERE referral_id = u.id) AS '추천개수',
            COALESCE(u.cash_balance, 0) AS '캐시금액',
            COALESCE(u.cash_withdrawn, 0) AS '캐시인출',
            COALESCE(u.avatar_point_balance, 0) AS '아바타금액',
            COALESCE(u.avatar_point_count, 0) AS '아바타개수',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'referral' AND payment_type = 'cash'),
                0
            ) AS '추천수당_캐시',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'referral' AND payment_type = 'avatar_point'),
                0
            ) AS '추천수당_아바타',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'edge' AND payment_type = 'cash'),
                0
            ) AS '엣지수당_캐시',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'edge' AND payment_type = 'avatar_point'),
                0
            ) AS '엣지수당_아바타',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'matching' AND payment_type = 'cash'),
                0
            ) AS '매칭수당_캐시',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'matching' AND payment_type = 'avatar_point'),
                0
            ) AS '매칭수당_아바타',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'rollup' AND payment_type = 'cash'),
                0
            ) AS '롤업수당_캐시',
            COALESCE(
                (SELECT SUM(amount) FROM bonuses
                 WHERE user_id = u.id AND bonus_type = 'rollup' AND payment_type = 'avatar_point'),
                0
            ) AS '롤업수당_아바타',
            u.level AS '레벨',
            u.status AS '상태',
            DATE_FORMAT(u.created_at, '%Y-%m-%d %H:%i:%s') AS '가입일시'
        FROM users u
        LEFT JOIN users ref_user ON u.referral_id = ref_user.id
        LEFT JOIN users spon_user ON u.sponsor_id = spon_user.user_id
        ORDER BY u.id ASC
    ";

    $stmt = $db->query($sql);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($members)) {
        die('다운로드할 회원 데이터가 없습니다.');
    }

    // CSV 파일명 생성 (현재 날짜 포함)
    $filename = '회원목록_' . date('Y-m-d_His') . '.csv';

    // CSV 다운로드 헤더 설정
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    // UTF-8 BOM 추가 (엑셀에서 한글 깨짐 방지)
    echo "\xEF\xBB\xBF";

    // CSV 출력 스트림 생성
    $output = fopen('php://output', 'w');

    // 헤더 행 출력 (컬럼명)
    $headers = array_keys($members[0]);
    fputcsv($output, $headers);

    // 데이터 행 출력
    foreach ($members as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    error_log('엑셀 다운로드 오류: ' . $e->getMessage());
    http_response_code(500);
    die('엑셀 다운로드 중 오류가 발생했습니다: ' . $e->getMessage());
}
