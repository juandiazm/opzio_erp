<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class github_connection extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'last_tested_at' => 'datetime',
    ];

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials ?? [], $key, $default);
    }

    public function repositoryUrl(string $owner, string $repository): string
    {
        if (trim($owner) === '' || trim($repository) === '') {
            return '';
        }

        $baseUrl = rtrim((string) ($this->base_url ?: config('ai_development.github.base_url', 'https://api.github.com')), '/');
        $parts = parse_url($baseUrl);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = rtrim((string) ($parts['path'] ?? ''), '/');

        if ($host === 'api.github.com') {
            $host = 'github.com';
            $path = '';
        } else {
            $path = preg_replace('#/api/v\d+$#i', '', $path) ?: $path;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $scheme.'://'.$host.$port.$path.'/'.rawurlencode($owner).'/'.rawurlencode($repository);
    }
}