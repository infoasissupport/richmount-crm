<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Zoho Books API Credentials & Configuration
    |--------------------------------------------------------------------------
    |
    | Rich Mount ERP connects to Zoho Books for two-way synchronization:
    | - Project creation & budget allocation
    | - Customer contact details
    | - Tax Invoices (with 5% UAE VAT & item lines)
    | - Site Petty Cash and Card Expenses
    | - Customer Payments
    |
    */

    'client_id' => env('ZOHO_CLIENT_ID', '1000.DEMO_CLIENT_ID'),
    'client_secret' => env('ZOHO_CLIENT_SECRET', 'demo_client_secret_xyz123'),
    'refresh_token' => env('ZOHO_REFRESH_TOKEN', '1000.demo_refresh_token_abc789'),
    'organization_id' => env('ZOHO_ORGANIZATION_ID', '849201938'),

    'accounts_url' => env('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com'),
    'api_url' => env('ZOHO_API_URL', 'https://www.zohoapis.com/books/v3'),

    /*
    |--------------------------------------------------------------------------
    | Mock Driver
    |--------------------------------------------------------------------------
    |
    | Set to true for local testing, demo mode, or when offline.
    | When true, the integration layer simulates realistic Zoho Books API
    | responses with generated Zoho IDs without performing outbound HTTP calls.
    |
    */
    'mock_driver' => env('ZOHO_MOCK_DRIVER', true),
];
