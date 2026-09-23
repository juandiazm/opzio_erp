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

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
        'whatsapp' => [
            'from' => env('TWILIO_WHATSAPP_FROM', env('TWILIO_FROM')),
            'messaging_service_sid' => env('TWILIO_WHATSAPP_MESSAGING_SERVICE_SID'),
            'webhook_url' => env('TWILIO_WHATSAPP_WEBHOOK_URL'),
            'status_callback_url' => env('TWILIO_WHATSAPP_STATUS_CALLBACK_URL'),
            'validate_webhooks' => (bool) env('TWILIO_WHATSAPP_VALIDATE_WEBHOOKS', true),
            'default_country_code' => env('TWILIO_WHATSAPP_DEFAULT_COUNTRY_CODE', '+57'),
            'template_limit' => (int) env('TWILIO_WHATSAPP_TEMPLATE_LIMIT', 100),
            'ai' => [
                'enabled' => (bool) env('WHATSAPP_AI_ENABLED', true),
                'admin_numbers' => array_values(array_filter(array_map('trim', explode(',', (string) env('WHATSAPP_AI_ADMIN_NUMBERS', ''))))),
                'model' => env('WHATSAPP_AI_MODEL', env('OPENAI_MODEL_CHAT', 'gpt-5.6-terra')),
                'planner_max_output_tokens' => (int) env('WHATSAPP_AI_PLANNER_MAX_OUTPUT_TOKENS', 700),
                'answer_max_output_tokens' => (int) env('WHATSAPP_AI_ANSWER_MAX_OUTPUT_TOKENS', 900),
                'catalog_limit' => (int) env('WHATSAPP_AI_CATALOG_LIMIT', 100),
                'log_messages' => (bool) env('WHATSAPP_AI_LOG_MESSAGES', true),
                'fallback_enabled' => (bool) env('WHATSAPP_AI_FALLBACK_ENABLED', true),
                'fallback_model' => env('WHATSAPP_AI_FALLBACK_MODEL', env('OPENAI_MODEL_FAST', 'gpt-5.6-luna')),
                'fallback_max_output_tokens' => (int) env('WHATSAPP_AI_FALLBACK_MAX_OUTPUT_TOKENS', 180),
            ],
        ],
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
            'content' => 'gpt-5.6-luna',
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

    'jira' => [
        'timeout' => (float) env('JIRA_TIMEOUT', 30),
        'retries' => (int) env('JIRA_RETRIES', 2),
    ],

    'pdf' => [
        'timeout' => (int) env('PDF_TIMEOUT', 180),
        'protocol_timeout' => (int) env('PDF_PROTOCOL_TIMEOUT', 180),
        'jira_timeout' => (int) env('PDF_JIRA_TIMEOUT', 300),
        'jira_protocol_timeout' => (int) env('PDF_JIRA_PROTOCOL_TIMEOUT', 300),
        'chrome_path' => env(
            'PDF_CHROME_PATH',
            PHP_OS_FAMILY === 'Windows' ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe' : null
        ),
        'node_binary' => env('PDF_NODE_BINARY', 'node'),
        'node_module_path' => env('PDF_NODE_MODULE_PATH', base_path('node_modules')),
    ],

];
