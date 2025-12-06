<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

echo "=== Debug Start ===\n\n";

// 1. Config 로드 테스트
echo "1. Loading config...\n";
try {
    require_once '../config/database.php';
    echo "   ✓ Config loaded\n\n";
} catch (Exception $e) {
    echo "   ✗ Config error: " . $e->getMessage() . "\n\n";
    exit;
}

// 2. Database 클래스 확인
echo "2. Checking Database class...\n";
if (class_exists('Database')) {
    echo "   ✓ Database class exists\n";
} else {
    echo "   ✗ Database class not found\n";
    exit;
}

// 3. Database 연결 테스트
echo "\n3. Testing database connection...\n";
try {
    echo "   Creating Database instance...\n";
    $db = Database::getInstance();
    echo "   ✓ Database instance created\n";

    echo "   Getting connection...\n";
    $conn = $db->getConnection();
    echo "   ✓ Connected to database\n";
    echo "   Connection type: " . get_class($conn) . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Connection error: " . $e->getMessage() . "\n";
    echo "   Error trace: " . $e->getTraceAsString() . "\n\n";
    exit;
} catch (Error $e) {
    echo "   ✗ Fatal error: " . $e->getMessage() . "\n";
    echo "   Error trace: " . $e->getTraceAsString() . "\n\n";
    exit;
}

// 4. POST 데이터 확인
echo "4. Checking POST data...\n";
$rawInput = file_get_contents('php://input');
echo "   Raw input: " . $rawInput . "\n";

$input = json_decode($rawInput, true);
echo "   Decoded input: " . print_r($input, true) . "\n";
echo "   user_ids: " . print_r($input['user_ids'] ?? 'NOT SET', true) . "\n\n";

// 5. 간단한 쿼리 테스트
echo "5. Testing simple query...\n";
try {
    echo "   Preparing query...\n";
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE user_id = ?");
    echo "   ✓ Query prepared\n";

    $testId = 'BYDY7570';
    echo "   Executing query with user_id: $testId\n";
    $stmt->execute([$testId]);
    echo "   ✓ Query executed\n";

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   User exists: " . ($row['cnt'] > 0 ? 'YES' : 'NO') . "\n";
    echo "   Count: " . $row['cnt'] . "\n";
    echo "   ✓ Query successful\n\n";
} catch (PDOException $e) {
    echo "   ✗ PDO error: " . $e->getMessage() . "\n";
    echo "   Error code: " . $e->getCode() . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Query error: " . $e->getMessage() . "\n\n";
}

echo "=== Debug End ===\n";
?>
