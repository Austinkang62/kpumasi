<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting...<br>\n";

try {
    echo "1. Loading config...<br>\n";
    require_once '../config/database.php';
    echo "Config loaded<br>\n";

    echo "2. Getting database instance...<br>\n";
    $db = Database::getInstance();
    echo "Database instance OK<br>\n";

    echo "3. Getting PDO connection...<br>\n";
    $pdo = $db->getConnection();
    echo "PDO connection OK<br>\n";

    echo "4. Simulating DELETE (dry run)...<br>\n";
    $user_ids = ['BYDY7570'];
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));

    echo "Placeholders: $placeholders<br>\n";
    echo "User IDs: " . print_r($user_ids, true) . "<br>\n";

    echo "5. Starting transaction...<br>\n";
    $pdo->beginTransaction();
    echo "Transaction started<br>\n";

    echo "6. Preparing DELETE query...<br>\n";
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE user_id IN ($placeholders)");
    echo "Query prepared<br>\n";

    echo "7. Executing query...<br>\n";
    $stmt->execute($user_ids);
    echo "Query executed, rows affected: " . $stmt->rowCount() . "<br>\n";

    echo "8. Rolling back (dry run)...<br>\n";
    $pdo->rollback();
    echo "Rolled back<br>\n";

    echo "<br><strong>ALL TESTS PASSED!</strong><br>\n";

} catch (Exception $e) {
    echo "<br><strong>ERROR:</strong> " . $e->getMessage() . "<br>\n";
    echo "File: " . $e->getFile() . "<br>\n";
    echo "Line: " . $e->getLine() . "<br>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>
