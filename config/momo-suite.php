<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment provider that will be used when
    | using the MomoSuite package. You may set this to any of the providers
    | defined in the "providers" configuration array below.
    |
    */
    'default' => env('MOMO_PROVIDER', 'korba'),

    /*
    |--------------------------------------------------------------------------
    | Payment Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the payment providers for your application. Each
    | provider requires specific configuration values such as API keys,
    | base URLs, and other settings needed for the integration.
    |
    */
    'providers' => [
        'korba' => [
            'client_id' => env('KORBA_CLIENT_ID'),
            'secret_key' => env('KORBA_SECRET_KEY'),
            'client_key' => env('KORBA_CLIENT_KEY'),
            'base_url' => env('KORBA_URL', 'https://xchange.korba365.com/api/v1.0/'),

        ],
        'hubtel' => [
            'username' => env('HUBTEL_USERNAME'),
            'password' => env('HUBTEL_PASSWORD'),
            'pos_id_sales' => env('HUBTEL_POS_ID_SALES'),
            'pos_id_deposit' => env('HUBTEL_POS_ID_DEPOSIT'),
            'receive_url' => env('HUBTEL_RECEIVE_URL', 'https://rmp.hubtel.com/merchantaccount/merchants/'),
            'send_url' => env('HUBTEL_SEND_MONEY_URL', 'https://smp.hubtel.com/api/merchants/'),
            'receive_status_url' => env('HUBTEL_RECEIVE_STATUS_URL', 'https://api-txnstatus.hubtel.com/transactions/'),
            'send_status_url' => env('HUBTEL_SEND_STATUS_URL', 'https://smrsc.hubtel.com/api/merchants/'),
        ],
        'itc' => [
            'product_id_credit' => env('ITC_PRODUCT_ID_CREDIT'),
            'product_id_debit' => env('ITC_PRODUCT_ID_DEBIT'),
            'transflow_id' => env('ITC_TRANSFLOW_ID'),
            'api_key' => env('ITC_API_KEY'),
            'base_url' => env('ITC_BASE_URL', 'https://uniwallet.itcsrvc.com/uniwallet/v2'),
        ],
        'paystack' => [
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard UI
    |--------------------------------------------------------------------------
    |
    | If true, the package will load its dashboard views and routes. If false,
    | only backend (webhook/API) routes will be loaded. This is more secure.
    |
    */
    'load_dashboard' => env('MOMO_SUITE_LOAD_DASHBOARD', false),
];
