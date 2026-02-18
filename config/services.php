<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'lark' => [
        'app_id' => env('LARK_APP_ID'),
        'app_secret' => env('LARK_APP_SECRET'),
        'base_id' => env('LARK_BASE_ID'),
        'table_id' => env('LARK_TABLE_ID'),
        'view_id' => env('LARK_VIEW_ID'), // Optional: Filter data by specific view
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY', '6LfD2WgsAAAAAKM7FHahZOxYuFvtRHDIVt_uhkPX'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', '6LfD2WgsAAAAAFWpgoubzDNlqh_0q7ns5v_5mYgj'),
    ],
];
