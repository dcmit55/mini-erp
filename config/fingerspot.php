<?php

return [
    'api_token'        => env('FINGERSPOT_API_TOKEN'),
    'base_url'         => env('FINGERSPOT_API_URL', 'https://developer.fingerspot.io/api'),
    'device_id'        => env('FINGERSPOT_DEVICE_ID'),
    'device_start_date'=> env('FINGERSPOT_DEVICE_START_DATE', '2026-03-07'), // tanggal device mulai dipakai
];
