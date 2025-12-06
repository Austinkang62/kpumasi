<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP Info ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current file: " . __FILE__ . "\n";
echo "Directory: " . __DIR__ . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "\n";

echo "=== File Check ===\n";
$getRefTreeFile = __DIR__ . '/get-ref-tree.php';
echo "get-ref-tree.php exists: " . (file_exists($getRefTreeFile) ? 'YES' : 'NO') . "\n";
echo "get-ref-tree.php path: $getRefTreeFile\n";
echo "get-ref-tree.php size: " . filesize($getRefTreeFile) . " bytes\n";

// PHP 문법 체크 시도
$output = [];
$return = 0;
exec("php -l " . escapeshellarg($getRefTreeFile) . " 2>&1", $output, $return);
echo "\nSyntax check:\n";
echo implode("\n", $output) . "\n";
