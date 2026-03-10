<?php
// ============================================
// KONFIGURASI - SESUAIKAN!
// ============================================
$url = 'https://caprice-noncotyledonal-protestingly.ngrok-free.dev/api/webhook/fingerprint/b368aad6-87f7-4a78-9edb-001fcdf1e543';
$token = '2a6ed3ef8a9f7ae3b2744129b583d392f7f066f478e5fc0699413777b25a25d2'; // Ganti dengan token sebenarnya
$data = [
    'trans_id' => '1',
    'cloud_id' => 'C8846625511310',
    'timestamp' => date('c'), // optional, ini hanya contoh data
];
$body = json_encode($data);

// ============================================
// HITUNG SIGNATURE
// ============================================
$timestamp = time(); // Unix timestamp dalam detik (integer)
$message = $timestamp . '.' . $body;
$signature = hash_hmac('sha256', $message, $token);

// ============================================
// KIRIM REQUEST
// ============================================
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token,
    'X-Timestamp: ' . $timestamp,
    'X-Signature: ' . $signature,
    'ngrok-skip-browser-warning: true'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch)) {
    echo 'CURL Error: ' . curl_error($ch) . PHP_EOL;
} else {
    echo 'HTTP Code: ' . $httpCode . PHP_EOL;
    echo 'Response: ' . $result . PHP_EOL;
}
curl_close($ch);
?>