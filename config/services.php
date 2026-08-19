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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'verify_tls' => (bool) env('OPENAI_VERIFY_TLS', true),
        'timeout' => (float) env('OPENAI_TIMEOUT', 240),
        'retries' => (int) env('OPENAI_RETRIES', 2),
        'models' => [
            'fast' => env('OPENAI_MODEL_FAST', 'gpt-5.6-luna'),
            'chat' => env('OPENAI_MODEL_CHAT', 'gpt-5.6-terra'),
            'content' => env('OPENAI_MODEL_CONTENT', 'gpt-5.6-terra'),
            'reasoning' => env('OPENAI_MODEL_REASONING', 'gpt-5.6-sol'),
            'image' => env('OPENAI_IMAGE_MODEL', 'gpt-image-2'),
        ],
        'image' => [
            'quality' => env('OPENAI_IMAGE_QUALITY', 'low'),
            'size' => env('OPENAI_IMAGE_SIZE', '1024x1024'),
            'format' => env('OPENAI_IMAGE_FORMAT', 'webp'),
            'compression' => (int) env('OPENAI_IMAGE_COMPRESSION', 90),
        ],
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

    'servers' => [
        'token' => env('OPZIO_OBSERVER_TOKEN', ''),
        'loopback_only' => (bool) env('OPZIO_OBSERVER_LOOPBACK_ONLY', true),
        'max_payload_bytes' => (int) env('OPZIO_OBSERVER_MAX_PAYLOAD_BYTES', 10485760),
    ],

    'pdf' => [
        'chrome_path' => env(
            'PDF_CHROME_PATH',
            PHP_OS_FAMILY === 'Windows' ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe' : null
        ),
        'node_binary' => env('PDF_NODE_BINARY', 'node'),
        'node_module_path' => env('PDF_NODE_MODULE_PATH', base_path('node_modules')),
    ],

];
