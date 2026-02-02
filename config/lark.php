<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lark API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Lark (Feishu/Bytedance) API integration
    | Following iSyment pattern for external service configuration
    |
    */

    'app_id' => env('LARK_APP_ID'),
    'app_secret' => env('LARK_APP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Lark Base Configuration
    |--------------------------------------------------------------------------
    |
    | Base (Multidimensional Table) configuration for projects sync
    |
    */

    'base_id' => env('LARK_BASE_ID'),
    'table_id' => env('LARK_TABLE_ID'),
    'view_id' => env('LARK_VIEW_ID', null), // Optional

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'enabled' => env('LARK_SYNC_ENABLED', true),
        'auto_deactivate' => env('LARK_AUTO_DEACTIVATE', true), // Auto soft delete jika tidak ada di Lark
        'batch_size' => env('LARK_BATCH_SIZE', 100),
    ],
];
