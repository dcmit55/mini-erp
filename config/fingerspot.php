<?php

return [
    'api_token' => env('FINGERSPOT_API_TOKEN'),
    'base_url'  => env('FINGERSPOT_API_URL', 'https://developer.fingerspot.io/api'),
    'device_id' => env('FINGERSPOT_DEVICE_ID'), // default device ID (ID fisik mesin)
];
