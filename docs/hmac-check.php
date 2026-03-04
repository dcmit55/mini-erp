<?php
if ($argc < 3) {
    echo "Usage: php hmac-check.php <timestamp> <body>\n";
    exit(1);
}

$timestamp = $argv[1];
$body = $argv[2];
$secret = "3d4e60c6cdc64e16451cb61cfb265582c865c995a4a69e4a0329d0e1162fd8b3";

$message = $timestamp . '.' . $body;
$signature = hash_hmac('sha256', $message, $secret);

echo $signature . "\n";