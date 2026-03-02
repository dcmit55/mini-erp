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
    | Staging Tables Configuration (Courier & Item Tracking)
    |--------------------------------------------------------------------------
    |
    | Separate staging tables for raw Lark data before mapping to ERP
    |
    */

    'staging' => [
        // BT-SG (Batam to Singapore) - Courier
        'bt_sg_courier' => [
            'table_id' => env('LARK_BT_SG_COURIER_TABLE_ID', 'tblnwbXvyEnz6G60'),
            'view_id' => env('LARK_BT_SG_COURIER_VIEW_ID', 'vew11b8N8m'),
        ],

        // BT-SG (Batam to Singapore) - Items
        'bt_sg_items' => [
            'table_id' => env('LARK_BT_SG_ITEMS_TABLE_ID', 'tbl0Z7U3UpjDm8S0'),
            'view_id' => env('LARK_BT_SG_ITEMS_VIEW_ID', 'vewrGHKZN5'),
        ],

        // SG-BT (Singapore to Batam) - Courier
        // CATATAN: Table ID ini SALAH! Saat ini menggunakan table Items (tbl0Z7U3UpjDm8S0)
        // Seharusnya ada table terpisah untuk SG-BT Courier IDs
        // TODO: Minta user untuk provide table_id yang benar untuk SG-BT Courier
        'sg_bt_courier' => [
            'table_id' => env('LARK_SG_BT_COURIER_TABLE_ID', 'tblt8ioLwKa1ZjVe'), // WRONG! This is items table
            'view_id' => env('LARK_SG_BT_COURIER_VIEW_ID', 'vew11b8N8m'),
        ],

        // SG-BT (Singapore to Batam) - Items
        'sg_bt_items' => [
            'table_id' => env('LARK_SG_BT_ITEMS_TABLE_ID', 'tbl43ITOZDDkUJ2Z'),
            'view_id' => env('LARK_SG_BT_ITEMS_VIEW_ID', 'vewrGHKZN5'),
        ],
    ],

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
