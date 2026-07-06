<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
        'backups' => [
            'driver' => 'local',
            'root' => storage_path('backups'),
        ],
        'google' => [
            'driver' => 'google',
            'clientId' => env('GOOGLE_DRIVE_CLIENT_ID'),
            'clientSecret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
            'refreshToken' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
            'folderId' => env('GOOGLE_DRIVE_FOLDER_ID'),
        ],
        'client_document' => [
            'driver' => 'local',
            'root' => storage_path('app/public/client_documents'),
            'url' => env('APP_URL').'storage/client_documents',
            'visibility' => 'public',
        ],
        'employee_document' => [
            'driver' => 'local',
            'root' => storage_path('app/public/employee_documents'),
            'url' => env('APP_URL').'storage/employee_documents',
            'visibility' => 'public',
        ],
        'provider_document' => [
            'driver' => 'local',
            'root' => storage_path('app/public/provider_documents'),
            'url' => env('APP_URL').'storage/provider_documents',
            'visibility' => 'public',
        ],
        'license_document' => [
            'driver' => 'local',
            'root' => storage_path('app/public/license_documents'),
            'url' => env('APP_URL').'storage/license_documents',
            'visibility' => 'public',
        ],
        'incomes_qr' => [
            'driver' => 'local',
            'root' => storage_path('app/public/incomes/qr'),
            'url' => env('APP_URL').'storage/incomes/qr',
            'visibility' => 'public',
        ],
        'incomes_pdfs' => [
            'driver' => 'local',
            'root' => storage_path('app/public/incomes/pdfs'),
            'url' => env('APP_URL').'storage/incomes/pdfs',
            'visibility' => 'public',
        ],
        'blog_principal_images' => [
            'driver' => 'local',
            'root' => storage_path('app/public/blog/principal'),
            'url' => env('APP_URL').'storage/blog/principal',
            'visibility' => 'public',
        ],
        'blog_principal_segment_images' => [
            'driver' => 'local',
            'root' => storage_path('app/public/blog/segment'),
            'url' => env('APP_URL').'storage/blog/segment',
            'visibility' => 'public',
        ],
        'instagram_post_images' => [
            'driver' => 'local',
            'root' => storage_path('app/public/instagram/post_images'),
            'url' => env('APP_URL').'storage/instagram/post_images',
            'visibility' => 'public',
        ],
        'facebook_post_images' => [
            'driver' => 'local',
            'root' => storage_path('app/public/facebook/post_images'),
            'url' => env('APP_URL').'storage/facebook/post_images',
            'visibility' => 'public',
        ],
        'linkedin_post_images' => [
            'driver' => 'local',
            'root' => storage_path('app/public/linkedin/post_images'),
            'url' => env('APP_URL').'storage/linkedin/post_images',
            'visibility' => 'public',
        ],
        'twitter_post_images' => [
            'driver' => 'local',
            'root' => storage_path('app/public/twitter/post_images'),
            'url' => env('APP_URL').'storage/twitter/post_images',
            'visibility' => 'public',
        ],
        'freepik_post_images' => [
            'driver' => 'local',
            'root' => storage_path('app/public/freepik/post_images'),
            'url' => env('APP_URL').'storage/freepik/post_images',
            'visibility' => 'public',
        ],
        'reports' => [
            'driver' => 'local',
            'root' => storage_path('app/public/reports'),
            'url' => env('APP_URL').'storage/reports',
            'visibility' => 'public',
        ],
        'incomes' => [
            'driver' => 'local',
            'root' => storage_path('app/public/incomes'),
            'url' => env('APP_URL') . '/storage/incomes',
            'visibility' => 'public',
        ],
        'ia_reports' => [
            'driver' => 'local',
            'root' => storage_path('app/public/ia_reports'),
            'url' => env('APP_URL') . 'storage/ia_reports',
            'visibility' => 'public',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
