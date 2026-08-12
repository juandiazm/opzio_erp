<?php

namespace App\Providers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

class GoogleDriveServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Storage::extend('google', function ($app, $config) {
            foreach (['clientId', 'clientSecret', 'refreshToken'] as $credential) {
                if (empty($config[$credential])) {
                    throw new \RuntimeException("Google Drive configuration is missing: {$credential}");
                }
            }

            $client = new \Google\Client();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $token = $client->fetchAccessTokenWithRefreshToken($config['refreshToken']);
            if (!empty($token['error']) || empty($token['access_token'])) {
                Log::error('Google Drive OAuth refresh failed', [
                    'error' => $token['error'] ?? 'missing_access_token',
                    'error_description' => $token['error_description'] ?? null,
                ]);
                throw new \RuntimeException('Google Drive OAuth did not return an access token.');
            }
            $client->setAccessToken($token);
            Log::info('Google Drive OAuth credentials loaded', [
                'client_id_present' => true,
                'refresh_token_present' => true,
                'folder_id_present' => !empty($config['folderId']),
            ]);

            $service = new \Google\Service\Drive($client);
            $adapter = new GoogleDriveAdapter($service, $config['folderId'] ?: '/');
            $driver  = new Filesystem($adapter);

            return new FilesystemAdapter($driver, $adapter, $config);
        });
    }

    public function register()
    {
        //
    }
}
