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
    | Job Orders Configuration
    |--------------------------------------------------------------------------
    |
    | Lark Job Orders table/view configuration
    | Uses same base_id as projects
    |
    */

    'job_orders' => [
        'table_id' => env('LARK_JOB_ORDERS_TABLE_ID', 'tblXJcCC3h7gF5aF'),
        'view_id' => env('LARK_JOB_ORDERS_VIEW_ID', 'vewvk3plGP'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory Listing Configuration
    |--------------------------------------------------------------------------
    |
    | Lark Inventory Listing table/view configuration
    | Uses same base_id as projects and job orders
    | Filter: Destination = BATAM AND Status = Sent Out
    |
    */

    'inventory' => [
        'table_id' => env('LARK_INVENTORY_TABLE_ID', 'tblTulpgtvkyrKxo'),
        'view_id' => env('LARK_INVENTORY_VIEW_ID', 'vewEW56Qcr'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Order Type Gradings Configuration
    |--------------------------------------------------------------------------
    |
    | Lark Job Order Type Gradings table/view configuration
    | Uses same base_id as other tables
    |
    */

    'job_order_type_gradings' => [
        'table_id' => env('LARK_JOB_ORDER_TYPE_GRADINGS_TABLE_ID', 'tblz7t2pCphoy6hk'),
        'view_id' => env('LARK_JOB_ORDER_TYPE_GRADINGS_VIEW_ID', 'vew3AnbBNI'),
    ],

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
