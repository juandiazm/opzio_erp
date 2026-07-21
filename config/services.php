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

    'siigo' => [
        'username' => env('SIIGO_USERNAME'),
        'access_key' => env('SIIGO_ACCESS_KEY'),
        'partner_id' => env('SIIGO_PARTNER_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nini Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for nini_admin_app integration.
    | Wallet recharges from Nini are synced as incomes with electronic invoicing.
    |
    */
    'nini_integration' => [
        'api_token' => env('NINI_INTEGRATION_API_TOKEN', ''),
        'service_id' => env('NINI_SERVICE_ID', 4),                    // "Software de Facturación P.O.S"
        'service_name' => env('NINI_SERVICE_NAME', 'Software de Facturación P.O.S'),
        'employee_id' => env('NINI_EMPLOYEE_ID', null),
        'employee_name' => env('NINI_EMPLOYEE_NAME', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Integration (opzio_web → ERP)
    |--------------------------------------------------------------------------
    */
    'web_integration' => [
        'api_token' => env('WEB_INTEGRATION_API_TOKEN', ''),
    ],

];
