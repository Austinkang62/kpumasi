<?php
/**
 * Deploy get-ref-tree.php to correct location
 */

$source = __DIR__ . '/get-ref-tree.php';
$dest = __DIR__ . '/get-ref-tree-deployed.php';

if (file_exists($source)) {
    if (copy($source, $dest)) {
        echo json_encode([
            'success' => true,
            'message' => 'File deployed successfully',
            'source' => $source,
            'dest' => $dest,
            'source_dir' => __DIR__,
            'files' => scandir(__DIR__)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to copy file',
            'error' => error_get_last()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Source file not found',
        'source' => $source,
        'current_dir' => __DIR__,
        'files' => scandir(__DIR__)
    ]);
}
