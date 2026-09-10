<?php

return [
    'default_timezone' => env('JIRA_TIMEZONE', 'America/Bogota'),
    'timeout' => (float) env('JIRA_TIMEOUT', 30),
    'retries' => (int) env('JIRA_RETRIES', 2),
    'test_timeout' => (float) env('JIRA_TEST_TIMEOUT', 10),
    'sync_timeout' => (float) env('JIRA_SYNC_TIMEOUT', 4),
    'sync_retries' => (int) env('JIRA_SYNC_RETRIES', 0),
    'web_batch_size' => (int) env('JIRA_WEB_BATCH_SIZE', 10),
    'max_page_size' => (int) env('JIRA_MAX_PAGE_SIZE', 50),
    'max_issues_per_sync' => (int) env('JIRA_MAX_ISSUES_PER_SYNC', 5000),
];
