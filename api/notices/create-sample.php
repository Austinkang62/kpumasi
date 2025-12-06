<?php
/**
 * 샘플 공지사항 생성 스크립트
 * 브라우저에서 실행: /api/notices/create-sample.php
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

echo "<h2>샘플 공지사항 생성</h2>";

try {
    $db = Database::getInstance();

    // 테이블 생성
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            is_important TINYINT(1) DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $db->execute($createTableQuery);

    // 샘플 데이터 삽입
    $sampleNotices = [
        [
            'title' => '🎉 K-Pumasi 플랫폼 오픈을 환영합니다!',
            'content' => "안녕하세요, K-Pumasi 회원 여러분!\n\nK-Pumasi 플랫폼의 정식 오픈을 진심으로 축하드립니다.\n\n저희 플랫폼은 회원 여러분의 성공적인 비즈니스를 위해 최선을 다하고 있습니다.\n\n궁금하신 사항은 언제든지 고객센터로 문의해 주시기 바랍니다.\n\n감사합니다.",
            'is_important' => 1
        ],
        [
            'title' => '💰 출금 처리 시간 안내',
            'content' => "출금 신청 처리 시간 안내드립니다.\n\n- 평일: 신청 후 24시간 이내 처리\n- 주말/공휴일: 다음 영업일 처리\n\n출금 신청 시 정확한 지갑 주소를 입력해 주시기 바랍니다.\n\n잘못된 주소로 인한 손실은 복구가 불가능합니다.",
            'is_important' => 1
        ],
        [
            'title' => '📊 조직도 기능 업데이트',
            'content' => "조직도 기능이 업데이트되었습니다.\n\n주요 변경사항:\n- 실시간 업데이트 기능 추가\n- 미니맵 네비게이션 개선\n- 회원 상세 정보 표시 개선\n\n더욱 편리해진 조직도를 경험해 보세요!",
            'is_important' => 0
        ],
        [
            'title' => '🔒 보안 강화 안내',
            'content' => "회원님의 계정 보안을 위해 다음 사항을 준수해 주시기 바랍니다.\n\n1. 비밀번호는 정기적으로 변경해 주세요\n2. 타인과 계정 정보를 공유하지 마세요\n3. 피싱 사이트에 주의하세요\n4. 공용 PC 사용 후 반드시 로그아웃하세요\n\n안전한 서비스 이용을 위해 협조 부탁드립니다.",
            'is_important' => 0
        ],
        [
            'title' => '📢 시스템 점검 안내 (완료)',
            'content' => "시스템 점검이 완료되었습니다.\n\n점검 일시: 2025년 1월 1일 02:00 ~ 04:00\n점검 내용: 서버 안정화 및 성능 개선\n\n점검으로 인한 불편을 드려 죄송합니다.\n\n감사합니다.",
            'is_important' => 0
        ]
    ];

    foreach ($sampleNotices as $notice) {
        $query = "INSERT INTO notices (title, content, is_important) VALUES (?, ?, ?)";
        $db->execute($query, [$notice['title'], $notice['content'], $notice['is_important']]);
    }

    echo "<p style='color: green;'>✅ 샘플 공지사항이 생성되었습니다.</p>";
    echo "<p>총 " . count($sampleNotices) . "개의 공지사항이 추가되었습니다.</p>";
    echo "<p><a href='/html/dashboard.html'>대시보드로 이동</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ 오류 발생: " . $e->getMessage() . "</p>";
}
