<?php
// 보안 키 체크
if (!isset($_GET['key']) || $_GET['key'] !== 'avatar_cron_2024') {
    http_response_code(403);
    die('Access denied. Invalid key.');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

echo "Step 1: Basic PHP OK\n";

try {
    require_once __DIR__ . '/../config/database.php';
    echo "Step 2: Config loaded OK\n";

    $db = Database::getInstance();
    echo "Step 3: Database connected OK\n";

    $users = $db->select("
        SELECT id, user_id, name, avatar_points
        FROM users
        WHERE avatar_points >= 100.00
          AND deleted_at IS NULL
          AND is_avatar = 0
        ORDER BY avatar_points DESC
        LIMIT 5
    ");
    echo "Step 4: Query OK - Found " . count($users) . " users\n";

    foreach ($users as $user) {
        echo "  - {$user['user_id']}: \${$user['avatar_points']}\n";
    }

    echo "\nAll steps passed!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
