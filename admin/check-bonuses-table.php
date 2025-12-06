<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // bonuses 테이블 구조 확인
    $columns = $pdo->query("SHOW COLUMNS FROM bonuses")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'table' => 'bonuses',
        'columns' => $columns
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
