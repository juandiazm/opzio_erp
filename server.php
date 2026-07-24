<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    $file = __DIR__.'/public'.$uri;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimes = [
        'js'    => 'application/javascript',
        'css'   => 'text/css',
        'json'  => 'application/json',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'webp'  => 'image/webp',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'map'   => 'application/json',
        'html'  => 'text/html',
        'txt'   => 'text/plain',
        'xml'   => 'application/xml',
        'webmanifest' => 'application/manifest+json',
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: '.$mimes[$ext]);
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit;
    }
    return false;
}

require_once __DIR__.'/public/index.php';
