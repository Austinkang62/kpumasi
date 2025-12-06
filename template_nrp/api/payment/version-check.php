<?php
header('Content-Type: application/json; charset=utf-8');

$txid = 'demo';

if (strtolower($txid) === 'demo' || strtolower($txid) === 'test') {
    echo json_encode([
        'version' => '2024-10-26 v2',
        'demo_mode' => 'WORKING',
        'txid' => $txid,
        'check' => strtolower($txid) === 'demo' ? 'TRUE' : 'FALSE'
    ]);
} else {
    echo json_encode([
        'version' => '2024-10-26 v2',
        'demo_mode' => 'NOT WORKING',
        'txid' => $txid
    ]);
}
