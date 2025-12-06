<?php
/**
 * 데이터베이스 전체 스키마 추출
 * 실제 데이터베이스의 현재 구조를 schema 파일로 저장
 */

require_once __DIR__ . '/../config/database.php';

$outputFile = __DIR__ . '/../database/schema-current.sql';

try {
    $db = Database::getInstance();

    $output = "-- ============================================================================\n";
    $output .= "-- K-Pumasi Database Schema (Current)\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Database: ai22\n";
    $output .= "-- ============================================================================\n\n";
    $output .= "USE ai22;\n\n";

    // 모든 테이블 목록 가져오기
    $tables = $db->select("SHOW TABLES");
    $tableNames = [];

    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        // VIEW는 제외
        if (stripos($tableName, 'view') === false && stripos($tableName, '_view') === false) {
            $tableNames[] = $tableName;
        }
    }

    sort($tableNames);

    $output .= "-- ============================================================================\n";
    $output .= "-- Tables: " . count($tableNames) . "\n";
    $output .= "-- ============================================================================\n\n";

    $tableIndex = 1;
    foreach ($tableNames as $tableName) {
        echo "처리 중: $tableName\n";

        try {
            $output .= "-- ----------------------------------------------------------------------------\n";
            $output .= "-- $tableIndex. Table: $tableName\n";
            $output .= "-- ----------------------------------------------------------------------------\n\n";

            // DROP TABLE 구문
            $output .= "DROP TABLE IF EXISTS `$tableName`;\n\n";

            // CREATE TABLE 구문 가져오기 (직접 쿼리)
            $pdo = $db->getConnection();
            $stmt = $pdo->query("SHOW CREATE TABLE `$tableName`");
            $createTable = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($createTable) {
                $createStatement = $createTable['Create Table'];
                $output .= "$createStatement;\n\n";
            }

            // 테이블 통계 (직접 쿼리)
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$tableName`");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            $output .= "-- Current rows: " . $count['cnt'] . "\n\n";

        } catch (Exception $e) {
            echo "  ⚠️  오류 (계속 진행): " . $e->getMessage() . "\n";
            $output .= "-- Error processing table: " . $e->getMessage() . "\n\n";
        }

        $tableIndex++;
    }

    // VIEW 목록
    $output .= "\n-- ============================================================================\n";
    $output .= "-- VIEWS\n";
    $output .= "-- ============================================================================\n\n";

    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        if (stripos($tableName, 'view') !== false || stripos($tableName, '_view') !== false) {
            echo "처리 중 (VIEW): $tableName\n";

            $output .= "-- View: $tableName\n";
            try {
                $createView = $db->selectOne("SHOW CREATE VIEW `$tableName`");
                if ($createView) {
                    $output .= "DROP VIEW IF EXISTS `$tableName`;\n";
                    $output .= $createView['Create View'] . ";\n\n";
                }
            } catch (Exception $e) {
                $output .= "-- Error getting view definition: " . $e->getMessage() . "\n\n";
            }
        }
    }

    // 파일로 저장
    file_put_contents($outputFile, $output);

    echo "\n====================================\n";
    echo "✅ 스키마 추출 완료!\n";
    echo "====================================\n";
    echo "파일: $outputFile\n";
    echo "테이블 수: " . count($tableNames) . "\n";
    echo "파일 크기: " . number_format(filesize($outputFile)) . " bytes\n";
    echo "====================================\n\n";

    echo "다운로드 URL:\n";
    echo "https://ai22.mycafe24.com/database/schema-current.sql\n\n";

} catch (Exception $e) {
    echo "❌ 오류 발생: " . $e->getMessage() . "\n\n";

    if (defined('APP_ENV') && APP_ENV === 'development') {
        echo "디버그 정보:\n";
        echo $e->getTraceAsString() . "\n";
    }

    exit(1);
}
