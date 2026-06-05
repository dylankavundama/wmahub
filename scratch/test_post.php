<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'user_id' => '1000',
    'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
];

// Let's enable display errors for this test
ini_set('display_errors', '1');
error_reporting(E_ALL);

include 'api/contract.php';
