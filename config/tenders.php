<?php

return [
    'tenant_id' => env('TENDERS_TENANT_ID', 'opzio'),
    'default_timezone' => env('TENDERS_TIMEZONE', 'America/Bogota'),
    'timeout' => (float) env('TENDERS_TIMEOUT', 30),
    'retries' => (int) env('TENDERS_RETRIES', 2),
    'test_timeout' => (float) env('TENDERS_TEST_TIMEOUT', 10),
    'web_batch_size' => (int) env('TENDERS_WEB_BATCH_SIZE', 25),
    'max_page_size' => (int) env('TENDERS_MAX_PAGE_SIZE', 250),
    'max_rows_per_sync' => (int) env('TENDERS_MAX_ROWS_PER_SYNC', 5000),
    'document_max_bytes' => (int) env('TENDERS_DOCUMENT_MAX_BYTES', 25000000),
    'document_disk' => env('TENDERS_DOCUMENT_DISK', 'tenders_documents'),
    'allowed_download_hosts' => [
        'community.secop.gov.co',
        'www.datos.gov.co',
        'datos.gov.co',
    ],
];
