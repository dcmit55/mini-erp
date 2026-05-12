<?php

return [
    'api_token'        => env('FINGERSPOT_API_TOKEN'),
    'base_url'         => env('FINGERSPOT_API_URL', 'https://developer.fingerspot.io/api'),
    'device_id'        => env('FINGERSPOT_DEVICE_ID'),
    'device_start_date'=> env('FINGERSPOT_DEVICE_START_DATE', '2026-03-07'), // tanggal device mulai dipakai

    // Tanggal minimum data attlog yang diterima saat sync.
    // Data scan SEBELUM tanggal ini akan diabaikan walaupun ada di cloud Fingerspot.
    // Ubah via FINGERSPOT_SYNC_VALID_FROM di .env jika ingin reset/ignore data lama.
    'sync_valid_from'  => env('FINGERSPOT_SYNC_VALID_FROM'),
];
